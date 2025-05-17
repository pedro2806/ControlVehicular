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
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Control Vehicular - Documentación</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
</head>
<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <?php include 'menu.php'; ?>
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <?php include 'encabezado.php'; ?>
                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <!-- Content Row -->
                    <div class="row">
                        <div class="col-xl-12 col-lg-12">
                            <h1 class="h3 mb-0 text-black-800">Historial de Documentación</h1>
                            <br>
                            <!-- CONTENEDOR INFO AUTO --> 
                            <div id="placaSeleccionada" class="alert alert-info" style="display: none;"></div> 
                            <button id="btnCambiarVehiculo" class="btn btn-outline-primary" style="display: none;" onclick="cambiarVehiculo()">Cambiar Vehículo</button>
                            <div class="card shadow mb-4">
                                <!-- Tabla de Vehículos -->
                                <div class="card-body">
                                    <div class="table-responsive" style="overflow-x: auto;">
                                        <table class="table table-bordered" id="TablaInventario" width="100%" cellspacing="0">
                                            <thead></thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                                <!-- Tabla de Registros -->
                                <div class="card-body">
                                    <div class="table-responsive" style="overflow-x: auto; display: none;">
                                        <table class="table table-bordered" id="TablaRegistrosDocumentacion" width="100%" cellspacing="0">
                                            <thead></thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                                <div id="detalleDocumentacionContainer"></div>
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
    <!-- Bootstrap core JavaScript-->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>
    <!-- DataTables JavaScript -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            // Inicializar DataTables
            var TablaInventario = $('#TablaInventario').DataTable({
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
            var TablaRegistrosDocumentacion = $('#TablaRegistrosDocumentacion').DataTable({
                data: [],
                columns: [
                    { title: "Fecha de Registro" },
                    { title: "Usuario" },
                    { title: "Contacto" },
                    { title: "Acciones" }
                ],
                paging: true,
                pageLength: 5,
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
            cargarVehiculos(TablaInventario);
        });

        // Cargar Vehículos
        function cargarVehiculos(TablaInventario) {
            $.ajax({
                type: "POST",
                url: 'acciones_ver_registros',
                data: {accion: 'ver_inventario'},
                dataType: "json",
                success: function(respuesta) {
                    TablaInventario.clear(); 
                    respuesta.forEach(function (vehiculo) {
                        var fila = [
                            `<i class="fas fa-car"></i> ${vehiculo.placa}`,
                            `${vehiculo.modelo} - ${vehiculo.marca}`,
                            `${vehiculo.color}`,
                            `${vehiculo.anio}`,
                            `${vehiculo.usuario}`,
                            `<center>
                                <button class="btn btn-outline-warning btn-sm" onclick="seleccionarVehiculo('${vehiculo.id_vehiculo}', '${vehiculo.placa}' , '${vehiculo.modelo}', '${vehiculo.marca}', '${vehiculo.anio}', '${vehiculo.color}', '${vehiculo.usuario}')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </center>`
                        ];
                        TablaInventario.row.add(fila);
                    });
                    TablaInventario.draw();
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "No se pudo cargar la información de los vehículos.",
                        confirmButtonText: "Aceptar"
                    });
                }
            });
        }

        // Seleccionar Vehículo
        function seleccionarVehiculo(id_vehiculo, placa, modelo, marca, color) {
            $("#placaSeleccionada")
                .html(`
                    <div style="display: flex; justify-content: space-between; font-weight: bold;">
                        <span>Placa:<span id="placaVehiculo" style="font-weight: normal;">${placa}</span>
                        <span>Modelo:<span style="font-weight: normal;">${modelo}</span>
                        <span>Marca:<span style="font-weight: normal;">${marca}</span>
                        <span>Color:<span style="font-weight: normal;">${color}</span>
                    </div>
                `).show();
            $("#id_vehiculo").val(id_vehiculo);
            $("#btnCambiarVehiculo").show();
            $("#TablaInventario").closest(".table-responsive").hide();
            $("#TablaRegistrosDocumentacion").closest(".table-responsive").show();
            documentacionXvehiculo(id_vehiculo);
        }
        // Cambiar de Vehículo
        function cambiarVehiculo() {
            $("#placaSeleccionada").hide();
            $("#btnCambiarVehiculo").hide();
            $("#TablaInventario").closest(".table-responsive").show();
            $("#TablaRegistrosDocumentacion").closest(".table-responsive").hide();
        }

        // Cargar registros de documentación
        function documentacionXvehiculo(id_vehiculo) {
            $.ajax({
                type: "POST",
                url: 'acciones_ver_registros',
                data: { accion: 'verDocumentacionXVehiculo', id_vehiculo: id_vehiculo },
                dataType: "json",
                success: function(respuesta) {
                    var TablaRegistrosDocumentacion = $('#TablaRegistrosDocumentacion').DataTable();
                    TablaRegistrosDocumentacion.clear();

                    respuesta.forEach(function(documento) {
                        var fila = [
                            `${documento.fecha_registro || ''}`,
                            `${documento.usuario || ''}`,
                            `${documento.contacto || ''}`,
                            `<center>
                                <button class="btn btn-outline-warning btn-sm" onclick='mostrarDetalleDocumentacion(${JSON.stringify(documento)})'>
                                    <i class="fas fa-eye"></i> 
                                </button>
                            </center>`
                        ];
                        TablaRegistrosDocumentacion.row.add(fila);
                    });
                    TablaRegistrosDocumentacion.draw();
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "No se pudo cargar la información de la documentación.",
                        confirmButtonText: "Aceptar"
                    });
                }
            });
        }

        // Mostrar detalle de la documentación
        function mostrarDetalleDocumentacion(documento) {
            // Puedes personalizar los nombres y el orden según tus columnas reales
            const docs = [
                { nombre: "Licencia", archivo: documento.licencia },
                { nombre: "Tarjeta de Circulación", archivo: documento.tarjeta_circulacion },
                { nombre: "Refrendo Actual", archivo: documento.refrendo_actual },
                { nombre: "Seguro Vehículo", archivo: documento.seguro_vehiculo },
                { nombre: "Verificación Vigente", archivo: documento.verificacion_vigente }
            ];

            let docsHtml = docs.map(doc =>
                doc.archivo && doc.archivo !== 'S/R'
                    ? `<tr>
                        <td><strong>${doc.nombre}:</strong></td>
                        <td>
                            <a href="${doc.archivo}" target="_blank">
                                <img src="${doc.archivo}" alt="${doc.nombre}" style="max-height:100px;max-width:150px;" class="img-thumbnail"/>
                            </a>
                        </td>
                    </tr>`
                    : `<tr>
                        <td><strong>${doc.nombre}:</strong></td>
                        <td>S/R</td>
                    </tr>`
            ).join('');

            if (!docsHtml) {
                docsHtml = `<tr><td colspan="2" class="text-center">No hay documentos disponibles.</td></tr>`;
            }

            var tarjeta = `
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-black">Detalle de Documentación</h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><strong>Fecha de Registro:</strong> ${documento.fecha_registro || 'N/A'}</p>
                                <p><strong>Usuario:</strong> ${documento.usuario || 'N/A'}</p>
                                <p><strong>Contacto:</strong> ${documento.contacto || 'N/A'}</p>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tbody>
                                    ${docsHtml}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;

            $('#detalleDocumentacionContainer').html(tarjeta);
        }
    </script>
</body>
</html>