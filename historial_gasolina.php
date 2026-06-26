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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.css">
    
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
                        <h1 class="h3 mb-0 text-gray-800">Actividades de vehiculos</h1>
                    </div>
                    <!-- Content Row -->
                    <div class="row">
                        <ul class="nav nav-tabs" id="myTab" role="tablist">                                                        
                            <li class="nav-item">
                                <button class="btn btn-outline-warning btn-sm" onclick="descargarTabla()">Descargar XLSX</button>
                            </li>
                        </ul><br>
                        <!-- Tabla Carga de Gas -->
                        <div class="tab-pane fade show active" id="gas">
                            <div class="table-responsive">
                                <table id="tablaGas" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Vehiculo</th>
                                            <th>Usuario</th>
                                            <th>Monto</th>
                                            <th>Pagos</th>
                                            <th>Saldo</th>
                                            <th>Km Actual</th>
                                            <th>Fecha Carga</th>
                                            <th>Fecha Registro</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>                    
                <!-- /.container-fluid -->
            </div>
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; MESS <?php echo date("Y"); ?></span>
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
    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>
    <!-- DataTables JavaScript -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <!-- Popper.js -->
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <!-- Librería XLSX -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    
    <script type="text/javascript">
        $(document).ready(function() {
            $('#tablaGas').DataTable({
                destroy: true,
                paging: true,
                pageLength: 5, 
                lengthMenu: [5, 10, 20, 50],
                ordering: true,
                searching: true,
                info: true,
                language: {
                    lengthMenu: "Mostrar _MENU_ registros por página",
                    zeroRecords: "No se encontraron resultados",
                    info: "Mostrando página _PAGE_ de _PAGES_",
                    infoEmpty: "No hay registros disponibles",
                    infoFiltered: "(filtrado de _MAX_ registros totales)",
                    search: "Buscar:",
                    paginate: {
                        first: "Primero",
                        last: "Último",
                        next: "Siguiente",
                        previous: "Anterior"
                    }
                }
            });
            //verTabla('tablaGas')            
            cargarGas();                        

        });

        //Función para cargar recargas de gas
        function cargarGas() {
            $.ajax({
                url: 'acciones_gas.php',
                type: 'POST',
                data: { accion: 'obtenerRegistrosGasTodos' },
                dataType: 'json',
                success: function(data) {
                    var table = $('#tablaGas').DataTable();
                    table.clear();
                    $.each(data, function(index, carga) {
                        table.row.add([
                            carga.Vehiculo,
                            carga.usuario,
                            carga.monto,
                            carga.pagos,
                            carga.saldo,
                            carga.km_actual,
                            carga.fecha_carga,
                            carga.fecha_registro
                        ]).draw(false);
                    });
                },
                error: function(xhr, status, error) {
                    console.error('Error al cargar registros de gas:', error);
                }
            });
        }

        function descargarTabla() {
            var wb = XLSX.utils.book_new();
            var ws = XLSX.utils.table_to_sheet(document.getElementById('tablaGas'));
            XLSX.utils.book_append_sheet(wb, ws, 'Gasolina');
            XLSX.writeFile(wb, 'Historial_Gasolina.xlsx');
        }
    </script>
</body>
</html> 