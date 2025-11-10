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
                                            <th>Vehiculo</th>
                                            <th>Usuario</th>
                                            <th>OT</th>
                                            <th>Fecha Inicio</th>
                                            <th>Km Inicio</th>
                                            <th>Fecha Fin</th>
                                            <th>Km Fin</th>
                                            <th>Km Recorridos</th>
                                            <th>Notas</th>                                            
                                        </tr>
                                    </thead>
                                    <tbody> 
                                        <button class="btn btn-outline-primary" onclick="descargarTabla()">Descargar XLSX</button><hr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item">                                
                                <button class="nav-link active btn-outline-warning" onclick="verCalendario('planeadas')" type="button">Planeadas</button>
                            </li>
                            <li class="nav-item">                                
                                <button class="nav-link active btn-outline-success" onclick="verCalendario('checks')"type="button">Checks</button>
                            </li>                            
                        </ul><br>
                    </div>
                        
                        <!-- PLANEADAS -->
                        <div id="divPlaneadas">
                            <div class="alert alert-warning" role="alert">
                                Actividades planeadas para los vehiculos.
                            </div>
                            <div id="calendarioActividadesPlaneadas" name="calendarioActividadesPlaneadas"></div>            
                        </div>
                        <!-- CHECKS -->
                        <div id="divChecks">
                            <div class="alert alert-success" role="alert">
                                Actividades de checks de los vehiculos.
                            </div>
                            <div id="calendarioActividades" name="calendarioActividades"></div>
                        </div>
                        
                    
                </div>
                <!-- /.container-fluid -->
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
    <!-- Librería XLSX -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    
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
            mostrarCalendarioActividadesPlaneadas();
            $('#divChecks').hide();
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
                                actividad.placa + ' - ' + actividad.modelo + ' - ' + actividad.marca,
                                actividad.usuario,
                                actividad.ot,
                                actividad.Fecha_Inicio,
                                actividad.km_inicio,
                                actividad.Fecha_Fin,
                                actividad.km_final,
                                actividad.km_recorridos,
                                'Nota Inicio: ' + actividad.notasI + '<br> Nota Fin: ' + actividad.notasF
                            ]).draw(false);
                        });
                    } else {
                        Swal.fire({
                            icon: 'warning',
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
                    initialView: 'timeGridWeek',
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
        
        function mostrarCalendarioActividadesPlaneadas() {
            // Limpiar el contenedor antes de agregar el calendario
            $('#calendarioActividadesPlaneadas').empty();

            // Crear un array de eventos para FullCalendar
            $.ajax({
                url: 'acciones_kilometraje.php',
                type: 'POST',
                data: { accion: 'ActividadesCalendarioPlaneadas' },
                dataType: 'json',
                success: function(data) {
                    if (data.status === 'success') {
                        var eventos = [];
                        // Filtros de ejemplo: por estatus y por placa
                        // Agregar campos de filtro a la vista si no existen
                        if ($('#filtroEstatus').length === 0) {
                            $('#calendarioActividadesPlaneadas').before(
                                '<div class="row mb-3">' +
                                    '<div class="col-md-4">' +
                                        '<label for="filtroEstatus" class="form-label">Filtrar por estatus:</label>' +
                                        '<select id="filtroEstatus" class="form-select">' +
                                            '<option value="">Todos</option>' +
                                            '<option value="PENDIENTE">Pendiente</option>' +                                            
                                            '<option value="AUTORIZADO">Autorizado</option>' +
                                            '<option value="DENEGADO">Cancelado</option>' +
                                        '</select>' +
                                    '</div>' +
                                    '<div class="col-md-4">' +
                                        '<label for="filtroPlaca" class="form-label">Filtrar por placa:</label>' +
                                        '<input type="text" id="filtroPlaca" class="form-control" placeholder="Placa">' +
                                    '</div>' +
                                    '<div class="col-md-4 d-flex align-items-end">' +
                                        '<button id="btnFiltrar" class="btn btn-primary w-100">Filtrar</button>' +
                                    '</div>' +
                                '</div>'
                            );
                            // Evento para recargar el calendario al filtrar
                            $('#btnFiltrar').on('click', function() {
                                mostrarCalendarioActividadesPlaneadas();
                            });
                        }
                        var filtroEstatus = $('#filtroEstatus').val();
                        var filtroPlaca = $('#filtroPlaca').val();

                        $.each(data.actividades, function(index, actividad) {
                            // Aplicar filtros si están definidos
                            if ((filtroEstatus && actividad.estatus !== filtroEstatus) ||
                                (filtroPlaca && actividad.placa !== filtroPlaca)) {
                                return; // Saltar si no cumple el filtro
                            }

                            // Definir color según el estatus
                            var colorEvento = '';
                            switch (actividad.estatus) {
                                case 'PENDIENTE':
                                    colorEvento = '#ffc107'; // Amarillo
                                    break;
                                case 'AUTORIZADO':
                                    colorEvento = '#28a745'; // Verde
                                    break;
                                case 'Cancelado':
                                    colorEvento = '#dc3545'; // Rojo
                                    break;
                                default:
                                    colorEvento = '#007bff'; // Azul por defecto
                            }
                            eventos.push({
                                title: actividad.nombre + ' - ' + actividad.placa,
                                start: actividad.fecha_inc_prestamo,
                                end: actividad.fecha_fin_prestamo,
                                description:
                                    'Modelo: ' + actividad.modelo + '<br>' +
                                    'Marca: ' + actividad.marca + '<br>' +
                                    'Notas: ' + (actividad.motivo_us || ''),
                                color: colorEvento
                            });
                        });
                        

                        // Crear el calendario
                        var calendarEl = document.createElement('div');
                        calendarEl.id = 'fullcalendar';
                        $('#calendarioActividadesPlaneadas').append(calendarEl);

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
                        $('#calendarioActividadesPlaneadas').html('<p>No hay actividades pendientes.</p>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error al cargar actividades pendientes:', error);
                    $('#calendarioActividadesPlaneadas').html('<p>Error al cargar actividades.</p>');
                }
            });

            function inicializarCalendario(calendarEl, eventos) {
                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'timeGridWeek',
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

        function verCalendario(tipo) {
            if (tipo === 'planeadas') {
                $('#divPlaneadas').show();
                $('#divChecks').hide();
                mostrarCalendarioActividadesPlaneadas();
            } else if (tipo === 'checks') {
                $('#divPlaneadas').hide();
                $('#divChecks').show();
                mostrarCalendarioActividades();
            }
        }

        // Función para descargar la tabla como XLSX
        function descargarTabla() {
            let tabla = document.getElementById("tablaVerActividades");
            let wb = XLSX.utils.table_to_book(tabla, { sheet: "Actividades" });
            XLSX.writeFile(wb, "Actividades_Vehiculo.xlsx");
        }
    </script>
</body>
</html> 