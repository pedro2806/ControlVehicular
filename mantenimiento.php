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

    <title>Control Vehicular</title>

    <!-- Custom fonts for this template-->
    <link href = "vendor/fontawesome-free/css/all.min.css" rel = "stylesheet" type = "text/css">
    <!-- Custom styles for this template-->
    <link href = "css/sb-admin-2.min.css" rel = "stylesheet">
    <!-- Bootstrap CSS -->
    <style>
        @keyframes pulso-vehiculo {
            0%   { box-shadow: 0 0 0 0 rgba(78, 115, 223, 0.7); border-color: #4e73df; }
            70%  { box-shadow: 0 0 3px 3px rgba(78, 115, 223, 0); border-color: #4e73df; }
            100% { box-shadow: 0 0 0 0 rgba(78, 115, 223, 0); border-color: #4e73df; }
        }
        #vehiculo_select.select-pulso {
            animation: pulso-vehiculo 1.4s ease-in-out infinite;
            border: 2px solid #4e73df;
            overflow: hidden;
        }
    </style>
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
                        <h1 class = "h3 mb-0 text-black-800">Registro de Mantenimiento</h1>                        
                    </div>
                    <!-- FORMULARIO DEL MANTENIMIENTO -->
                    <form id="formRegistroMantenimiento">
                        <input type="hidden" id="fecha" name="fecha">
                        <input type="hidden" id="hora" name="hora">
                        <input type="hidden" id="id_vehiculo" name="id_vehiculo">
                        <span id="placaVehiculo" style="display:none;"></span>

                        <!-- Detalles del Servicio -->
                        <h1 class="h5 mb-2 text-black" style="font-weight: bold;">Detalles del Servicio</h1>
                        <div class="row mb-4">
                            <div class="col-lg-3 col-md-3 col-sm-6 col-6">
                                <label>Vehículo:</label>
                                <select class="form-select select-pulso" id="vehiculo_select" name="vehiculo_select" required onchange="alSeleccionarVehiculo(this)">
                                    <option value="">Seleccione...</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-6 col-6">
                                <label>Tipo de Servicio:</label>
                                <select class="form-select" id="servicio" name="servicio" required>
                                    <option value="">Seleccione...</option>
                                    <option value="Preventivo">Preventivo</option>
                                    <option value="Correctivo">Correctivo</option>
                                    <option value="Verificacion">Verificación</option>
                                    <option value="Cambio de llantas">Cambio de llantas</option>
                                </select>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-12 col-12">
                                <label>Descripción:</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" required></textarea>
                            </div>
                        </div>

                        <!-- Info llantas (visible solo con Cambio de llantas) -->
                        <div id="info_llantas" class="alert alert-info py-2 mb-3" style="display:none;">
                            <strong><i class="fas fa-circle-info"></i> Último registro de llantas:</strong>
                            <span class="ms-3">Rin: <strong id="llantas_rin">—</strong></span>
                            <span class="ms-3">Medidas: <strong id="llantas_medidas">—</strong></span>
                        </div>

                        <!-- Detalles del Automovil -->
                        <h1 class="h5 mb-2 text-black" style="font-weight: bold;">Detalles del Automovil</h1>
                        <div class="row mb-4">
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Kilometraje:</label>
                                <input class="form-control" id="kilometraje" name="kilometraje" type="number" min="0">
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Gasolina:</label>
                                <select class="form-select" id="gasolina" name="gasolina">
                                    <option value="">Seleccione...</option>
                                    <option value="SD">Sin Datos</option>
                                    <option value="1/8">1/8</option>
                                    <option value="2/8">2/8</option>
                                    <option value="3/8">3/8</option>
                                    <option value="4/8">4/8</option>
                                    <option value="5/8">5/8</option>
                                    <option value="6/8">6/8</option>
                                    <option value="7/8">7/8</option>
                                    <option value="8/8">8/8</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Contacto:</label>
                                <input class="form-control" id="contacto" name="contacto" type="tel" required>
                            </div>
                        </div>

                        <!-- Foto del Automovil -->
                        <h1 class="h5 mb-2 text-black" style="font-weight: bold;">Foto del Automovil</h1>
                        <div class="row mb-4">
                            <div class="col-12">
                                <input
                                    type="file"
                                    class="form-control"
                                    id="foto"
                                    name="foto"
                                    accept="image/*"
                                    capture="environment"
                                    multiple
                                    required>
                            </div>
                        </div>

                        <center>
                            <button type="button" class="btn btn-outline-success" onclick="RegistrarMantenimiento()">Guardar</button>
                        </center>
                    </form>
                    <br>
                </div>
            </div>
            <footer class = "sticky-footer bg-white">
                <div class = "container my-auto">
                    <div class = "copyright text-center my-auto">
                        <span>Copyright &copy; MESS<?php echo date("Y"); ?></span>
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
    <script type="text/javascript">
        $(document).ready(function() {
            infoVehiculos();
            cargarUsuarios();
            const now = new Date();
            const fecha = now.toISOString().split('T')[0]; // Formato YYYY-MM-DD
            const hora = now.toTimeString().split(' ')[0].slice(0, 5); // Formato HH:MM
            $("#fecha").val(fecha);
            $("#hora").val(hora);
        });
        
        // FUNCION PARA CARGAR INFORMACIÓN DE LOS VEHÍCULOS
        function infoVehiculos() {
            $.ajax({
                type: "POST",
                url: "acciones_siniestro",
                data: { accion: "consultarInventario" },
                dataType: "json",
                success: function (respuesta) {
                    var select = $("#vehiculo_select");
                    respuesta.forEach(function (v) {
                        select.append(`<option value="${v.id_vehiculo}" data-placa="${v.placa}">${v.placa} - ${v.modelo} ${v.marca}</option>`);
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

        $("#servicio").on("change", function() {
            $("#info_llantas").hide();
            if ($(this).val() !== "Cambio de llantas") return;

            var id_vehiculo = $("#id_vehiculo").val();
            if (!id_vehiculo) {
                Swal.fire({ icon: "warning", title: "Vehículo requerido", text: "Selecciona primero el vehículo.", confirmButtonText: "Aceptar" });
                $(this).val("");
                return;
            }

            $.ajax({
                type: "POST",
                url: "acciones_mantenimiento",
                data: { accion: "infoLlantas", id_vehiculo: id_vehiculo },
                dataType: "json",
                success: function(r) {
                    function tieneValor(v) {
                        return v && v.trim() !== '' && v.trim().toUpperCase() !== 'S/R';
                    }
                    if (r.found && tieneValor(r.no_rin) && tieneValor(r.medidas)) {
                        $("#llantas_rin").text(r.no_rin);
                        $("#llantas_medidas").text(r.medidas);
                        $("#info_llantas").show();
                    } else {
                        Swal.fire({
                            icon: "warning",
                            title: "Sin datos de llantas",
                            text: "No se encontró información de rin y medidas para este vehículo. Por favor llena el checklist de llantas antes de registrar este mantenimiento.",
                            showCancelButton: true,
                            confirmButtonText: "Ir al Checklist",
                            cancelButtonText: "Cerrar",
                            confirmButtonColor: "#4e73df"
                        }).then(function(result) {
                            if (result.isConfirmed) {
                                window.location.href = "checkVehiculo";
                            }
                        });
                    }
                }
            });
        });

        function alSeleccionarVehiculo(sel) {
            var opt = sel.options[sel.selectedIndex];
            $("#id_vehiculo").val(opt.value);
            $("#placaVehiculo").text($(opt).data("placa") || "");

            // Limpiar campos
            $("#kilometraje").val("");
            $("#gasolina").val("");
            $("#contacto").val("");
            $("#info_llantas").hide();

            if (opt.value) {
                $("#vehiculo_select").removeClass("select-pulso");
                $.ajax({
                    type: "POST",
                    url: "acciones_mantenimiento",
                    data: { accion: "ultimoRegistroVehiculo", id_vehiculo: opt.value },
                    dataType: "json",
                    success: function(r) {
                        if (r.km_actual)  $("#kilometraje").val(r.km_actual);
                        if (r.gasolina)   $("#gasolina").val(r.gasolina);
                    }
                });
            } else {
                $("#vehiculo_select").addClass("select-pulso");
            }
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
        
       

        //FUNCION REGISTRO DE MANTENIMIENTO
        function RegistrarMantenimiento() {
            var id_vehiculo = $("#id_vehiculo").val();
            var fecha_registro = $("#fecha").val();
            var kilometraje = $("#kilometraje").val();
            var gasolina = $("#gasolina").val();
            var tipo_mantenimiento = $("#servicio").val();
            var descripcion = $("#descripcion").val();
            var solicitante = getCookie("id_usuario"); 
            var VoBo_jefe = "PENDIENTE"; 
            var fecha_proxi ='';// $("#prox_fecha").val();
            var km_proxi = '';//$("#prox_kilometraje").val();
            var tipo_carro = $("#tipo_carro").val();
            var id_dueno = $("#id_dueno").val();
            var placa = $("#placaVehiculo").text().replace("Vehículo seleccionado: ", "").trim();
            var accion = "RegistrarMantenimiento";
            var rutaImagen = $("#foto")[0].files[0];

            //Verificacion de campos faltantes
            var camposFaltantes = [];
            // Validar cada campo y agregar al array si está vacío
            if (!placa) camposFaltantes.push("Vehículo seleccionado");
            //if (!tipo_carro) camposFaltantes.push("Tipo de vehículo");
            //if (tipo_carro === "Prestado" && !id_dueno) camposFaltantes.push("Propietario");
            if (!descripcion) camposFaltantes.push("Descripción");
            //if (!fecha_proxi) camposFaltantes.push("Fecha del próximo servicio");
            //if (!km_proxi) camposFaltantes.push("Kilometraje del próximo servicio");
            if (!kilometraje) camposFaltantes.push("Kilometraje actual");
            if (!tipo_mantenimiento) camposFaltantes.push("Tipo de servicio");
            if (!contacto) camposFaltantes.push("Contacto");

            //VALIDACIONES DE CAMPOS
            if (camposFaltantes.length > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campos incompletos',
                    html: `<p>Por favor, completa los siguientes campos:</p><ul>${camposFaltantes.map(campo => `<li>${campo}</li>`).join('')}</ul>`,
                    confirmButtonText: 'Aceptar'
                });
                return;
            }
            
            // Validar que la fecha del próximo servicio sea mayor que la fecha del servicio actual
            if (new Date(fecha_proxi) <= new Date(fecha_registro)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Fecha inválida',
                    text: 'La fecha del próximo servicio debe ser posterior a la fecha del servicio actual.',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }
            // Si el tipo de carro es "Propio", usar el id_usuario en lugar de id_dueno
            if (tipo_carro === "Propio") {
                id_dueno = getCookie("id_usuario");
            }
            // Subir la imagen y registrar el mantenimiento
            enviaImg(function (rutaImagen) {
                //var accion = "manejarCarpetasYFoto";
                $.ajax({
                    type: "POST",
                    url: "acciones_mantenimiento",
                    data: { id_vehiculo, fecha_registro, kilometraje, gasolina, tipo_mantenimiento, descripcion, solicitante, 
                            tipo_carro, id_dueno, rutaImagen, accion},
                    dataType: 'json',
                    success: function (respuesta) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: 'Mantenimiento registrado exitosamente.',
                            confirmButtonText: 'Aceptar'
                        });
                        //$("#formRegistroMantenimiento")[0].reset();
                        $("#placaSeleccionada").hide();
                        $("#tablaInventario").closest(".container").show();
                        $.ajax({
                            type: "POST",
                            url: "correoMantenimiento.php",
                            data: { },
                            success: function () {
                                window.location.replace("seguimiento_mantenimiento");
                            },
                            error: function () {
                                window.location.replace("seguimiento_mantenimiento");
                            }
                        });
                        return;
                        //window.location.replace("seguimiento_mantenimiento");
                    },
                    error: function () {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Hubo un problema al registrar el mantenimiento.',
                            confirmButtonText: 'Aceptar'
                        });
                    }
                });
            });
        }

        //FUNCION PARA MANEJAR CARPETAS Y FOTO
        //callback: hace que la función "RegistraMantenimiento" se ejecute después de enviar la imagen
        function enviaImg(callback) {
            var formData = new FormData();
            var foto = $("#foto")[0].files[0];
            var placa = $("#placaVehiculo").text().replace("Vehículo seleccionado: ", "").trim(); // Obtener la placa seleccionada
            
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
                //console.log("No se seleccionó ninguna foto.");
                callback(null);
                return;
            }
        
            formData.append("foto", foto);
            formData.append("placa", placa);
            formData.append("accion", "manejarCarpetasYFoto");
        
            $.ajax({
                type: "POST",
                url: "acciones_mantenimiento",
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

        // Función para denegar un mantenimiento
        function denegarMantenimiento(id_mantenimiento) {
            // Guardar el ID del mantenimiento en un atributo del modal
            $("#modalMantenimiento").data("id_mantenimiento", id_mantenimiento);
            $("#modalMantenimiento").data("accion", "denegarMantenimiento");
            $("#modalMantenimientoLabel").text("Denegar Mantenimiento");
            $("#btnGuardarModal").text("Guardar");

            // Deshabilitar el campo de fecha y asignar la fecha actual
            const now = new Date();
            const fechaActual = now.toISOString().split("T")[0]; // Formato YYYY-MM-DD
            $("#fecha_programada").val(fechaActual).prop("disabled", true); // Asignar fecha y deshabilitar el campo

            // Ocultar el campo de fecha de registro
            $("#fecha_programada").closest(".form-group").hide();
            $("#modalMantenimiento").modal("show");
        }
    </script>
</body>
</html>