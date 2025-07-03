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
                    <h1>Calendario de Actividades Planeadas</h1>

                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="filtro-area" class="mr-2">Filtrar por Área:</label>
                            <select id="filtro-area" class="form-select mr-3">
                                <option value="">Todas las áreas</option>
                                <option value="MMZ">MMZ</option>
                                <option value="FZ">FZ</option>
                                <option value="MG">MG</option>
                                <option value="BR">BR</option>
                                <option value="LC">LC</option>
                                <option value="MT">MT</option>
                                <option value="MTS">MTS</option>
                                <option value="EV">EV</option>
                                <option value="DU">DU</option>
                                <option value="MO">MO</option>
                                <option value="MMM">MMM</option>
                                <option value="SFG">SFG</option>
                                <option value="DISL">DISL</option>
                                <option value="LE">LE</option>
                                <option value="PT">PT</option>
                                <option value="OPT">OPT</option>
                                <option value="TE">TE</option>
                                <option value="MA">MA</option>
                                <option value="APP">APP</option>
                                <option value="LS">LS</option>
                                <option value="PR">PR</option>
                                <option value="FM">FM</option>
                                <option value="EL">EL</option>
                                <option value="MI">MI</option>
                                <option value="AX">AX</option>
                                <option value="PRSL">PRSL</option>
                                <option value="FZSL">FZSL</option>
                                <option value="D">D</option>
                                <option value="PTSL">PTSL</option>
                                <option value="ELSL">ELSL</option>
                                <option value="OPTM">OPTM</option>
                                <option value="ME">ME</option>
                                <option value="LD">LD</option>
                                <option value="TF">TF</option>
                                <option value="LDM">LDM</option>
                                <option value="AM">AM</option>
                                <option value="DMTY">DMTY</option>
                                <option value="TI">TI</option>
                                <option value="HU">HU</option>
                                <option value="DINL">DINL</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filtro-ingeniero" class="mr-2">Filtrar por Ingeniero:</label>
                            <input type="text" id="filtro-ingeniero" class="form-control mr-3" placeholder="Buscar ingeniero...">
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button class="btn btn-primary btn-md w-100" style="margin-top: 24px;" onclick="filtrar()">Aplicar filtro</button>
                        </div>
                    </div>
                    <br><br>
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
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.min.js"></script>    
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/5.10.1/main.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/5.10.1/locales/es.js"></script>
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
                            // Extraer el área de order_code usando substring antes del primer '-'                            
                            areaOT = actividad.order_code.substring(0, actividad.order_code.indexOf('-')).replace('25', '');

                            switch (areaOT) {
                                case 'MMZ': colorEvento = '#b08805'; break;    // Amarillo (oscurecido)
                                case 'FZ': colorEvento = '#b06b00'; break;     // Naranja (oscurecido)
                                case 'MG': colorEvento = '#a11646'; break;     // Rosa (oscurecido)
                                case 'BR': colorEvento = '#573d32'; break;     // Café (oscurecido)
                                case 'LC': colorEvento = '#1b722f'; break;     // Verde (oscurecido)
                                case 'MT': colorEvento = '#008692'; break;     // Cyan (oscurecido)
                                case 'MTS': colorEvento = '#62872f'; break;    // Verde claro (oscurecido)
                                case 'EV': colorEvento = '#6e1c7d'; break;     // Morado (oscurecido)
                                case 'DU': colorEvento = '#42565e'; break;     // Azul grisáceo (oscurecido)
                                case 'MO': colorEvento = '#2d397e'; break;     // Azul (oscurecido)
                                case 'MMM': colorEvento = '#ae2e26'; break;    // Rojo (oscurecido)
                                case 'SFG': colorEvento = '#929900'; break;    // Lima (oscurecido)
                                case 'DISL': colorEvento = '#b8a92b'; break;   // Amarillo claro (oscurecido)
                                case 'LE': colorEvento = '#827f4c'; break;     // Caqui (oscurecido)
                                case 'PT': colorEvento = '#00b800'; break;     // Verde neón (oscurecido)
                                case 'OPT': colorEvento = '#006c61'; break;    // Verde azulado (oscurecido)
                                case 'TE': colorEvento = '#176bb0'; break;     // Azul claro (oscurecido)
                                case 'MA': colorEvento = '#b33e18'; break;     // Naranja fuerte (oscurecido)
                                case 'APP': colorEvento = '#49287f'; break;    // Morado oscuro (oscurecido)
                                case 'LS': colorEvento = '#7c8690'; break;     // Gris azulado (oscurecido)
                                case 'PR': colorEvento = '#9b252f'; break;     // Rojo oscuro (oscurecido)
                                case 'FM': colorEvento = '#42562a'; break;     // Verde oliva (oscurecido)
                                case 'EL': colorEvento = '#b39800'; break;     // Dorado (oscurecido)
                                case 'MI': colorEvento = '#357335'; break;     // Verde medio (oscurecido)
                                case 'AX': colorEvento = '#b38000'; break;     // Amarillo anaranjado (oscurecido)
                                case 'PRSL': colorEvento = '#760e3c'; break;   // Rosa oscuro (oscurecido)
                                case 'FZSL': colorEvento = '#b34c2e'; break;   // Naranja suave (oscurecido)
                                case 'D': colorEvento = '#434343'; break;      // Gris oscuro (oscurecido)
                                case 'PTSL': colorEvento = '#008778'; break;   // Verde azulado claro (oscurecido)
                                case 'ELSL': colorEvento = '#b39837'; break;   // Amarillo pastel (oscurecido)
                                case 'OPTM': colorEvento = '#157a7f'; break;   // Verde menta (oscurecido)
                                case 'ME': colorEvento = '#8c1b1b'; break;     // Rojo intenso (oscurecido)
                                case 'LD': colorEvento = '#725f59'; break;     // Marrón claro (oscurecido)
                                case 'TF': colorEvento = '#6287b2'; break;     // Azul pastel (oscurecido)
                                case 'LDM': colorEvento = '#b89486'; break;    // Naranja pastel (oscurecido)
                                case 'AM': colorEvento = '#b8949e'; break;     // Rosa pastel (oscurecido)
                                case 'DMTY': colorEvento = '#7ea2a0'; break;   // Verde agua (oscurecido)
                                case 'TI': colorEvento = '#7e6e9e'; break;     // Morado pastel (oscurecido)
                                case 'HU': colorEvento = '#b28594'; break;     // Rosa claro (oscurecido)
                                case 'DINL': colorEvento = '#8a9b72'; break;   // Verde claro pastel (oscurecido)
                                default: colorEvento = '#00559f';              // Azul por defecto (oscurecido)
                            }                                                        
                            
                            // Construir la descripción con todos los campos
                            var descripcionCompleta = 
                                '<i class="fas fa-user"></i> <b>' + actividad.engineer + '</b>\n' +
                                'Area: ' + areaOT + '\n' +
                                'OT: ' + actividad.order_code + '\n' +
                                'Cliente: ' + (actividad.cliente || '') + '\n'+
                                '<hr style="margin-top:0;margin-bottom:0;border-width:2px; border-color:black; border-style:solid;">';

                            eventos.push({
                                title: descripcionCompleta.replace(/\n/g, '<br>'), // Mostrar toda la descripción en el title
                                description: descripcionCompleta.replace(/\n/g, '<br>'), // Para el tooltip en HTML
                                start: actividad.FechaPlaneadaInicioDate,
                                end: actividad.FechaPlaneadaInicioDate,
                                color: colorEvento
                            });

                            // Construir la descripción con todos los campos
                            /*var descripcionCompleta = 
                                'Ingeniero(s): ' + actividad.Engineers + '\n' +
                                'Area: ' + actividad.qualityAreas + '\n' +
                                'OT: ' + actividad.orderCode + '\n' +
                                'Cliente: ' + (actividad.cliente || '') + '\n'+
                                '<hr style="margin-top:0;margin-bottom:0;border-width:2px; border-color:black; border-style:solid;">';

                            eventos.push({
                                title: descripcionCompleta.replace(/\n/g, '<br>'), // Mostrar toda la descripción en el title
                                description: descripcionCompleta.replace(/\n/g, '<br>'), // Para el tooltip en HTML
                                start: actividad.FechaPlaneadaInicioDate,
                                end: actividad.FechaPlaneadaInicioDate,
                                color: colorEvento
                            });*/
                        });
                        

                        // Crear el calendario
                        var calendarEl = document.createElement('div');
                        calendarEl.id = 'fullcalendar';
                        $('#calendarioActividadesPlaneadas').append(calendarEl);

                        // Inicializar FullCalendar 
                            inicializarCalendario(calendarEl, eventos);
                        
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
                    initialView: 'dayGridWeek',
                    locale: 'es',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,dayGridWeek,timeGridDay'
                    },
                    events: eventos,
                    eventContent: function(arg) {
                        // Permitir HTML en el título del evento
                        return { html: arg.event.title };
                    },
                    eventDidMount: function(info) {
                        // Tooltip con la descripción
                        $(info.el).tooltip({
                            title: info.event.extendedProps.description,
                            html: true,
                            placement: 'top',
                            container: 'body',
                            trigger: 'hover'
                        });
                    }
                });
                calendar.render();
            }
        }

        //Funcion para Enviar los datos
        function filtrar() {
            var ing = $('#filtro-ingeniero').val();
            var area = $('#filtro-area').val();
            var accion = "ActividadesCalendarioPlaneadasfiltro";
            
            $.ajax({
                url: 'acciones_calendario.php',
                method: 'POST',
                async: false,
                dataType: 'json',
                data: { accion, ing, area },
                success: function (data) {
                    $('#calendarioActividadesPlaneadas').empty();
                    if (data.status === 'success') {
                        var eventos = [];
                        $.each(data.actividades, function(index, actividad) {
                            // Definir color según el estatus
                            var colorEvento = '';
                            // Extraer el área de order_code usando substring antes del primer '-'                            
                            areaOT = actividad.order_code.substring(0, actividad.order_code.indexOf('-')).replace('25', '');

                            switch (areaOT) {
                                case 'MMZ': colorEvento = '#b08805'; break;    // Amarillo (oscurecido)
                                case 'FZ': colorEvento = '#b06b00'; break;     // Naranja (oscurecido)
                                case 'MG': colorEvento = '#a11646'; break;     // Rosa (oscurecido)
                                case 'BR': colorEvento = '#573d32'; break;     // Café (oscurecido)
                                case 'LC': colorEvento = '#1b722f'; break;     // Verde (oscurecido)
                                case 'MT': colorEvento = '#008692'; break;     // Cyan (oscurecido)
                                case 'MTS': colorEvento = '#62872f'; break;    // Verde claro (oscurecido)
                                case 'EV': colorEvento = '#6e1c7d'; break;     // Morado (oscurecido)
                                case 'DU': colorEvento = '#42565e'; break;     // Azul grisáceo (oscurecido)
                                case 'MO': colorEvento = '#2d397e'; break;     // Azul (oscurecido)
                                case 'MMM': colorEvento = '#ae2e26'; break;    // Rojo (oscurecido)
                                case 'SFG': colorEvento = '#929900'; break;    // Lima (oscurecido)
                                case 'DISL': colorEvento = '#b8a92b'; break;   // Amarillo claro (oscurecido)
                                case 'LE': colorEvento = '#827f4c'; break;     // Caqui (oscurecido)
                                case 'PT': colorEvento = '#00b800'; break;     // Verde neón (oscurecido)
                                case 'OPT': colorEvento = '#006c61'; break;    // Verde azulado (oscurecido)
                                case 'TE': colorEvento = '#176bb0'; break;     // Azul claro (oscurecido)
                                case 'MA': colorEvento = '#b33e18'; break;     // Naranja fuerte (oscurecido)
                                case 'APP': colorEvento = '#49287f'; break;    // Morado oscuro (oscurecido)
                                case 'LS': colorEvento = '#7c8690'; break;     // Gris azulado (oscurecido)
                                case 'PR': colorEvento = '#9b252f'; break;     // Rojo oscuro (oscurecido)
                                case 'FM': colorEvento = '#42562a'; break;     // Verde oliva (oscurecido)
                                case 'EL': colorEvento = '#b39800'; break;     // Dorado (oscurecido)
                                case 'MI': colorEvento = '#357335'; break;     // Verde medio (oscurecido)
                                case 'AX': colorEvento = '#b38000'; break;     // Amarillo anaranjado (oscurecido)
                                case 'PRSL': colorEvento = '#760e3c'; break;   // Rosa oscuro (oscurecido)
                                case 'FZSL': colorEvento = '#b34c2e'; break;   // Naranja suave (oscurecido)
                                case 'D': colorEvento = '#434343'; break;      // Gris oscuro (oscurecido)
                                case 'PTSL': colorEvento = '#008778'; break;   // Verde azulado claro (oscurecido)
                                case 'ELSL': colorEvento = '#b39837'; break;   // Amarillo pastel (oscurecido)
                                case 'OPTM': colorEvento = '#157a7f'; break;   // Verde menta (oscurecido)
                                case 'ME': colorEvento = '#8c1b1b'; break;     // Rojo intenso (oscurecido)
                                case 'LD': colorEvento = '#725f59'; break;     // Marrón claro (oscurecido)
                                case 'TF': colorEvento = '#6287b2'; break;     // Azul pastel (oscurecido)
                                case 'LDM': colorEvento = '#b89486'; break;    // Naranja pastel (oscurecido)
                                case 'AM': colorEvento = '#b8949e'; break;     // Rosa pastel (oscurecido)
                                case 'DMTY': colorEvento = '#7ea2a0'; break;   // Verde agua (oscurecido)
                                case 'TI': colorEvento = '#7e6e9e'; break;     // Morado pastel (oscurecido)
                                case 'HU': colorEvento = '#b28594'; break;     // Rosa claro (oscurecido)
                                case 'DINL': colorEvento = '#8a9b72'; break;   // Verde claro pastel (oscurecido)
                                default: colorEvento = '#00559f';              // Azul por defecto (oscurecido)
                            }                                                        
                            
                            // Construir la descripción con todos los campos
                            var descripcionCompleta = 
                                '<i class="fas fa-user"></i> <b>' + actividad.engineer + '</b>\n' +
                                'Area: ' + areaOT + '\n' +
                                'OT: ' + actividad.order_code + '\n' +
                                'Cliente: ' + (actividad.cliente || '') + '\n'+
                                '<hr style="margin-top:0;margin-bottom:0;border-width:2px; border-color:black; border-style:solid;">';

                            eventos.push({
                                title: descripcionCompleta.replace(/\n/g, '<br>'), // Mostrar toda la descripción en el title
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

                        // Inicializar FullCalendar 
                            inicializarCalendariof(calendarEl, eventos);
                        
                    } else {
                        $('#calendarioActividadesPlaneadas').html('<p>No hay actividades pendientes.</p>');
                    }
                    
                }, error: function (jqXHR, textStatus, errorThrown) {
                    console.error('Error al aplicar el filtro', error);
                }
            });
            function inicializarCalendariof(calendarEl, eventos) {
                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridWeek',
                    locale: 'es',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,dayGridWeek,timeGridDay'
                    },
                    events: eventos,
                    eventContent: function(arg) {
                        // Permitir HTML en el título del evento
                        return { html: arg.event.title };
                    },
                    eventDidMount: function(info) {
                        // Tooltip con la descripción
                        $(info.el).tooltip({
                            title: info.event.extendedProps.description,
                            html: true,
                            placement: 'top',
                            container: 'body',
                            trigger: 'hover'
                        });
                    }
                });
                calendar.render();
            }
        }
    </script>
</body>
</html>