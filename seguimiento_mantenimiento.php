<?php
session_start();
include 'conn.php';
if ($_COOKIE['noEmpleado'] == '' || $_COOKIE['noEmpleado'] == null) {
    echo '<script>window.location.assign("index")</script>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Control Vehicular - Mantenimiento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include 'menu.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'encabezado.php'; ?>
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-xl-12 col-lg-12">
                            <h1 class="h3 mb-0 text-black-800">Historial de Mantenimiento</h1>
                            <br>
                            <div class="card shadow mb-4">
                                <div id="placaSeleccionadaMantenimiento" class="alert alert-info" style="display: none;"></div>
                                <button id="btnCambiarVehiculoMantenimiento" class="btn btn-outline-primary" style="display: none;" onclick="cambiarVehiculoMantenimiento()">Cambiar Vehículo</button>
                                <div class="card-body">
                                    <div class="table-responsive" style="overflow-x: auto;">
                                        <table class="table table-bordered" id="TablaInventarioMantenimiento" width="100%" cellspacing="0">
                                            <thead></thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive" style="overflow-x: auto; display: none;">
                                        <table class="table table-bordered" id="TablaRegistrosMantenimiento" width="100%" cellspacing="0">
                                            <thead></thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                                <div id="detalleMantenimientoContainer"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; MESS 2025</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript">
        var TablaRegistrosMantenimiento;
        $(document).ready(function() {
            var TablaInventarioMantenimiento = $('#TablaInventarioMantenimiento').DataTable({
                data: [],
                columns: [
                    { title: "Placa" },
                    { title: "Modelo" },
                    { title: "Color" },
                    { title: "Año" },
                    { title: "Asignado" },
                    { title: "Acciones" }
                ],
                paging: true,
                pageLength: 10,
                ordering: true,
                searching: true,
                info: true,
                language: {
                    decimal: ",",
                    thousands: ".",
                    processing: "Procesando...",
                    loadingRecords: "Cargando...",
                    zeroRecords: "No se encontraron resultados",
                    emptyTable: "No hay datos disponibles en la tabla",
                    info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    infoEmpty: "Mostrando 0 a 0 de 0 registros",
                    infoFiltered: "(filtrado de _MAX_ registros totales)",
                    search: "Buscar:",
                    paginate: {
                        first: "Primero",
                        last: "Último",
                        next: "Siguiente",
                        previous: "Anterior"
                    },
                    lengthMenu: "Mostrar _MENU_ registros",
                    aria: {
                        sortAscending: ": activar para ordenar la columna de manera ascendente",
                        sortDescending: ": activar para ordenar la columna de manera descendente"
                    }
                }
            });
            TablaRegistrosMantenimiento = $('#TablaRegistrosMantenimiento').DataTable({
                data: [],
                columns: [
                    { title: "Fecha de Registro" },
                    { title: "Tipo" },
                    { title: "Descripción" },
                    { title: "Acciones" }
                ],
                paging: true,
                pageLength: 5,
                ordering: true,
                searching: true,
                info: true,
                language: TablaInventarioMantenimiento.settings()[0].oLanguage
            });
            cargarVehiculosMantenimiento(TablaInventarioMantenimiento);
        });

        // Función para cargar los vehículos en la tabla de inventario
        function cargarVehiculosMantenimiento(TablaInventarioMantenimiento) {
            $.ajax({
                type: "POST",
                url: 'acciones_ver_registros',
                data: {accion: 'ver_inventario'},
                dataType: "json",
                success: function(respuesta) {
                    TablaInventarioMantenimiento.clear();
                    respuesta.forEach(function (vehiculo) {
                        var fila = [
                            `<strong><i class="fas fa-car"></i> ${vehiculo.placa}</strong>`,
                            `<strong>${vehiculo.modelo} - ${vehiculo.marca}</strong>`,
                            `<strong>${vehiculo.color}</strong>`,
                            `<strong>${vehiculo.anio}</strong>`,
                            `<strong>${vehiculo.usuario}</strong>`,
                            `<center>
                                <button class="btn btn-outline-info btn-sm" onclick="seleccionarVehiculoMantenimiento('${vehiculo.id_vehiculo}', '${vehiculo.placa}', '${vehiculo.modelo}', '${vehiculo.marca}', '${vehiculo.anio}', '${vehiculo.color}', '${vehiculo.usuario}')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </center>`
                        ];
                        TablaInventarioMantenimiento.row.add(fila);
                    });
                    TablaInventarioMantenimiento.draw();
                }
            });
        }

        // Función para seleccionar un vehículo y mostrar su historial de mantenimiento
        function seleccionarVehiculoMantenimiento(id_vehiculo, placa, modelo, marca, color) {
            $("#placaSeleccionadaMantenimiento")
                .html(`
                    <div style="display: flex; justify-content: space-between; font-weight: bold;">
                        <span><strong>Placa:</strong> <span style="font-weight: normal;">${placa}</span></span>
                        <span><strong>Modelo:</strong> <span style="font-weight: normal;">${modelo}</span></span>
                        <span><strong>Marca:</strong> <span style="font-weight: normal;">${marca}</span></span>
                        <span><strong>Color:</strong> <span style="font-weight: normal;">${color}</span></span>
                    </div>
                `).show();
            $("#btnCambiarVehiculoMantenimiento").show();
            $("#TablaInventarioMantenimiento").closest(".table-responsive").hide();
            $("#TablaRegistrosMantenimiento").closest(".table-responsive").show();
            mantenimientoXvehiculo(id_vehiculo, placa);
        }
        function cambiarVehiculoMantenimiento() {
            $("#placaSeleccionadaMantenimiento").hide();
            $("#btnCambiarVehiculoMantenimiento").hide();
            $("#TablaInventarioMantenimiento").closest(".table-responsive").show();
            $("#TablaRegistrosMantenimiento").closest(".table-responsive").hide();
            $("#detalleMantenimientoContainer").html('');
        }

        // Función para cargar el historial de mantenimiento del vehículo seleccionado
        function mantenimientoXvehiculo(id_vehiculo, placa) {
            $.ajax({
                type: "POST",
                url: 'acciones_ver_registros',
                data: { accion: 'verMantenimientoXVehiculo', id_vehiculo: id_vehiculo },
                dataType: "json",
                success: function(respuesta) {
                    TablaRegistrosMantenimiento.clear();
                    respuesta.forEach(function(mantenimiento) {
                        var fila = [
                            `<strong>${mantenimiento.fecha_registro || ''}</strong>`,
                            `<strong>${mantenimiento.tipo_mantenimiento || ''}</strong>`,
                            `<strong>${mantenimiento.descripcion || ''}</strong>`,
                            `<center>
                                <button class="btn btn-outline-info btn-sm" onclick='mostrarDetalleMantenimiento(${JSON.stringify(mantenimiento)}, "${placa}")'>
                                    <i class="fas fa-eye"></i>
                                </button>
                            </center>`
                        ];
                        TablaRegistrosMantenimiento.row.add(fila);
                    });
                    TablaRegistrosMantenimiento.draw();
                }
            });
        }

        // Función para mostrar el detalle del mantenimiento
        function mostrarDetalleMantenimiento(mantenimiento, placa) {
            // Ajusta la ruta base según tu estructura
            var rutaBase = '/ControlVehicular/img_control_vehicular/' + placa + '/Mantenimientos/';
            console.log('Ruta generada:', rutaBase + mantenimiento.foto); // Depuración: verifica la ruta generada en la consola
            var imgHtml = '';
            if (mantenimiento.foto && mantenimiento.foto !== '') {
                imgHtml = `
                    <a href="${rutaBase + mantenimiento.foto}" target="_blank">
                        <img src="${rutaBase + mantenimiento.foto}" alt="Foto Mantenimiento" style="max-height:100px;max-width:150px;" class="img-thumbnail"/>
                    </a>
                `;
            } else {
                imgHtml = 'No hay foto disponible.';
            }

            var tarjeta = `
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-black">Detalle de Mantenimiento</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Fecha de Registro:</strong> ${mantenimiento.fecha_registro || 'N/A'}</p>
                                <p><strong>Tipo:</strong> ${mantenimiento.tipo_mantenimiento || 'N/A'}</p>
                                <p><strong>Kilometraje:</strong> ${mantenimiento.kilometraje || 'N/A'}</p>
                                <p><strong>Descripción:</strong> ${mantenimiento.descripcion || 'N/A'}</p>
                                <p><strong>Fecha Proximo Mantenimiento:</strong> ${mantenimiento.fecha_proxi || 'N/A'}</p>
                            </div>
                            <div class="col-md-6 d-flex align-items-center justify-content-center">
                                ${imgHtml}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            $('#detalleMantenimientoContainer').html(tarjeta);
        }
    </script>
</body>
</html>