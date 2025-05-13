<?php
session_start();
include 'conn.php';
if ($_COOKIE['noEmpleado'] == '' || $_COOKIE['noEmpleado'] == null) {
    echo '<script>window.location.assign("index")</script>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Control Vehicular</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    
</head>
<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <?php
        include 'menu.php';
        ?>
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <?php
                include 'encabezado.php';
                ?>
                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Control Vehicular MESS</h1>
                    </div>
                    <!-- Content Row -->
                    <div class="row">
                        <!-- Tabla de Vehiculos -->
                        <div class="tab-pane fade show active" id="pendientes">
                            <div class="table-responsive">
                                <table id="tablaVerDoc" class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Placa</th>    
                                            <th>Modelo</th>
                                            <th>Marca</th>
                                            <th>Año</th>
                                            <th>Usuario</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Las filas se cargarán dinámicamente -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- Modal Documentacion-->
                    <div class="modal fade" id="modalDetallesVehiculo" tabindex="-1" aria-labelledby="modalDetallesVehiculoLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modalDetallesVehiculoLabel">Detalles del Vehículo</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <!-- Lista de Documentos -->
                                        <div class="col-md-4">
                                            <ul class="list-group">
                                                <li class="list-group-item list-group-item-action" data-tipo="licencia">
                                                    Licencia de Conducir
                                                </li>
                                                <li class="list-group-item list-group-item-action" data-tipo="tarjeta">
                                                    Tarjeta de Circulación
                                                </li>
                                                <li class="list-group-item list-group-item-action" data-tipo="refrendo">
                                                    Refrendo
                                                </li>
                                                <li class="list-group-item list-group-item-action" data-tipo="seguro">
                                                    Seguro
                                                </li>
                                                <li class="list-group-item list-group-item-action" data-tipo="verificacion">
                                                    Verificación
                                                </li>
                                            </ul>
                                        </div>

                                        <!-- Área de Previsualización -->
                                        <div class="col-md-8 text-center">
                                            <img id="imagenDocumento" src="img/MESS_07_CuboMess_1.png" class="img-fluid border" alt="Previsualización del documento" style="max-height: 300px;">
                                        </div>
                                    </div>
                                    <hr>
                                    <!-- Información del Vehículo -->
                                    <p id="textoVehiculo" class="mt-3"></p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                </div>
                            </div>
                        </div>  
                    </div>

                    <!-- Modal Mantenimiento -->
                    <div class="modal fade" id="modalMantenimiento" tabindex="-1" aria-labelledby="modalMantenimientoLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modalMantenimientoLabel">Detalle de Mantenimiento</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <!-- Columna Izquierda: Imagen -->
                                        <div class="col-md-6 text-center">
                                            <img id="imagenMantenimiento" src="img/MESS_07_CuboMess_1.png" class="img-fluid border" alt="Imagen del mantenimiento" style="max-height: 300px; cursor: pointer;" onclick="ampliarImagen()">
                                        </div>

                                        <!-- Columna Derecha: Detalles -->
                                        <div class="col-md-6">
                                            <h5><strong>Detalles del Servicio:</strong></h5>
                                            <p><strong>Fecha:</strong> <span id="fechaMantenimiento"></span></p>
                                            <p><strong>Tipo:</strong> <span id="tipoMantenimiento"></span></p>
                                            <p><strong>Descripción:</strong> <span id="descripcionMantenimiento"></span></p>
                                            <p><strong>Costo:</strong> <span id="costoMantenimiento"></span></p>
                                            <hr>
                                            <h5><strong>Vehículo:</strong></h5>
                                            <p><strong>Placa:</strong> <span id="placaVehiculo"></span></p>
                                            <p><strong>Modelo:</strong> <span id="modeloVehiculo"></span></p>
                                            <p><strong>Marca:</strong> <span id="marcaVehiculo"></span></p>
                                            <p><strong>Año:</strong> <span id="anioVehiculo"></span></p>
                                            <p><strong>Usuario:</strong> <span id="usuarioVehiculo"></span></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Siniestro -->
                    <div class="modal fade" id="modalSiniestro" tabindex="-1" aria-labelledby="modalSiniestroLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modalSiniestroLabel">Detalles del Siniestro</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <!-- Encabezado del Vehículo -->
                                    <div class="mb-3">
                                        <h6>Vehículo: <span id="vehiculoSiniestro"></span></h6>
                                        <p><strong>Placa:</strong> <span id="placaVehiculo"></span></p>
                                        <p><strong>Modelo:</strong> <span id="modeloVehiculo"></span></p>
                                        <p><strong>Marca:</strong> <span id="marcaVehiculo"></span></p>
                                        <p><strong>Año:</strong> <span id="anioVehiculo"></span></p>
                                        <p><strong>Usuario:</strong> <span id="usuarioVehiculo"></span></p>
                                    </div>
                                    <!-- Pestañas -->
                                    <ul class="nav nav-tabs" id="siniestroTabs" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab" aria-controls="info" aria-selected="true">Información</button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="imagenes-tab" data-bs-toggle="tab" data-bs-target="#imagenes" type="button" role="tab" aria-controls="imagenes" aria-selected="false">Imágenes</button>
                                        </li>
                                    </ul>
                                    <!-- Contenido de las Pestañas -->
                                    <div class="tab-content mt-3" id="siniestroTabsContent">
                                        <!-- Pestaña Información -->
                                        <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
                                            <p><strong>Fecha y Hora:</strong> <span id="fechaHoraSiniestro"></span></p>
                                            <p><strong>Ubicación:</strong> <span id="ubicacionSiniestro"></span></p>
                                            <p><strong>Daños:</strong> <span id="partesSiniestro"></span></p>
                                            <p><strong>Monto Estimado:</strong> <span id="montoSiniestro"></span></p>
                                            <p><strong>Descripción:</strong></p>
                                            <p id="descripcionSiniestro"></p>
                                        </div>
                                        <!-- Pestaña Imágenes -->
                                        <div class="tab-pane fade" id="imagenes" role="tabpanel" aria-labelledby="imagenes-tab">
                                            <div id="carouselSiniestro" class="carousel slide" data-bs-ride="carousel">
                                                <div class="carousel-inner" id="imagenesCarrusel">
                                                    <!-- Las imágenes se cargarán dinámicamente -->
                                                </div>
                                                <button class="carousel-control-prev" type="button" data-bs-target="#carouselSiniestro" data-bs-slide="prev">
                                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                    <span class="visually-hidden">Anterior</span>
                                                </button>
                                                <button class="carousel-control-next" type="button" data-bs-target="#carouselSiniestro" data-bs-slide="next">
                                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                                    <span class="visually-hidden">Siguiente</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>
    <!-- DataTables JavaScript -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    
    <script type="text/javascript">
        $(document).ready(function() {
            // Inicializar DataTable
            $('#tablaVerDoc').DataTable({
                destroy: true,
                paging: true,
                pageLength: 5,
                ordering: true,
                searching: true,
                info: true,
                "language": {
                    "lengthMenu": "Mostrar _MENU_ registros por página",
                    "zeroRecords": "No se encontraron resultados",
                    "info": "Mostrando página _PAGE_ de _PAGES_",
                    "infoEmpty": "No hay registros disponibles",
                    "infoFiltered": "(filtrado de _MAX_ registros totales)",
                    "search": "Buscar:",
                    "paginate": {
                        "first":      "Primero",
                        "last":       "Último",
                        "next":       "Siguiente",
                        "previous":   "Anterior"
                    }
                }
            });
            cargarVehiculos();
        });

        // Cargar Vehículos
        function cargarVehiculos() {
            $.ajax({
                type: "POST",
                url: 'acciones_ver_doc',
                type: 'POST',
                data: {accion: 'ver_inventario'},
                dataType: "json",
                success: function(respuesta) {
                    var tablaVehiculos = $("#tablaVerDoc tbody");
                    tablaVehiculos.empty(); 

                    respuesta.forEach(function (vehiculo) {
                        var botones = `
                            <button class="btn btn-outline-success" onclick="documentacionVehiculo('${vehiculo.id_vehiculo}')">
                                <i class="fas fa-file-alt"></i>
                            </button>
                            <button class="btn btn-outline-primary" onclick="mantenimientoVehiculo('${vehiculo.id_vehiculo}')">
                                <i class="fas fa-wrench"></i>
                            </button>
                            <button class="btn btn-outline-warning" onclick="siniestroVehiculo('${vehiculo.id_vehiculo}')">
                                <i class="fas fa-exclamation-triangle"></i>
                            </button>
                            `;
                        
                        var fila = `
                            <tr>
                                <td>${vehiculo.placa}</td>
                                <td>${vehiculo.modelo}</td>
                                <td>${vehiculo.marca}</td>
                                <td>${vehiculo.anio}</td>
                                <td>${vehiculo.usuario}</td>
                                <td>${botones}</td>
                            </tr>`;
                        tablaVehiculos.append(fila);
                    });
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "No se pudo cargar la información de los vehículos.",
                        confirmButtonText: "Aceptar"
                    });
                }
            });
        }

        // Función para mostrar la documentación del vehículo
        function documentacionVehiculo(id_vehiculo) {
            $.ajax({
                type: "POST",
                url: 'acciones_ver_doc',
                data: {accion: 'documentosVehiculo', id_vehiculo: id_vehiculo},
                dataType: "json",
                success: function(respuesta) {
                    if (respuesta.error) {
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: respuesta.error,
                            confirmButtonText: "Aceptar"
                        });
                        return;
                    }

                    // Actualiza la información del vehículo with style
                    $("#textoVehiculo").html(`
                        <p><strong class="text-primary">Placa:</strong> ${respuesta.placa}</p>
                        <p><strong class="text-primary">Modelo:</strong> ${respuesta.modelo}</p>
                        <p><strong class="text-primary">Marca:</strong> ${respuesta.marca}</p>
                        <p><strong class="text-primary">Año:</strong> ${respuesta.anio}</p>
                        <p><strong class="text-primary">Usuario:</strong> ${respuesta.usuario}</p>
                    `);

                    // Actualiza los eventos de la lista de documentos
                    $(".list-group-item-action").off("click"); // Elimina eventos previos
                    $(".list-group-item-action").on("click", function() {
                        const tipoDocumento = $(this).attr("data-tipo");
                        mostrarImagenDoc(tipoDocumento, respuesta);
                    });

                    // Muestra el modal
                    const modal = new bootstrap.Modal(document.getElementById("modalDetallesVehiculo"));
                    modal.show();
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "No se pudo cargar la información del vehículo.",
                        confirmButtonText: "Aceptar"
                    });
                }
            });
        }

        // Función para mostrar la imagen del documento seleccionado
        function mostrarImagenDoc(tipoDocumento, datosVehiculo) {
            // Obtén la ruta de la imagen correspondiente al tipo de documento
            const rutasDocumentos = {
                licencia: datosVehiculo.licencia,
                tarjeta: datosVehiculo.tarjeta_circulacion,
                refrendo: datosVehiculo.refrendo_actual,
                seguro: datosVehiculo.seguro_vehiculo,
                verificacion: datosVehiculo.verificacion_vigente
            };

            // Cambia la imagen en el área de previsualización
            const rutaImagen = rutasDocumentos[tipoDocumento] || "img/MESS_07_CuboMess_1.png";
            $("#imagenDocumento").attr("src", rutaImagen);
        }

        // Función para mostrar la documentación de mantenimiento del vehículo
        function mantenimientoVehiculo(id_vehiculo) {
            $.ajax({
                type: "POST",
                url: 'acciones_ver_doc',
                data: {accion: 'mantenimientoVehiculo', id_vehiculo: id_vehiculo},
                dataType: "json",
                success: function(respuesta) {
                    if (respuesta.error) {
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: respuesta.error,
                            confirmButtonText: "Aceptar"
                        });
                        return;
                    }
                    // Construye la ruta completa de la imagen
                    const rutaImagen = respuesta.foto || "img/MESS_07_CuboMess_1.png";

                    // Actualiza los datos del mantenimiento
                    $("#imagenMantenimiento").attr("src", rutaImagen);
                    $("#fechaMantenimiento").text(respuesta.fecha_registro || "N/A");
                    $("#tipoMantenimiento").text(respuesta.tipo_mantenimiento || "N/A");
                    $("#descripcionMantenimiento").text(respuesta.descripcion || "N/A");
                    $("#costoMantenimiento").text(respuesta.costo || "$0.00");

                    // Actualiza los datos del vehículo
                    $("#placaVehiculo").text(respuesta.placa || "N/A");
                    $("#modeloVehiculo").text(respuesta.modelo || "N/A");
                    $("#marcaVehiculo").text(respuesta.marca || "N/A");
                    $("#anioVehiculo").text(respuesta.anio || "N/A");
                    $("#usuarioVehiculo").text(respuesta.usuario || "N/A");

                    // Muestra el modal
                    const modal = new bootstrap.Modal(document.getElementById("modalMantenimiento"));
                    modal.show();
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "No se pudo cargar la información del mantenimiento.",
                        confirmButtonText: "Aceptar"
                    });
                }
            });
        }

        // Función para mostrar la imagen del Mantenimiento seleccionado
        function mostrarImagenMant(tipoDocumento, datosVehiculo) {
            // Obtén la ruta de la imagen correspondiente al tipo de documento
            const rutasDocumentos = {
                foto: datosVehiculo.foto
            };

            // Cambia la imagen en el área de previsualización
            const rutaImagen = rutasDocumentos[tipoDocumento] || "img/MESS_07_CuboMess_1.png";
            $("#imagenDocumento").attr("src", rutaImagen);
        }

        // Función para ampliar la imagen del mantenimiento
        function ampliarImagen() {
            const rutaImagen = $("#imagenMantenimiento").attr("src");
            Swal.fire({
                imageUrl: rutaImagen,
                imageAlt: "Imagen del mantenimiento",
                showCloseButton: true,
                showConfirmButton: false
            });
        }

        // Función para mostrar la documentación de siniestros del vehículo
        function siniestroVehiculo(id_vehiculo) {
            $.ajax({
                type: "POST",
                url: 'acciones_ver_doc',
                data: {accion: 'siniestrosVehiculo', id_vehiculo: id_vehiculo},
                dataType: "json",
                success: function(respuesta) {
                    if (respuesta.error) {
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: respuesta.error,
                            confirmButtonText: "Aceptar"
                        });
                        return;
                    }

                    // Verifica si la respuesta contiene datos
                    if (respuesta.length > 0) {
                        const siniestro = respuesta[0]; // Obtén el primer registro

                        // Actualiza la información del modal
                        $("#fechaHoraSiniestro").text(`${siniestro.fecha || "N/A"} ${siniestro.hora || ""}`);

                        $("#ubicacionSiniestro").text(siniestro.lugar || "N/A");
                        $("#partesSiniestro").text(siniestro.partes_dañadas || "N/A");
                        $("#montoSiniestro").text(siniestro.monto || "$0.00");
                        $("#descripcionSiniestro").text(siniestro.descripcion || "Sin descripción.");

                        // Actualiza los datos del vehículo
                        $("#placaVehiculo").text(respuesta.placa || "N/A");
                        $("#modeloVehiculo").text(respuesta.modelo || "N/A");
                        $("#marcaVehiculo").text(respuesta.marca || "N/A");
                        $("#anioVehiculo").text(respuesta.anio || "N/A");
                        $("#usuarioVehiculo").text(respuesta.usuario || "N/A");

                        // Cargar imágenes en el carrusel
                        const imagenesCarrusel = $("#imagenesCarrusel");
                        imagenesCarrusel.empty();
                        respuesta.forEach(function (siniestro) {    
                            if (siniestro.imagen) {
                                imagenesCarrusel.append(`
                                    <div class="carousel-item active">
                                        <img src="${siniestro.imagen}" class="d-block w-100" alt="Imagen del siniestro" style="max-height: 400px; object-fit: contain;">
                                    </div>
                                `);
                            } else {
                                imagenesCarrusel.append(`
                                    <div class="carousel-item">
                                        <img src="img/MESS_07_CuboMess_1.png" class="d-block w-100" alt="Sin imágenes disponibles" style="max-height: 400px; object-fit: contain;">
                                    </div>
                                `);
                            }
                        });
                        // Mostrar el modal
                        const modal = new bootstrap.Modal(document.getElementById("modalSiniestro"));
                        modal.show();
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: "No se encontró información del siniestro.",
                            confirmButtonText: "Aceptar"
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "No se pudo cargar la información del siniestro.",
                        confirmButtonText: "Aceptar"
                    });
                }
            });
        }
    </script>
</body>
</html>