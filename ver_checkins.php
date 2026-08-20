<?php
session_start();
require_once __DIR__ . '/includes/sesion_cookies.php';
include 'conn.php';
if ($_COOKIE['noEmpleado'] == '' || $_COOKIE['noEmpleado'] == null) {
    echo '<script>window.location.assign("index")</script>';
    exit;
}

// Guard de la vista. El endpoint valida por su cuenta (acciones_kilometraje.php,
// accion 'consultarCheckins'); esto solo evita mostrar la página vacía a quien no
// tiene el acceso.
//
// El permiso es verTodosVehiculo: ver los check-in de toda la flota implica ver toda
// la flota, así que no se creó un permiso aparte que pudiera darse sin el otro.
$stmtAcc = $conn->prepare(
    "SELECT 1 FROM mess_rrhh.accesos_especiales
     WHERE noEmpleado = ? AND sistema = 'ctrlVehicular' AND opcion = 'verTodosVehiculo' AND estatus = 1
     LIMIT 1"
);
$noEmpVista = intval($_COOKIE['noEmpleado']);
$stmtAcc->bind_param("i", $noEmpVista);
$stmtAcc->execute();
$tieneAcceso = (bool) $stmtAcc->get_result()->fetch_assoc();
$stmtAcc->close();
if (!$tieneAcceso) {
    echo '<script>window.location.assign("inicio")</script>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Control Vehicular - Check-ins</title>
    <!-- MESS Design System -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="css/mess-ds.css" rel="stylesheet">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include 'menu.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'encabezado.php'; ?>
                <div class="container-fluid">

                    <div class="d-sm-flex align-items-center justify-content-between mb-3">
                        <h1 class="h3 mb-0 text-black-800">Check-ins de vehículos</h1>
                        <span class="badge bg-secondary fs-6" id="badgeTotal"></span>
                    </div>

                    <div class="card shadow mb-3">
                        <div class="card-body py-3">
                            <div class="row g-2 align-items-end">
                                <div class="col-6 col-md-3">
                                    <label for="fDesde" class="form-label mb-1 small fw-semibold">Desde</label>
                                    <input type="date" class="form-control form-control-sm" id="fDesde">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label for="fHasta" class="form-label mb-1 small fw-semibold">Hasta</label>
                                    <input type="date" class="form-control form-control-sm" id="fHasta">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label for="fVehiculo" class="form-label mb-1 small fw-semibold">Vehículo</label>
                                    <select class="form-select form-select-sm" id="fVehiculo">
                                        <option value="0">Todos</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-2 d-grid">
                                    <button class="btn btn-primary btn-sm" id="btnBuscar">
                                        <i class="fas fa-search me-1"></i> Buscar
                                    </button>
                                </div>
                            </div>
                            <div class="row g-2 mt-1">
                                <div class="col-12">
                                    <input type="text" class="form-control form-control-sm" id="fTexto"
                                           placeholder="Filtrar por placa, usuario, OT/OV o notas...">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-sm align-middle mb-0" id="tablaCheckins">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Vehículo</th>
                                            <th>Usuario</th>
                                            <th>Tipo</th>
                                            <th class="text-end">Km</th>
                                            <th>OT / OV</th>
                                            <th class="text-center">Foto</th>
                                            <th class="text-center">Ubicación</th>
                                            <th>Notas</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                            <div id="sinResultados" class="text-center text-muted py-5" style="display:none;">
                                <i class="fas fa-clipboard-list fa-3x mb-3 d-block"></i>
                                No hay check-ins en el rango seleccionado.
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <footer class="sticky-footer bg-white">
                <div class="container my-auto"><div class="copyright text-center my-auto">
                    <span>Grupo MESS</span>
                </div></div>
            </footer>
        </div>
    </div>

    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

<script>
    var filas = [];

    function esc(s) {
        return $('<div>').text(s == null ? '' : s).html();
    }

    function fmtFecha(f) {
        if (!f) return '';
        var p = f.split(/[- :]/);
        return p[2] + '/' + p[1] + '/' + p[0] + ' ' + (p[3] || '00') + ':' + (p[4] || '00');
    }

    // Cada tipo con su color, para poder barrer la tabla de un vistazo. Un INICIO sin
    // FINALIZACION posterior sale como "Abierto": es el caso que hay que perseguir,
    // porque el usuario nunca cerró la actividad.
    function badgeTipo(f) {
        if (f.tipo_actividad === 'INICIO') {
            return f.abierto == 1
                ? '<span class="badge bg-warning text-dark">Abierto</span>'
                : '<span class="badge bg-success">Entrada</span>';
        }
        if (f.tipo_actividad === 'FINALIZACION') return '<span class="badge bg-secondary">Salida</span>';
        if (f.tipo_actividad === 'KM_SEMANAL')   return '<span class="badge bg-info text-dark">Km semanal</span>';
        return '<span class="badge bg-light text-dark">' + esc(f.tipo_actividad) + '</span>';
    }

    function pintar() {
        var texto = $('#fTexto').val().toLowerCase().trim();
        var $tb = $('#tablaCheckins tbody').empty();
        var mostradas = 0;

        filas.forEach(function (f) {
            if (texto) {
                var blob = [f.placa, f.usuario, f.ot, f.notas, f.marca, f.modelo].join(' ').toLowerCase();
                if (blob.indexOf(texto) === -1) return;
            }
            mostradas++;

            var vehiculo = esc(f.placa || 'S/P');
            if (f.marca || f.modelo) {
                vehiculo += '<br><span class="small text-muted">' + esc([f.marca, f.modelo].filter(Boolean).join(' ')) + '</span>';
            }

            var foto = f.foto_url
                ? '<a href="' + esc(f.foto_url) + '" target="_blank" rel="noopener" title="Ver foto"><i class="fas fa-image"></i></a>'
                : '<span class="text-muted">&mdash;</span>';

            // Sin coordenadas no se pinta nada: un enlace de mapa vacío solo estorba.
            var ubic = f.coordenadas
                ? '<a href="https://www.google.com/maps?q=' + encodeURIComponent(f.coordenadas) + '" target="_blank" rel="noopener" title="' + esc(f.coordenadas) + '"><i class="fas fa-map-marker-alt"></i></a>'
                : '<span class="text-muted">&mdash;</span>';

            $tb.append(
                '<tr' + (f.abierto == 1 ? ' class="table-warning"' : '') + '>' +
                '<td class="text-nowrap small">' + fmtFecha(f.fecha_actividad) + '</td>' +
                '<td class="small">' + vehiculo + '</td>' +
                '<td class="small">' + esc(f.usuario || 'S/R') + '</td>' +
                '<td>' + badgeTipo(f) + '</td>' +
                '<td class="text-end small">' + (f.km_actual ? Number(f.km_actual).toLocaleString('es-MX') : '') + '</td>' +
                '<td class="small">' + esc(f.ot || '') + '</td>' +
                '<td class="text-center">' + foto + '</td>' +
                '<td class="text-center">' + ubic + '</td>' +
                '<td class="small text-muted">' + esc(f.notas || '') + '</td>' +
                '</tr>'
            );
        });

        $('#sinResultados').toggle(mostradas === 0);
        var abiertos = filas.filter(function (f) { return f.abierto == 1; }).length;
        $('#badgeTotal').text(mostradas + ' registros' + (abiertos ? ' · ' + abiertos + ' sin cerrar' : ''));
    }

    function cargar() {
        $('#tablaCheckins tbody').html('<tr><td colspan="9" class="text-center py-4"><i class="fas fa-spinner fa-spin"></i></td></tr>');
        $.ajax({
            url: 'acciones_kilometraje.php',
            method: 'POST',
            dataType: 'json',
            data: {
                accion: 'consultarCheckins',
                desde: $('#fDesde').val(),
                hasta: $('#fHasta').val(),
                id_vehiculo: $('#fVehiculo').val()
            },
            success: function (resp) {
                if (!resp || resp.status !== 'success') {
                    $('#tablaCheckins tbody').empty();
                    Swal.fire({ icon: 'error', title: 'Error', text: (resp && resp.message) ? resp.message : 'No se pudieron cargar los check-ins.' });
                    return;
                }
                // Array.isArray antes del forEach: las acciones_*.php devuelven un objeto
                // de error cuando algo falla y iterar sobre él rompe en silencio.
                filas = Array.isArray(resp.checkins) ? resp.checkins : [];
                $('#fDesde').val(resp.desde);
                $('#fHasta').val(resp.hasta);
                pintar();
            },
            error: function () {
                $('#tablaCheckins tbody').empty();
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar los check-ins.' });
            }
        });
    }

    $(document).ready(function () {
        // Lista de vehículos para el filtro. Se reutiliza la acción que ya existe.
        $.ajax({
            url: 'AccionesCheckVehiculo.php',
            method: 'POST',
            dataType: 'json',
            data: { opcion: 'llenaTVehiculosAsignados', cookieNoEmpleado: getCookie('noEmpleado') },
            success: function (data) {
                if (!Array.isArray(data)) return;
                data.forEach(function (v) {
                    $('#fVehiculo').append('<option value="' + v.id + '">' + esc(v.placa + ' - ' + (v.modelo || '')) + '</option>');
                });
            }
        });

        cargar();
        $('#btnBuscar').on('click', cargar);
        $('#fTexto').on('input', pintar);
    });
</script>
</body>
</html>
