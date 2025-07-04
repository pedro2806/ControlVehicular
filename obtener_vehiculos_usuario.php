<?php
header('Content-Type: application/json');
include 'conn.php';

$usuario = $_GET['usuario'] ?? '';
$usuario_esc = $conn->real_escape_string($usuario);

$vehiculos = [];

if ($usuario_esc) {
    // Solo vehículos usados por el usuario
    $res = $conn->query("
        SELECT DISTINCT inv.id_vehiculo AS id, inv.placa, inv.marca, inv.modelo
        FROM actividad_vehiculo av
        JOIN inventario inv ON inv.id_vehiculo = av.id_vehiculo
        WHERE av.id_usuario = '$usuario_esc'
        ORDER BY inv.marca, inv.modelo, inv.placa
    ");
} else {
    // Todos los vehículos si no hay usuario
    $res = $conn->query("
        SELECT id_vehiculo AS id, placa, marca, modelo
        FROM inventario
        ORDER BY marca, modelo, placa
    ");
}

while ($row = $res->fetch_assoc()) {
    $vehiculos[] = $row;
}

echo json_encode($vehiculos);