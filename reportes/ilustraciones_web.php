<?php
/**
 * Copias ligeras de las ilustraciones de los vehículos.
 *
 * Las originales (img/QRide_vehiculos_ilustraciones/{id_vehiculo}.png) pesan unos 650 KB a
 * 2048×1152 y las vistas las muestran en un recuadro de 128×72. Este script deja al lado de
 * cada una web/{id_vehiculo}.jpg a 512×288 (nítida en pantallas 2× y 3×, unos 30 KB), que
 * es la que piden las vistas primero (htmlFotoVehiculo en js/global/vehiculos.js). Si una
 * copia falta, la vista usa la PNG original.
 *
 * Uso:  php reportes/ilustraciones_web.php
 *
 * Solo genera las que falten o cuya PNG sea más nueva que su copia: al agregar o cambiar la
 * ilustración de un vehículo basta con volver a correrlo.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Solo desde la terminal: php reportes/ilustraciones_web.php');
}

const ANCHO   = 512;
const ALTO    = 288;
const CALIDAD = 82;

$dir = __DIR__ . '/../img/QRide_vehiculos_ilustraciones';
$web = "$dir/web";
if (!is_dir($dir)) {
    fwrite(STDERR, "No existe la carpeta de ilustraciones: $dir\n");
    exit(1);
}
if (!is_dir($web) && !mkdir($web, 0755, true)) {
    fwrite(STDERR, "No se pudo crear $web\n");
    exit(1);
}

$inicio = microtime(true);
$hechas = $al_dia = $errores = 0;
$bytesPng = $bytesJpg = 0;

foreach (glob("$dir/*.png") as $png) {
    $id = basename($png, '.png');
    if (!ctype_digit($id)) continue;   // solo {id_vehiculo}.png
    $jpg = "$web/$id.jpg";

    if (is_file($jpg) && filemtime($jpg) >= filemtime($png)) { $al_dia++; continue; }

    $src = @imagecreatefrompng($png);
    if (!$src) { $errores++; echo "  No se pudo leer $id.png\n"; continue; }

    // Se escala para que quepa completa en 512×288, centrada sobre blanco (el fondo de las
    // ilustraciones). Las actuales ya son 16:9 y llenan el lienzo.
    $w = imagesx($src);
    $h = imagesy($src);
    $escala = min(ANCHO / $w, ALTO / $h);
    $dw = (int) round($w * $escala);
    $dh = (int) round($h * $escala);

    $dst = imagecreatetruecolor(ANCHO, ALTO);
    imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
    imagecopyresampled($dst, $src, (int) ((ANCHO - $dw) / 2), (int) ((ALTO - $dh) / 2), 0, 0, $dw, $dh, $w, $h);

    if (!imagejpeg($dst, $jpg, CALIDAD)) { $errores++; echo "  No se pudo escribir web/$id.jpg\n"; continue; }
    $hechas++;
    $bytesPng += filesize($png);
    $bytesJpg += filesize($jpg);
}

printf("Copias generadas: %d · ya al día: %d · errores: %d\n", $hechas, $al_dia, $errores);
if ($hechas > 0) {
    printf("Peso: %.1f MB en PNG -> %.1f MB en JPG (%.0f KB por vehículo).\n",
        $bytesPng / 1048576, $bytesJpg / 1048576, $bytesJpg / $hechas / 1024);
}
printf("Tiempo: %.1f seg.\n", microtime(true) - $inicio);
exit($errores > 0 ? 1 : 0);
