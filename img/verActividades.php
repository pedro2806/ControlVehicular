<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>PLANEACION SEMANAL</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom fonts for this template-->
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <!-- Custom styles for this template-->
    <link href="../css/sb-admin-2.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.css">
    
</head>
<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
            
                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Actividades Semanales</h1>
                    </div>                        
                    <!-- PLANEADAS -->
                    <div id="divPlaneadas">                        
                        <div id="calendarioActividadesPlaneadas" name="calendarioActividadesPlaneadas"></div>            
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
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- Core plugin JavaScript-->
    <script src="../vendor/jquery-easing/jquery.easing.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom scripts for all pages-->
    <script src="../js/sb-admin-2.min.js"></script>
    <!-- DataTables JavaScript -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <!-- Popper.js -->
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    
    <script type="text/javascript">
        $(document).ready(function() {            
            mostrarCalendarioActividadesPlaneadas();
            
        });        
        
        function mostrarCalendarioActividadesPlaneadas() {
            // Limpiar el contenedor antes de agregar el calendario
            $('#calendarioActividadesPlaneadas').empty();

            // Crear un array de eventos para FullCalendar
            $.ajax({
                url: 'acciones_calendario.php',
                type: 'POST',
                data: { accion: 'ActividadesCalendarioPlaneadas' },
                dataType: 'json',
                success: function(data) {
                    if (data.status === 'success') {
                        var eventos = [];
                        $.each(data.actividades, function(index, actividad) {
                            // Definir color según el estatus
                            var colorEvento = '';
                            switch (actividad.qualityAreas) {
                                case 'MMZ': colorEvento = '#ffc107'; break;    // Amarillo
                                case 'FZ': colorEvento = '#ff9800'; break;     // Naranja
                                case 'MG': colorEvento = '#e91e63'; break;     // Rosa
                                case 'BR': colorEvento = '#795548'; break;     // Café
                                case 'LC': colorEvento = '#28a745'; break;     // Verde
                                case 'MT': colorEvento = '#00bcd4'; break;     // Cyan
                                case 'MTS': colorEvento = '#8bc34a'; break;    // Verde claro
                                case 'EV': colorEvento = '#9c27b0'; break;     // Morado
                                case 'DU': colorEvento = '#607d8b'; break;     // Azul grisáceo
                                case 'MO': colorEvento = '#3f51b5'; break;     // Azul
                                case 'MMM': colorEvento = '#f44336'; break;    // Rojo
                                case 'SFG': colorEvento = '#cddc39'; break;    // Lima
                                case 'DISL': colorEvento = '#ffeb3b'; break;   // Amarillo claro
                                case 'LE': colorEvento = '#bdb76b'; break;     // Caqui
                                case 'PT': colorEvento = '#00ff00'; break;     // Verde neón
                                case 'OPT': colorEvento = '#009688'; break;    // Verde azulado
                                case 'TE': colorEvento = '#2196f3'; break;     // Azul claro
                                case 'MA': colorEvento = '#ff5722'; break;     // Naranja fuerte
                                case 'APP': colorEvento = '#673ab7'; break;    // Morado oscuro
                                case 'LS': colorEvento = '#b0bec5'; break;     // Gris azulado
                                case 'PR': colorEvento = '#dc3545'; break;     // Rojo oscuro
                                case 'FM': colorEvento = '#607d3b'; break;     // Verde oliva
                                case 'EL': colorEvento = '#ffd700'; break;     // Dorado
                                case 'MI': colorEvento = '#4caf50'; break;     // Verde medio
                                case 'AX': colorEvento = '#ffb300'; break;     // Amarillo anaranjado
                                case 'PRSL': colorEvento = '#ad1457'; break;   // Rosa oscuro
                                case 'FZSL': colorEvento = '#ff7043'; break;   // Naranja suave
                                case 'D': colorEvento = '#616161'; break;      // Gris oscuro
                                case 'PTSL': colorEvento = '#00bfae'; break;   // Verde azulado claro
                                case 'ELSL': colorEvento = '#ffd54f'; break;   // Amarillo pastel
                                case 'OPTM': colorEvento = '#1de9b6'; break;   // Verde menta
                                case 'ME': colorEvento = '#c62828'; break;     // Rojo intenso
                                case 'LD': colorEvento = '#a1887f'; break;     // Marrón claro
                                case 'TF': colorEvento = '#90caf9'; break;     // Azul pastel
                                case 'LDM': colorEvento = '#ffccbc'; break;    // Naranja pastel
                                case 'AM': colorEvento = '#ffd1dc'; break;     // Rosa pastel
                                case 'DMTY': colorEvento = '#b2dfdb'; break;   // Verde agua
                                case 'TI': colorEvento = '#b39ddb'; break;     // Morado pastel
                                case 'HU': colorEvento = '#f8bbd0'; break;     // Rosa claro
                                case 'DINL': colorEvento = '#c5e1a5'; break;   // Verde claro pastel
                                default: colorEvento = '#007bff';              // Azul por defecto
                            }
                            // Construir la descripción con todos los campos
                            var descripcionCompleta = 
                                'Ingeniero(s): ' + actividad.Engineers + '\n' +
                                'Area: ' + actividad.qualityAreas + '\n' +
                                'OT: ' + actividad.orderCode + '\n' +
                                'Cliente: ' + (actividad.cliente || '') + '\n';

                            eventos.push({
                                title: descripcionCompleta, // Mostrar toda la descripción en el title
                                description: descripcionCompleta.replace(/\n/g, '<br>'), // Para el tooltip en HTML
                                start: actividad.FechaPlaneadaInicioDate,
                                end: actividad.FechaPlaneadaInicioDate,
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

    </script>
</body>
</html>