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
                        <table id="tablaInventario" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Placa</th>
                                    <th>Mod/Marca</th>
                                    <th>Color</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Las filas se cargarán dinámicamente -->
                            </tbody>
                        </table>
                    </div>

                    <!-- CONTENEDOR INFO AUTO -->
                    <div id="placaSeleccionada" class="alert alert-info" style="display: none;"></div> 
                    <button id="btnCambiarVehiculo" class="btn btn-outline-primary btn-sm" style="display: none;" onclick="cambiarVehiculo()">Cambiar Vehículo</button>
                    <!-- FORMULARIO DEL SINIESTRO -->
                    <form id="formRegistroSiniestro" style="display: none;">
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
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6" style="display: none;">
                                <label>Tipo de Vehiculo:</label> 
                                <select class="form-select" id="tipo_carro" name="tipo_carro" required onchange="mostrarCampoDueno()">
                                    <option value="S/R">Seleccione...</option>
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
                                <input type="text" class="form-control" id="descripcion" name="descripcion" required>
                            </div>
                            <div id="contenedorFotos">
                                <label>Fotos del Siniestro:</label>
                                <div class="input-group mb-3">
                                    <input 
                                        type="file" 
                                        class="form-control foto-siniestro" 
                                        name="foto[]" 
                                        accept="image/*" 
                                        capture="environment" 
                                        required>
                                </div>
                            </div>
                        </div>                        
                        <button type="button" class="btn btn-outline-info btn-sm" id="btnAgregarFoto" onclick="agregarCampoFoto()">
                            <i class="fas fa-plus"></i> Agregar Foto
                        </button>
                        <input type="hidden" id = "coordenadas" name = "coordenadas">
                        <input type="hidden" id = "id_vehiculo" name = "id_vehiculo">
                        <center>
                            <button type="button" class="btn btn-outline-success" onclick="RegistrarSiniestro()">Guardar</button>
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
            const now = new Date();
            const fecha = now.toISOString().split('T')[0];
            const hora = now.toTimeString().split(' ')[0].slice(0, 5); 
            $("#fecha").val(fecha);
            $("#hora").val(hora); 
            // Obtener coordenadas del usuario
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        const lat = position.coords.latitude.toFixed(6); 
                        const lon = position.coords.longitude.toFixed(6); 
                        $("#coordenadas").val(`${lat}, ${lon}`); // Establecer las coordenadas en el campo
                    }
                );
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
                                <td><strong><i class="fas fa-car"></i> ${vehiculo.placa}</strong></td>
                                <td><strong>${vehiculo.modelo} ${vehiculo.marca}</strong></td>
                                <td><strong>${vehiculo.color}</strong></td>
                                <td>
                                    <center>
                                        <button class="btn btn-outline-success btn-sm" onclick="seleccionarVehiculo('${vehiculo.id_vehiculo}', '${vehiculo.placa}', '${vehiculo.modelo}', '${vehiculo.marca}', '${vehiculo.color}')">
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
        function seleccionarVehiculo(id_vehiculo, placa, modelo, marca, color) {
            $("#placaSeleccionada")
                    .html(`
                        <div style="display: flex; justify-content: space-between; font-weight: bold;">
                            <span><strong>Placa:</strong> <span id="placaVehiculo" style="font-weight: normal;">${placa}</span></span>
                            <span><strong>Modelo:</strong> <span style="font-weight: normal;">${modelo}</span></span>
                            <span><strong>Marca:</strong> <span style="font-weight: normal;">${marca}</span></span>
                            <span><strong>Color:</strong> <span style="font-weight: normal;">${color}</span></span>
                        </div>
                    `)
                    .show();
            $("#id_vehiculo").val(id_vehiculo);
            $("#btnCambiarVehiculo").show();
            $("#tablaInventario").closest(".container").hide();
            $("#formRegistroSiniestro").show();
        }

        // FUNCION PARA CAMBIAR DE VEHICULO
        function cambiarVehiculo() {
            $("#placaSeleccionada").hide();
            $("#btnCambiarVehiculo").hide();
            $("#tablaInventario").closest(".container").show();
            $("#formRegistroSiniestro").hide();
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
            var placa = $("#placaVehiculo").text().replace("Vehículo seleccionado: ", "").trim();
            var id_vehiculo = $("#id_vehiculo").val();
            var accion = "registroSiniestro";

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
                !contacto || !descripcion || (tipo_carro === "Prestado" && !id_dueno)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campos incompletos',
                    text: 'Por favor, completa todos los campos requeridos antes de enviar.',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            var formData = new FormData();
            formData.append("id_vehiculo", id_vehiculo);
            formData.append("fecha", fecha);
            formData.append("hora", hora);
            formData.append("origen", origen);
            formData.append("destino", destino);
            formData.append("lugar", lugar);
            formData.append("empresa", empresa);
            formData.append("servicio", servicio);
            formData.append("coordenadas", coordenadas);
            formData.append("kilometraje", kilometraje);
            formData.append("gasolina", gasolina);
            formData.append("ubicacion", ubicacion);
            formData.append("daños", daños);
            formData.append("contacto", contacto);
            formData.append("descripcion", descripcion);
            formData.append("tipo_carro", tipo_carro);
            formData.append("id_dueno", id_dueno);
            formData.append("placa", placa);
            formData.append("accion", 'registroSiniestro');

            // Adjuntar todos los archivos de imagen seleccionados (incluyendo los inputs adicionales)
            var archivos = document.querySelectorAll('input[name="foto[]"]');
            archivos.forEach(function(input) {
                if (input.files.length > 0) {
                    for (var i = 0; i < input.files.length; i++) {
                        formData.append('foto[]', input.files[i]);
                    }
                }
            });

            $.ajax({
                type: "POST",
                url: "acciones_siniestro",
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (respuesta) {
                    if (respuesta.success) {
                        // Subir imágenes con el id_formato devuelto
                        //subirImagenes(id_vehiculo, placa, respuesta.id_formato);
                        Swal.fire({
                            icon: 'success',
                            title: 'Siniestro registrado exitosamente.',
                            text: 'Esperemos se encuentre fuera de peligro.',
                            confirmButtonText: 'Aceptar'
                        });
                        location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: respuesta.message,
                            confirmButtonText: 'Aceptar'
                        });
                    }
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
            
        }

        //FUNCION PARA SUBIR IMAGENES
        function subirImagenes(id_vehiculo, placa, id_formato) {
            var formData = new FormData();
            var fotos = document.querySelectorAll('.foto-siniestro');
            formData.append("id_vehiculo", id_vehiculo);
            formData.append("placa", placa);
            formData.append("id_formato", id_formato);
            formData.append("accion", "subirImagenes");
            
            fotos.forEach(function(fotoInput) {
                var foto = fotoInput.files[0];
                if (foto) {
                    formData.append("fotos[]", foto);
                }
            });
            $.ajax({
                type: "POST",
                url: "acciones_siniestro",
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (respuesta) {
                    if (respuesta.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: 'Imágenes subidas exitosamente.',
                            confirmButtonText: 'Aceptar'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: respuesta.message,
                            confirmButtonText: 'Aceptar'
                        });
                    }
                },
                error: function () {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Hubo un problema al subir las imágenes.',
                        confirmButtonText: 'Aceptar'
                    });
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
        
        //Validar el número máximo de imágenes (4)
        $("#foto").on("change", function () {
            if (this.files.length > 4) {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Solo puedes subir un máximo de 4 imágenes.",
                    confirmButtonText: "Aceptar"
                });
                this.value = ""; // Limpia el campo de archivos
            }
        });

        //FUNCION PARA AGREGAR CAMPO DE FOTO
        function agregarCampoFoto() {
            // Contar cuántos campos de fotos existen actualmente
            var totalFotos = document.querySelectorAll(".foto-siniestro").length;

            // Validar que no se agreguen más de 4 campos en total
            if (totalFotos >= 4) {
                Swal.fire({
                    icon: "error",
                    title: "Límite alcanzado",
                    text: "Solo puedes agregar un máximo de 4 imágenes.",
                    confirmButtonText: "Aceptar"
                });
                return;
            }

            // Crear un nuevo grupo de input y botón eliminar
            var nuevoCampo = `
                <div class="input-group mb-3">
                    <input 
                        type="file" 
                        class="form-control foto-siniestro" 
                        name="foto[]" 
                        accept="image/*" 
                        capture="environment" 
                        required>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="eliminarCampo(this)">Eliminar</button>
                </div>`;
            
            // Agregar el nuevo campo al contenedor 
            $("#contenedorFotos").append(nuevoCampo);
        }

        //FUNCION PARA ELIMINAR CAMPO DE FOTO
        function eliminarCampo(boton) {
            $(boton).closest(".input-group").remove();
        }

        // Validar el número máximo de imágenes dinámicas (4)
        $("#foto").on("change", ".foto-siniestro", function () {
            var totalFotos = document.querySelectorAll(".foto-siniestro").length;

            if (totalFotos > 4) {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Asegurate que cada campo tenga una imagen.",
                    confirmButtonText: "Aceptar"
                });
                this.value = ""; // Limpia el campo de archivos
            }
        });
    </script>
</body>
</html>