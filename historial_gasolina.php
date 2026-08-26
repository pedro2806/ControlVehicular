<?php
session_start();
include 'conn.php';
if (empty($_COOKIE['noEmpleado'])) {
    echo '<script>window.location.assign("index")</script>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Control Vehicular - Historial de Gasolina</title>
    <!-- MESS Design System -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="css/mess-ds.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <!-- Responsive de DataTables (2.5.0): en móvil esconde las columnas que no caben
         y las muestra en una fila desplegable. Sin esto la tabla se salía de la
         pantalla. Se sirve desde css/lib y js/lib, no por CDN, para que no dependa
         de internet. -->
    <link rel="stylesheet" href="css/lib/responsive.dataTables.min.css">
</head>
<body id="page-top">
<div id="wrapper">
    <?php include 'menu.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include 'encabezado.php'; ?>
            <div class="container-fluid">

                <div class="d-sm-flex align-items-center justify-content-between mb-3">
                    <h1 class="h3 mb-0 text-gray-800">Historial de Cargas de Gasolina</h1>
                    <div class="d-flex gap-2">
                        <button id="btnDescargar" class="btn btn-outline-warning btn-sm" onclick="descargarTabla()" disabled>
                            <i class="fas fa-file-excel me-1"></i> Descargar XLSX
                        </button>
                        <button id="btnReposicion" class="btn btn-primary btn-sm" onclick="solicitarReposicionVehiculo()" disabled>
                            <i class="fas fa-gas-pump me-1"></i> Solicitar reposición de crédito
                        </button>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header bg-white py-2">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-6">
                                <label class="form-label small mb-1">Vehículo (tarjeta de gas)</label>
                                <select id="filtroVehiculo" class="form-select form-select-sm">
                                    <option value="">Selecciona un vehículo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tablaGas" class="table table-striped table-bordered w-100">
                                <thead>
                                    <tr>
                                        <th>Vehículo</th>
                                        <th>Usuario</th>
                                        <th>Monto</th>
                                        <th>Pagos</th>
                                        <th>Saldo</th>
                                        <th>Km Actual</th>
                                        <th>Km Consumidos</th>
                                        <!-- Cliente y OT/OV juntos: son opcionales y muchas cargas
                                             no traen ninguno, así que dos columnas casi vacías no
                                             se justifican. -->
                                        <th>Destino</th>
                                        <th>Fecha Carga</th>
                                        <th>Fecha Registro</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Seguimiento de lo que YO pedí. Antes, quien solicitaba una reposición
                     no tenía forma de saber en qué quedó: el estado solo existía en la
                     pantalla de quien autoriza, que casi nadie puede abrir.
                     La card se oculta cuando el usuario nunca ha solicitado nada. -->
                <div class="card shadow mb-4" id="cardMisSolicitudes" style="display:none;">
                    <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between">
                        <h6 class="m-0 fw-bold text-dark">
                            <i class="fas fa-clipboard-list me-1"></i> Mis solicitudes de recarga
                        </h6>
                        <span class="badge bg-secondary" id="badgeMisSolicitudes"></span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0" id="tablaMisSolicitudes">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center">Fecha</th>
                                        <th class="text-center">Vehículo</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-center d-none d-md-table-cell">Saldo al pedir</th>
                                        <th class="text-center">Abonado</th>
                                        <th class="text-center d-none d-lg-table-cell">Resolución</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div><!-- /.container-fluid -->
        </div><!-- /#content -->

        <footer class="sticky-footer bg-white">
            <div class="container my-auto">
                <div class="copyright text-center my-auto">
                    <span>Copyright &copy; MESS <?php echo date("Y"); ?></span>
                </div>
            </div>
        </footer>
    </div><!-- /#content-wrapper -->
</div><!-- /#wrapper -->

<a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="js/lib/dataTables.responsive.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
var tabla;
var idVehSel = 0;
var placaSel = '';
var saldoSel = 0;

$(document).ready(function () {
    tabla = $('#tablaGas').DataTable({
        destroy: true,
        paging: true,
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],
        ordering: true,
        searching: true,
        info: true,
        autoWidth: false,
        responsive: true,
        order: [[8, 'desc']],
        // El orden de prioridad decide qué columnas sobreviven en pantalla chica.
        // Lo que importa desde el celular es cuándo se cargó, cuánto queda y poder
        // actuar; el vehículo ya viene del selector de arriba y el usuario casi
        // siempre es uno mismo, así que esos dos son los primeros en esconderse.
        //
        // OJO: estos índices se corrieron al insertar "Destino" en la posición 7. Si se
        // agrega o mueve otra columna hay que recorrerlos de nuevo, o el orden por
        // defecto y la columna no ordenable apuntan a la equivocada.
        // 0 Vehículo · 1 Usuario · 2 Monto · 3 Pagos · 4 Saldo · 5 Km Actual
        // 6 Km Consumidos · 7 Destino · 8 Fecha Carga · 9 Fecha Registro · 10 Acciones
        columnDefs: [
            { orderable: false, targets: [10] },
            { responsivePriority: 1,  targets: [8, 10] },  // Fecha Carga, Acciones
            { responsivePriority: 2,  targets: 4 },        // Saldo
            { responsivePriority: 3,  targets: 2 },        // Monto
            { responsivePriority: 4,  targets: 7 },        // Destino
            { responsivePriority: 5,  targets: 3 },        // Pagos
            { responsivePriority: 6,  targets: 5 },        // Km Actual
            { responsivePriority: 7,  targets: 6 },        // Km Consumidos
            { responsivePriority: 8,  targets: 9 },        // Fecha Registro
            { responsivePriority: 9,  targets: 1 },        // Usuario
            { responsivePriority: 10, targets: 0 }         // Vehículo
        ],
        language: {
            lengthMenu: "Mostrar _MENU_ registros",
            zeroRecords: "No se encontraron resultados",
            emptyTable: "Selecciona un vehículo para ver su tarjeta de gas",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            infoEmpty: "Sin registros disponibles",
            infoFiltered: "(filtrado de _MAX_ totales)",
            search: "Buscar:",
            paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" }
        }
    });

    // El select se llena con los vehículos asignados (o todos si super usuario),
    // igual que mantenimiento; al cargar se precarga uno automáticamente.
    cargarSelectVehiculos(preseleccionarVehiculo);

    $('#filtroVehiculo').on('change', cargarHistorialVehiculo);

    cargarMisSolicitudes();
});

/** Badge del estado de una solicitud. Mismos colores que validar_recargas.php. */
function badgeSolicitud(e) {
    if (e === 'PENDIENTE') return '<span class="badge bg-warning text-dark">Pendiente</span>';
    // Parcialidad en azul, no en amarillo: ya recibió dinero, pero sigue abierta.
    if (e === 'PARCIAL')   return '<span class="badge bg-info text-dark">Parcialidad</span>';
    if (e === 'APROBADA')  return '<span class="badge bg-success">Aprobada</span>';
    return '<span class="badge bg-danger">Rechazada</span>';
}

/**
 * Solicitudes que hizo el usuario, para que pueda seguirlas sin depender de la pantalla
 * de autorización (que requiere un acceso especial que casi nadie tiene).
 */
function cargarMisSolicitudes() {
    $.ajax({
        url: 'acciones_gas.php',
        method: 'POST',
        dataType: 'json',
        data: { accion: 'misSolicitudesGas' },
        success: function (resp) {
            if (!resp || resp.status !== 'success' || !Array.isArray(resp.solicitudes)) return;

            var lista = resp.solicitudes;
            // Sin solicitudes la card no aparece: no tiene caso una tabla vacía en una
            // pantalla que ya trae otra.
            if (!lista.length) { $('#cardMisSolicitudes').hide(); return; }

            var credito = Number(resp.credito || 4000);
            var abiertas = lista.filter(function (s) {
                return s.estatus === 'PENDIENTE' || s.estatus === 'PARCIAL';
            }).length;

            $('#badgeMisSolicitudes').text(
                lista.length + (lista.length === 1 ? ' solicitud' : ' solicitudes')
                + (abiertas ? ' · ' + abiertas + ' sin cerrar' : '')
            );

            var $tb = $('#tablaMisSolicitudes tbody').empty();
            lista.forEach(function (s) {
                var abonado = Number(s.abonado_acumulado || 0);
                var falta = Math.max(0, credito - Number(s.saldo_solicitud || 0) - abonado);

                // Solo interesa lo abonado cuando hubo abono; y el "faltan" solo mientras
                // siga abierta, porque en una cerrada ya no hay nada que esperar.
                var celdaAbonado = '<span class="text-muted">—</span>';
                if (abonado > 0) {
                    celdaAbonado = '$' + abonado.toLocaleString('es-MX', { minimumFractionDigits: 2 });
                    if (s.estatus === 'PARCIAL' && falta > 0) {
                        celdaAbonado += '<div class="small text-muted">faltan $'
                            + falta.toLocaleString('es-MX', { minimumFractionDigits: 2 }) + '</div>';
                    }
                }

                var resolucion = '<span class="text-muted">—</span>';
                if (s.fecha_resuelto) {
                    resolucion = '<div class="small">' + escHtml(s.resolvio || 'S/R') + '</div>'
                        + '<div class="small text-muted">' + escHtml(s.fecha_resuelto) + '</div>'
                        + (s.notas_resolucion
                            ? '<div class="small fst-italic">' + escHtml(s.notas_resolucion) + '</div>'
                            : '');
                }

                $tb.append(
                    '<tr' + (s.estatus === 'PARCIAL' ? ' class="table-info"' : '') + '>'
                    + '<td class="small text-nowrap">' + escHtml(s.fecha) + '</td>'
                    + '<td class="small">' + escHtml(s.placa || 'S/P')
                    +   '<div class="text-muted d-lg-none">' + escHtml([s.marca, s.modelo].filter(Boolean).join(' ')) + '</div></td>'
                    + '<td class="text-center">' + badgeSolicitud(s.estatus) + '</td>'
                    + '<td class="text-end small text-nowrap d-none d-md-table-cell">$'
                    +   Number(s.saldo_solicitud || 0).toLocaleString('es-MX', { minimumFractionDigits: 2 }) + '</td>'
                    + '<td class="text-end small text-nowrap">' + celdaAbonado + '</td>'
                    + '<td class="small d-none d-lg-table-cell">' + resolucion + '</td>'
                    + '</tr>'
                );
            });

            $('#cardMisSolicitudes').show();
        }
    });
}

// Llena el select con consultarInventario (asignados / todos), como mantenimiento
function cargarSelectVehiculos(callback) {
    $.ajax({
        url: 'acciones_siniestro',
        type: 'POST',
        data: { accion: 'consultarInventario' },
        dataType: 'json',
        success: function (data) {
            var sel = $('#filtroVehiculo');
            sel.empty().append('<option value="">Selecciona un vehículo</option>');
            if (Array.isArray(data)) {
                data.forEach(function (v) {
                    sel.append('<option value="' + v.id_vehiculo + '" data-placa="' + escHtml(v.placa) + '">'
                        + escHtml(v.placa) + ' - ' + escHtml(v.modelo) + ' ' + escHtml(v.marca) + '</option>');
                });
            }
            if (typeof callback === 'function') callback();
        },
        error: function () {
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar la lista de vehículos.' });
        }
    });
}

// Precarga: 1 vehículo -> ese; varios -> el de más km recorridos (vehiculoPrincipalGas)
function preseleccionarVehiculo() {
    var opciones = $('#filtroVehiculo option').filter(function () { return this.value !== ''; });
    if (opciones.length === 0) { cargarHistorialVehiculo(); return; }
    if (opciones.length === 1) {
        $('#filtroVehiculo').val(opciones.first().val());
        cargarHistorialVehiculo();
        return;
    }
    $.ajax({
        url: 'acciones_gas.php',
        type: 'POST',
        data: { accion: 'vehiculoPrincipalGas' },
        dataType: 'json',
        success: function (resp) {
            var id = (resp && resp.id_vehiculo) ? String(resp.id_vehiculo) : '';
            if (id && $('#filtroVehiculo option[value="' + id + '"]').length) {
                $('#filtroVehiculo').val(id);
            } else {
                $('#filtroVehiculo').val(opciones.first().val());
            }
            cargarHistorialVehiculo();
        },
        error: function () {
            $('#filtroVehiculo').val(opciones.first().val());
            cargarHistorialVehiculo();
        }
    });
}

function cargarHistorialVehiculo() {
    idVehSel = parseInt($('#filtroVehiculo').val()) || 0;
    placaSel = $('#filtroVehiculo option:selected').data('placa') || '';

    if (!idVehSel) {
        tabla.clear().draw();
        toggleBotones(false);
        return;
    }

    $.ajax({
        url: 'acciones_gas.php',
        type: 'POST',
        data: { accion: 'obtenerHistorialGas', id_vehiculo: idVehSel },
        dataType: 'json',
        success: function (data) {
            if (!Array.isArray(data)) data = [];
            tabla.clear();

            // La carga más reciente (mayor id) muestra el botón de reposición si es del usuario actual
            var currentUserId = getCookie('id_usuario') || getCookie('id_usuarioL') || '';
            var latest = null;
            data.forEach(function (r) {
                if (!latest || parseInt(r.id) > parseInt(latest.id)) latest = r;
            });
            saldoSel = latest ? (parseFloat(latest.saldo) || 0) : 0;

            data.forEach(function (r) {
                var saldo   = parseFloat(r.saldo) || 0;
                var kmCons  = parseInt(r.km_consumidos) || 0;
                var usuario = r.nombre_usuario || r.usuario || '—';

                var badgeSaldo = saldo <= 0
                    ? '<span class="badge bg-danger">$' + saldo.toFixed(2) + '</span>'
                    : saldo < 500
                        ? '<span class="badge bg-warning text-dark">$' + saldo.toFixed(2) + '</span>'
                        : '<span class="badge bg-success">$' + saldo.toFixed(2) + '</span>';

                var kmBadge = kmCons > 0
                    ? '<span class="text-primary fw-bold">' + kmCons.toLocaleString() + ' km</span>'
                    : '<span class="text-muted">—</span>';

                var esUltimoPropio = latest && String(r.id) === String(latest.id)
                    && String(r.id_usuario) === String(currentUserId);
                var btnRepos = esUltimoPropio
                    ? '<button class="btn btn-outline-primary btn-sm" '
                        + 'onclick="solicitarReposicion(' + r.id_vehiculo + ',' + saldo.toFixed(2) + ',\'' + escHtml(placaSel) + '\')" '
                        + 'title="Solicitar reposición de crédito">'
                        + '<i class="fas fa-redo me-1"></i>Renovar</button>'
                    : '';

                // Cliente y OT/OV en una sola celda. Cuando no hay ninguno se pone un guion
                // en vez de dejarla vacía, para que se lea como "sin dato" y no como error.
                var destino = '';
                if (r.cliente) destino += escHtml(r.cliente);
                if (r.ot) destino += (destino ? '<div class="text-muted small">' : '<span class="text-muted small">')
                                   + 'OT/OV: ' + escHtml(r.ot) + (destino ? '</div>' : '</span>');
                if (!destino) destino = '<span class="text-muted">—</span>';

                tabla.row.add([
                    escHtml(r.Vehiculo),
                    escHtml(usuario),
                    '$' + parseFloat(r.monto).toFixed(2),
                    '$' + parseFloat(r.pagos || 0).toFixed(2),
                    badgeSaldo,
                    (parseInt(r.km_actual) || 0).toLocaleString() + ' km',
                    kmBadge,
                    destino,
                    r.fecha_carga || '—',
                    r.fecha_registro || '—',
                    btnRepos
                ]);
            });

            tabla.draw(false);
            toggleBotones(data.length > 0);
        },
        error: function () {
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar el historial.' });
        }
    });
}

function toggleBotones(activo) {
    $('#btnDescargar, #btnReposicion').prop('disabled', !activo);
}

function solicitarReposicion(id_vehiculo, saldo, placa) {
    Swal.fire({
        title: '¿Solicitar reposición?',
        html: 'Se enviará un correo al encargado solicitando crédito de gasolina para <strong>' + escHtml(placa) + '</strong>.<br>Saldo actual: <strong>$' + parseFloat(saldo).toFixed(2) + '</strong>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-paper-plane me-1"></i> Enviar solicitud',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#050D9E'
    }).then(function (result) {
        if (!result.isConfirmed) return;
        $.ajax({
            url: 'acciones_gas.php',
            method: 'POST',
            dataType: 'json',
            data: { accion: 'solicitarReposicionGas', id_vehiculo: id_vehiculo, saldo: saldo },
            success: function (resp) {
                Swal.fire({
                    icon: resp.status === 'success' ? 'success' : 'error',
                    title: resp.status === 'success' ? '¡Enviado!' : 'Error',
                    text: resp.message,
                    timer: 2500,
                    showConfirmButton: false
                });
                // Se refresca el seguimiento para que la solicitud recién hecha aparezca
                // de inmediato; si no, habría que recargar la página para verla.
                if (resp.status === 'success') cargarMisSolicitudes();
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo enviar la solicitud.' });
            }
        });
    });
}

function solicitarReposicionVehiculo() {
    if (!idVehSel) return;
    Swal.fire({
        title: '¿Solicitar reposición?',
        html: 'Se enviará un correo al encargado solicitando crédito de gasolina para <strong>' + escHtml(placaSel) + '</strong>.<br>Saldo actual: <strong>$' + saldoSel.toFixed(2) + '</strong>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-paper-plane me-1"></i> Enviar solicitud',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#050D9E'
    }).then(function (result) {
        if (!result.isConfirmed) return;
        $.ajax({
            url: 'acciones_gas.php',
            method: 'POST',
            dataType: 'json',
            data: { accion: 'solicitarReposicionGas', id_vehiculo: idVehSel, saldo: saldoSel },
            success: function (resp) {
                Swal.fire({
                    icon: resp.status === 'success' ? 'success' : 'error',
                    title: resp.status === 'success' ? '¡Enviado!' : 'Error',
                    text: resp.message,
                    timer: 2500,
                    showConfirmButton: false
                });
                // Se refresca el seguimiento para que la solicitud recién hecha aparezca
                // de inmediato; si no, habría que recargar la página para verla.
                if (resp.status === 'success') cargarMisSolicitudes();
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo enviar la solicitud.' });
            }
        });
    });
}

function descargarTabla() {
    if (!idVehSel) return;
    var wb = XLSX.utils.book_new();
    var ws = XLSX.utils.table_to_sheet(document.getElementById('tablaGas'));
    XLSX.utils.book_append_sheet(wb, ws, 'Gasolina');
    var placa = placaSel ? String(placaSel).replace(/[^A-Za-z0-9_-]/g, '') : ('veh' + idVehSel);
    XLSX.writeFile(wb, 'Gasolina_' + placa + '.xlsx');
}

function escHtml(str) {
    if (str == null) return '';
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function getCookie(name) {
    var v = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
    return v ? decodeURIComponent(v[2]) : '';
}
</script>
</body>
</html>
