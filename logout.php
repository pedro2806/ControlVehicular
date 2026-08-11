<?php
// Cierre de sesión de Control Vehicular.
//
// Sin HTML ni JS a propósito: el navegador no llega a pintar nada, así que no
// hay pantalla en blanco si falla un CDN (era el bug del logout viejo, que
// dependía de jQuery y moría con ReferenceError dejando el <body> vacío).
//
// Solo se borran las cookies de CV. Las *L (noEmpleadoL, id_usuarioL, ...) son
// de loginMaster y viven en path=/: se respetan porque el destino es el inicio
// del hub, donde el usuario sigue logueado.

// CV escribe sus cookies sin path (quedan en /ControlVehicular), pero
// js/global/vehiculos.js sincroniza 'noEmpleado' en path=/ y ese es el que usa
// el guard de las vistas: hay que matar ambos o la sesión no se cierra.
$cookies = array(
    'id_usuario', 'nombredelusuario', 'noEmpleado', 'rol',
    'gps', 'SesionLogin', 'navSesion', 'antiguedad', 'diasD'
);

foreach ($cookies as $cookie) {
    foreach (array('/ControlVehicular', '/') as $path) {
        setcookie($cookie, '', time() - 3600, $path);
    }
}

header('Location: ../loginMaster/inicio');
exit;
