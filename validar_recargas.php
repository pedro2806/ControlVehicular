<?php
session_start();
require_once __DIR__ . '/includes/sesion_cookies.php';
include 'conn.php';
if ($_COOKIE['noEmpleado'] == '' || $_COOKIE['noEmpleado'] == null) {
    echo '<script>window.location.assign("index")</script>';
    exit;
}

// Guard de la vista. El endpoint valida por su cuenta; esto solo evita mostrar la
// página a quien no tiene el acceso de consulta.
$stmtAcc = $conn->prepare(
    "SELECT 1 FROM mess_rrhh.accesos_especiales
     WHERE noEmpleado = ? AND sistema = 'ctrlVehicular' AND opcion = 'verSolicitudesGas' AND estatus = 1
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
    <title>Control Vehicular - Recargas</title>
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
                        <h1 class="h3 mb-0 text-black-800">Solicitudes de Recarga</h1>
                        <span class="badge bg-secondary fs-6" id="badgeTotal"></span>
                    </div>

                    <ul class="nav nav-pills mb-3" id="tabsEstatus">
                        <li class="nav-item"><button class="nav-link active" data-estatus="PENDIENTE">Pendientes <span class="badge bg-light text-dark ms-1" id="cntPENDIENTE">0</span></button></li>
                        <li class="nav-item"><button class="nav-link" data-estatus="APROBADA">Aprobadas <span class="badge bg-light text-dark ms-1" id="cntAPROBADA">0</span></button></li>
                        <li class="nav-item"><button class="nav-link" data-estatus="RECHAZADA">Rechazadas <span class="badge bg-light text-dark ms-1" id="cntRECHAZADA">0</span></button></li>
                        <li class="nav-item"><button class="nav-link" data-estatus="">Todas</button></li>
                    </ul>

                    <div id="feedSolicitudes" class="row"></div>

                    <div id="noResultados" class="text-center text-muted py-5" style="display:none;">
                        <i class="fas fa-gas-pump fa-3x mb-3 d-block"></i>
                        <p class="mb-0">No hay solicitudes en este estado.</p>
                    </div>
                </div>
            </div>
            <footer class="sticky-footer bg-white">
                <div class="container my-auto"><div class="copyright text-center my-auto">
                    <span>Copyright &copy; MESS <?php echo date("Y"); ?></span>
                </div></div>
            </footer>
        </div>
    </div>

    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

<script>
    var solicitudes = [], puedeResolver = false, estatusActivo = 'PENDIENTE';

    function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }

    function fmtFecha(f) {
        if (!f) return '';
        var p = String(f).split(/[- :]/);
        return p[2] + '/' + p[1] + '/' + p[0] + (p[3] ? ' ' + p[3] + ':' + (p[4] || '00') : '');
    }

    function badgeEstatus(e) {
        if (e === 'PENDIENTE') return '<span class="badge bg-warning text-dark">Pendiente</span>';
        if (e === 'APROBADA')  return '<span class="badge bg-success">Aprobada</span>';
        return '<span class="badge bg-danger">Rechazada</span>';
    }

    function pintar() {
        var $c = $('#feedSolicitudes').empty();
        var lista = estatusActivo ? solicitudes.filter(function (s) { return s.estatus === estatusActivo; }) : solicitudes;

        $('#noResultados').toggle(lista.length === 0);
        $('#badgeTotal').text(lista.length + (lista.length === 1 ? ' solicitud' : ' solicitudes'));

        lista.forEach(function (s) {
            var vehiculo = esc([s.marca, s.modelo].filter(Boolean).join(' ')) + ' — ' + esc(s.placa || 'S/P');

            // km_recorrido puede venir null: el cálculo se descarta cuando la lectura del
            // odómetro es incoherente, y es mejor no mostrar nada que una cifra falsa.
            var km = (s.km_recorrido !== null && s.km_recorrido !== undefined)
                ? Number(s.km_recorrido).toLocaleString('es-MX') + ' km'
                : '<span class="text-muted">sin dato</span>';

            var acciones = '';
            if (puedeResolver && s.estatus === 'PENDIENTE') {
                acciones =
                    '<div class="d-flex gap-2 mt-3">'
                    + '<button class="btn btn-success btn-sm flex-fill btn-resolver" data-id="' + s.id + '" data-res="APROBADA">'
                    + '<i class="fas fa-check me-1"></i>Aprobar</button>'
                    + '<button class="btn btn-outline-danger btn-sm flex-fill btn-resolver" data-id="' + s.id + '" data-res="RECHAZADA">'
                    + '<i class="fas fa-xmark me-1"></i>Rechazar</button>'
                    + '</div>';
            }

            var resuelta = '';
            if (s.estatus !== 'PENDIENTE') {
                resuelta = '<div class="small text-muted mt-2 border-top pt-2">'
                    + '<i class="fas fa-user-check me-1"></i>' + esc(s.resolvio || 'S/R')
                    + ' · ' + fmtFecha(s.fecha_resuelto)
                    + (s.notas_resolucion ? '<div class="mt-1"><i class="fas fa-comment me-1"></i>' + esc(s.notas_resolucion) + '</div>' : '')
                    + '</div>';
            }

            $c.append(
                '<div class="col-lg-6 col-12 mb-3">'
                + ' <div class="card shadow-sm h-100 border-0"><div class="card-body">'
                + '  <div class="d-flex justify-content-between align-items-start mb-2">'
                + '   <div><h5 class="mb-0 text-primary fw-bold"><i class="fas fa-car me-1"></i>' + esc(s.placa || 'S/P') + '</h5>'
                + '    <small class="text-muted">' + vehiculo + '</small></div>'
                + '   ' + badgeEstatus(s.estatus)
                + '  </div>'
                + '  <table class="table table-sm mb-0 small">'
                + '   <tr><td class="text-muted">Solicitó</td><td class="text-end fw-semibold">' + esc(s.solicitante || 'S/R') + '</td></tr>'
                + '   <tr><td class="text-muted">Fecha</td><td class="text-end">' + fmtFecha(s.fecha) + '</td></tr>'
                + '   <tr><td class="text-muted">Saldo al solicitar</td><td class="text-end fw-semibold">$' + Number(s.saldo_solicitud || 0).toLocaleString('es-MX', {minimumFractionDigits: 2}) + '</td></tr>'
                + '   <tr><td class="text-muted">Km del crédito</td><td class="text-end">' + km + '</td></tr>'
                + '   <tr><td class="text-muted">Kilometraje</td><td class="text-end">' + (s.km_actual ? Number(s.km_actual).toLocaleString('es-MX') + ' km' : '<span class="text-muted">S/R</span>') + '</td></tr>'
                + '  </table>'
                + resuelta + acciones
                + ' </div></div>'
                + '</div>'
            );
        });
    }

    function contar() {
        ['PENDIENTE', 'APROBADA', 'RECHAZADA'].forEach(function (e) {
            $('#cnt' + e).text(solicitudes.filter(function (s) { return s.estatus === e; }).length);
        });
    }

    function cargar() {
        $('#feedSolicitudes').html('<div class="col-12 text-center py-5"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i></div>');
        $.ajax({
            url: 'acciones_gas.php', method: 'POST', dataType: 'json',
            data: { accion: 'consultarSolicitudesGas' }
        }).done(function (resp) {
            if (!resp || resp.status !== 'success') {
                $('#feedSolicitudes').empty();
                Swal.fire({ icon: 'error', title: 'Error', text: (resp && resp.message) ? resp.message : 'No se pudieron cargar las solicitudes.' });
                return;
            }
            // Array.isArray antes de iterar: el endpoint devuelve un objeto de error
            // cuando algo falla y recorrerlo rompe en silencio.
            solicitudes  = Array.isArray(resp.solicitudes) ? resp.solicitudes : [];
            puedeResolver = !!resp.puedeResolver;
            contar();
            pintar();
        }).fail(function () {
            $('#feedSolicitudes').html('<div class="col-12 text-center text-danger py-5"><i class="fas fa-exclamation-circle fa-2x"></i><p class="mt-2">Error al cargar.</p></div>');
        });
    }

    $(document).ready(function () {
        cargar();

        $('#tabsEstatus button').on('click', function () {
            $('#tabsEstatus button').removeClass('active');
            $(this).addClass('active');
            estatusActivo = $(this).data('estatus');
            pintar();
        });

        $(document).on('click', '.btn-resolver', function () {
            var id  = $(this).data('id');
            var res = $(this).data('res');
            var aprobar = (res === 'APROBADA');

            Swal.fire({
                icon: aprobar ? 'question' : 'warning',
                title: aprobar ? '¿Aprobar la recarga?' : '¿Rechazar la solicitud?',
                input: 'textarea',
                inputLabel: 'Notas (opcional)',
                inputPlaceholder: aprobar ? 'Monto autorizado, comentarios...' : 'Motivo del rechazo...',
                showCancelButton: true,
                confirmButtonText: aprobar ? 'Aprobar' : 'Rechazar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: aprobar ? '#1cc88a' : '#e74a3b'
            }).then(function (r) {
                if (!r.isConfirmed) return;
                $.ajax({
                    url: 'acciones_gas.php', method: 'POST', dataType: 'json',
                    data: { accion: 'resolverSolicitudGas', id_solicitud: id, resolucion: res, notas: r.value || '' }
                }).done(function (resp) {
                    if (!resp || resp.status !== 'success') {
                        Swal.fire({ icon: 'error', title: 'No se pudo resolver', text: (resp && resp.message) ? resp.message : 'Error del servidor.' });
                        return;
                    }
                    Swal.fire({
                        icon: 'success',
                        title: aprobar ? 'Aprobada' : 'Rechazada',
                        text: resp.notificado ? 'Se avisó al solicitante por correo.' : 'Resuelta. No se pudo enviar el correo al solicitante.',
                        timer: 2500, timerProgressBar: true
                    });
                    cargar();
                }).fail(function () {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo resolver la solicitud.' });
                });
            });
        });
    });
</script>
</body>
</html>
