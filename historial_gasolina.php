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
        order: [[7, 'desc']],
        columnDefs: [{ orderable: false, targets: [9] }],
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
});

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

                tabla.row.add([
                    escHtml(r.Vehiculo),
                    escHtml(usuario),
                    '$' + parseFloat(r.monto).toFixed(2),
                    '$' + parseFloat(r.pagos || 0).toFixed(2),
                    badgeSaldo,
                    (parseInt(r.km_actual) || 0).toLocaleString() + ' km',
                    kmBadge,
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
