<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
$_POST['accion'] = 'checkInQR';
$_POST['id_vehiculo'] = 1;
$_POST['km_actual'] = 100;
$_COOKIE['noEmpleado'] = '1';
$_COOKIE['id_usuario'] = '1';
include 'acciones_qr.php';
