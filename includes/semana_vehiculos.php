<?php
/**
 * Resumen semanal por vehículo (pedido del 24/11/2026). Lo comparten:
 *   - api_analisis_semanal.php: el agente b4 lee aquí los datos de la semana y publica su
 *     análisis (alertas y comentario) en analisis_semanal_vehiculo.
 *   - acciones_resumen_semanal.php: la pestaña Vehículo del portal (loginMaster/inicio.php)
 *     muestra los números de la semana y el análisis que publicó b4.
 *
 * Los km NO se recalculan aquí: salen de sqlKmMessCheckin() (calcular_ruta.php), la única
 * definición de KM Mess / personales (CLAUDE.md). Quien incluya este archivo debe incluir
 * antes includes/api_bootstrap.php y calcular_ruta.php.
 *
 * Semana ISO, lunes a domingo, como reporte_km_vehiculo.
 */

/**
 * Semana pedida: el lunes de la fecha 'Y-m-d' recibida; sin fecha válida, la semana pasada
 * (la última completa). Nunca una semana futura.
 * @return array{lunes:DateTimeImmutable,desde:string,hasta:string,domingo:string,numero:int,anio:int,en_curso:bool}
 *         desde = lunes, hasta = lunes siguiente (exclusivo).
 */
function semanaPedida(string $fecha = ''): array {
    $hoy = new DateTimeImmutable('today');
    $lunesActual = $hoy->modify('-' . ($hoy->format('N') - 1) . ' days');
    $ref = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) ? DateTimeImmutable::createFromFormat('!Y-m-d', $fecha) : false;
    $lunes = $ref ? $ref->modify('-' . ($ref->format('N') - 1) . ' days') : $lunesActual->modify('-7 days');
    if ($lunes > $lunesActual) $lunes = $lunesActual;
    return [
        'lunes'    => $lunes,
        'desde'    => $lunes->format('Y-m-d'),
        'hasta'    => $lunes->modify('+7 days')->format('Y-m-d'),
        'domingo'  => $lunes->modify('+6 days')->format('Y-m-d'),
        'numero'   => (int) $lunes->format('W'),
        'anio'     => (int) $lunes->format('o'),
        'en_curso' => $lunes == $lunesActual,
    ];
}

/** Placeholders '?,?,?' y tipos 'iii' para una lista de enteros. */
function inEnteros(array $ids): array {
    return [implode(',', array_fill(0, count($ids), '?')), str_repeat('i', count($ids))];
}

/** Ejecuta un SELECT preparado y devuelve todas las filas. */
function filasPreparadas(mysqli $conn, string $sql, string $tipos = '', array $params = []): array {
    $st = $conn->prepare($sql);
    if ($tipos !== '') $st->bind_param($tipos, ...$params);
    $st->execute();
    $filas = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
    return $filas;
}

/**
 * Nombre de RRHH por id_usuario. id_usuario no es único en RRHH: se toma la fila activa de
 * menor id (mismo criterio que acciones_mapa.php). El 0 (altas sin id) no se resuelve.
 */
function nombresRrhh(mysqli $conn, array $ids): array {
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), function ($i) { return $i > 0; })));
    if (!$ids) return [];
    list($in, $tipos) = inEnteros($ids);
    $filas = filasPreparadas($conn,
        "SELECT id_usuario, TRIM(CONCAT(IFNULL(nombres,''),' ',IFNULL(apellidos,''))) AS nombre FROM (
             SELECT id_usuario, nombres, apellidos,
                    ROW_NUMBER() OVER (PARTITION BY id_usuario ORDER BY estatus = 1 DESC, id) AS rn
             FROM mess_rrhh.usuarios WHERE id_usuario IN ($in)) u
         WHERE rn = 1", $tipos, $ids);
    $nombres = [];
    foreach ($filas as $f) {
        $n = trim(preg_replace('/\s+/u', ' ', (string) $f['nombre']));
        if ($n !== '') $nombres[(int) $f['id_usuario']] = $n;
    }
    return $nombres;
}

// Hora de lectura de una carga: la misma regla que ultimaLecturaKm() (api_bootstrap.php).
// Las cargas viejas guardaron solo la fecha (00:00); si se registraron ese mismo día se usa
// la hora del registro.
const SQL_FECHA_LECTURA_CARGA = "CASE WHEN DATE(cg.fecha_registro) = cg.fecha_carga THEN cg.fecha_registro
                                      ELSE CAST(cg.fecha_carga AS DATETIME) END";

/**
 * Datos de la semana por vehículo activo.
 *
 * Sin $ids: los vehículos activos con al menos un check-in o una carga en la semana (lo que
 * b4 analiza). Con $ids: esos vehículos activos aunque no tengan movimientos (la pestaña).
 *
 * Por vehículo:
 *   km.inicial          último check-in antes de la semana (desde ahí se mide el primer tramo)
 *   km.final            último check-in de la semana
 *   km.odometro         Σ tramos válidos de los check-ins de la semana = mess + personales
 *   km.avance_lecturas  final − inicial. Si es mayor que odometro hubo tramos descartados
 *                       (negativos o de más de KM_VIAJE_MAX), que cuentan 0.
 *   checkins[]          con su tramo, días desde el check-in anterior y km Mess / personal
 *   cargas[]            con la hora de lectura (sin_hora = la carga no guardó hora)
 *   lectura_anterior    última lectura de km (check-in o carga) antes de la semana
 *   ultima_carga_al_cierre  última carga con km hasta el domingo (para "km sin cargas")
 */
function datosSemanaVehiculos(mysqli $conn, array $semana, array $ids = []): array {
    $desde = $semana['desde'];
    $hasta = $semana['hasta'];
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

    // ----- Vehículos
    if ($ids) {
        list($in, $t) = inEnteros($ids);
        $vehiculos = filasPreparadas($conn,
            "SELECT id_vehiculo, placa, TRIM(marca) AS marca, TRIM(modelo) AS modelo, id_us_asignado
             FROM inventario WHERE estatus = 'Activo' AND id_vehiculo IN ($in) ORDER BY placa", $t, $ids);
    } else {
        $vehiculos = filasPreparadas($conn,
            "SELECT inv.id_vehiculo, inv.placa, TRIM(inv.marca) AS marca, TRIM(inv.modelo) AS modelo, inv.id_us_asignado
             FROM inventario inv
             WHERE inv.estatus = 'Activo'
               AND (EXISTS (SELECT 1 FROM actividad_vehiculo av
                            WHERE av.id_vehiculo = inv.id_vehiculo AND av.tipo_actividad = 'INICIO'
                              AND av.fecha_actividad >= ? AND av.fecha_actividad < ?)
                    OR EXISTS (SELECT 1 FROM carga_gasolina cg
                               WHERE cg.id_vehiculo = inv.id_vehiculo
                                 AND cg.fecha_carga >= ? AND cg.fecha_carga < ?))
             ORDER BY inv.placa", "ssss", [$desde, $hasta, $desde, $hasta]);
    }
    if (!$vehiculos) return [];
    $idsVeh = array_map(function ($v) { return (int) $v['id_vehiculo']; }, $vehiculos);
    list($in, $t) = inEnteros($idsVeh);

    // ----- Check-ins de la semana con su tramo (sqlKmMessCheckin: única definición de km)
    $checkins = filasPreparadas($conn,
        "SELECT av.id_actividad, av.id_vehiculo, av.fecha_actividad, av.km_actual, av.id_usuario, av.ot,
                av.foto_url, av.detalle_tipo_uso, m.km_tramo, m.dias, m.km_mess, m.km_personal, m.tipo_ref
         FROM actividad_vehiculo av
         LEFT JOIN " . sqlKmMessCheckin() . " m ON m.id_actividad = av.id_actividad
         WHERE av.tipo_actividad = 'INICIO' AND av.fecha_actividad >= ? AND av.fecha_actividad < ?
           AND av.id_vehiculo IN ($in)
         ORDER BY av.id_vehiculo, av.fecha_actividad, av.id_actividad",
        "ss" . $t, array_merge([$desde, $hasta], $idsVeh));

    // ----- Cargas de la semana
    $cargas = filasPreparadas($conn,
        "SELECT cg.id, cg.id_vehiculo, cg.fecha_carga, " . SQL_FECHA_LECTURA_CARGA . " AS fecha_lectura,
                cg.km_actual, cg.monto, cg.pagos, cg.saldo, cg.id_usuario, cg.ot
         FROM carga_gasolina cg
         WHERE cg.fecha_carga >= ? AND cg.fecha_carga < ? AND cg.id_vehiculo IN ($in)
         ORDER BY cg.id_vehiculo, fecha_lectura, cg.km_actual, cg.id",
        "ss" . $t, array_merge([$desde, $hasta], $idsVeh));

    // ----- Último check-in antes de la semana (km inicial)
    $ciAntes = [];
    foreach (filasPreparadas($conn,
        "SELECT id_vehiculo, fecha_actividad, km_actual FROM (
             SELECT id_vehiculo, fecha_actividad, km_actual,
                    ROW_NUMBER() OVER (PARTITION BY id_vehiculo ORDER BY fecha_actividad DESC, id_actividad DESC) AS rn
             FROM actividad_vehiculo
             WHERE tipo_actividad = 'INICIO' AND km_actual > 0 AND fecha_actividad < ? AND id_vehiculo IN ($in)) x
         WHERE rn = 1", "s" . $t, array_merge([$desde], $idsVeh)) as $f) {
        $ciAntes[(int) $f['id_vehiculo']] = $f;
    }

    // ----- Última lectura (check-in o carga) antes de la semana. kilometrajes no entra: el
    // check-in QR la inserta con el mismo km y hora, sería la misma lectura dos veces.
    $lecturaAntes = [];
    foreach (filasPreparadas($conn,
        "SELECT id_vehiculo, fuente, f, km FROM (
             SELECT t.*, ROW_NUMBER() OVER (PARTITION BY id_vehiculo ORDER BY f DESC, km DESC) AS rn FROM (
                 SELECT id_vehiculo, 'checkin' AS fuente, fecha_actividad AS f, km_actual AS km
                   FROM actividad_vehiculo
                  WHERE tipo_actividad = 'INICIO' AND km_actual > 0 AND fecha_actividad < ? AND id_vehiculo IN ($in)
                 UNION ALL
                 SELECT cg.id_vehiculo, 'carga', " . SQL_FECHA_LECTURA_CARGA . ", cg.km_actual
                   FROM carga_gasolina cg
                  WHERE cg.km_actual > 0 AND cg.fecha_carga < ? AND cg.id_vehiculo IN ($in)) t) x
         WHERE rn = 1", "s" . $t . "s" . $t, array_merge([$desde], $idsVeh, [$desde], $idsVeh)) as $f) {
        $lecturaAntes[(int) $f['id_vehiculo']] = $f;
    }

    // ----- Última carga con km hasta el cierre de la semana
    $cargaCierre = [];
    foreach (filasPreparadas($conn,
        "SELECT id_vehiculo, f, km_actual FROM (
             SELECT cg.id_vehiculo, " . SQL_FECHA_LECTURA_CARGA . " AS f, cg.km_actual,
                    ROW_NUMBER() OVER (PARTITION BY cg.id_vehiculo
                                       ORDER BY " . SQL_FECHA_LECTURA_CARGA . " DESC, cg.km_actual DESC, cg.id DESC) AS rn
             FROM carga_gasolina cg
             WHERE cg.km_actual > 0 AND cg.fecha_carga < ? AND cg.id_vehiculo IN ($in)) x
         WHERE rn = 1", "s" . $t, array_merge([$hasta], $idsVeh)) as $f) {
        $cargaCierre[(int) $f['id_vehiculo']] = $f;
    }

    // ----- Nombres
    $nombres = nombresRrhh($conn, array_merge(
        array_column($vehiculos, 'id_us_asignado'), array_column($checkins, 'id_usuario'), array_column($cargas, 'id_usuario')));
    $nombre = function ($id) use ($nombres) { $id = (int) $id; return $nombres[$id] ?? null; };

    // ----- Armado por vehículo
    $res = [];
    foreach ($vehiculos as $v) {
        $idv = (int) $v['id_vehiculo'];
        $res[$idv] = [
            'id_vehiculo' => $idv,
            'placa'       => $v['placa'],
            'marca'       => $v['marca'],
            'modelo'      => $v['modelo'],
            'asignado'    => ['id_usuario' => (int) $v['id_us_asignado'], 'nombre' => $nombre($v['id_us_asignado'])],
            'km'          => ['inicial' => null, 'final' => null, 'odometro' => 0, 'mess' => 0, 'personales' => 0,
                              'pct_mess' => null, 'avance_lecturas' => null],
            'conteo'      => ['checkins' => 0, 'ot' => 0, 'ov' => 0, 'otros' => 0, 'cargas' => 0, 'monto_cargado' => 0.0],
            'checkins'    => [],
            'cargas'      => [],
            'lectura_anterior' => isset($lecturaAntes[$idv])
                ? ['fuente' => $lecturaAntes[$idv]['fuente'], 'fecha' => $lecturaAntes[$idv]['f'], 'km' => (int) $lecturaAntes[$idv]['km']]
                : null,
            'ultima_carga_al_cierre' => isset($cargaCierre[$idv])
                ? ['fecha' => $cargaCierre[$idv]['f'], 'km' => (int) $cargaCierre[$idv]['km_actual']]
                : null,
        ];
        if (isset($ciAntes[$idv])) $res[$idv]['km']['inicial'] = (int) $ciAntes[$idv]['km_actual'];
    }

    foreach ($checkins as $c) {
        $idv = (int) $c['id_vehiculo'];
        $r = &$res[$idv];
        $tramo = $c['km_tramo'] === null ? null : (int) $c['km_tramo'];
        $r['checkins'][] = [
            'id_actividad' => (int) $c['id_actividad'],
            'fecha'        => $c['fecha_actividad'],
            'km'           => (int) $c['km_actual'],
            'km_tramo'     => $tramo,                       // null: primer check-in del vehículo o sin km
            'dias'         => $c['dias'] === null ? null : (int) $c['dias'],
            'km_mess'      => (int) ($c['km_mess'] ?? 0),
            'km_personal'  => (int) ($c['km_personal'] ?? 0),
            'tipo_ref'     => $c['tipo_ref'],               // 'OT', 'OV' o null
            'regreso_sede' => $c['detalle_tipo_uso'] === 'REGRESO_SEDE',
            'visita'       => $c['ot'],                     // texto libre del campo OT/visita
            'tiene_foto'   => trim((string) $c['foto_url']) !== '',
            'usuario'      => ['id_usuario' => (int) $c['id_usuario'], 'nombre' => $nombre($c['id_usuario'])],
        ];
        $r['conteo']['checkins']++;
        if ($c['tipo_ref'] === 'OT') $r['conteo']['ot']++;
        elseif ($c['tipo_ref'] === 'OV') $r['conteo']['ov']++;
        else $r['conteo']['otros']++;
        $r['km']['odometro']   += (int) ($tramo ?? 0);
        $r['km']['mess']       += (int) ($c['km_mess'] ?? 0);
        $r['km']['personales'] += (int) ($c['km_personal'] ?? 0);
        if ((int) $c['km_actual'] > 0) $r['km']['final'] = (int) $c['km_actual'];
        unset($r);
    }

    foreach ($cargas as $c) {
        $idv = (int) $c['id_vehiculo'];
        $res[$idv]['cargas'][] = [
            'id'            => (int) $c['id'],
            'fecha'         => $c['fecha_lectura'],
            'sin_hora'      => substr((string) $c['fecha_lectura'], 11) === '00:00:00',
            'km'            => (int) $c['km_actual'],
            'monto_cargado' => (float) $c['pagos'],
            'saldo_antes'   => (float) $c['monto'],
            'saldo_despues' => (float) $c['saldo'],
            'visita'        => $c['ot'],
            'usuario'       => ['id_usuario' => (int) $c['id_usuario'], 'nombre' => $nombre($c['id_usuario'])],
        ];
        $res[$idv]['conteo']['cargas']++;
        $res[$idv]['conteo']['monto_cargado'] += (float) $c['pagos'];
    }

    foreach ($res as &$r) {
        $k = &$r['km'];
        if ($k['odometro'] > 0) $k['pct_mess'] = round($k['mess'] / $k['odometro'] * 100);
        if ($k['inicial'] !== null && $k['final'] !== null) $k['avance_lecturas'] = $k['final'] - $k['inicial'];
        $r['conteo']['monto_cargado'] = round($r['conteo']['monto_cargado'], 2);
        unset($k);
    }
    unset($r);

    return array_values($res);
}

// ---------------------------------------------------------------------------
// Análisis publicado por b4
// ---------------------------------------------------------------------------

function crearTablaAnalisisSemanal(mysqli $conn): void {
    $conn->query("CREATE TABLE IF NOT EXISTS analisis_semanal_vehiculo (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        id_vehiculo   INT NOT NULL,
        semana_inicio DATE NOT NULL,
        nivel         VARCHAR(10) NOT NULL,
        resumen       TEXT NULL,
        alertas       MEDIUMTEXT NOT NULL,
        autor         VARCHAR(30) NOT NULL,
        creado        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        actualizado   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_veh_semana (id_vehiculo, semana_inicio),
        KEY idx_semana (semana_inicio)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/** Análisis publicado de una semana, por id_vehiculo. Sin $ids, todos los de esa semana. */
function analisisPublicado(mysqli $conn, string $desde, array $ids = []): array {
    crearTablaAnalisisSemanal($conn);
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    $sql = "SELECT a.id_vehiculo, i.placa, a.nivel, a.resumen, a.alertas, a.autor, a.creado, a.actualizado
            FROM analisis_semanal_vehiculo a
            LEFT JOIN inventario i ON i.id_vehiculo = a.id_vehiculo
            WHERE a.semana_inicio = ?";
    $tipos = 's';
    $params = [$desde];
    if ($ids) {
        list($in, $t) = inEnteros($ids);
        $sql .= " AND a.id_vehiculo IN ($in)";
        $tipos .= $t;
        $params = array_merge($params, $ids);
    }
    $res = [];
    foreach (filasPreparadas($conn, $sql . " ORDER BY i.placa", $tipos, $params) as $f) {
        $alertas = json_decode((string) $f['alertas'], true);
        $res[(int) $f['id_vehiculo']] = [
            'id_vehiculo' => (int) $f['id_vehiculo'],
            'placa'       => $f['placa'],
            'nivel'       => $f['nivel'],
            'resumen'     => $f['resumen'],
            'alertas'     => is_array($alertas) ? $alertas : [],
            'autor'       => $f['autor'],
            'creado'      => $f['creado'],
            'actualizado' => $f['actualizado'],
        ];
    }
    return $res;
}
