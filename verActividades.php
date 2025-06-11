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
                        <!-- Tabla de Vehiculos -->
                        <div class="tab-pane fade show active" id="pendientes">
                            <div class="table-responsive">
                                <table id="tablaVerActividades" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Placa</th>
                                            <th>Modelo</th>
                                            <th>Marca</th>
                                            <th>Usuario</th>
                                            <th>Actividad</th>
                                            <th>Fecha</th>
                                            <th>Descripción</th>                                            
                                        </tr>
                                    </thead>
                                    <tbody>                                    
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div id="calendarioActividades" name="calendarioActividades"></div>
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
    <!-- Popper.js -->
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    
    <script type="text/javascript">
        $(document).ready(function() {
            $('#tablaVerActividades').DataTable({
                destroy: true,
                paging: true,
                pageLength: 10,
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
            cargarActividades(); 
            mostrarCalendarioActividades();
        });

        function cargarActividades() {
            $.ajax({
                url: 'acciones_kilometraje.php',
                type: 'POST',
                data: { accion: 'Actividades' },
                dataType: 'json',
                success: function(data) {
                    if (data.status === 'success') {
                        var table = $('#tablaVerActividades').DataTable();
                        table.clear();
                        $.each(data.actividades, function(index, actividad) {
                            table.row.add([
                                actividad.placa,
                                actividad.modelo,
                                actividad.marca,                                
                                actividad.usuario,
                                actividad.tipo_actividad,
                                actividad.fecha_actividad,
                                actividad.notas
                            ]).draw(false);
                        });
                    } else {
                        Swal.fire({
                            icon: 'Warning',
                            title: 'Atención',
                            text: data.message
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error al cargar actividades:', error);
                }
            });
        }

        function mostrarCalendarioActividades() {
            // Limpiar el contenedor antes de agregar el calendario
            $('#calendarioActividades').empty();

            // Crear un array de eventos para FullCalendar
            $.ajax({
                url: 'acciones_kilometraje.php',
                type: 'POST',
                data: { accion: 'ActividadesCalendario' },
                dataType: 'json',
                success: function(data) {
                    if (data.status === 'success') {
                        var eventos = [];
                        $.each(data.actividades, function(index, actividad) {
                            eventos.push({
                                title: actividad.nombre+ ' - ' + actividad.placa + ' -  Patron:' + actividad.patron_inicio,
                                start: actividad.fecha_inicio,
                                end: actividad.fecha_fin,
                                description: 
                                    'Modelo: ' + actividad.modelo + '<br>' +
                                    'Marca: ' + actividad.marca + '<br>' +
                                    'Notas: ' + (actividad.notas || '')
                            });
                        });

                        // Crear el calendario
                        var calendarEl = document.createElement('div');
                        calendarEl.id = 'fullcalendar';
                        $('#calendarioActividades').append(calendarEl);

                        // Cargar scripts de FullCalendar si no están cargados
                        if (typeof FullCalendar === 'undefined') {
                            $.when(
                                $.getScript('https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.min.js')
                            ).done(function() {
                                inicializarCalendario(calendarEl, eventos);
                            });
                        } else {
                            inicializarCalendario(calendarEl, eventos);
                        }
                    } else {
                        $('#calendarioActividades').html('<p>No hay actividades pendientes.</p>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error al cargar actividades pendientes:', error);
                    $('#calendarioActividades').html('<p>Error al cargar actividades.</p>');
                }
            });

            function inicializarCalendario(calendarEl, eventos) {
                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    locale: 'es',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    events: eventos,
                    eventDidMount: function(info) {
                        // Tooltip con la descripción
                        $(info.el).tooltip({
                            title: info.event.extendedProps.description,
                            html: true,
                            placement: 'top',
                            container: 'body'
                        });
                    }
                });
                calendar.render();
            }
        }
        
    </script>
</body>
</html>