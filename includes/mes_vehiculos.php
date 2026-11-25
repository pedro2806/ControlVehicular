<?php
/**
 * Resumen mensual por vehículo (pedido del 24/11/2026). Lo comparten:
 *   - api_analisis_mensual.php: el agente b4 lee los números del mes y publica su análisis.
 *   - acciones_resumen_mensual.php: la pestaña Vehículo del portal (loginMaster/inicio.php).
 *   - reportes/generar_analisis_mensual.php: genera el mes pasado desde cron (día 1).
 *
 * Los números de cada mes se guardan UNA vez en analisis_mensual_vehiculo.datos (foto del mes,
 * con la fecha en datos_actualizado) y después solo se leen: la pestaña no recalcula. Se
 * generan para toda la flota activa la primera vez que se pide un mes cerrado (cron del día 1,
 * la llamada de b4 o la primera consulta). Solo meses cerrados: nunca el mes en curso.
 *
 * Los km NO se recalculan aquí: salen de sqlKmMessCheckin() (calcular_ruta.php), la única
 * definición de KM Mess / personales (CLAUDE.md). Quien incluya este archivo debe incluir
 * antes la conexión (includes/api_bootstrap.php o conn.php) y calcular_ruta.php.
 */

const TABLA_ANALISIS_MENSUAL = 'analisis_mensual_vehiculo';

/**
 * Mes pedido ('YYYY-MM'). Sin mes válido, o si es el mes en curso o uno futuro, el mes pasado
 * (el último cerrado).
 * @return array{inicio:DateTimeImmutable,clave:string,desde:string,hasta:string,fin:string,anterior:string,siguiente:?string}
 *         desde = día 1, hasta = día 1 del mes siguiente (exclusivo), fin = último día.
 */
function mesPedido(string $mes = ''): array {
    $ultimoCerrado = (new DateTimeImmutable('today'))->modify('first day of this month')->modify('-1 month');
    $ref = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mes) ? DateTimeImmutable::createFromFormat('!Y-m-d', $mes . '-01') : false;
    $inicio = ($ref && $ref <= $ultimoCerrado) ? $ref : $ultimoCerrado;
    return [
        'inicio'    => $inicio,
        'clave'     => $inicio->format('Y-m'),
        'desde'     => $inicio->format('Y-m-d'),
        'hasta'     => $inicio->modify('+1 month')->format('Y-m-d'),
        'fin'       => $inicio->modify('last day of this month')->format('Y-m-d'),
        'anterior'  => $inicio->modify('-1 month')->format('Y-m'),
        'siguiente' => $inicio < $ultimoCerrado ? $inicio->modify('+1 month')->format('Y-m') : null,
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
 * Calcula los números del mes de cada vehículo activo de $ids (aunque no tenga movimientos).
 * Solo lo llama asegurarMesGenerado(): el resto lee la foto guardada.
 *
 * Por vehículo:
 *   km.inicial          último check-in antes del mes (desde ahí se mide el primer tramo);
 *                       km.inicial_fecha, su fecha
 *   km.final            último check-in del mes
 *   km.odometro         Σ tramos válidos de los check-ins del mes = mess + personales
 *   km.avance_lecturas  final − inicial. Si es mayor que odometro hubo tramos descartados
 *                       (negativos o de más de KM_VIAJE_MAX), que cuentan 0.
 *   checkins[]          con su tramo, días desde el check-in anterior y km Mess / personal
 *   cargas[]            con la hora de lectura (sin_hora = la carga no guardó hora)
 *   lectura_anterior    última lectura de km (check-in o carga) antes del mes
 *   ultima_carga_al_cierre  última carga con km hasta el último día del mes
 */
function datosMesVehiculos(mysqli $conn, array $mes, array $ids): array {
    $desde = $mes['desde'];
    $hasta = $mes['hasta'];
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    if (!$ids) return [];

    list($in, $t) = inEnteros($ids);
    $vehiculos = filasPreparadas($conn,
        "SELECT id_vehiculo, placa, TRIM(marca) AS marca, TRIM(modelo) AS modelo, id_us_asignado
         FROM inventario WHERE estatus = 'Activo' AND id_vehiculo IN ($in) ORDER BY placa", $t, $ids);
    if (!$vehiculos) return [];
    $idsVeh = array_map(function ($v) { return (int) $v['id_vehiculo']; }, $vehiculos);
    list($in, $t) = inEnteros($idsVeh);

    // ----- Check-ins del mes con su tramo (sqlKmMessCheckin: única definición de km)
    $checkins = filasPreparadas($conn,
        "SELECT av.id_actividad, av.id_vehiculo, av.fecha_actividad, av.km_actual, av.id_usuario, av.ot,
                av.foto_url, av.detalle_tipo_uso, m.km_tramo, m.dias, m.km_mess, m.km_personal, m.tipo_ref
         FROM actividad_vehiculo av
         LEFT JOIN " . sqlKmMessCheckin() . " m ON m.id_actividad = av.id_actividad
         WHERE av.tipo_actividad = 'INICIO' AND av.fecha_actividad >= ? AND av.fecha_actividad < ?
           AND av.id_vehiculo IN ($in)
         ORDER BY av.id_vehiculo, av.fecha_actividad, av.id_actividad",
        "ss" . $t, array_merge([$desde, $hasta], $idsVeh));

    // ----- Cargas del mes
    $cargas = filasPreparadas($conn,
        "SELECT cg.id, cg.id_vehiculo, cg.fecha_carga, " . SQL_FECHA_LECTURA_CARGA . " AS fecha_lectura,
                cg.km_actual, cg.monto, cg.pagos, cg.saldo, cg.id_usuario, cg.ot
         FROM carga_gasolina cg
         WHERE cg.fecha_carga >= ? AND cg.fecha_carga < ? AND cg.id_vehiculo IN ($in)
         ORDER BY cg.id_vehiculo, fecha_lectura, cg.km_actual, cg.id",
        "ss" . $t, array_merge([$desde, $hasta], $idsVeh));

    // ----- Último check-in antes del mes (km inicial)
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

    // ----- Última lectura (check-in o carga) antes del mes. kilometrajes no entra: el
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

    // ----- Última carga con km hasta el cierre del mes
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
            'km'          => ['inicial' => null, 'inicial_fecha' => null, 'final' => null, 'odometro' => 0, 'mess' => 0,
                              'personales' => 0, 'pct_mess' => null, 'avance_lecturas' => null],
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
        if (isset($ciAntes[$idv])) {
            $res[$idv]['km']['inicial'] = (int) $ciAntes[$idv]['km_actual'];
            $res[$idv]['km']['inicial_fecha'] = $ciAntes[$idv]['fecha_actividad'];
        }
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
// Tabla del mes: la foto de los números (datos) y el análisis que publica b4
// ---------------------------------------------------------------------------

/**
 * En producción la tabla ya existía y se reutilizó con RENAME + ALTER (24/11/2026). Esto solo
 * la crea en un entorno nuevo.
 */
function crearTablaAnalisisMensual(mysqli $conn): void {
    $conn->query("CREATE TABLE IF NOT EXISTS " . TABLA_ANALISIS_MENSUAL . " (
        id                INT AUTO_INCREMENT PRIMARY KEY,
        id_vehiculo       INT NOT NULL,
        mes               DATE NOT NULL,
        datos             MEDIUMTEXT NULL,
        datos_actualizado DATETIME NULL,
        nivel             VARCHAR(10) NULL,
        resumen           TEXT NULL,
        alertas           MEDIUMTEXT NULL,
        autor             VARCHAR(30) NULL,
        publicado         DATETIME NULL,
        creado            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        actualizado       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_veh_mes (id_vehiculo, mes),
        KEY idx_mes (mes)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/**
 * Garantiza que el mes tenga su foto. Si el mes nunca se generó, la genera para toda la flota
 * activa; si ya se generó, solo para los vehículos de $ids que falten (p. ej. uno que se dio de
 * alta después). Lo ya guardado no se vuelve a calcular.
 * @return int vehículos generados en esta llamada
 */
function asegurarMesGenerado(mysqli $conn, array $mes, array $ids = []): int {
    crearTablaAnalisisMensual($conn);
    $candado = 'analisis_mensual_' . $mes['clave'];   // clave = 'YYYY-MM', ya validada
    $conn->query("SELECT GET_LOCK('$candado', 60)");
    try {
        $generados = [];
        foreach (filasPreparadas($conn, "SELECT id_vehiculo FROM " . TABLA_ANALISIS_MENSUAL . "
                                         WHERE mes = ? AND datos IS NOT NULL", "s", [$mes['desde']]) as $f) {
            $generados[(int) $f['id_vehiculo']] = true;
        }
        if (!$generados) {
            $objetivo = array_map('intval', array_column(
                filasPreparadas($conn, "SELECT id_vehiculo FROM inventario WHERE estatus = 'Activo'"), 'id_vehiculo'));
        } else {
            $objetivo = array_map('intval', $ids);
        }
        $faltan = array_values(array_unique(array_filter($objetivo, function ($i) use ($generados) {
            return $i > 0 && !isset($generados[$i]);
        })));
        if (!$faltan) return 0;

        $datos = datosMesVehiculos($conn, $mes, $faltan);
        $st = $conn->prepare("INSERT INTO " . TABLA_ANALISIS_MENSUAL . " (id_vehiculo, mes, datos, datos_actualizado)
                              VALUES (?, ?, ?, NOW())
                              ON DUPLICATE KEY UPDATE datos = VALUES(datos), datos_actualizado = VALUES(datos_actualizado)");
        foreach ($datos as $d) {
            $idv = (int) $d['id_vehiculo'];
            $json = json_encode($d, JSON_UNESCAPED_UNICODE);
            $st->bind_param("iss", $idv, $mes['desde'], $json);
            $st->execute();
        }
        $st->close();
        return count($datos);
    } finally {
        $conn->query("SELECT RELEASE_LOCK('$candado')");
    }
}

/**
 * Filas guardadas del mes, por id_vehiculo: datos (foto), datos_actualizado y analisis (null
 * mientras b4 no publique). Sin $ids, todo el mes.
 */
function filasMes(mysqli $conn, array $mes, array $ids = []): array {
    $sql = "SELECT id_vehiculo, datos, datos_actualizado, nivel, resumen, alertas, autor, publicado
            FROM " . TABLA_ANALISIS_MENSUAL . " WHERE mes = ? AND datos IS NOT NULL";
    $tipos = 's';
    $params = [$mes['desde']];
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    if ($ids) {
        list($in, $t) = inEnteros($ids);
        $sql .= " AND id_vehiculo IN ($in)";
        $tipos .= $t;
        $params = array_merge($params, $ids);
    }
    $res = [];
    foreach (filasPreparadas($conn, $sql, $tipos, $params) as $f) {
        $datos = json_decode((string) $f['datos'], true);
        if (!is_array($datos)) continue;
        $alertas = json_decode((string) $f['alertas'], true);
        $res[(int) $f['id_vehiculo']] = [
            'datos'       => $datos,
            'actualizado' => $f['datos_actualizado'],
            'analisis'    => $f['nivel'] === null ? null : [
                'nivel'     => $f['nivel'],
                'resumen'   => $f['resumen'],
                'alertas'   => is_array($alertas) ? $alertas : [],
                'autor'     => $f['autor'],
                'publicado' => $f['publicado'],
            ],
        ];
    }
    return $res;
}
