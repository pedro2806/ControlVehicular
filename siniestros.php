<?php
    session_start();
    include 'conn.php';
?>
<!DOCTYPE html>
<html lang = "en">
<head>
    <meta charset = "utf-8">
    <meta http-equiv = "X-UA-Compatible" content = "IE = edge">
    <meta name = "viewport" content = "width = device-width, initial-scale = 1, shrink-to-fit = no">
    <meta name = "description" content = "">
    <meta name = "author" content = "">

    <title>CONTROL VEHICULAR</title>

    <!-- Custom fonts for this template-->
    <link href = "vendor/fontawesome-free/css/all.min.css" rel = "stylesheet" type = "text/css">
    <!-- Custom styles for this template-->
    <link href = "css/sb-admin-2.min.css" rel = "stylesheet">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
</head>
<body id = "page-top">
    <!-- Page Wrapper -->
    <div id = "wrapper">
        <?php  
            include 'menu.php';
        ?>
        <!-- Content Wrapper -->
        <div id = "content-wrapper" class = "d-flex flex-column">
            <!-- Main Content -->
            <div id = "content">
            
                <?php
                    include 'encabezado.php';
                ?>
                
                <!-- Begin Page Content -->
                <div class = "container-fluid">

                    <!-- Page Heading -->
                    <div class = "d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class = "h3 mb-0 text-black-800">Registro de Siniestro</h1>                        
                    </div>
                    <!-- TABLA DE VEHICULOS -->
                    <div class="container">
                        <h3>Inventario de Vehículos</h3>
                        <table id="tablaInventario" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Placa</th>
                                    <th>Modelo</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Las filas se cargarán dinámicamente -->
                            </tbody>
                        </table>
                    </div>

                    <!-- CONTENEDOR INFO AUTO -->
                    <div id="placaSeleccionada" class="alert alert-info" style="display: none;"></div> 
                    <button id="btnCambiarVehiculo" class="btn btn-outline-primary" style="display: none;" onclick="cambiarVehiculo()">Cambiar Vehículo</button>

                    <!-- FORMULARIO DEL SINIESTRO -->
                    <form id="formRegistroSiniestro">
                        <!-- Content Row -->
                        <div class = "row">
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Fecha:</label>
                                <input type = "date" class = "form-control" id = "fecha" name = "fecha" required>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Hora:</label> 
                                <input type = "time" class = "form-control" id = "hora" name = "hora" required>       
                            </div>
                            <br>
                            <br>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Tipo de Vehiculo:</label> 
                                <select class="form-select" id="tipo_carro" name="tipo_carro" required onchange="mostrarCampoDueno()">
                                    <option value="">Seleccione...</option>
                                    <option value="Asignado">Asignado</option>
                                    <option value="Propio">Propio</option>
                                    <option value="Prestado">Prestado</option>
                                </select>       
                            </div>
                            <br>
                            <br>
                            <!-- Campo adicional para el nombre del dueño -->
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6" id="campo_dueno" style="display: none;">
                                <label>Propietario:</label>
                                <select class="form-select" id="id_dueno" name="id_dueno" required>
                                    <option value="">Seleccione...</option>
                                    <!-- Las opciones se cargarán dinámicamente -->
                                </select>
                            </div>
                        </div>
                        <br>
                        <!-- Content Row -->
                        <h1 class = "h5 mb-0 text-gray-800">Ubicacion</h1>
                        <div class="row">
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Origen:</label>  
                                <input class = "form-control" id = "origen" name = "origen"  required>    
                            </div>
                            <br>
                            <br>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Destino:</label>
                                <input type = "text" class = "form-control" id = "destino" name = "destino" required>
                            </div>
                            <br>
                            <br>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Lugar:</label>  
                                <input class = "form-control" id = "lugar" name = "lugar">
                            </div>
                            <br>
                            <br>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Empresa:</label>
                                <input type = "text" class = "form-control" id = "empresa" name = "empresa" oninput="this.value = this.value.toUpperCase()">
                            </div>
                            <br>
                            <br>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Servicio:</label>
                                <input type = "text" class = "form-control" id = "servicio" name = "servicio">
                            </div>
                        </div>
                        <br>
                        <!-- Content Row -->
                        <div class = "row">
                            <h1 class = "h5 mb-0 text-gray-800">Detalles del Automovil</h1>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Kilometraje:</label>  
                                <input class = "form-control" id = "kilometraje" name = "kilometraje" type="number" min="0">
                            </div>
                            <br>
                            <br>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Gasolina:</label>
                                <select class = "form-select" id = "gasolina" name = "gasolina">
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
                            <br>
                            <br>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Ubicacion del Vehiculo:</label>  
                                <input class = "form-control" id = "ubicacion" name = "ubicacion" required>
                            </div>
                            <br>
                            <br>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Partes Dañadas:</label>  
                                <input class = "form-control" id = "daños" name = "daños" required>
                            </div>
                            <br>
                            <br>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Contacto:</label>  
                                <input class = "form-control" id = "contacto" name = "contacto" type="tel" required>
                            </div>
                            <br>
                            <br>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Descripción:</label>
                                <textarea class = "form-control" id = "descripcion" name = "descripcion" required></textarea>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6 col-6">
                                <label>Foto del Siniestro:</label>
                                <input 
                                    type="file" 
                                    class="form-control" 
                                    id="foto" 
                                    name="foto" 
                                    accept="image/*" 
                                    capture="environment" 
                                    required>
                            </div>
                        </div>
                        <br>
                        <input type="hidden" id = "coordenadas" name = "coordenadas">
                        <input type="hidden" id = "id_vehiculo" name = "id_vehiculo">
                        <center>
                            <button type="button" class="btn btn-outline-success" onclick="RegistrarSiniestro()">Confirmar</button>
                        </center>
                    </form>
                    <br>
                </div>
            </div>
            <footer class = "sticky-footer bg-white">
                <div class = "container my-auto">
                    <div class = "copyright text-center my-auto">
                        <span>Copyright &copy; MESS2025</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <a class = "scroll-to-top rounded" href = "#page-top">
        <i class = "fas fa-angle-up"></i>
    </a>
    <!-- Bootstrap core JavaScript-->
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <script src = "vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- Core plugin JavaScript-->
    <script src = "vendor/jquery-easing/jquery.easing.min.js"></script>
    <!-- Custom scripts for all pages-->
    <script src = "js/sb-admin-2.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script type="text/javascript">
        $(document).ready(function() {
            infoVehiculos();
            cargarUsuarios();

            // Llenar automáticamente los campos de fecha y hora
            const now = new Date();
            const fecha = now.toISOString().split('T')[0]; // Formato YYYY-MM-DD
            const hora = now.toTimeString().split(' ')[0].slice(0, 5); // Formato HH:MM

            $("#fecha").val(fecha); // Establecer la fecha actual
            $("#hora").val(hora); // Establecer la hora actual

            // Obtener coordenadas del usuario
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        const lat = position.coords.latitude.toFixed(6); 
                        const lon = position.coords.longitude.toFixed(6); 
                        $("#coordenadas").val(`${lat}, ${lon}`); // Establecer las coordenadas en el campo
                    }
                );
            } else {
                console.error("Geolocalización no soportada por el navegador.");
            }
        });
        
         // FUNCION PARA CARGAR INFORMACIÓN DE LOS VEHÍCULOS
        function infoVehiculos() {
            $.ajax({
                type: "POST",
                url: "acciones_siniestro",
                data: { accion: "consultarInventario" },
                dataType: "json",
                success: function (respuesta) {
                    var tabla = $("#tablaInventario tbody");
                    tabla.empty(); 
                    respuesta.forEach(function (vehiculo) {
                        var fila = 
                            `<tr>
                                <td>${vehiculo.placa}</td>
                                <td>${vehiculo.modelo}</td>
                                <td>
                                    <center>
                                        <button class="btn btn-outline-success btn-sm" onclick="seleccionarVehiculo('${vehiculo.id_vehiculo}', '${vehiculo.placa}')">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </center>
                                </td>
                            </tr>`;
                        tabla.append(fila);
                    });
                    $("#tablaInventario").DataTable({
                        destroy: true, // Permitir reinicializar la tabla
                        language: {
                            decimal: ",",
                            thousands: ".",
                            processing: "Procesando...",
                            search: "Buscar:",
                            lengthMenu: "Mostrar _MENU_ registros",
                            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                            infoEmpty: "Mostrando 0 a 0 de 0 registros",
                            infoFiltered: "(filtrado de _MAX_ registros totales)",
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
                },
                error: function () {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "Hubo un problema al cargar los datos del inventario.",
                        confirmButtonText: "Aceptar"
                    });
                }
            });
        }

        // FUNCION PARA MANEJAR EL BOTÓN "CHECK"
        function seleccionarVehiculo(id_vehiculo, placa) {
            // Actualizar el contenido del contenedor con la placa seleccionada
            $("#placaSeleccionada")
                .text(`Vehículo seleccionado: ${placa}`)
                .show();
            $("#id_vehiculo").val(id_vehiculo);
            
            // Mostrar el botón para cambiar de vehículo
            $("#btnCambiarVehiculo").show();

            // Ocultar la tabla de inventario
            $("#tablaInventario").closest(".container").hide();
        }

        function cambiarVehiculo() {
            // Ocultar el contenedor de la placa seleccionada
            $("#placaSeleccionada").hide();

            // Ocultar el botón para cambiar de vehículo
            $("#btnCambiarVehiculo").hide();

            // Mostrar la tabla de inventario
            $("#tablaInventario").closest(".container").show();
        }

        // Cargar la lista de usuarios 
        function cargarUsuarios() {
            $.ajax({
                type: "POST",
                url: "acciones_mantenimiento",
                data: { accion: "consultarUsuarios" },
                dataType: "json",
                success: function (respuesta) {
                    var select = $("#id_dueno");
                    select.empty(); // Limpiar las opciones existentes
                    select.append('<option value="">Seleccione un propietario...</option>'); // Opción por defecto
                    respuesta.forEach(function (usuario) {
                        select.append(`<option value="${usuario.id_usuario}">${usuario.nombre}</option>`);
                    });
                },
                error: function () {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "Hubo un problema al cargar la lista de usuarios.",
                        confirmButtonText: "Aceptar"
                    });
                }
            });
        }

        //FUNCION REGISTRO DE SINIESTRO
        function RegistrarSiniestro() {
            var fecha = $("#fecha").val();
            var hora = $("#hora").val();
            var origen = $("#origen").val();
            var destino = $("#destino").val();
            var lugar = $("#lugar").val();
            var empresa = $("#empresa").val();
            var servicio = $("#servicio").val();
            var coordenadas = $("#coordenadas").val();
            var kilometraje = $("#kilometraje").val();
            var gasolina = $("#gasolina").val();
            var ubicacion = $("#ubicacion").val();
            var daños = $("#daños").val();
            var contacto = $("#contacto").val();
            var descripcion = $("#descripcion").val();
            var tipo_carro = $("#tipo_carro").val();
            var id_dueno = $("#id_dueno").val();
            var rutaImagen = $("#foto")[0].files[0];
            var placa = $("#placaSeleccionada").text().replace("Vehículo seleccionado: ", "").trim();
            var id_vehiculo = $("#id_vehiculo").val();

            // Validar que la placa esté seleccionada
            if (!placa) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Placa no seleccionada',
                    text: 'Por favor, selecciona un vehículo antes de continuar.',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            // Validar que los campos requeridos no estén vacíos
            if (!fecha || !hora || !origen || !destino || !ubicacion || !daños || 
                !contacto || !descripcion || (tipo_carro === "Prestado" && !id_dueno) || !foto) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campos incompletos',
                    text: 'Por favor, completa todos los campos requeridos antes de enviar.',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            // Subir la imagen y registrar el siniestro
            enviaImg(function (rutaImagen) {
                var accion = "registroSiniestro";
                console.log("Ruta de la imagen:", rutaImagen); // Verificar la ruta de la imagen
                $.ajax({
                    type: "POST",
                    url: "acciones_siniestro",
                    data:{  id_vehiculo, fecha, hora, origen, destino, lugar, empresa, servicio, coordenadas,
                            kilometraje, gasolina, ubicacion, daños, contacto, descripcion, tipo_carro,
                            id_dueno, placa, rutaImagen, accion 
                        },
                    dataType: 'json',
                    success: function (respuesta) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: 'Siniestro registrado exitosamente.',
                            confirmButtonText: 'Aceptar'
                        });
                        $("#formRegistroSiniestro")[0].reset();
                        $("#btnCambiarVehiculo").hide();
                        $("#tablaInventario").closest(".container").show();
                    },
                    error: function () {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Hubo un problema al registrar el siniestro.',
                            confirmButtonText: 'Aceptar'
                        });
                    }
                });
            });
        }

        //FUNCION PARA MANEJAR CARPETAS Y FOTO
        //callback: hace que la función "RegistrarSiniestro" se ejecute después de enviar la imagen
        function enviaImg(callback) {
            var formData = new FormData();
            var foto = $("#foto")[0].files[0];
            var placa = $("#placaSeleccionada").text().replace("Vehículo seleccionado: ", "").trim(); // Obtener la placa seleccionada
        
            if (!placa) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Placa no seleccionada',
                    text: 'Por favor, selecciona un vehículo antes de continuar.',
                    confirmButtonText: 'Aceptar'
                });
                callback(null);
                return;
            }
        
            if (!foto) {
                console.log("No se seleccionó ninguna foto.");
                callback(null);
                return;
            }
        
            formData.append("foto", foto);
            formData.append("placa", placa);
            formData.append("accion", "manejarCarpetasYFoto");
        
            $.ajax({
                type: "POST",
                url: "acciones_siniestro",
                data: formData,
                processData: false, 
                contentType: false, 
                dataType: 'json',
                success: function (respuesta) {
                    if (respuesta.success) {
                        callback(respuesta.rutaImagen);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: respuesta.message,
                            confirmButtonText: 'Aceptar'
                        });
                        callback(null);
                    }
                },
                error: function () {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Hubo un problema al manejar la foto y las carpetas.',
                        confirmButtonText: 'Aceptar'
                    });
                    callback(null);
                }
            });
        }

        //FUNCION PARA MOSTRAR CAMPO DEL DUEÑO DEL VEHICULO
        function mostrarCampoDueno() {
            var tipo_carro = $("#tipo_carro").val();
            var campo_dueno = $("#campo_dueno");

            if (tipo_carro === "Prestado" || tipo_carro === "Asignado") {
                campo_dueno.show(); // Mostrar el campo
                $("#id_dueno").attr("required", true); // Hacer el campo obligatorio
            } else {
                campo_dueno.hide(); // Ocultar el campo
                $("#id_dueno").removeAttr("required"); // Quitar la obligatoriedad
            }
        }
    </script>
</body>
</html>