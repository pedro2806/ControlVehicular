<?php
include __DIR__ . '/../conn.php';
header('Content-Type: application/json');
mysqli_set_charset($conn, "utf8mb4");
date_default_timezone_set('America/Mexico_City');

// El login (loginMaster/messbook) a veces deja $_COOKIE['id_usuario'] vacío aunque sí
// puebla id_usuarioL (el id de usuario de CV confiable). Se normaliza aquí, una sola
// vez, para que TODO el código que lee $_COOKIE['id_usuario'] use el id correcto —
// sin tener que parchear cada módulo (gas, kilometraje, siniestros/consultarInventario, etc.).
if (!empty($_COOKIE['id_usuarioL']) && intval($_COOKIE['id_usuarioL']) > 0) {
    $_COOKIE['id_usuario'] = $_COOKIE['id_usuarioL'];
}

$tieneVehiculo = false;
if (!empty($_COOKIE['noEmpleadoL'])) {
    $connCV = new mysqli("localhost", "mess_incidencias", "Pipmytrade123", "mess_control_vehicular");
    if (!$connCV->connect_error) {
        $noEmpVeh = intval($_COOKIE['noEmpleadoL']);
        $stmtVeh = $connCV->prepare(
            "SELECT 1 FROM usuarios u
             WHERE u.noEmpleado = ?
               AND (
                 EXISTS (SELECT 1 FROM inventario i WHERE i.id_usuario = u.id_usuario OR i.id_us_asignado = u.id_usuario)
                 OR EXISTS (SELECT 1 FROM prestamos p WHERE p.id_usuario = u.id_usuario AND p.estatus IN ('AUTORIZADO','EN CURSO'))
               )
             LIMIT 1"
        );
        $stmtVeh->bind_param("i", $noEmpVeh);
        $stmtVeh->execute();
        $tieneVehiculo = (bool) $stmtVeh->get_result()->fetch_assoc();
        $stmtVeh->close();
        $connCV->close();
    }
}