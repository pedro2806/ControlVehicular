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
    <!-- Custom styles for this template-->
    <!-- MESS Design System -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="css/mess-ds.css" rel="stylesheet">
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
                    <div class="card shadow-sm mb-3" id="placaSeleccionada" style="display:none;">
                        <div class="card-body py-3 px-3">
                            <div class="d-flex align-items-center">
                                <div style="width:60px;height:60px;border-radius:8px;background:var(--card-soft);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="fas fa-car fa-2x text-muted"></i>
                                </div>
                                <div class="ml-3 flex-grow-1">
                                    <h5 class="mb-0 font-weight-bold text-primary" id="infoPlacaDoc"></h5>
                                    <span class="text-dark" id="infoModeloMarcaDoc"></span><br>
                                    <small class="text-muted" id="infoColorDoc"></small>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="cambiarVehiculo()">
                                        <i class="fas fa-exchange-alt me-1"></i> Cambiar Vehículo
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
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
                            <div class="col-lg-4 col-md-6 col-sm-6 col-6">
                                <label>
                                    Fecha Próxima Verificación: <span class="text-danger">*</span>
                                    <button type="button" class="btn btn-link p-0 ms-1" data-bs-toggle="modal" data-bs-target="#modalCalendarioVerificacion" title="Ver calendario de verificación">
                                        <i class="fas fa-info-circle text-secondary"></i>
                                    </button>
                                </label>
                                <input type="date" class="form-control" id="prox_fecha" name="prox_fecha" min="<?php echo date('Y-m-d'); ?>" required>
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
                        <div class="text-center">
                            <button type="button" id="btnGuardarDocumentos" class="btn btn-primary" onclick="RegistrarDocumentos()" style="display:none;">Guardar</button>
                        </div>
                    </form>

                    <!-- Modal Calendario de Verificación -->
                    <div class="modal fade" id="modalCalendarioVerificacion" tabindex="-1" role="dialog" aria-labelledby="modalCalendarioVerificacionLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header" style="background-color: #4a3728;">
                                    <h5 class="modal-title text-white font-weight-bold" id="modalCalendarioVerificacionLabel">
                                        <i class="fas fa-calendar-alt me-2"></i> Calendario de Verificación
                                    </h5>
                                    <button type="button" class="close text-white" data-bs-dismiss="modal" aria-label="Cerrar">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body p-0">
                                    <table class="table table-bordered mb-0 text-center">
                                        <thead>
                                            <tr style="background-color: #4a3728; color: white;">
                                                <th>Engomado</th>
                                                <th>Período</th>
                                                <th>Fecha límite</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td><span class="badge font-weight-bold px-3 py-2" style="background-color:#f5c518; color:#333; font-size:1rem;">5 o 6</span></td>
                                                <td>Enero – Febrero</td>
                                                <td class="font-weight-bold">28 de febrero</td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge font-weight-bold px-3 py-2" style="background-color:#e06c7a; color:#fff; font-size:1rem;">7 u 8</span></td>
                                                <td>Febrero – Marzo</td>
                                                <td class="font-weight-bold">31 de marzo</td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge font-weight-bold px-3 py-2" style="background-color:#4caf50; color:#fff; font-size:1rem;">3 o 4</span></td>
                                                <td>Marzo – Abril</td>
                                                <td class="font-weight-bold">30 de abril</td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge font-weight-bold px-3 py-2" style="background-color:#e67e22; color:#fff; font-size:1rem;">1 o 2</span></td>
                                                <td>Abril – Mayo</td>
                                                <td class="font-weight-bold">30 de mayo</td>
                                            </tr>
                                            <tr>
                                                <td><span class="badge font-weight-bold px-3 py-2" style="background-color:#6c3483; color:#fff; font-size:1rem;">9 o 0</span></td>
                                                <td>Mayo – Junio</td>
                                                <td class="font-weight-bold">30 de junio</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <p class="text-muted small px-3 pt-2 mb-2">
                                        <i class="fas fa-info-circle"></i> El engomado corresponde al último dígito de la placa del vehículo.
                                    </p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <footer class = "sticky-footer bg-white">
                <div class = "container my-auto">
                    <div class = "copyright text-center my-auto">
                        <span>Copyright &copy; MESS <?php echo date("Y"); ?></span>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <a class = "scroll-to-top rounded" href = "#page-top">
        <i class = "fas fa-angle-up"></i>
    </a>
    <!-- Bootstrap core JavaScript-->
    <!-- Core plugin JavaScript-->
    <!-- Custom scripts for all pages-->
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

            // Listeners para validación de completitud del formulario
            $('#contacto').on('input', verificarCompletitud);
            $('#prox_fecha').on('change', verificarCompletitud);
            $('#archivoLicenciaInput, #archivoCirculacionInput, #archivoRefrendoInput, #archivoPolizaInput, #archivoVerificacionInput').on('change', verificarCompletitud);
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
                campo.show();
                $("#" + campoId + "Input").attr("required", true);
            } else {
                campo.hide();
                $("#" + campoId + "Input").val('').removeAttr("required");
            }
            verificarCompletitud();
        }

        //FUNCION PARA VALIDAR QUE HAYA AL MENOS UN DOC CON ARCHIVO ANTES DE MOSTRAR GUARDAR
        function verificarCompletitud() {
            var proxFecha = $('#prox_fecha').val();

            var pares = [
                { check: 'licencia',     file: 'archivoLicenciaInput' },
                { check: 'circulacion',  file: 'archivoCirculacionInput' },
                { check: 'refrendo',     file: 'archivoRefrendoInput' },
                { check: 'poliza',       file: 'archivoPolizaInput' },
                { check: 'verificacion', file: 'archivoVerificacionInput' }
            ];

            var algunoMarcado = false;
            var todosConArchivo = true;

            pares.forEach(function(par) {
                if ($('#' + par.check).is(':checked')) {
                    algunoMarcado = true;
                    if ($('#' + par.file)[0].files.length === 0) {
                        todosConArchivo = false;
                    }
                }
            });

            var valido = proxFecha !== '' && algunoMarcado && todosConArchivo;
            $('#btnGuardarDocumentos').toggle(valido);
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
                    Array.isArray(respuesta) && respuesta.forEach(function (vehiculo) {
                        var fila = [
                            `<strong><i class="fas fa-car"></i> ${vehiculo.placa}</strong>`,
                            `<strong>${vehiculo.modelo}</strong>`,
                            `<strong>${vehiculo.marca}</strong>`,
                            `<strong>${vehiculo.color}</strong>`,
                            `<div class="text-center">
                                <button class="btn btn-outline-success btn-sm" onclick="seleccionarVehiculo('${vehiculo.id_vehiculo}', '${vehiculo.placa}', '${vehiculo.modelo}', '${vehiculo.marca}', '${vehiculo.color}')">
                                    <i class="fas fa-check"></i>
                                </button>
                            </div>`
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
            $("#infoPlacaDoc").text(placa);
            $("#infoModeloMarcaDoc").text(modelo + ' ' + marca);
            $("#infoColorDoc").text(color);
            $("#placaSeleccionada").show();
            $("#id_vehiculo").val(id_vehiculo);
            $("#placa").val(placa);
            $("#tablaInventario").closest(".container").hide();
            $("#formRegistroDocumentacion").show();
        }

        //FUNCION PARA CAMBIAR DE VEHÍCULO
        function cambiarVehiculo() {
            $("#placaSeleccionada").hide();
            $("#tablaInventario").closest(".container").show();
            $("#formRegistroDocumentacion").hide();
        }

        // Auto-seleccionar vehículo si viene ?v={id_vehiculo} desde QR
        (function () {
            var params = new URLSearchParams(window.location.search);
            var idV = params.get('v');
            if (!idV) return;
            $.ajax({
                url: 'acciones_qr.php',
                method: 'POST',
                dataType: 'json',
                data: { accion: 'obtenerDatosVehiculo', id_vehiculo: idV },
                success: function (v) {
                    if (v && v.id_vehiculo) {
                        seleccionarVehiculo(v.id_vehiculo, v.placa, v.modelo, v.marca, v.color || '');
                    }
                }
            });
        })();

        //FUNCION PARA OBTENER LA COOKIE
    </script>
</body>
</html>