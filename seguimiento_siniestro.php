<?php
session_start();
include 'conn.php';
if ($_COOKIE['noEmpleado'] == '' || $_COOKIE['noEmpleado'] == null) {
    echo '<script>window.location.assign("index")</script>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Control Vehicular</title>

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
        <?php
        include 'menu.php';
        ?>
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <?php
                include 'encabezado.php';
                ?>
                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <!-- Content Row -->
                    <div class="row">
                        <div class="col-xl-12 col-lg-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                    <h6 class="m-0 font-weight-bold text-primary">Historial de Siniestros</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive" style="overflow-x: auto;">
                                        <table class="table table-bordered" id="TablaSiniestros" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>Placa</th>
                                                    <th>Modelo</th>
                                                    <th>Marca</th>
                                                    <th>Año</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
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
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
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
            // Inicializar DataTables después de cargar los datos
            $('#TablaSiniestros').DataTable({
                destroy: true, 
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
            cargarVehiculos();
        });

        // Cargar Vehículos
        function cargarVehiculos() {
            $.ajax({
                type: "POST",
                url: 'acciones_ver_registros',
                type: 'POST',
                data: {accion: 'ver_inventario'},
                dataType: "json",
                success: function(respuesta) {
                    var tablaVehiculos = $("#TablaSiniestros tbody");
                    tablaVehiculos.empty(); 

                    respuesta.forEach(function (vehiculo) {
                        var botones = `
                            <button class="btn btn-outline-warning" onclick="siniestroVehiculo('${vehiculo.id_vehiculo}')">
                                <i class="fas fa-eye"></i>
                            </button>
                            `;
                        
                        var fila = `
                            <tr>
                                <td><strong><i class="fas fa-car"></i> ${vehiculo.placa}</strong></td>
                                <td><strong>${vehiculo.modelo}</strong></td>
                                <td><strong>${vehiculo.marca}</strong></td>
                                <td><strong>${vehiculo.color}</strong></td>
                                <td>
                                    <center>
                                        <button class="btn btn-outline-success btn-sm" onclick="seleccionarVehiculo('${vehiculo.id_vehiculo}', '${vehiculo.placa}' , '${vehiculo.modelo}', '${vehiculo.marca}', '${vehiculo.color}')">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </center>
                                </td>
                            </tr>`;
                        tablaVehiculos.append(fila);
                    });
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

        //FUNCION PARA MANEJAR EL BOTÓN "CHECK"
        function seleccionarVehiculo(id_vehiculo, placa, modelo, marca, color) {
            $("#placaSeleccionada")
                .html(`
                    <div style="display: flex; justify-content: space-between; font-weight: bold;">
                        <span><strong>Placa:</strong> <span style="font-weight: normal;">${placa}</span></span>
                        <span><strong>Modelo:</strong> <span style="font-weight: normal;">${modelo}</span></span>
                        <span><strong>Marca:</strong> <span style="font-weight: normal;">${marca}</span></span>
                        <span><strong>Color:</strong> <span style="font-weight: normal;">${color}</span></span>
                    </div>
                `)
                .show();
            $("#id_vehiculo").val(id_vehiculo);
            $("#placa").val(placa);
            $("#btnCambiarVehiculo").show();
            $("#tablaInventario").closest(".container").hide();
            $("#formRegistroDocumentacion").show();
        }
        
        //FUNCION PARA CAMBIAR DE VEHÍCULO
        function cambiarVehiculo() {
            $("#placaSeleccionada").hide();
            $("#btnCambiarVehiculo").hide();
            $("#tablaInventario").closest(".container").show();
            $("#formRegistroDocumentacion").hide();
        }
        
    </script>
</body>
</html>