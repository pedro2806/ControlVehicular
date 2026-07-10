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
                        <button class="btn btn-outline-warning btn-sm" onclick="descargarTabla()">
                            <i class="fas fa-file-excel me-1"></i> Descargar XLSX
                        </button>
                        <button class="btn btn-primary btn-sm" onclick="solicitarReposicionGeneral()">
                            <i class="fas fa-gas-pump me-1"></i> Solicitar reposición de crédito
                        </button>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header bg-white py-2">
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-4">
                                <label class="form-label small mb-1">Filtrar por vehículo</label>
                                <select id="filtroVehiculo" class="form-select form-select-sm">
                                    <option value="">Todos los vehículos</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small mb-1">Filtrar por usuario</label>
                                <select id="filtroUsuario" class="form-select form-select-sm">
                                    <option value="">Todos los usuarios</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <button class="btn btn-outline-secondary btn-sm" onclick="limpiarFiltros()">
                                    <i class="fas fa-times me-1"></i> Limpiar filtros
                                </button>
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
var datosGas = [];

// Filtros personalizados por columna (índice 0=vehículo, 1=usuario)
$.fn.dataTable.ext.search.push(function(settings, data) {
    if (settings.nTable.id !== 'tablaGas') return true;
    var filtroV = $('#filtroVehiculo').val();
    var filtroU = $('#filtroUsuario').val();
    if (filtroV && data[0].indexOf(filtroV) === -1) return false;
    if (filtroU && data[1].indexOf(filtroU) === -1) return false;
    return true;
});

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
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            infoEmpty: "Sin registros disponibles",
            infoFiltered: "(filtrado de _MAX_ totales)",
            search: "Buscar:",
            paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" }
        }
    });

    cargarHistorial();

    $('#filtroVehiculo, #filtroUsuario').on('change', function () {
        tabla.draw();
    });
});

function cargarHistorial() {
    $.ajax({
        url: 'acciones_gas.php',
        type: 'POST',
        data: { accion: 'obtenerHistorialGas' },
        dataType: 'json',
        success: function (data) {
            if (!Array.isArray(data)) return;
            datosGas = data;
            tabla.clear();

            // Registro más reciente (mayor id) por vehículo
            var latestIdPorVehiculo = {};
            data.forEach(function (r) {
                var vid = String(r.id_vehiculo);
                if (!latestIdPorVehiculo[vid] || parseInt(r.id) > parseInt(latestIdPorVehiculo[vid].id)) {
                    latestIdPorVehiculo[vid] = r;
                }
            });

            // IDs que muestran botón: son el más reciente de su vehículo Y el dueño es el usuario actual
            var currentUserId = getCookie('id_usuario') || getCookie('id_usuarioL') || '';
            var mostrarBoton = new Set();
            Object.values(latestIdPorVehiculo).forEach(function (r) {
                if (String(r.id_usuario) === String(currentUserId)) {
                    mostrarBoton.add(String(r.id));
                }
            });

            var vehiculos = {}, usuarios = {};

            data.forEach(function (r) {
                var saldo    = parseFloat(r.saldo) || 0;
                var kmCons   = parseInt(r.km_consumidos) || 0;
                var usuario  = r.nombre_usuario || r.usuario || '—';
                var placa    = r.placa || r.Vehiculo;

                vehiculos[placa]  = r.Vehiculo;
                usuarios[usuario] = usuario;

                var badgeSaldo = saldo <= 0
                    ? '<span class="badge bg-danger">$' + saldo.toFixed(2) + '</span>'
                    : saldo < 500
                        ? '<span class="badge bg-warning text-dark">$' + saldo.toFixed(2) + '</span>'
                        : '<span class="badge bg-success">$' + saldo.toFixed(2) + '</span>';

                var kmBadge = kmCons > 0
                    ? '<span class="text-primary fw-bold">' + kmCons.toLocaleString() + ' km</span>'
                    : '<span class="text-muted">—</span>';

                var btnRepos = mostrarBoton.has(String(r.id))
                    ? '<button class="btn btn-outline-primary btn-sm" '
                        + 'onclick="solicitarReposicion(' + r.id_vehiculo + ',' + saldo.toFixed(2) + ',\'' + escHtml(placa) + '\')" '
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

            // Poblar filtros
            var selV = $('#filtroVehiculo'), selU = $('#filtroUsuario');
            var currentV = selV.val(), currentU = selU.val();
            selV.empty().append('<option value="">Todos los vehículos</option>');
            selU.empty().append('<option value="">Todos los usuarios</option>');
            Object.entries(vehiculos).forEach(function([placa, label]) {
                selV.append('<option value="' + escHtml(placa) + '">' + escHtml(label) + '</option>');
            });
            Object.keys(usuarios).sort().forEach(function(u) {
                selU.append('<option value="' + escHtml(u) + '">' + escHtml(u) + '</option>');
            });
            if (currentV) selV.val(currentV);
            if (currentU) selU.val(currentU);
        },
        error: function () {
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar el historial.' });
        }
    });
}

function limpiarFiltros() {
    $('#filtroVehiculo, #filtroUsuario').val('');
    tabla.draw();
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

function solicitarReposicionGeneral() {
    // Obtiene el vehículo con menor saldo del historial del usuario actual
    var noEmp = getCookie('noEmpleado') || '';
    Swal.fire({
        title: 'Solicitar reposición de crédito',
        html: 'Se notificará al encargado que necesitas reposición de crédito de gasolina.',
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
            data: { accion: 'solicitarReposicionGas', id_vehiculo: 0, saldo: 0 },
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
    var wb = XLSX.utils.book_new();
    var ws = XLSX.utils.table_to_sheet(document.getElementById('tablaGas'));
    XLSX.utils.book_append_sheet(wb, ws, 'Gasolina');
    XLSX.writeFile(wb, 'Historial_Gasolina.xlsx');
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
