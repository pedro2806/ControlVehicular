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
    <!-- Custom styles for this template-->
    <!-- MESS Design System -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="css/mess-ds.css" rel="stylesheet">
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
                        <div id="seccionVehiculo" style="display:none;" class="col-12 mb-3">
                            <div id="contenedorVehiculos"></div>
                        </div>
                        <div class="col-12 mb-3">
                            <div id="vehiculosAsignados" name="vehiculosAsignados"></div>
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
                        <span>Copyright &copy; MESS <?php echo date("Y"); ?></span>
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

    <!-- Core plugin JavaScript-->

    <!-- Custom scripts for all pages-->

    <script type="text/javascript">
        $(document).ready(function() {
            //cargarVehiculosInicio('');
            cargarVehiculosDocs();
        });

        // Cargar vehículos prestados
        function cargarVehiculosInicio(selectVehiculo) {
            $.ajax({
                url: 'acciones_kilometraje.php',
                method: 'POST',
                dataType: 'json',
                data: { accion: 'CargarVehiculosPTenencia' },
                success: function(response) {
                    if (response.success && response.vehiculos && response.vehiculos.length > 0) {
                        // Contenedor con Flexbox: horizontal, con espacio y permite saltos de línea
                        let html = '<div class="d-flex flex-wrap gap-4 align-items-center">';
                        
                        Array.isArray(response.vehiculos) && response.vehiculos.forEach(function(vehiculo) {
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

        // ===== Tab Vehículo: semáforo de documentación =====
        // 1) Obtiene los vehículos del usuario (getPlaca → ../incidencias/validaLoginMaster.php).
        // 2) Para cada vehículo consulta su documentación (obtenerDatosVehiculo → ../ControlVehicular/acciones_qr.php).
        // 3) Renderiza una card por vehículo con check verde / cruz roja por documento.
        function cargarVehiculosDocs() {
            var $cont = $('#contenedorVehiculos');
            var noEmp = getCookie('noEmpleadoL') || '';
            if (!noEmp) {
                $cont.html('<div class="alert alert-info">No hay sesión válida.</div>');
                return;
            }

            $.ajax({
                url: '../incidencias/validaLoginMaster.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    accion: 'getPlaca',
                    noEmpleado: noEmp
                }
            }).done(function(resp) {
                if (!resp || !resp.success || !resp.vehiculos || resp.vehiculos.length === 0) {
                    vehiculosDocsCargados = true;
                    return;
                }

                $('#seccionVehiculo').show();

                var html = '<div class="accordion" id="accordionVehiculos">';
                resp.vehiculos.forEach(function(v, idx){
                    var idv = parseInt(v.id_vehiculo, 10) || 0;
                    html += renderCardVehiculo(idv, v.placa, v.modelo, idx === 0);
                });
                html += '</div>';
                $cont.html(html);

                resp.vehiculos.forEach(function(v){
                    var idv = parseInt(v.id_vehiculo, 10) || 0;
                    if (!idv) return;
                    syncCookieNoEmpleado();

                    $.ajax({
                        url: 'acciones_qr.php',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            accion: 'obtenerDatosVehiculo',
                            id_vehiculo: idv
                        }
                    }).done(function(data) {
                        if (!data || data.error) {
                            $('#docsVeh-' + idv).html('<p class="text-muted small mb-0 p-3">No se pudo cargar la documentación</p>');
                            return;
                        }
                        $('#docsVeh-' + idv).html(renderListaDocs(data));
                    }).fail(function(){
                        $('#docsVeh-' + idv).html('<p class="text-danger small mb-0 p-3">Error al obtener documentación</p>');
                    });

                    $.ajax({
                        url: 'acciones_qr.php',
                        type: 'POST', dataType: 'json',
                        data: { accion: 'obtenerValidacionesVehiculo', id_vehiculo: idv }
                    }).done(function(data){
                        if (!data || data.error) {
                            $('#chkVeh-' + idv).html('<p class="text-muted small mb-0 p-3">No se pudo cargar el checklist</p>');
                            return;
                        }
                        $('#chkVeh-' + idv).html(renderListaChecklist(data));
                    }).fail(function(){
                        $('#chkVeh-' + idv).html('<p class="text-danger small mb-0 p-3">Error</p>');
                    });
                });
                vehiculosDocsCargados = true;
            }).fail(function() {
                $cont.html('<div class="alert alert-danger">No se pudo obtener la información de vehículos.</div>');
            });
        }
    </script>

</body>

</html>
