<!-- Topbar -->
<nav class="navbar navbar-expand navbar-light bg-white topbar mb-2 static-top shadow">
    <!-- Enlace a Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Enlace a Bootstrap JS (necesario para el funcionamiento del modal) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Enlace a FontAwesome para los íconos (si usas íconos) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Sidebar Toggle (Topbar) -->
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-0">
        <h6 class="h6 mb-0 text-gray-800">Control Vehicular</h6>
    </div>

    <!-- Topbar Navbar -->
    <ul class="navbar-nav ml-auto">
        <div class="topbar-divider d-none d-sm-block"></div>
        <!-- Nav Item - User Information -->
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="mr-0 d-lg-inline text-gray-600" style="font-size: 0.85em;">
                    <?php echo ' '.$_COOKIE['nombredelusuario'].' ';?>
                </span>
                <img class="img-profile rounded-circle" src="img/undraw_profile.svg" style="width: 100%;">
            </a>
            <input type="hidden" id="coordenadasCheck" name="coordenadasCheck">
            <input type="hidden" id="placaElegida" name="placaElegida">
            <input type="hidden" class="form-control" id="PidPrestamo" name="PidPrestamo">
            <!-- Dropdown - User Information -->
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                    Cambiar Contraseña
                </button>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModalN">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                    Salir
                </a>
            </div>
        </li>
    </ul>

    <!-- MODAL PARA CAMBIO DE CONTRASEÑA-->
    <div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="staticBackdropLabel">Cambiar Contraseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <label>Contraseña Nueva:</label>
                            <input id="nuevapass" name="nuevapass" class="form-control" type="password" required>
                        </div>
                        <div class="col-sm-6">
                            <label>Confirmar Contraseña:</label>
                            <input id="confirmapass" name="confirmapass" class="form-control" type="password" required>
                            <label id="msgPassword" name="msgPassword"></label>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-1"></div>
                        <div class="col-sm-6">
                            <input class="form-check-input" type="checkbox" id="showPassword">
                            <label class="form-check-label" for="showPassword">
                                Ver Contraseña
                            </label>
                            </input>
                        </div>
                        <div class="col-sm-1">
                            <input type="hidden" id="noEmpleado" name="noEmpleado">
                            </input>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" OnClick="validarContrasenas()">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModalN" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content border-left-danger">
                <div class="modal-header">
                    <h4 class="modal-title" id="exampleModalLabel"> Cerrar sesión </h4>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">X</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h5><b>¿Estas seguro?</b></h5>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-warning" type="button" data-dismiss="modal">Cancelar</button>
                    <a class="btn btn-danger" href="logout">Salir</a>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL PARA CAPTURAR KM DE UN AUTO -->
    <div class="modal fade" id="capturaKmModal" tabindex="-1" aria-labelledby="capturaKmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="capturaKmModalLabel">Check In Uso Vehiculo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="formCapturaKm">
                        <div class="mb-2">
                            <label for="vehiculoAsignado" class="form-label">Vehículo Asignado</label>
                            <select class="form-select" id="vehiculoAsignado" name="vehiculoAsignado" onchange="verPlaca()" required>
                                <option value="">Seleccione un vehículo</option>                                
                            </select>                            
                        </div>
                        <div class="mb-2 row g-2">
                            <div class="col-6 col-md-6">
                                <label>Tipo de uso:</label>
                                <select class="form-select" id="tipoServicio" name="tipoServicio" onchange="cambiaLabelTServicio()" required>
                                    <option value="" disabled selected>Seleccione una opción</option>
                                    <option value="Entrega">Entrega</option>
                                    <option value="Recoleccion">Recolección</option>
                                    <option value="Prospeccion">Prospección</option>
                                    <option value="Negociacion">Negociación</option>
                                    <option value="Proyecto">Proyecto</option>
                                    <option value="OV">OV</option>
                                    <option value="OT">OT</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-6">
                                <label id="labelTServicio" name="labelTServicio" class="form-label">OT Relacionada</label>
                                <input type="text" class="form-control" id="otRelacionada" name="otRelacionada" required>
                            </div>                            
                        </div>
                        <div class="mb-2 row g-2">
                            <div class="col-4 col-md-4">
                                <label for="kmActual" class="form-label">Km Actual</label>
                                <input type="number" class="form-control" id="kmActual" name="kmActual" min="0" required>
                            </div>
                            <div class="col-4 col-md-4">
                                <label for="kmActual" class="form-label">Gas. Actual</label>                                
                                <select class = "form-select" id = "gasActual" name = "gasActual">
                                    <option value = "">Seleccione...</option>
                                    <option value = "SD">Sin Datos</option>
                                    <option value = "1/8">1/8</option>
                                    <option value = "2/8">2/8</option>
                                    <option value = "3/8">3/8</option>    
                                    <option value = "4/8">4/8</option>
                                    <option value = "5/8">5/8</option>
                                    <option value = "6/8">6/8</option>
                                    <option value = "7/8">7/8</option>
                                    <option value = "8/8">8/8</option>
                                </select> 
                            </div>
                            <div class="col-4 col-md-4">
                                <label class="form-label">Patron</label>
                                <input type="text" class="form-control" id="patronRelacionado" name="patronRelacionado" required>
                            </div>
                        </div>
                        <div class="mb-2 row g-2">
                            <div class="col-12 col-md-12">
                                <label for="kmActual" class="form-label">Notas</label>
                                <textarea name="notasCheckin" id="notasCheckin" class="form-control" rows="2" cols="5"></textarea>
                            </div>
                        </div>
                        <div class="mb-2 row g-2">
                            <div class="col-12 col-md-12">
                                <label for="kmActual" class="form-label">Img.</label>
                                <input type="file" class="form-control" id="imgCheckin" name="imgCheckin[]" accept=".jpg,.jpeg,.png">
                                <div id="contenedorImgCheckin"></div>
                                <button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="agregarInputImagen()">Agregar otra imagen</button>
                            </div>    
                        </div>
                        <div id="msgKm" class="form-text text-danger"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarCheckIn()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL PARA VER ACTIVIDADES PENDIENTES -->
    <div class="modal fade" id="actividadesPendientesModal" tabindex="-1" aria-labelledby="actividadesPendientesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="actividadesPendientesModalLabel">Actividades Pendientes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div id="actividadesPendientesContent">
                        <div class="text-center">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Título</th>
                                        <th>Descripción</th>
                                        <th>Vehículo</th>                                    
                                    </tr>
                                </thead>
                                <tbody id="tablaActividadesPendientes">                                    
                                </tbody>
                            </table>
                            <form id="formNuevaActividadPendiente" class="mb-3">                                
                                <div class="row g-2 mt-2">

                                    <div class="col-md-3">
                                        <label for="kmActualNuevo" class="form-label">Km Actual</label>
                                        <input type="number" class="form-control" id="kmActualNuevo" name="kmActualNuevo" min="0" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="gasActualNuevo" class="form-label">Gas. Actual</label>
                                        <select class = "form-select" id = "gasActualNuevo" name = "gasActualNuevo">
                                            <option value = "">Seleccione...</option>
                                            <option value = "SD">Sin Datos</option>
                                            <option value = "1/8">1/8</option>
                                            <option value = "2/8">2/8</option>
                                            <option value = "3/8">3/8</option>    
                                            <option value = "4/8">4/8</option>
                                            <option value = "5/8">5/8</option>
                                            <option value = "6/8">6/8</option>
                                            <option value = "7/8">7/8</option>
                                            <option value = "8/8">8/8</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="notasCheckinNuevo" class="form-label">Notas</label>
                                        <textarea name="notasCheckinNuevo" id="notasCheckinNuevo" class="form-control" rows="1"></textarea>
                                    </div>
                                </div>
                                <div class="row g-2 mt-2">
                                    <div class="col-12 col-md-12 alert alert-primary me-0" id="divFinalizarPrestamo" name=="divFinalizarPrestamo">
                                        <div class="form-check form-switch d-flex align-items-center mb-2" style="font-size: 1.1em;">
                                            <input class="form-check-input me-4" type="checkbox" id="finalizarPrestamo" name="finalizarPrestamo" style="width:2.5em; height:1.5em;">
                                            <label class="form-check-label fw-bold text-primary" for="finalizarPrestamo" style="margin-left: 1.5em;">                                                
                                                Marca esta casilla si vas a <span class="text-success">finalizar tu préstamo</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-2 mt-2">
                                    <div class="col-12 col-md-12">                                        
                                        <input type="file" class="form-control" id="imgCheckinNuevo" name="imgCheckinNuevo[]" accept=".jpg,.jpeg,.png,.pdf">
                                        <div id="contenedorImgCheckout"></div>
                                        <button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="agregarInputImagenOut()">Agregar otra imagen</button>
                                    </div>                                      
                                    <input type="hidden" class="form-control" id="PidVehiculo" name="PidVehiculo">
                                    <input type="hidden" class="form-control" id="PtipoActividad" name="¨PtipoActividad">
                                    <input type="hidden" class="form-control" id="Ppatron" name="Ppatron">
                                    <input type="hidden" class="form-control" id="Pot" name="Pot">
                                </div>
                                <div class="mt-2" id ="cerrarActividadPendiente">                                    
                                </div>
                            </form>                            
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    </nav>
<!-- End of Topbar -->
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <script  type="text/javascript">
        $(document).ready(function () {
            // Obtener coordenadas del usuario
            obtenerCoordenadas();   
        });

        // Función para obtener las coordenadas del usuario
        function obtenerCoordenadas() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        const lat = position.coords.latitude.toFixed(6); 
                        const lon = position.coords.longitude.toFixed(6); 
                        $("#coordenadasCheck").val(`${lat}, ${lon}`); // Establecer las coordenadas en el campo                
                    },
                    function (error) {
                        //console.error("Error al obtener la ubicación: ", error);                        
                    }
                );
            } else {
                //console.error("Geolocalización no es soportada por este navegador.");
            }
        }
        
        // Función para cargar y mostrar actividades pendientes en el modal
        // Sobrescribe la función para mostrar actividades en tabla
    function mostrarActividadesPendientes() {
        $('#actividadesPendientesModal').modal('show');
        $('#tablaActividadesPendientes').html('<tr><td colspan="4" class="text-center"><span class="spinner-border" role="status" aria-hidden="true"></span> Cargando actividades...</td></tr>');
        $.ajax({
            url: 'acciones_kilometraje.php',
            method: 'POST',
            dataType: 'json',
            data: { accion: 'ActividadesPendientes' },
            success: function (data) {
                if (data.length > 0) {
                    var html = '';
                    var botonCerrarActividad = '';
                    data.forEach(function (actividad, idx) {
                        // Si la actividad tiene los datos necesarios, guarda el botón en una variable aparte (no en la tabla)
                        botonCerrarActividad = '<button type="button" class="btn btn-success" onclick="CapturaCheckOut()">' + '<i class="fas fa-check"></i> Captura CheckOut</button>';
                        
                        if (!actividad.id_prestamo) {
                            $('#divFinalizarPrestamo').hide();
                        } else {
                            $('#divFinalizarPrestamo').show();
                        }

                        html += '<tr>';
                        html += '<td>' + (idx + 1) + '</td>';
                        html += '<td>' + (actividad.titulo || 'Actividad') + '</td>';
                        html += '<td>' + (actividad.notas || '') + '</td>';
                        html += '<td>' + (actividad.vehiculo || actividad.placa || '') + '</td>';                        
                        html += '</tr>';

                        // Asignar los valores a los campos del formulario
                        $('#kmActualNuevo').val(actividad.km_actual);
                        $('#gasActualNuevo').val(actividad.gasolina_actual);
                        $('#PidPrestamo').val(actividad.id_prestamo);
                        $('#PidVehiculo').val(actividad.id_vehiculo);
                        $('#PtipoActividad').val(actividad.detalle_tipo_uso);
                        $('#Ppatron').val(actividad.patron);
                        $('#Pot').val(actividad.ot);
                        $('#placaElegida').val(actividad.placa);
                    });
                    $('#tablaActividadesPendientes').html(html);
                    $('#cerrarActividadPendiente').html(botonCerrarActividad);
                } else {
                    $('#tablaActividadesPendientes').html('<tr><td colspan="4" class="text-center text-success">No tienes actividades pendientes.</td></tr>');
                }
            },
            error: function () {
                $('#tablaActividadesPendientes').html('<tr><td colspan="4" class="text-center text-danger">Error al cargar las actividades pendientes.</td></tr>');
            }
        });
    }

    function guardarCheckIn() {
            // Obtener los valores de los campos del formulario
            var vehiculoAsignado = $('#vehiculoAsignado').val();
            var otRelacionada = $('#otRelacionada').val();
            var kmActual = $('#kmActual').val();
            var patronRelacionado = $('#patronRelacionado').val();
            var notasCheckin = $('#notasCheckin').val();
            var gasActual = $('#gasActual').val();            
            var placaElegida = $('#placaElegida').val();
            var tipoServicio = $('#tipoServicio').val();
            var coordenadas = $('#coordenadasCheck').val();
            
            // Validar que el tipo de servicio sea válido               
            // Validar que los campos no estén vacíos
            if (!vehiculoAsignado || !kmActual || !gasActual || !tipoServicio || !otRelacionada) {
                $('#msgKm').text('Por favor, complete todos los campos obligatorios.');
                return;
            }

            var formData = new FormData();
            formData.append('id_prestamo', $('#PidPrestamo').val()); // Asignar un ID de préstamo por defecto
            formData.append('accion', 'CapturaCheckIn');
            formData.append('vehiculoAsignado', vehiculoAsignado);
            formData.append('otRelacionada', otRelacionada);
            formData.append('kmActual', kmActual);
            formData.append('patronRelacionado', patronRelacionado);
            formData.append('notasCheckin', notasCheckin);
            formData.append('gasActual', gasActual);
            formData.append('tipoServicio', tipoServicio);
            formData.append('placa', placaElegida);
            formData.append('coordenadas', coordenadas);

            // Adjuntar todos los archivos de imagen seleccionados (incluyendo los inputs adicionales)
            var archivos = document.querySelectorAll('input[name="imgCheckin[]"]');
            archivos.forEach(function(input) {
                if (input.files.length > 0) {
                    for (var i = 0; i < input.files.length; i++) {
                        formData.append('imgCheckin[]', input.files[i]);
                    }
                }
            });
            

            $.ajax({
                url: 'acciones_kilometraje.php',
                method: 'POST',
                dataType: 'json',
                data: formData,
                processData: false,
                contentType: false,
                success: function (resp) {
                    Swal.fire({
                    title: "¡Guardado!",
                    text: "Kilometraje registrado correctamente.",
                    icon: "success",
                    timer: 2000,
                    timerProgressBar: true
                    }).then(function () {
                        $('#formCapturaKm')[0].reset();
                        $('#capturaKmModal').modal('hide');
                        $('#msgKm').text('');
                    });
                },
            error: function () {
                $('#msgKm').text('Error al guardar el kilometraje.');
            }
            });
        }

        // Función para capturar el check-out
        function CapturaCheckOut() {

            var id_prestamo = $('#PidPrestamo').val();
            var vehiculoAsignado = $('#PidVehiculo').val();
            var otRelacionada = $('#Pot').val();
            var kmActual = $('#kmActualNuevo').val();
            var patronRelacionado = $('#Ppatron').val();
            var notasCheckin = $('#notasCheckinNuevo').val();
            var gasActual = $('#gasActualNuevo').val();                        
            var tipoServicio = $('#PtipoActividad').val();
            var coordenadas = $('#coordenadasCheck').val();
            var placaElegida = $('#placaElegida').val();
            // Validar que los campos no estén vacíos
            if (!vehiculoAsignado || !kmActual || !gasActual) {
            $('#msgKm').text('Por favor, complete todos los campos obligatorios.');
            return;
            }

            var formData = new FormData();
            formData.append('accion', 'CapturaCheckOut');
            formData.append('id_prestamo', id_prestamo);
            formData.append('vehiculoAsignado', vehiculoAsignado);
            formData.append('otRelacionada', otRelacionada);
            formData.append('kmActual', kmActual);
            formData.append('patronRelacionado', patronRelacionado);
            formData.append('notasCheckin', notasCheckin);
            formData.append('gasActual', gasActual);
            formData.append('tipoServicio', tipoServicio);
            formData.append('coordenadas', coordenadas);
            formData.append('placa', placaElegida);
            formData.append('finalizarPrestamo', $('#finalizarPrestamo').is(':checked') ? 'Si' : 'No');
            // Adjuntar la imagen del check-in si existe
            // Adjuntar todos los archivos de imagen seleccionados (incluyendo los inputs adicionales)
            var archivos = document.querySelectorAll('input[name="imgCheckinNuevo[]"]');
            archivos.forEach(function(input) {
                if (input.files.length > 0) {
                    for (var i = 0; i < input.files.length; i++) {
                        formData.append('imgCheckinNuevo[]', input.files[i]);
                    }
                }
            });

            $.ajax({
            url: 'acciones_kilometraje.php',
            method: 'POST',
            dataType: 'json',
            data: formData,
            processData: false,
            contentType: false,
            success: function (resp) {
                Swal.fire({
                title: "¡Guardado!",
                text: "Kilometraje registrado correctamente.",
                icon: "success",
                timer: 2000,
                timerProgressBar: true
                }).then(function () {
                $('#formCapturaKm')[0].reset();
                $('#actividadesPendientesModal').modal('hide');                
                $('#msgKm').text('');
                });
            },
            error: function () {
                $('#msgKm').text('Error al guardar el kilometraje.');
            }
            });
            
        }
        
        // Función para agregar otro input de archivo, hasta un máximo de 4
        function agregarInputImagen() {
            // Contenedor donde están los inputs de imagen
            var contenedor = document.getElementById('contenedorImgCheckin');
            // Contar cuántos inputs hay actualmente
            var inputs = contenedor.querySelectorAll('input[type="file"]');
            if (inputs.length < 3) {
                // Crear un nuevo input
                var nuevoInput = document.createElement('input');
                nuevoInput.type = 'file';
                nuevoInput.className = 'form-control mt-1';
                nuevoInput.name = 'imgCheckin[]';
                nuevoInput.accept = '.jpg,.jpeg,.png';
                contenedor.appendChild(nuevoInput);
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Límite alcanzado',
                    text: 'Solo puedes subir hasta 4 imágenes.'
                });
            }
        }
        // Función para agregar otro input de archivo, hasta un máximo de 4
        function agregarInputImagenOut() {
            // Contenedor donde están los inputs de imagen
            var contenedor = document.getElementById('contenedorImgCheckout');
            // Contar cuántos inputs hay actualmente
            var inputs = contenedor.querySelectorAll('input[name="imgCheckinNuevo[]"]');
            if (inputs.length < 3) {
                // Crear un nuevo input
                var nuevoInput = document.createElement('input');
                nuevoInput.type = 'file';
                nuevoInput.className = 'form-control mt-1';
                nuevoInput.name = 'imgCheckinNuevo[]';
                nuevoInput.accept = '.jpg,.jpeg,.png';
                contenedor.appendChild(nuevoInput);
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Límite alcanzado',
                    text: 'Solo puedes subir hasta 4 imágenes.'
                });
            }
        }

        // Cargar vehículos al select
        function cargarVehiculos() {
            $.ajax({
                url: 'acciones_kilometraje.php',
                method: 'POST',
                dataType: 'json',
                data: { accion: 'CargarVehiculos' },
                success: function (data) {
                    var select = $('#vehiculoAsignado');
                    select.empty();
                    if (data && data.length > 0) {
                        select.append('<option value="">Seleccione un vehículo</option>');
                        $.each(data, function (index, vehiculo) {                            
                            // Asignar id prestamo al input oculto solo si no es vacío o nulo
                            if (vehiculo.id_prestamo !== null && vehiculo.id_prestamo !== '') {
                                $('#PidPrestamo').val(vehiculo.id_prestamo);
                                select.append('<option value="' + vehiculo.id_vehiculo + '" style="background-color: #ffeeba;">PRESTAMO - ' + vehiculo.placa + '-' + vehiculo.modelo + '-  '+ vehiculo.estatus+'</option>');
                            }
                            else{
                                select.append('<option value="' + vehiculo.id_vehiculo + '">' + vehiculo.placa + '-' + vehiculo.modelo + '</option>');
                            }
                        });
                        
                    } else {
                        select.append('<option value="">No hay vehículos disponibles</option>');
                    }
                },
                error: function () {
                    console.error('Error al cargar los vehículos');
                }
            });
        }

        //valida actividades pendientes
        function validarActividadesPendientes() {
            
            $.ajax({
                url: 'acciones_kilometraje.php',
                method: 'POST',
                dataType: 'json',
                data: { accion: 'ActividadesPendientes' },
                success: function (data) {
                    if (data.length > 0) {
                        Swal.fire({
                            title: "¡Atención!",
                            text: "Tienes actividades pendientes de inicio.",
                            icon: "warning",
                            confirmButtonText: "Ver Actividades"
                        }).then(function () {
                            $('#actividadesPendientesModal').modal('show'); // Mostrar el modal de captura de km
                            mostrarActividadesPendientes(); // Cargar vehículos al select
                        });
                    }
                    else{
                        cargarVehiculos(); // Cargar vehículos al select si no hay actividades pendientes
                        $('#capturaKmModal').modal('show'); // Mostrar el modal de captura de km
                    }
                },
                error: function () {
                    cargarVehiculos();
                    $('#capturaKmModal').modal('show'); // Mostrar el modal de captura de km
                    console.error('Error al verificar actividades pendientes');
                }
            });
        }

        function cambiaLabelTServicio() {
            var tipoServicio = document.getElementById("tipoServicio").value;
            var labelTServicio = document.getElementById("labelTServicio");
            if (tipoServicio === "OV") {
                labelTServicio.innerHTML = "OV Relacionada";                
                document.getElementById("otRelacionada").placeholder = "Ej. 0000-2025";
            } else if (tipoServicio === "OT") {
                labelTServicio.innerHTML = "OT Relacionada";
                document.getElementById("otRelacionada").placeholder = "Ej. XX25-00X-000";
            } else if (tipoServicio === "Proyecto") {
                labelTServicio.innerHTML = "Proyecto Relacionado";
                document.getElementById("otRelacionada").placeholder = "0000";
            } else {
                labelTServicio.innerHTML = "Detalle"; // Valor por defecto
                document.getElementById("otRelacionada").placeholder = "Si aplica, OV/OT/Proyecto";
            }
        }
        
        function verPlaca() {            
            var vehiculoAsignado = document.getElementById("vehiculoAsignado").textContent;
            if (vehiculoAsignado) {
                // Tomar solo el primer valor antes del guion "-"
                var primerValor = vehiculoAsignado.split('-')[0].trim();
                primerValor = primerValor.replace("Seleccione un vehículo", "");
                // Asignar el valor al input oculto
                document.getElementById("placaElegida").value = primerValor;
            }
        }
        function verPlacaCheckOut() {
            var vehiculoAsignado = document.getElementById("vehiculoAsignado").textContent;
            if (vehiculoAsignado) {
                // Tomar solo el primer valor antes del guion "-"
                var primerValor = vehiculoAsignado.split('-')[0].trim();
                primerValor = primerValor.replace("Seleccione un vehículo", "");
                // Asignar el valor al input oculto
                document.getElementById("placaElegida").value = primerValor;
            }
        }

        // Función para mostrar/ocultar contraseñas
        document.getElementById('showPassword').addEventListener('change', function () {
            var passwordField = document.getElementById('nuevapass');
            var confirmPasswordField = document.getElementById('confirmapass');

            if (this.checked) {
                // Mostrar contraseñas (tipo 'text')
                passwordField.type = 'text';
                confirmPasswordField.type = 'text';
            } else {
                // Ocultar contraseñas (tipo 'password')
                passwordField.type = 'password';
                confirmPasswordField.type = 'password';
            }
        });

        //Funcion para validar las contraseñas
        function validarContrasenas() {
            var password = $('#nuevapass').val()
            var confirmPassword = $('#confirmapass').val()
            var error = document.getElementById("error");

            // Si las contraseñas no coinciden
            if (password !== confirmPassword) {
                $('#msgPassword').text("Las constraseñas no coinciden.");
            } else {
                Confirmar();
            }
        }

        //Funcion para Enviar los datos
        function Confirmar() {
            var password = $('#nuevapass').val();
            var noEmpleado = $('#noEmpleado').val();
            var accion = "CambioPassword";

            $.ajax({
                url: 'acciones_contrasena.php',
                method: 'POST',
                async: false,
                dataType: 'json',
                data: { accion, password, noEmpleado },
                success: function (Registros) {
                    Swal.fire({
                        title: "Confirmado!",
                        text: "Contraseña cambiada!",
                        icon: "success",
                        timer: 2000,
                        timerProgressBar: true
                    }).then(function () {
                        // Limpiar los campos después de cerrar la alerta
                        $('#nuevapass').val('');
                        $('#confirmapass').val('');
                        $('#staticBackdrop').modal('hide');
                    });
                }, error: function (jqXHR, textStatus, errorThrown) {
                    console.error('Error al aplicar el cambio', error);
                }
            });
        }

        //Funcion para leer cookies
        function getCookie(name) {
            let value = "; " + document.cookie;
            let parts = value.split("; " + name + "=");
            if (parts.length === 2) return parts.pop().split(";").shift();
            return null; // Si no encuentra la cookie, retorna null
        }
        // Asignar el valor de la cookie al input
        window.onload = function () {
            var cookieValue = getCookie("noEmpleado"); // Aquí "noEmpleadoCookie" es el nombre de la cookie

            // Verificar si la cookie existe y asignar el valor al input
            if (cookieValue) {
                document.getElementById("noEmpleado").value = cookieValue;
            }
        };

        
    </script>

