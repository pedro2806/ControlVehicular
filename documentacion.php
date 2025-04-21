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
                        <h1 class = "h3 mb-0 text-black-800">Registro de Documentos</h1>                        
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

                    <!-- FORMULARIO DEL DOCUMENTACION    -->
                    <form id="formRegistroDocumentacion">
                        <!-- Content Row -->
                        <div class = "row">
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Fecha Registro:</label>
                                <input type = "date" class = "form-control" id = "fecha" name = "fecha" required>
                            </div>
                            <br>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Contacto:</label>  
                                <input class="form-control" id="contacto" name="contacto" type="tel" required>
                            </div>
                            <br>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Fecha Prox. Revisión:</label>
                                <input type="date" class="form-control" id="prox_fecha" name="prox_fecha" required>
                            </div>
                        </div>
                        <br>
                        <!-- Content Row -->
                        <!--<h1 class="h5 mb-0 text-black" style="font-weight: bold;">Detalles del Servicio</h1>-->
                        <div class="row">
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Tarjeta de Circulación:</label>
                                <select class="form-select" id="circulacion" name="circulacion" onchange="mostrarCampoArchivo('circulacion', 'archivoCirculacion')" required>
                                    <option value="">Seleccione...</option>
                                    <option value="SI">Si</option>
                                    <option value="NO">No</option>
                                </select>
                            </div>
                        </div>
                        <br>
                        <div class="row" id="archivoCirculacion" style="display: none;">
                            <div class="col-lg-10">
                                <label>Subir Tarjeta de Circulación:</label>
                                <input type="file" class="form-control" id="archivoCirculacionInput" name="archivoCirculacion" accept="image/png, image/jpeg">
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Refrendo:</label>
                                <select class="form-select" id="refrendo" name="refrendo" onchange="mostrarCampoArchivo('refrendo', 'archivoRefrendo')" required>
                                    <option value="">Seleccione...</option>
                                    <option value="SI">Si</option>
                                    <option value="NO">No</option>
                                </select>
                            </div>
                        </div>
                        <br>
                        <div class="row" id="archivoRefrendo" style="display: none;">
                            <div class="col-lg-10">
                                <label>Subir Refrendo:</label>
                                <input type="file" class="form-control" id="archivoRefrendoInput" name="archivoRefrendo" accept="image/png, image/jpeg">
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Poliza de Seguro:</label>
                                <select class="form-select" id="poliza" name="poliza" onchange="mostrarCampoArchivo('poliza', 'archivoPoliza')" required>
                                    <option value="">Seleccione...</option>
                                    <option value="SI">Si</option>
                                    <option value="NO">No</option>
                                </select>
                            </div>
                        </div>
                        <br>
                        <div class="row" id="archivoPoliza" style="display: none;">
                            <div class="col-lg-10">
                                <label>Subir Poliza de Seguro:</label>
                                <input type="file" class="form-control" id="archivoPolizaInput" name="archivoPoliza" accept="image/png, image/jpeg">
                            </div>
                        </div> 
                        <br>
                        <div class="row">
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Verificacion Vigente:</label>
                                <select class="form-select" id="verificacion" name="verificacion" onchange="mostrarCampoArchivo('verificacion', 'archivoVerificacion')" required>
                                    <option value="">Seleccione...</option>
                                    <option value="SI">Si</option>
                                    <option value="NO">No</option>
                                </select>
                            </div>
                        </div>
                        <br>
                        <div class="row" id="archivoVerificacion" style="display: none;">
                            <div class="col-lg-10">
                                <label>Subir Verificacion Vigente:</label>
                                <input type="file" class="form-control" id="archivoVerificacionInput" name="archivoVerificacion" accept="image/png, image/jpeg">
                            </div>
                        </div> 
                        <br>
                        <input type="hidden" id = "id_vehiculo" name = "id_vehiculo">
                        <center>
                            <button type="button" class="btn btn-outline-success" onclick="RegistrarDocumentos()">Guardar</button>
                        </center>
                    </form>
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
                destroy: true, 
                paging: false, 
                ordering: false,
                searching: false,
                info: false,
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

            var fecha = new Date().toISOString().split('T')[0];
            $("#fecha").val(fecha); 
        });
        
        //FUNCION REGISTRO DE LA DOCUMENTACION
        function RegistrarDocumentos() {
            var fecha_registro = $("#fecha").val();
            var contacto = $("#contacto").val();
            var fecha_prox = $("#prox_fecha").val();
            var id_vehiculo = $("#id_vehiculo").val();
            var tarjeta_circulacion = $("#circulacion").val();
            var refrendo_actual = $("#refrendo").val();
            var seguro_auto = $("#poliza").val();
            var verificacion_vigente = $("#verificacion").val();
            var archivoCirculacion = $("#archivoCirculacionInput")[0].files[0];
            var archivoRefrendo = $("#archivoRefrendoInput")[0].files[0];
            var archivoPoliza = $("#archivoPolizaInput")[0].files[0];
            var archivoVerificacion = $("#archivoVerificacionInput")[0].files[0];
            var accion = "RegistrarDocumentos";

            // Validar campos obligatorios generales
            if (!id_vehiculo || !contacto || !fecha_prox || !fecha_registro) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Algun campo no seleccionado',
                    text: 'Por favor, completa el formulario antes de continuar.',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            // Validar campos condicionales
            if (tarjeta_circulacion === "SI" && !archivoCirculacion) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Archivo faltante',
                    text: 'Por favor, sube la Tarjeta de Circulación.',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            if (refrendo_actual === "SI" && !archivoRefrendo) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Archivo faltante',
                    text: 'Por favor, sube el archivo de Refrendo.',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            if (seguro_auto === "SI" && !archivoPoliza) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Archivo faltante',
                    text: 'Por favor, sube el archivo de la Póliza de Seguro.',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            if (verificacion_vigente === "SI" && !archivoVerificacion) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Archivo faltante',
                    text: 'Por favor, sube el archivo de Verificación Vigente.',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            // Si todas las validaciones pasan, continuar con el flujo
            enviaImg(function (respuesta) {
                if (!respuesta || !respuesta.success) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Hubo un problema al manejar los documentos.',
                        confirmButtonText: 'Aceptar'
                    });
                    return;
                }

                $.ajax({
                    type: "POST",
                    url: "acciones_documentacion",
                    data: {
                        id_vehiculo: id_vehiculo,
                        fecha_registro: fecha_registro,
                        contacto: contacto,
                        fecha_prox: fecha_prox,
                        tarjeta_circulacion: tarjeta_circulacion,
                        refrendo_actual: refrendo_actual,
                        seguro_auto: seguro_auto,
                        verificacion_vigente: verificacion_vigente,
                        ruta_documento: respuesta.rutaDocumentacion,
                        accion: "RegistrarDocumentos"
                    },
                    success: function (respuesta) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: 'Documentación registrada exitosamente.',
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            $("#formRegistroDocumentacion")[0].reset();
                            $("#archivoCirculacion").hide();
                            $("#archivoRefrendo").hide();
                            $("#archivoPoliza").hide();
                            $("#archivoVerificacion").hide();
                            cambiarVehiculo();
                            infoVehiculos();
                        });
                    },
                    error: function (xhr, status, error) {
                        //console.error("Error en la solicitud AJAX:", error); 
                        //console.error("Detalles del error:", xhr.responseText); 
                        //console.error("Estado:", status); 
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Hubo un problema al registrar la documentación.',
                            confirmButtonText: 'Aceptar'
                        });
                    }
                });
            });
        }

        //FUNCION PARA MANEJAR CARPETAS Y FOTO
        function enviaImg(callback) {
            var formData = new FormData();
            var placa = $("#placaSeleccionada").text().replace("Vehículo seleccionado: ", "").trim(); 
            var archivoCirculacion = $("#archivoCirculacionInput")[0].files[0];
            var archivoRefrendo = $("#archivoRefrendoInput")[0].files[0];
            var archivoPoliza = $("#archivoPolizaInput")[0].files[0];
            var archivoVerificacion = $("#archivoVerificacionInput")[0].files[0]; 

            // Validar que la placa esté seleccionada
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

            // Agregar los archivos al FormData si existen
            if (archivoCirculacion) {
                formData.append("archivoCirculacion", archivoCirculacion);
            }
            if (archivoRefrendo) {
                formData.append("archivoRefrendo", archivoRefrendo);
            }
            if (archivoPoliza) {
                formData.append("archivoPoliza", archivoPoliza);
            }
            if (archivoVerificacion) {
                formData.append("archivoVerificacion", archivoVerificacion);
            }

            // Agregar la placa y la acción al FormData
            formData.append("placa", placa);
            formData.append("accion", "manejarDocumentos");

            // Enviar los datos al servidor
            $.ajax({
                type: "POST",
                url: "acciones_documentacion",
                data: formData,
                processData: false, 
                contentType: false, 
                dataType: 'json',
                success: function (respuesta) {
                    //console.log("Respuesta de manejarDocumentos:", respuesta); 
                    if (respuesta.success) {
                        callback(respuesta); // Callback con la respuesta completa
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
                        text: 'Hubo un problema al manejar los documentos.',
                        confirmButtonText: 'Aceptar'
                    });
                    callback(null);
                }
            });
        }

        //FUNCION PARA MOSTRAR CAMPO DE ARCHIVO
        function mostrarCampoArchivo(selectId, campoId) {
            var valor = $("#" + selectId).val();
            var campo = $("#" + campoId);

            if (valor === "SI") {
                campo.show(); // Mostrar el campo
                $("#" + campoId + "Input").attr("required", true); 
            } else {
                campo.hide(); // Ocultar el campo
                $("#" + campoId + "Input").removeAttr("required"); 
            }
        }

        //FUNCION PARA CARGAR INFORMACIÓN DE LOS VEHÍCULOS
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

        //FUNCION PARA MANEJAR EL BOTÓN "CHECK"
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
        
        //FUNCION PARA CAMBIAR DE VEHÍCULO
        function cambiarVehiculo() {
            // Ocultar el contenedor de la placa seleccionada
            $("#placaSeleccionada").hide();

            // Ocultar el botón para cambiar de vehículo
            $("#btnCambiarVehiculo").hide();

            // Mostrar la tabla de inventario
            $("#tablaInventario").closest(".container").show();
        }
    </script>
</body>
</html>