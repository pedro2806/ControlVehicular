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
                        <h1 class = "h3 mb-0 text-black-800">Registro de Documentos</h1>                        
                    </div>
                    <!-- TABLA DE VEHICULOS -->
                    <div class="container">
                        <table id="tablaInventario" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Placa</th>
                                    <th>Modelo</th>
                                    <th>Marca</th>
                                    <th>Color</th>
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
                    <br>
                    <!-- FORMULARIO DEL DOCUMENTACION    -->
                    <form id="formRegistroDocumentacion" style="display: none;">
                        <!-- Content Row -->
                        <div class = "row">
                                <input type = "hidden" class = "form-control" id = "fecha" name = "fecha" readonly>
                            <div class="col-lg-4 col-md-6 col-sm-6 col-6">
                                <label>Contacto:</label>  
                                <input class="form-control" id="contacto" name="contacto" type="tel" required>
                            </div>
                            <br>
                            <div class="col-lg-4 col-md-6 col-sm-6 col-6" style="display: none">
                                <label>Fecha Prox. Revisión:</label>
                                <input type="date" class="form-control" id="prox_fecha" name="prox_fecha" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-lg-6 col-md-12">
                                <div class="d-flex align-items-center">
                                    <div class="form-check form-switch me-3">
                                        <input class="form-check-input" type="checkbox" id="licencia" name="licencia" onchange="mostrarCampoArchivoCheckbox('licencia', 'archivoLicencia')">
                                        <label class="form-check-label" for="licencia">Licencia para Conducir</label>
                                    </div>
                                    <div id="archivoLicencia" style="display: none;">
                                        <input type="file" class="form-control" id="archivoLicenciaInput" name="archivoLicencia" accept="image/png, image/jpeg, application/pdf">
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="d-flex align-items-center">
                                    <div class="form-check form-switch me-3">
                                        <input class="form-check-input" type="checkbox" id="circulacion" name="circulacion" onchange="mostrarCampoArchivoCheckbox('circulacion', 'archivoCirculacion')">
                                        <label class="form-check-label" for="circulacion">Tarjeta de Circulación</label>
                                    </div>
                                    <div id="archivoCirculacion" style="display: none;">
                                        <input type="file" class="form-control" id="archivoCirculacionInput" name="archivoCirculacion" accept="image/png, image/jpeg, application/pdf">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-lg-6 col-md-12">
                                <div class="d-flex align-items-center">
                                    <div class="form-check form-switch me-3">
                                        <input class="form-check-input" type="checkbox" id="refrendo" name="refrendo" onchange="mostrarCampoArchivoCheckbox('refrendo', 'archivoRefrendo')">
                                        <label class="form-check-label" for="refrendo">Refrendo</label>
                                    </div>
                                    <div id="archivoRefrendo" style="display: none;">
                                        <input type="file" class="form-control" id="archivoRefrendoInput" name="archivoRefrendo" accept="image/png, image/jpeg, application/pdf">
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="d-flex align-items-center">
                                    <div class="form-check form-switch me-3">
                                        <input class="form-check-input" type="checkbox" id="poliza" name="poliza" onchange="mostrarCampoArchivoCheckbox('poliza', 'archivoPoliza')">
                                        <label class="form-check-label" for="poliza">Poliza de Seguro</label>
                                    </div>
                                    <div id="archivoPoliza" style="display: none;">
                                        <input type="file" class="form-control" id="archivoPolizaInput" name="archivoPoliza" accept="image/png, image/jpeg, application/pdf">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-lg-6 col-md-12">
                                <div class="d-flex align-items-center">
                                    <div class="form-check form-switch me-3">
                                        <input class="form-check-input" type="checkbox" id="verificacion" name="verificacion" onchange="mostrarCampoArchivoCheckbox('verificacion', 'archivoVerificacion')">
                                        <label class="form-check-label" for="verificacion">Verificación Vigente</label>
                                    </div>
                                    <div id="archivoVerificacion" style="display: none;">
                                        <input type="file" class="form-control" id="archivoVerificacionInput" name="archivoVerificacion" accept="image/png, image/jpeg, application/pdf">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <br>
                        <input type="hidden" id="placa" name="placa">
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
            var fecha = new Date().toISOString().split('T')[0];
            var hora = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            $("#fecha").val(fecha); 
            $("#hora").val(hora);
            // Inicializar DataTables para la tabla de inventario.
            $("#tablaInventario").DataTable({
                destroy: true, 
                paging: true, 
                pageLength: 10, 
                ordering: true, 
                searching: true, 
                info: true, 
                language: {
                    decimal: ",",
                    thousands: ".",
                    processing: "Procesando...",
                    loadingRecords: "Cargando...",
                    zeroRecords: "No se encontraron resultados",
                    emptyTable: "No hay datos disponibles en la tabla",
                    info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    infoEmpty: "Mostrando 0 a 0 de 0 registros",
                    infoFiltered: "(filtrado de _MAX_ registros totales)",
                    search: "Buscar:",
                    paginate: {
                        first: "Primero",
                        last: "Último",
                        next: "Siguiente",
                        previous: "Anterior"
                    },
                    lengthMenu: "Mostrar _MENU_ registros",
                    aria: {
                        sortAscending: ": activar para ordenar la columna de manera ascendente",
                        sortDescending: ": activar para ordenar la columna de manera descendente"
                    }
                }
            });
        });
        
        //FUNCION REGISTRO DE LA DOCUMENTACION
        function RegistrarDocumentos() {
            var formData = new FormData();
            var id_vehiculo = $("#id_vehiculo").val();
            var fecha_registro = $("#fecha").val();
            let id_usuario = getCookie("id_usuario");
            var contacto = $("#contacto").val();
            var fecha_prox = $("#prox_fecha").val();
            var placa = $("#placa").val();

            // Validar campos obligatorios generales
            if (!placa || !contacto || !fecha_prox || !fecha_registro) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campos incompletos',
                    text: 'Por favor, completa todos los campos obligatorios antes de continuar.',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            // Validar y agregar imágenes al FormData
            if ($("#circulacion").is(":checked")) {
                var archivoCirculacion = $("#archivoCirculacionInput")[0].files[0];
                if (!archivoCirculacion) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Archivo faltante',
                        text: 'Por favor, sube la Tarjeta de Circulación.',
                        confirmButtonText: 'Aceptar'
                    });
                    return;
                }
                formData.append("archivoCirculacion", archivoCirculacion);
            }
            
            if ($("#licencia").is(":checked")) {
                var archivoLicencia = $("#archivoLicenciaInput")[0].files[0];
                if (!archivoLicencia) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Archivo faltante',
                        text: 'Por favor, sube la Licencia para Conducir.',
                        confirmButtonText: 'Aceptar'
                    });
                    return;
                }
                formData.append("archivoLicencia", archivoLicencia);
            }

            if ($("#refrendo").is(":checked")) {
                var archivoRefrendo = $("#archivoRefrendoInput")[0].files[0];
                if (!archivoRefrendo) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Archivo faltante',
                        text: 'Por favor, sube el archivo de Refrendo.',
                        confirmButtonText: 'Aceptar'
                    });
                    return;
                }
                formData.append("archivoRefrendo", archivoRefrendo);
            }

            if ($("#poliza").is(":checked")) {
                var archivoPoliza = $("#archivoPolizaInput")[0].files[0];
                if (!archivoPoliza) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Archivo faltante',
                        text: 'Por favor, sube el archivo de la Póliza de Seguro.',
                        confirmButtonText: 'Aceptar'
                    });
                    return;
                }
                formData.append("archivoPoliza", archivoPoliza);
            }

            if ($("#verificacion").is(":checked")) {
                var archivoVerificacion = $("#archivoVerificacionInput")[0].files[0];
                if (!archivoVerificacion) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Archivo faltante',
                        text: 'Por favor, sube el archivo de Verificación Vigente.',
                        confirmButtonText: 'Aceptar'
                    });
                    return;
                }
                formData.append("archivoVerificacion", archivoVerificacion);
            }

            // Agregar otros datos al FormData
            formData.append("id_vehiculo", id_vehiculo);
            formData.append("fecha_registro", fecha_registro);
            formData.append("id_usuario", id_usuario);
            formData.append("contacto", contacto);
            formData.append("fecha_prox", fecha_prox);
            formData.append("placa", placa);
            formData.append("accion", "RegistrarDocumentos");

            $.ajax({
                type: "POST",
                url: "acciones_documentacion.php",
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (respuesta) {
                    //console.log("Respuesta del servidor:", respuesta); // Depuración
                    if (respuesta.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: 'Documentación registrada exitosamente.',
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            $("#formRegistroDocumentacion")[0].reset();
                            $("#archivoCirculacion").hide();
                            $("#archivoLicencia").hide();
                            $("#archivoRefrendo").hide();
                            $("#archivoPoliza").hide();
                            $("#archivoVerificacion").hide();
                            cambiarVehiculo();
                            infoVehiculos();
                            window.location.href = "seguimiento_documentacion";
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
                error: function (xhr, status, error) {
                    console.error("Error en la solicitud:", error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Hubo un problema al registrar la documentación.',
                        confirmButtonText: 'Aceptar'
                    });
                }
            });
        }

        //FUNCION PARA MANEJAR CARPETAS Y FOTO
        function enviaImg(callback) {
            var formData = new FormData();
            var placa = $("#placaSeleccionada").text().replace("Vehículo seleccionado: ", "").trim(); 
            var archivoCirculacion = $("#archivoCirculacionInput")[0].files[0];
            var archivoLicencia = $("#archivoLicenciaInput")[0].files[0];
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
            if (archivoLicencia) {
                formData.append("archivoLicencia", archivoLicencia);
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
                    //console.log("Respuesta de manejarDocumentos:", respuesta); // Depuración
                    if (respuesta.success) {
                        callback(respuesta.rutasArchivos);
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
        function mostrarCampoArchivoCheckbox(checkboxId, campoId) {
            var isChecked = $("#" + checkboxId).is(":checked");
            var campo = $("#" + campoId);

            if (isChecked) {
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
                    var tabla = $("#tablaInventario").DataTable(); 
                    tabla.clear(); 
                    respuesta.forEach(function (vehiculo) {
                        var fila = [
                            `<strong><i class="fas fa-car"></i> ${vehiculo.placa}</strong>`,
                            `<strong>${vehiculo.modelo}</strong>`,
                            `<strong>${vehiculo.marca}</strong>`,
                            `<strong>${vehiculo.color}</strong>`,
                            `<center>
                                <button class="btn btn-outline-success btn-sm" onclick="seleccionarVehiculo('${vehiculo.id_vehiculo}', '${vehiculo.placa}', '${vehiculo.modelo}', '${vehiculo.marca}', '${vehiculo.color}')">
                                    <i class="fas fa-check"></i>
                                </button>
                            </center>`
                        ];
                        tabla.row.add(fila); 
                    });

                    tabla.draw(); 
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
        function seleccionarVehiculo(id_vehiculo, placa, modelo, marca, color) {
            $("#placaSeleccionada")
                .html(`
                    <div style="display: flex; justify-content: space-between; font-weight: bold;">
                        <span><strong>Placa:</strong> <span style="font-weight: normal;">${placa}</span></span>
                        <span><strong>Modelo:</strong> <span style="font-weight: normal;">${modelo}</span></span>
                        <span><strong>Marca:</strong> <span style="font-weight: normal;">${marca}</span></span>
                        <span><strong>Color:</strong> <span style="font-weight: normal;">${color}</span></span>
                    </div>
                `)
                .show();
            $("#id_vehiculo").val(id_vehiculo);
            $("#placa").val(placa);
            $("#btnCambiarVehiculo").show();
            $("#tablaInventario").closest(".container").hide();
            $("#formRegistroDocumentacion").show();
        }
        
        //FUNCION PARA CAMBIAR DE VEHÍCULO
        function cambiarVehiculo() {
            $("#placaSeleccionada").hide();
            $("#btnCambiarVehiculo").hide();
            $("#tablaInventario").closest(".container").show();
            $("#formRegistroDocumentacion").hide();
        }

        //FUNCION PARA OBTENER LA COOKIE
        function getCookie(name) {
            let cookieArr = document.cookie.split(";"); 
            for (let i = 0; i < cookieArr.length; i++) {
                let cookie = cookieArr[i].trim(); 
                if (cookie.indexOf(name + "=") === 0) {
                    return cookie.substring(name.length + 1); 
                }
            }
            return null;
        }
    </script>
</body>
</html>