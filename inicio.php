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

    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <style>
    #vehiculosAsignados {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
        gap: 15px; /* Espacio entre cada placa */
    }
    </style>

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

                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Control Vehicular MESS</h1>
                    </div>
                    <hr>
                    <!-- Content Row -->
                    <div class="row">
                        <div class="stat-box mb-1">
                            <h3>Tenencia 2026</h3>
                            <h4 id ="vehiculosAsignados" name="vehiculosAsignados" style="color:#fff; margin-bottom: 0.1rem"></h4>
                        </div>
                        <div class="col-xl-12 col-lg-12">
                            <embed id="vistaPrevia" src='img/Manual Control Vehicular.pdf' type="application/pdf" width="100%" height="650">
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; MESS 2025</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Bootstrap core JavaScript-->
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            cargarVehiculos('');
        });

        // Cargar vehículos prestados
        function cargarVehiculos(selectVehiculo) {
            $.ajax({
                url: 'acciones_kilometraje.php',
                method: 'POST',
                dataType: 'json',
                data: { accion: 'CargarVehiculosPTenencia' },
                success: function(response) {
                    if (response.success && response.vehiculos && response.vehiculos.length > 0) {
                        // Contenedor con Flexbox: horizontal, con espacio y permite saltos de línea
                        let html = '<div class="d-flex flex-wrap gap-4 align-items-center">';
                        
                        response.vehiculos.forEach(function(vehiculo) {
                            html += '<div class="p-2 rounded bg-primary">' + 
                                        '<span class="me-2 fw-bold" style="font-size: 16px; color: #ffffff;">' + vehiculo.placa + '</span>' +
                                        '<a href="../loginMaster/TENENCIAS_2026/' + vehiculo.placa + '.pdf" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-info text-white">' +
                                            'Tenencia 2026 <i class="fas fa-download"></i>' +
                                        '</a>' +
                                    '</div>';
                        });
                        
                        html += '</div>';
                        $('#vehiculosAsignados').html(html);
                    } else {
                        $('#vehiculosAsignados').html('<span class="text-muted">Sin vehículo asignado</span>');
                    }
                },
                error: function () {
                    console.error('Error al cargar los vehículos');
                }
            });
        }
    </script>

</body>

</html>
