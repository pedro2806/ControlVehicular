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
                        <h1 class = "h3 mb-0 text-black-800">Registro de Mantenimiento</h1>                        
                    </div>
                    <!-- TABLA DE VEHICULOS -->
                    <div class="container">
                        <h3 class="h5 mb-0 text-black" style="font-weight: bold;">Inventario de Vehículos</h3>
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

                    <!-- FORMULARIO DEL MANTENIMIENTO -->
                    <form id="formRegistroMantenimiento">
                        <!-- Content Row -->
                        <div class = "row">
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Fecha Servicio:</label>
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
                                <select class="form-select" id="tipo_carro" name="tipo_carro" onchange="mostrarCampoDueno()" required>
                                    <option value="">Seleccione...</option>
                                    <option value="Propio">Propio</option>
                                    <option value="Prestado">Prestado</option>
                                </select>       
                            </div>
                            <br>
                            <br>

                            <!-- Campo adicional para el nombre del dueño -->
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6" id="campo_dueno" style="display: none;">
                                <label>Nombre del Propietario:</label>
                                <input type="text" class="form-control" id="nombre_dueno" name="nombre_dueno">
                            </div>
                        </div>
                        <br>
                        <!-- Content Row -->
                        <h1 class="h5 mb-0 text-black" style="font-weight: bold;">Detalles del Servicio</h1>
                        <div class="row">
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Tipo de Servicio:</label>
                                <select class = "form-select" id = "servicio" name = "servicio" required>
                                    <option value = "">Seleccione...</option>
                                    <option value = "Preventivo">Preventivo</option>
                                    <option value = "Correctivo">Correctivo</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Solicitante:</label>  
                                <input class = "form-control" id = "solicita" name = "solicita" required>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Descripción:</label>
                                <textarea class = "form-control" id = "descripcion" name = "descripcion" required></textarea>
                            </div>
                            <br>
                        </div>
                        <!-- Content Row -->
                        <div class = "row">
                            <h1 class="h5 mb-0 text-black" style="font-weight: bold;">Detalles del Automovil</h1> 
                            <br>
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
                                <label>Contacto:</label>  
                                <input class = "form-control" id = "contacto" name = "contacto" type="tel" required>
                            </div>
                            <br>
                            <br>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Fecha Prox. Servicio:</label>
                                <input type = "date" class = "form-control" id = "prox_fecha" name = "prox_fecha" required>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Kilometraje Prox. Servicio:</label>  
                                <input class = "form-control" id = "prox_kilometraje" name = "prox_kilometraje" type="number" min="0">
                            </div>
                            <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                                <label>Foto del Automovil:</label>
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
                        <br>
                        <input type="hidden" id = "id_coche" name = "id_coche">
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
            $("#tablaInventario").DataTable({
                destroy: true, // Permitir reinicializar la tabla
                paging: false, // Quitar paginado
                ordering: false, // Quitar orden
                searching: false, // Quitar buscador
                info: false, // Quitar leyendas a pie de tabla
                language: {
                    decimal: ",",
                    thousands: ".",
                    processing: "Procesando...",
                    loadingRecords: "Cargando...",
                    zeroRecords: "No se encontraron resultados",
                    emptyTable: "No hay datos disponibles en la tabla"
                },
                createdRow: function(row, data, dataIndex) {
                    $(row).css('font-size', '12px'); // Reducir tamaño del texto
                }
            });

            // Llenar automáticamente los campos de fecha y hora
            const now = new Date();
            const fecha = now.toISOString().split('T')[0]; // Formato YYYY-MM-DD
            const hora = now.toTimeString().split(' ')[0].slice(0, 5); // Formato HH:MM

            $("#fecha").val(fecha); // Establecer la fecha actual
            $("#hora").val(hora); // Establecer la hora actual
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
                                        <button class="btn btn-outline-success btn-sm" onclick="seleccionarVehiculo('${vehiculo.id_coche}', '${vehiculo.placa}')">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </center>
                                </td>
                            </tr>`;
                        tabla.append(fila);
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
        function seleccionarVehiculo(id_coche, placa) {
            // Actualizar el contenido del contenedor con la placa seleccionada
            $("#placaSeleccionada")
                .text(`Vehículo seleccionado: ${placa}`)
                .show();
            $("#id_coche").val(id_coche);
            
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

        //FUNCION REGISTRO DE MANTENIMIENTO
        function RegistrarMantenimiento() {
            var id_coche = $("#id_coche").val();
            var fecha_registro = $("#fecha").val();
            var kilometraje = $("#kilometraje").val();
            var gasolina = $("#gasolina").val();
            var tipo_mantenimiento = $("#servicio").val();
            var descripcion = $("#descripcion").val();
            var solicitante = $("#solicita").val();
            var VoBo_jefe = "PENDIENTE"; // Valor por defecto
            var fecha_proxi = $("#prox_fecha").val();
            var km_proxi = $("#prox_kilometraje").val();
            var tipo_carro = $("#tipo_carro").val();
            var nombre_dueno = $("#nombre_dueno").val();
            var placa = $("#placaSeleccionada").text().replace("Vehículo seleccionado: ", "").trim();
            var accion = "RegistrarMantenimiento";
            var rutaImagen = $("#foto")[0].files[0];

            //Verificacion de campos faltantes
            var camposFaltantes = [];
            // Validar cada campo y agregar al array si está vacío
            if (!placa) camposFaltantes.push("Vehículo seleccionado");
            if (!fecha_registro) camposFaltantes.push("Fecha del servicio");
            if (!tipo_carro) camposFaltantes.push("Tipo de vehículo");
            if (tipo_carro === "Prestado" && !$("#nombre_dueno").val()) camposFaltantes.push("Nombre del propietario");
            if (!solicitante) camposFaltantes.push("Solicitante");
            if (!descripcion) camposFaltantes.push("Descripción");
            if (!fecha_proxi) camposFaltantes.push("Fecha del próximo servicio");
            if (!km_proxi) camposFaltantes.push("Kilometraje del próximo servicio");
            if (!kilometraje) camposFaltantes.push("Kilometraje actual");
            if (!tipo_mantenimiento) camposFaltantes.push("Tipo de servicio");
            if (!contacto) camposFaltantes.push("Contacto");
            
            // Si hay campos faltantes, mostrar alerta
            if (camposFaltantes.length > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campos incompletos',
                    html: `<p>Por favor, completa los siguientes campos:</p><ul>${camposFaltantes.map(campo => `<li>${campo}</li>`).join('')}</ul>`,
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            // Validar que el kilometraje del próximo servicio sea mayor que el kilometraje actual
            if (parseFloat(km_proxi) <= parseFloat(kilometraje)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Kilometraje inválido',
                    text: 'El kilometraje del próximo servicio debe ser mayor que el kilometraje actual.',
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

            // Subir la imagen y registrar el mantenimiento
            enviaImg(function (rutaImagen) {
                //var accion = "manejarCarpetasYFoto";
                //console.log("Ruta de la imagen:", rutaImagen); // Verificar la ruta de la imagen
                $.ajax({
                    type: "POST",
                    url: "acciones_mantenimiento",
                    data: { id_coche, fecha_registro, kilometraje, gasolina, tipo_mantenimiento, descripcion, solicitante, 
                            fecha_proxi, km_proxi, tipo_carro, nombre_dueno, rutaImagen, accion},
                    dataType: 'json',
                    success: function (respuesta) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: 'Mantenimiento registrado exitosamente.',
                            confirmButtonText: 'Aceptar'
                        });
                        $("#formRegistroMantenimiento")[0].reset();
                        $("#placaSeleccionada").hide();
                        $("#tablaInventario").closest(".container").show();
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

            if (tipo_carro === "Prestado") {
                campo_dueno.show(); // Mostrar el campo
                $("#nombre_dueno").attr("required", true); // Hacer el campo obligatorio
            } else {
                campo_dueno.hide(); // Ocultar el campo
                $("#nombre_dueno").removeAttr("required"); // Quitar la obligatoriedad
            }
        }
    </script>
</body>
</html>