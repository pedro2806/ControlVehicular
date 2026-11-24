<?php
/**
 * Plantilla de config_analisis.php (ese archivo NO va en git: está en .gitignore).
 *
 * Token con el que el agente b4 usa api_analisis_semanal.php. Copiar este archivo como
 * config_analisis.php en el servidor y poner un token aleatorio de al menos 32 caracteres:
 *     php -r "echo bin2hex(random_bytes(24)), PHP_EOL;"
 * Es .php y no .ini a propósito: si alguien lo pide por URL, Apache lo ejecuta y no
 * muestra nada (token.ini, en cambio, se descarga tal cual).
 */
return [
    'token' => '',     // obligatorio, mínimo 32 caracteres
    'autor' => 'b4',   // se guarda en analisis_semanal_vehiculo.autor
];
