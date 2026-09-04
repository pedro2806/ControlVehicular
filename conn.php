<?php
$conn = new mysqli("localhost", "mess_incidencias", "Pipmytrade123", "mess_control_vehicular");
if ($conn->connect_error) {
    die("Error en la conexión: " . $conn->connect_error);
}