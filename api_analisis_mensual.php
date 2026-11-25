<?php
/**
 * API del análisis mensual para el agente b4 (muse.ai). Pedido del 24/11/2026: al inicio de
 * cada mes b4 lee los números del mes pasado y publica aquí sus alertas; la pestaña Vehículo
 * del portal (loginMaster/inicio.php, vía acciones_resumen_mensual.php) las muestra junto con
 * los números.
 *
 * Autenticación: token en el encabezado `X-Api-Token` (o en el campo `token` del cuerpo).
 * El token vive en config_analisis.php (fuera de git; ver config_analisis.example.php). No se
 * usa `Authorization: Bearer` porque en cPanel ese encabezado no siempre llega a PHP. Nunca en
 * la URL: quedaría en los logs de Apache.
 *
 * Solo POST, cuerpo JSON (Content-Type: application/json) o formulario. Respuestas JSON.
 * El mes va como "YYYY-MM". Solo meses cerrados: el mes en curso o uno futuro se toma como el
 * mes pasado.
 *
 * Acciones:
 *   mes        {accion, mes?}  → números del mes de cada vehículo activo con check-ins o cargas.
 *                                Sin `mes`, el mes pasado. Salen de la foto guardada del mes (se
 *                                genera una sola vez, la primera vez que se pide); los km son los
 *                                de la regla única (sqlKmMessCheckin) y b4 no los recalcula.
 *   publicar   {accion, mes, analisis: [{id_vehiculo, resumen?, alertas: [{nivel, texto, detalle?}]}]}
 *              → guarda (o reemplaza) el análisis de esos vehículos en ese mes. Todo o nada: si
 *                un renglón no es válido no se guarda ninguno. nivel de cada alerta: 'rojo' o
 *                'ambar'; texto es el título (hasta 500) y detalle, la explicación (opcional, hasta
 *                1000). El nivel del vehículo lo calcula el servidor (rojo si hay alguna roja,
 *                ámbar si hay alguna ámbar, verde si no hay alertas).
 *   publicado  {accion, mes?} → el análisis guardado de ese mes.
 */
include 'includes/api_bootstrap.php';
require_once __DIR__ . '/calcular_ruta.php';            // sqlKmMessCheckin(); incluirlo no dispara su endpoint
require_once __DIR__ . '/includes/mes_vehiculos.php';

const ANALISIS_MAX_VEHICULOS = 300;
const ANALISIS_MAX_ALERTAS   = 100;
const ANALISIS_MAX_TEXTO     = 500;
const ANALISIS_MAX_DETALLE   = 1000;
const ANALISIS_MAX_RESUMEN   = 4000;

function responder(int $codigo, array $cuerpo): void {
    http_response_code($codigo);
    echo json_encode($cuerpo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ----- Configuración y token
$cfgArchivo = __DIR__ . '/config_analisis.php';
$cfg = is_file($cfgArchivo) ? include $cfgArchivo : [];
$tokenEsperado = is_array($cfg) ? (string) ($cfg['token'] ?? '') : '';
$autor = is_array($cfg) ? substr((string) ($cfg['autor'] ?? 'b4'), 0, 30) : 'b4';
if (strlen($tokenEsperado) < 32) {
    responder(503, ['status' => 'error', 'message' => 'La API no está configurada (config_analisis.php).']);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    responder(405, ['status' => 'error', 'message' => 'Usa POST.']);
}

$entrada = $_POST;
if (stripos((string) ($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json') !== false) {
    $entrada = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($entrada)) {
        responder(400, ['status' => 'error',
            'message' => 'El cuerpo no es JSON válido (' . json_last_error_msg() . '). Debe ir en UTF-8.']);
    }
}

$token = (string) ($_SERVER['HTTP_X_API_TOKEN'] ?? ($entrada['token'] ?? ''));
if ($token === '' || !hash_equals($tokenEsperado, $token)) {
    responder(401, ['status' => 'error', 'message' => 'Token inválido.']);
}

$accion = (string) ($entrada['accion'] ?? '');
$mes = mesPedido((string) ($entrada['mes'] ?? ''));
$infoMes = ['mes' => $mes['clave'], 'inicio' => $mes['desde'], 'fin' => $mes['fin']];

// ----- Leer los números del mes
if ($accion === 'mes') {
    asegurarMesGenerado($conn, $mes);
    $vehiculos = [];
    $actualizado = null;
    foreach (filasMes($conn, $mes) as $f) {
        $d = $f['datos'];
        if ($d['conteo']['checkins'] === 0 && $d['conteo']['cargas'] === 0) continue;   // sin movimientos
        $d['actualizado'] = $f['actualizado'];
        $vehiculos[] = $d;
        if ($actualizado === null || $f['actualizado'] < $actualizado) $actualizado = $f['actualizado'];
    }
    usort($vehiculos, function ($a, $b) { return strcmp((string) $a['placa'], (string) $b['placa']); });
    responder(200, [
        'status'       => 'success',
        'mes'          => $infoMes + ['actualizado' => $actualizado],
        'km_viaje_max' => KM_VIAJE_MAX,   // tramos negativos o mayores cuentan 0 en el odómetro
        'vehiculos'    => $vehiculos,
    ]);
}

// ----- Lo publicado
if ($accion === 'publicado') {
    $lista = [];
    foreach (filasMes($conn, $mes) as $idv => $f) {
        if ($f['analisis'] === null) continue;
        $lista[] = ['id_vehiculo' => $idv, 'placa' => $f['datos']['placa'] ?? null] + $f['analisis'];
    }
    responder(200, ['status' => 'success', 'mes' => $infoMes, 'analisis' => $lista]);
}

// ----- Publicar
if ($accion === 'publicar') {
    // El mes tiene que venir explícito y ser un mes cerrado: publicar en el mes equivocado por
    // un valor por defecto sería un error silencioso.
    if ((string) ($entrada['mes'] ?? '') !== $mes['clave']) {
        responder(400, ['status' => 'error',
            'message' => 'mes es obligatorio y debe ser un mes cerrado (YYYY-MM), no el mes en curso.']);
    }
    $lista = $entrada['analisis'] ?? null;
    if (!is_array($lista) || !$lista || count($lista) > ANALISIS_MAX_VEHICULOS) {
        responder(400, ['status' => 'error',
            'message' => 'analisis debe ser una lista de 1 a ' . ANALISIS_MAX_VEHICULOS . ' vehículos.']);
    }

    // Vehículos activos que existen.
    $idsPedidos = [];
    foreach ($lista as $a) if (is_array($a)) $idsPedidos[] = (int) ($a['id_vehiculo'] ?? 0);
    $activos = [];
    $idsValidos = array_values(array_unique(array_filter($idsPedidos)));
    if ($idsValidos) {
        list($in, $t) = inEnteros($idsValidos);
        foreach (filasPreparadas($conn, "SELECT id_vehiculo FROM inventario WHERE estatus = 'Activo' AND id_vehiculo IN ($in)", $t, $idsValidos) as $f) {
            $activos[(int) $f['id_vehiculo']] = true;
        }
    }

    $errores = [];
    $filas = [];
    $vistos = [];
    foreach ($lista as $i => $a) {
        $pre = "analisis[$i]";
        if (!is_array($a)) { $errores[] = "$pre: debe ser un objeto."; continue; }
        $idv = (int) ($a['id_vehiculo'] ?? 0);
        if ($idv <= 0 || !isset($activos[$idv])) { $errores[] = "$pre: id_vehiculo $idv no es un vehículo activo."; continue; }
        if (isset($vistos[$idv])) { $errores[] = "$pre: id_vehiculo $idv viene repetido."; continue; }
        $vistos[$idv] = true;

        $resumen = $a['resumen'] ?? null;
        if ($resumen !== null && !is_string($resumen)) { $errores[] = "$pre.resumen: debe ser texto."; continue; }
        $resumen = $resumen === null ? null : trim($resumen);
        if ($resumen !== null && mb_strlen($resumen) > ANALISIS_MAX_RESUMEN) {
            $errores[] = "$pre.resumen: máximo " . ANALISIS_MAX_RESUMEN . " caracteres."; continue;
        }

        $alertas = $a['alertas'] ?? [];
        if (!is_array($alertas) || count($alertas) > ANALISIS_MAX_ALERTAS) {
            $errores[] = "$pre.alertas: debe ser una lista de hasta " . ANALISIS_MAX_ALERTAS . "."; continue;
        }
        $limpias = [];
        $nivel = 'verde';
        foreach ($alertas as $j => $al) {
            $n = is_array($al) ? (string) ($al['nivel'] ?? '') : '';
            $txt = is_array($al) && is_string($al['texto'] ?? null) ? trim($al['texto']) : '';
            if (!in_array($n, ['rojo', 'ambar'], true)) { $errores[] = "$pre.alertas[$j].nivel: 'rojo' o 'ambar'."; continue 2; }
            if ($txt === '' || mb_strlen($txt) > ANALISIS_MAX_TEXTO) {
                $errores[] = "$pre.alertas[$j].texto: obligatorio, máximo " . ANALISIS_MAX_TEXTO . " caracteres."; continue 2;
            }
            // detalle (opcional, 25/11/2026): la explicación debajo del título (texto) en la pestaña.
            $det = is_array($al) ? ($al['detalle'] ?? null) : null;
            if ($det !== null && !is_string($det)) { $errores[] = "$pre.alertas[$j].detalle: debe ser texto."; continue 2; }
            $det = $det === null ? '' : trim($det);
            if (mb_strlen($det) > ANALISIS_MAX_DETALLE) {
                $errores[] = "$pre.alertas[$j].detalle: máximo " . ANALISIS_MAX_DETALLE . " caracteres."; continue 2;
            }
            $limpias[] = ['nivel' => $n, 'texto' => $txt] + ($det !== '' ? ['detalle' => $det] : []);
            if ($n === 'rojo') $nivel = 'rojo';
            elseif ($nivel === 'verde') $nivel = 'ambar';
        }
        $filas[] = [$idv, $nivel, $resumen, json_encode($limpias, JSON_UNESCAPED_UNICODE)];
    }

    if ($errores) {
        responder(400, ['status' => 'error', 'message' => 'No se guardó nada.', 'errores' => $errores]);
    }

    // El análisis va en la misma fila que la foto del mes: si no existe todavía, se genera.
    asegurarMesGenerado($conn, $mes, array_column($filas, 0));
    $conn->begin_transaction();
    try {
        $st = $conn->prepare(
            "INSERT INTO " . TABLA_ANALISIS_MENSUAL . " (id_vehiculo, mes, nivel, resumen, alertas, autor, publicado)
             VALUES (?, ?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE nivel = VALUES(nivel), resumen = VALUES(resumen), alertas = VALUES(alertas),
                                     autor = VALUES(autor), publicado = VALUES(publicado)"
        );
        foreach ($filas as $f) {
            list($idv, $nivel, $resumen, $alertasJson) = $f;
            $st->bind_param("isssss", $idv, $mes['desde'], $nivel, $resumen, $alertasJson, $autor);
            $st->execute();
        }
        $st->close();
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
        error_log('api_analisis_mensual publicar: ' . $e->getMessage());
        responder(500, ['status' => 'error', 'message' => 'No se guardó nada: error al escribir en la base de datos.']);
    }

    $porNivel = ['rojo' => 0, 'ambar' => 0, 'verde' => 0];
    foreach ($filas as $f) $porNivel[$f[1]]++;
    responder(200, ['status' => 'success', 'mes' => $infoMes, 'guardados' => count($filas), 'por_nivel' => $porNivel]);
}

responder(400, ['status' => 'error', 'message' => 'Acción no reconocida. Usa mes, publicar o publicado.']);
