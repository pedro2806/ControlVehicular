<?php
// Vista pura: sin conn.php y sin consultas. La sesión la valida menu.php y el permiso
// lo resuelve acciones_vehiculos.php en cada llamada; si no lo tiene, el primer AJAX
// responde con el error y esta pantalla redirige.
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Control Vehicular - Vehículos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="css/mess-ds.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="css/lib/responsive.bootstrap5.min.css">
</head>
<body id="page-top">
<div id="wrapper">
    <!-- menu.php va DENTRO de #wrapper y antes de #content-wrapper: el layout es un flex
         de dos columnas (sidebar + contenido). Incluirlo antes del <!DOCTYPE> deja el
         sidebar fuera del wrapper y el contenido se pierde de la pantalla. -->
    <?php include 'menu.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include 'encabezado.php'; ?>
            <div class="container-fluid">

                <div class="d-sm-flex align-items-center justify-content-between mb-3">
                    <h1 class="h3 mb-0 text-black-800">Vehículos</h1>
                    <button class="btn btn-primary btn-sm" id="btnAlta">
                        <i class="fas fa-plus me-1"></i> Dar de alta
                    </button>
                </div>

                <ul class="nav nav-pills mb-3" id="tabsEstatusVeh">
                    <li class="nav-item"><button class="nav-link active" data-filtro="activos">Activos <span class="badge bg-light text-dark ms-1" id="cntActivos">0</span></button></li>
                    <li class="nav-item"><button class="nav-link" data-filtro="inactivos">Inactivos <span class="badge bg-light text-dark ms-1" id="cntInactivos">0</span></button></li>
                    <li class="nav-item"><button class="nav-link" data-filtro="todos">Todos</button></li>
                </ul>

                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tablaVehiculos" class="table table-striped table-bordered w-100">
                                <thead>
                                    <tr>
                                        <th>Placa</th>
                                        <th>Usuario</th>
                                        <th>Marca</th>
                                        <th>Modelo</th>
                                        <th>Año</th>
                                        <th>Área</th>
                                        <th>Tarjeta</th>
                                        <th>Estatus</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
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

<!-- Alta y edición. El mismo modal sirve para las dos: cambia el título y si se manda
     id_vehiculo o no. -->
<div class="modal fade" id="modalVehiculo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title mb-0" id="tituloModalVehiculo">Dar de alta un vehículo</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="formVehiculo" autocomplete="off">
                    <input type="hidden" id="vId" value="0">
                    <div class="row g-2">
                        <div class="col-12 col-md-4">
                            <label for="vPlaca" class="form-label">Placa <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" id="vPlaca" maxlength="9" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="vMarca" class="form-label">Marca <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="vMarca" maxlength="50" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="vModelo" class="form-label">Modelo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="vModelo" maxlength="50" required>
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="vAnio" class="form-label">Año <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="vAnio" min="1990" step="1" required>
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="vColor" class="form-label">Color</label>
                            <input type="text" class="form-control" id="vColor" maxlength="50">
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="vArea" class="form-label">Área</label>
                            <select class="form-select" id="vArea"></select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="vKmMant" class="form-label">Km mantenimiento</label>
                            <!-- 15,000 es el intervalo estándar de la flota: 106 de los 107
                                 vehículos lo tienen. Se deja editable por si alguna unidad
                                 requiere un intervalo distinto. -->
                            <input type="number" class="form-control" id="vKmMant" min="0" step="1" value="15000">
                        </div>
                        <div class="col-12 col-md-8">
                            <label for="vUsuario" class="form-label">Usuario asignado</label>
                            <select class="form-select" id="vUsuario"></select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="vEfecticard" class="form-label">Tarjeta Efecticard</label>
                            <input type="number" class="form-control" id="vEfecticard" min="0" step="1"
                                   placeholder="Sin tarjeta">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnGuardarVehiculo">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="js/lib/dataTables.responsive.min.js"></script>
<script src="js/lib/responsive.bootstrap5.min.js"></script>

<script>
    var tabla = null, filtroActivo = 'activos', areas = [], usuarios = [];

    function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }

    function cargarUsuarios() {
        // Endpoint propio y NO el de préstamos: aquel devuelve solo jefes (rol = 3) y a
        // un vehículo se le asigna cualquier empleado.
        return $.ajax({
            url: 'acciones_vehiculos.php', method: 'POST', dataType: 'json',
            data: { accion: 'usuariosAsignables' }
        }).then(function (resp) {
            usuarios = (resp && Array.isArray(resp.usuarios)) ? resp.usuarios : [];
        }, function () { usuarios = []; });
    }

    function pintarTabla(lista) {
        tabla.clear();
        lista.forEach(function (v) {
            var activo = String(v.estatus).trim() === 'Activo';
            var acciones =
                '<div class="d-flex gap-1 justify-content-center">'
                + '<a class="btn btn-outline-secondary btn-sm" href="historial_vehiculo?v=' + v.id_vehiculo + '" title="Historial"><i class="fas fa-clock-rotate-left"></i></a>'
                + '<button class="btn btn-outline-primary btn-sm btn-editar" data-id="' + v.id_vehiculo + '" title="Editar"><i class="fas fa-pen"></i></button>'
                + '<button class="btn btn-outline-' + (activo ? 'danger' : 'success') + ' btn-sm btn-estatus"'
                +   ' data-id="' + v.id_vehiculo + '" data-placa="' + esc(v.placa) + '"'
                +   ' data-estatus="' + (activo ? 'INACTIVO' : 'ACTIVO') + '"'
                +   ' title="' + (activo ? 'Dar de baja' : 'Reactivar') + '">'
                +   '<i class="fas fa-' + (activo ? 'ban' : 'rotate-left') + '"></i></button>'
                + '</div>';

            tabla.row.add([
                esc(v.placa),
                esc(v.nombre_usuario || v.usuario || 'S/A'),
                esc(v.marca), esc(v.modelo), esc(v.anio), esc(v.area || '—'),
                v.efecticard ? esc(v.efecticard) : '<span class="text-muted">—</span>',
                '<span class="badge bg-' + (activo ? 'success' : 'secondary') + '">'
                    + esc(String(v.estatus).trim()) + '</span>',
                acciones
            ]);
        });
        tabla.draw();
    }

    function cargarVehiculos() {
        $.ajax({
            url: 'acciones_vehiculos.php', method: 'POST', dataType: 'json',
            data: { accion: 'listarVehiculos', filtro: filtroActivo }
        }).done(function (resp) {
            if (!resp || resp.status !== 'success') {
                // El permiso lo decide el servidor. Si no lo tiene, no se le deja una
                // pantalla vacía sin explicación: se avisa y se le manda a inicio.
                if (resp && /acceso/i.test(resp.message || '')) {
                    Swal.fire({
                        icon: 'warning', title: 'Sin acceso', text: resp.message,
                        confirmButtonText: 'Entendido'
                    }).then(function () { window.location.assign('inicio'); });
                    return;
                }
                Swal.fire({ icon: 'error', title: 'Error', text: (resp && resp.message) || 'No se pudo cargar la lista.' });
                return;
            }
            areas = resp.areas || [];
            window._vehiculos = resp.vehiculos;
            pintarTabla(resp.vehiculos);

            // Los conteos salen de la lista actual solo cuando estás en esa pestaña; para
            // no hacer tres consultas, se piden los totales una vez con filtro=todos.
            if (filtroActivo === 'todos') {
                var act = resp.vehiculos.filter(function (v) { return String(v.estatus).trim() === 'Activo'; }).length;
                $('#cntActivos').text(act);
                $('#cntInactivos').text(resp.vehiculos.length - act);
            }
        });
    }

    function abrirModal(v) {
        $('#vId').val(v ? v.id_vehiculo : 0);
        $('#tituloModalVehiculo').text(v ? 'Editar ' + v.placa : 'Dar de alta un vehículo');
        $('#vPlaca').val(v ? v.placa : '');
        $('#vMarca').val(v ? v.marca : '');
        $('#vModelo').val(v ? v.modelo : '');
        $('#vAnio').val(v ? v.anio : new Date().getFullYear());
        $('#vColor').val(v ? v.color : '');
        // Al editar se respeta lo que traiga el vehículo; solo en el alta (y si viene en
        // blanco) se propone el estándar de la flota.
        $('#vKmMant').val(v ? (v.km_mantenimiento || 15000) : 15000);
        $('#vEfecticard').val(v && v.efecticard ? v.efecticard : '');

        var opAreas = '<option value="">Sin área</option>';
        areas.forEach(function (a) {
            opAreas += '<option value="' + esc(a) + '"' + (v && v.area === a ? ' selected' : '') + '>' + esc(a) + '</option>';
        });
        $('#vArea').html(opAreas);

        var opUsr = '<option value="0">Sin asignar</option>';
        var encontrado = false;
        usuarios.forEach(function (u) {
            var sel = v && String(v.id_usuario) === String(u.id_usuario);
            if (sel) encontrado = true;
            opUsr += '<option value="' + esc(u.id_usuario) + '"' + (sel ? ' selected' : '') + '>'
                   + esc(u.nombre) + '</option>';
        });
        // Si el dueño actual no está en el catálogo (usuario dado de baja, o un
        // id_usuario huérfano como los que dejó la conciliación), se agrega igual y
        // seleccionado: sin esto el select diría "Sin asignar" y guardar lo borraría.
        if (v && Number(v.id_usuario) > 0 && !encontrado) {
            opUsr += '<option value="' + esc(v.id_usuario) + '" selected>'
                   + esc((v.nombre_usuario || v.usuario || 'Usuario ' + v.id_usuario) + ' (fuera de catálogo)')
                   + '</option>';
        }
        $('#vUsuario').html(opUsr);

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalVehiculo')).show();
    }

    $(document).ready(function () {
        tabla = $('#tablaVehiculos').DataTable({
            destroy: true, paging: true, pageLength: 25, autoWidth: false, responsive: true,
            order: [[0, 'asc']],
            // targets 8 = Acciones. Si se inserta una columna hay que recorrer estos índices.
            columnDefs: [
                { orderable: false, targets: [8] },
                { responsivePriority: 1, targets: [0, 8] },   // Placa, Acciones
                { responsivePriority: 2, targets: 7 },        // Estatus
                { responsivePriority: 3, targets: 1 },        // Usuario
                { responsivePriority: 8, targets: [4, 5, 6] }
            ],
            language: {
                lengthMenu: "Mostrar _MENU_ registros",
                zeroRecords: "No se encontraron vehículos",
                emptyTable: "No hay vehículos en este estado",
                info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                infoEmpty: "Sin registros", infoFiltered: "(filtrado de _MAX_ totales)",
                search: "Buscar:",
                paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" }
            }
        });

        // Los usuarios se cargan una vez y se reutilizan en cada apertura del modal.
        cargarUsuarios().always(function () {
            // Primero 'todos' para tener los conteos de las pestañas, luego el filtro real.
            $.ajax({
                url: 'acciones_vehiculos.php', method: 'POST', dataType: 'json',
                data: { accion: 'listarVehiculos', filtro: 'todos' }
            }).done(function (resp) {
                if (resp && resp.status === 'success') {
                    var act = resp.vehiculos.filter(function (v) { return String(v.estatus).trim() === 'Activo'; }).length;
                    $('#cntActivos').text(act);
                    $('#cntInactivos').text(resp.vehiculos.length - act);
                }
                cargarVehiculos();
            });
        });

        $('#tabsEstatusVeh button').on('click', function () {
            $('#tabsEstatusVeh button').removeClass('active');
            $(this).addClass('active');
            filtroActivo = $(this).data('filtro');
            cargarVehiculos();
        });

        $('#btnAlta').on('click', function () { abrirModal(null); });

        $(document).on('click', '.btn-editar', function () {
            var id = $(this).data('id');
            var v = (window._vehiculos || []).filter(function (x) { return x.id_vehiculo == id; })[0];
            if (v) abrirModal(v);
        });

        $('#btnGuardarVehiculo').on('click', function () {
            var $btn = $(this).prop('disabled', true);
            $.ajax({
                url: 'acciones_vehiculos.php', method: 'POST', dataType: 'json',
                data: {
                    accion: 'guardarVehiculo',
                    id_vehiculo: $('#vId').val(),
                    placa: $('#vPlaca').val(),
                    marca: $('#vMarca').val(),
                    modelo: $('#vModelo').val(),
                    anio: $('#vAnio').val(),
                    color: $('#vColor').val(),
                    area: $('#vArea').val(),
                    km_mantenimiento: $('#vKmMant').val(),
                    id_usuario: $('#vUsuario').val(),
                    efecticard: $('#vEfecticard').val()
                }
            }).done(function (resp) {
                if (!resp || resp.status !== 'success') {
                    Swal.fire({ icon: 'error', title: 'No se pudo guardar', text: (resp && resp.message) || 'Error del servidor.' });
                    return;
                }
                // El backdrop se limpia a mano antes del SweetAlert: al cerrar un modal BS5
                // y abrir un Swal enseguida, el backdrop se queda bloqueando la pantalla.
                var modalEl = document.getElementById('modalVehiculo');
                modalEl.addEventListener('hidden.bs.modal', function () {
                    document.querySelectorAll('.modal-backdrop').forEach(function (el) { el.remove(); });
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                    Swal.fire({ icon: 'success', title: 'Listo', text: resp.message, timer: 2000, showConfirmButton: false });
                    cargarVehiculos();
                }, { once: true });
                bootstrap.Modal.getInstance(modalEl).hide();
            }).fail(function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo conectar con el servidor.' });
            }).always(function () { $btn.prop('disabled', false); });
        });

        $(document).on('click', '.btn-estatus', function () {
            var id = $(this).data('id');
            var placa = $(this).data('placa');
            var nuevo = $(this).data('estatus');
            var esBaja = nuevo === 'INACTIVO';

            Swal.fire({
                icon: esBaja ? 'warning' : 'question',
                title: esBaja ? '¿Dar de baja ' + placa + '?' : '¿Reactivar ' + placa + '?',
                html: esBaja
                    ? '<div class="text-start"><p class="small">El vehículo desaparece de los selectores, pero <b>conserva todo su historial</b>. Se puede reactivar después.</p>'
                      + '<label class="form-label small fw-semibold" for="swalMotivo">Motivo de la baja</label>'
                      + '<textarea id="swalMotivo" class="form-control" rows="3" placeholder="Vendido, siniestro total, fin de arrendamiento..."></textarea></div>'
                    : '<p class="small">Volverá a aparecer en los selectores y podrá recibir registros.</p>',
                showCancelButton: true,
                confirmButtonText: esBaja ? 'Dar de baja' : 'Reactivar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: esBaja ? '#e74a3b' : '#1cc88a',
                preConfirm: function () {
                    if (!esBaja) return { motivo: '' };
                    var m = ($('#swalMotivo').val() || '').trim();
                    // Obligatorio: sin motivo, meses después nadie sabe si el auto se
                    // vendió, se siniestró o solo se guardó.
                    if (!m) { Swal.showValidationMessage('Escribe el motivo de la baja.'); return false; }
                    return { motivo: m };
                }
            }).then(function (r) {
                if (!r.isConfirmed) return;
                $.ajax({
                    url: 'acciones_vehiculos.php', method: 'POST', dataType: 'json',
                    data: { accion: 'cambiarEstatusVehiculo', id_vehiculo: id, estatus: nuevo, motivo: r.value.motivo }
                }).done(function (resp) {
                    if (!resp || resp.status !== 'success') {
                        Swal.fire({ icon: 'error', title: 'Error', text: (resp && resp.message) || 'No se pudo cambiar el estatus.' });
                        return;
                    }
                    Swal.fire({ icon: 'success', title: 'Listo', text: resp.message, timer: 2000, showConfirmButton: false });
                    cargarVehiculos();
                });
            });
        });
    });
</script>
</body>
</html>
