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
    <link href = "vendor/fontawesome-free/css/all.min.css" rel = "stylesheet" type = "text/css">

    <!-- Custom styles for this template-->
    <link href = "css/sb-admin-2.min.css" rel = "stylesheet">    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
    <!--<link href="https://cdn.datatables.net/1.13.2/css/jquery.dataTables.min.css" rel="stylesheet" crossorigin="anonymous">-->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.css" />
    <link href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.dataTables.min.css" rel="stylesheet">

    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

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
                    <div class="row">                        
                        <div class="col-xl-6 col-lg-6 col-md-6 col-sm-6 col-6">
                            <table id="TVehiculosAsignados" name="TVehiculosAsignados" class="table table-bordered table-hover" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Vehiculo</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>                                    
                                </tbody>    
                            </table>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                            <label for="expediente">Expediente:</label>
                            <label id="expediente" name="expediente">123456</label>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                            <label for="empresa">Empresa:</label>
                            <label type="text" id="empresa" name="empresa">Empresa</label>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                            <label for="marca">Marca:</label>
                            <label id="marca" name="marca">Marca</label>
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                            <label for="modelo">Modelo:</label>
                            <label type="text" id="modelo" name="modelo">Modelo</label>
                        </div>
                    </div>
                    <div class="row">
                        
                        <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                            <label for="tipo">Tipo:</label>
                            <input type="text" id="tipo" name="tipo" class="form-control">
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                            <label for="color">Color:</label>
                            <input type="text" id="color" name="color" class="form-control">
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                            <label for="placa">Placa:</label>
                            <input type="text" id="placa" name="placa" class="form-control">
                        </div>
                        <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                            <label for="area">Área:</label>
                            <input type="text" id="area" name="area" class="form-control">
                        </div>
                    </div>
                    <div class="row">

                        <div class="col-md-4">
                            <label for="usuario">Nombre del Usuario:</label>
                            <input type="text" id="usuario" name="usuario" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <label for="fecha_ultimo_servicio">Fecha último servicio:</label>
                            <input type="date" id="fecha_ultimo_servicio" name="fecha_ultimo_servicio" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label for="referencia">Referencia:</label>
                            <input type="text" id="referencia" name="referencia" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label for="kilometraje">Kilometraje:</label>
                            <input type="number" id="kilometraje" name="kilometraje" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xl-12 col-lg-12 mt-3">
                            <h3>Inspección</h3>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <label for="fecha_inspeccion">Fecha de inspección:</label>
                            <input type="date" id="fecha_inspeccion" name="fecha_inspeccion" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label for="carta_resguardo">¿Tiene carta de resguardo?</label><br>
                            <input type="checkbox" id="carta_resguardo" name="carta_resguardo" value="1">
                        </div>
                        <div class="col-md-4">
                            <label for="foto_inspeccion">Tomar foto de inspección:</label>
                            <input type="file" id="foto_inspeccion" name="foto_inspeccion" class="form-control" accept="image/*" capture="camera">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mt-3">
                            <input type="submit" value="Guardar Inspección" class="btn btn-primary">
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
</body>

    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <script src = "vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src = "vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src = "js/sb-admin-2.min.js"></script>    
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/1.10.8/js/jquery.dataTables.min.js" defer="defer"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script> 
<script type="text/javascript">
    
    $(document).ready(function () {
        llenaTVehiculosAsignados(); //LLENAR TABLA DE VEHICULOS ASIGNADOS

        $('#TVehiculosAsignados').DataTable({            
                        destroy: true, // Permitir reinicializar la tabla
                        language: {
                            decimal: ",",
                            thousands: ".",
                            processing: "Procesando...",
                            search: "Buscar:",
                            lengthMenu: "Mostrar MENU registros",
                            info: "Mostrando START a END de TOTAL registros",
                            infoEmpty: "Mostrando 0 a 0 de 0 registros",
                            infoFiltered: "(filtrado de MAX registros totales)",
                            loadingRecords: "Cargando...",
                            zeroRecords: "No se encontraron resultados",
                            emptyTable: "No hay datos disponibles en la tabla",
                            paginate: {
                                first: "Primero",
                                previous: "Anterior",
                                next: "Siguiente",
                                last: "Último"
                            },
                            aria: {
                                sortAscending: ": activar para ordenar la columna de manera ascendente",
                                sortDescending: ": activar para ordenar la columna de manera descendente"
                            }
                        }
        });
        
    });
    function llenaTVehiculosAsignados() {
                    var opcion = "llenaTVehiculosAsignados";                                        
                    var cookieNoEmpleado = getCookie('noEmpleado');                     
                    $.ajax({
                        url: 'AccionesCheckVehiculo.php', 
                        method: 'POST',
                        dataType: 'json', //TIPO DE DATO JSON
                        data:{opcion, cookieNoEmpleado}, 
                        success: function(registros) {
                            
                            var table = $('#TVehiculosAsignados').DataTable();
                            
                            table.clear().draw();                            
                            registros.forEach(function(Registro) { 
                                                                
                                table.row.add([                                    
                                    '<i class="fas fa-car fa-1x"></i><b> ' + Registro.modelo + '-' + Registro.placa +' </b>',
                                    '<input type="submit" value="Seleccionar" class="btn btn-sm btn-primary">'
                                    ]).draw(false);
                            });
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            //console.error("Error in AJAX request:", textStatus, errorThrown);
                            //swal("Error", "Failed to load vehicle data. Please try again later.", "error");
                        }
                    });
        }

        
        function getCookie(name) {
            var value = "; " + document.cookie;
            var parts = value.split("; " + name + "=");
            if (parts.length === 2) return parts.pop().split(";").shift();
        }
</script>
</html>
