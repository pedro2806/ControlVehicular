<?php
/**
 * Km de fin de semana del personal con auto asignado.
 *
 * Sin check-out, los km del fin de semana no se podían separar del resto del uso. La
 * regla es:
 *   - Viernes: el asignado registra el km de cierre (tabla lecturas_odometro).
 *   - Sábado y domingo: si se usa el auto por horas extra, el check-in exige
 *     check-out (ver checkinAbierto() en acciones_qr.php).
 *   - Lunes: el primer check-in da la lectura de regreso.
 * El KPI semanal (calcular_ruta.php) separa con eso el km de trabajo del personal.
 *
 * El cierre del viernes va en su propia tabla y NO en actividad_vehiculo. Allí la
 * ÚLTIMA fila del usuario decide si tiene un check-in abierto (verificarEstadoCheckin
 * y el auto-cierre de checkInQR, ActividadesPendientes en acciones_kilometraje.php):
 * una lectura encima del check-in del viernes impediría que se cerrara. Por eso el
 * viejo 'registrarKMSemanal', que escribía ahí, se reemplazó.
 *
 * obtenerUltimoKM() (includes/api_bootstrap.php) y el KPI llaman a
 * asegurarTablaLecturasOdometro() antes de consultarla: si faltara, el prepare de
 * obtenerUltimoKM fallaría y todas las validaciones de "el km no puede ser menor al
 * último" quedarían en 0.
 */

if (!function_exists('asegurarTablaLecturasOdometro')) {
    function asegurarTablaLecturasOdometro(mysqli $conn): void {
        static $lista = false;
        if ($lista) return;
        $conn->query("CREATE TABLE IF NOT EXISTS lecturas_odometro (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            id_vehiculo INT NOT NULL,
            id_usuario  INT NOT NULL,
            tipo        ENUM('VIERNES') NOT NULL DEFAULT 'VIERNES',
            km          INT NOT NULL,
            foto_url    VARCHAR(255) NULL,
            fecha       DATETIME NOT NULL,
            KEY idx_vehiculo_fecha (id_vehiculo, fecha)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $lista = true;
    }
}

if (!function_exists('esViernesHoy')) {
    function esViernesHoy(): bool {
        return date('N') === '5';
    }
}
