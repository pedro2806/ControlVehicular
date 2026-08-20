<?php
/**
 * Normalización de las cookies de sesión. Incluir ANTES de leer $_COOKIE['id_usuario'].
 *
 * El login (loginMaster/messbook) a veces deja $_COOKIE['id_usuario'] vacío aunque sí
 * puebla id_usuarioL, que es el id de usuario de CV confiable.
 *
 * Esto vivía dentro de includes/api_bootstrap.php, que solo incluyen los endpoints
 * acciones_*.php. Las VISTAS que leen la cookie por su cuenta se quedaban fuera del
 * arreglo: en qr_vehiculo.php eso hacía que idUsuarioActual valiera 0, que la
 * comparación de dueño/asignado fallara y que el botón "Llenar Checklist" se ocultara
 * — el usuario veía el aviso de checklist incompleto sin forma de actuar.
 *
 * Se extrajo a este archivo para que endpoints y vistas compartan la misma lógica en
 * lugar de duplicarla.
 */
if (!empty($_COOKIE['id_usuarioL']) && intval($_COOKIE['id_usuarioL']) > 0) {
    $_COOKIE['id_usuario'] = $_COOKIE['id_usuarioL'];
}
