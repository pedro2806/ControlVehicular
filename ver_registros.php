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
                                    <!-- Información del Vehículo -->
                                    <p id="infoVehiculoDoc" class="mt-3"></p>
                                    <p id="infoDoc" class="mt-3"></p>
                                    <hr>
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
                                    <!-- Información del Vehículo -->
                                    <p id="infoVehiculoMant" class="mt-3"></p>
                                    <hr>
                                    <!-- Pestañas -->
                                    <ul class="nav nav-tabs" id="mantenimientoTabs" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#infoMantenimiento" type="button" role="tab" aria-controls="infoMantenimiento" aria-selected="true">Información</button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="imagenes-tab" data-bs-toggle="tab" data-bs-target="#imagenesMantenimiento" type="button" role="tab" aria-controls="imagenesMantenimiento" aria-selected="false">Imágenes</button>
                                        </li>
                                    </ul>
                                    <!-- Contenido de las Pestañas -->
                                    <div class="tab-content mt-3" id="mantenimientoTabsContent">
                                        <!-- Pestaña Información -->
                                        <div class="tab-pane fade show active" id="infoMantenimiento" role="tabpanel" aria-labelledby="info-tab">
                                            <p><strong>Fecha:</strong> <span id="fechaMantenimiento"></span></p>
                                            <p><strong>Tipo:</strong> <span id="tipoMantenimiento"></span></p>
                                            <p><strong>Descripción:</strong></p>
                                            <p id="descripcionMantenimiento"></p>
                                            <p><strong>Costo:</strong> <span id="costoMantenimiento"></span></p>
                                        </div>
                                        <!-- Pestaña Imágenes -->
                                        <div class="tab-pane fade" id="imagenesMantenimiento" role="tabpanel" aria-labelledby="imagenes-tab">
                                            <div id="carouselMantenimiento" class="carousel slide" data-bs-ride="carousel">
                                                <div class="carousel-inner" id="imagenesCarruselMantenimiento">
                                                    <!-- Las imágenes se cargarán dinámicamente -->
                                                </div>
                                                <button class="carousel-control-prev" type="button" data-bs-target="#carouselMantenimiento" data-bs-slide="prev">
                                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                    <span class="visually-hidden">Anterior</span>
                                                </button>
                                                <button class="carousel-control-next" type="button" data-bs-target="#carouselMantenimiento" data-bs-slide="next">
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

                    <!-- Modal Siniestro -->
                    <div class="modal fade" id="modalSiniestro" tabindex="-1" aria-labelledby="modalSiniestroLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modalSiniestroLabel">Detalles del Siniestro</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <!-- Información del Vehículo -->
                                    <p id="infoVehiculoSiniestro" class="mt-3"></p>
                                    <hr>
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
                                                <div class="carousel-inner" id="imagenesCarruselSiniestro">
                                                    <!-- Las imágenes se cargarán dinámicamente -->
                                                </div>
                                                <button class="carousel-control-prev" type="button" data-bs-target="#carouselSiniestro" data-bs-slide="prev" style="background-color: gray;">
                                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                    <span class="visually-hidden">Anterior</span>
                                                </button>
                                                <button class="carousel-control-next" type="button" data-bs-target="#carouselSiniestro" data-bs-slide="next" style="background-color: gray;">
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
    <!-- Popper.js -->
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    
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
                url: 'acciones_ver_registros',
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
                                <i class="fas fa-tools"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="siniestroVehiculo('${vehiculo.id_vehiculo}')">
                                <i class="fas fa-car-crash"></i>
                            </button>
                            `;
                        
                        var fila = `
                            <tr>
                             <td><strong><i class="fas fa-car"></i> ${vehiculo.placa}</strong></td>
                                <td><strong>${vehiculo.modelo}</strong></td>
                                <td><strong>${vehiculo.marca}</strong></td>
                                <td><strong>${vehiculo.anio}</strong></td>
                                <td><strong>${vehiculo.usuario}</strong></td>
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
                url: 'acciones_ver_registros',
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
                    $("#infoVehiculoDoc")
                    .html(`
                        <div style="display: flex; justify-content: space-between; font-weight: bold;">
                            <span><strong>Placa:</strong> <span style="font-weight: normal;">${respuesta.placa}</span></span>
                            <span><strong>Modelo:</strong> <span style="font-weight: normal;">${respuesta.modelo}</span></span>
                            <span><strong>Marca:</strong> <span style="font-weight: normal;">${respuesta.marca}</span></span>
                            <span><strong>Color:</strong> <span style="font-weight: normal;">${respuesta.color}</span></span>
                        </div>
                    `)
                    
                    //Información de los documentos
                    $("#infoDoc")
                    .html(`
                        <div style="display: flex; justify-content: space-between; font-weight: bold;">
                            <span><strong>Fecha de Registro:</strong> <span style="font-weight: normal;">${respuesta.fecha_registro || 'No disponible'}</span></span>
                            <span><strong>Contacto:</strong> <span style="font-weight: normal;">${respuesta.contacto || 'No disponible'}</span></span>
                            <span><strong>Próximo Refrendo:</strong> <span style="font-weight: normal;">${respuesta.fecha_prox || 'No disponible'}</span></span>
                        </div>
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
                url: 'acciones_ver_registros',
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

                    // Actualiza la información del vehículo with style
                    $("#infoVehiculoMant")
                    .html(`
                        <div style="display: flex; justify-content: space-between; font-weight: bold;">
                            <span><strong>Placa:</strong> <span style="font-weight: normal;">${respuesta.placa}</span></span>
                            <span><strong>Modelo:</strong> <span style="font-weight: normal;">${respuesta.modelo}</span></span>
                            <span><strong>Marca:</strong> <span style="font-weight: normal;">${respuesta.marca}</span></span>
                            <span><strong>Color:</strong> <span style="font-weight: normal;">${respuesta.color}</span></span>
                        </div>
                    `)

                    // Actualiza los datos del mantenimiento
                    $("#fechaMantenimiento").text(respuesta.fecha_registro || "N/A");
                    $("#tipoMantenimiento").text(respuesta.tipo_mantenimiento || "N/A");
                    $("#descripcionMantenimiento").text(respuesta.descripcion || "N/A");
                    $("#costoMantenimiento").text(respuesta.costo || "$0.00");

                    // Muestra el modal
                    const modal = new bootstrap.Modal(document.getElementById("modalMantenimiento"));
                    mostrarImagenMant(respuesta.foto);
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
        function mostrarImagenMant(foto) {
            const imagenesCarrusel = $("#imagenesCarruselMantenimiento");
            imagenesCarrusel.empty(); // Limpia cualquier imagen previa

            if (foto) {
                const imagenHTML = `
                    <div class="carousel-item active">
                        <img src="${foto}" class="d-block w-100 img-fluid border" alt="Imagen de Mantenimiento" style="max-height: 300px; object-fit: contain;">
                    </div>
                `;
                imagenesCarrusel.append(imagenHTML);

                // Inicializa el carrusel si no lo está
                const carouselMantenimiento = new bootstrap.Carousel(document.getElementById('carouselMantenimiento'));
            } else {
                // Si no hay foto, muestra un mensaje o una imagen por defecto
                imagenesCarrusel.html('<p class="text-center">No hay imágenes disponibles para este mantenimiento.</p>');
            }
        }

        // Función para mostrar la documentación de siniestros del vehículo
        function siniestroVehiculo(id_vehiculo) {
            $.ajax({
                type: "POST",
                url: 'acciones_ver_registros',
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
                    // Actualiza la información del vehículo with style
                    $("#infoVehiculoSiniestro")
                    .html(`
                        <div style="display: flex; justify-content: space-between; font-weight: bold;">
                            <span><strong>Placa:</strong> <span style="font-weight: normal;">${respuesta[0].placa}</span></span>
                            <span><strong>Modelo:</strong> <span style="font-weight: normal;">${respuesta[0].modelo}</span></span>
                            <span><strong>Marca:</strong> <span style="font-weight: normal;">${respuesta[0].marca}</span></span>
                            <span><strong>Color:</strong> <span style="font-weight: normal;">${respuesta[0].color}</span></span>
                        </div>
                    `)

                    // Actualiza la información del modal
                    $("#fechaHoraSiniestro").text(`${respuesta[0].fecha || "N/A"} ${respuesta[0].hora || ""}`);
                    $("#ubicacionSiniestro").text(respuesta[0].lugar || "N/A");
                    $("#partesSiniestro").text(respuesta[0].partes_dañadas || "N/A");
                    $("#montoSiniestro").text(respuesta[0].monto || "$0.00");
                    $("#descripcionSiniestro").text(respuesta[0].descripcion || "Sin descripción.");

                    // Mostrar el modal
                    const modal = new bootstrap.Modal(document.getElementById("modalSiniestro"));
                    mostrarImagenSiniestro(respuesta[0].imagen, respuesta[1].imagen, respuesta[2].imagen, respuesta[3].imagen);
                    modal.show();
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

        // Función para mostrar la imagen del Siniestro seleccionado
        function mostrarImagenSiniestro(imagen0, imagen1, imagen2, imagen3) {
            console.log("Ruta foto0:", imagen0);
            console.log("Ruta foto1:", imagen1);
            console.log("Ruta foto2:", imagen2);
            console.log("Ruta foto3:", imagen3);
            const imagenesCarrusel = $("#imagenesCarruselSiniestro");
            imagenesCarrusel.empty();

            const fotos = [imagen0, imagen1, imagen2, imagen3];
            let primeraImagen = true;

            fotos.forEach(imagen => {
                if (imagen) {
                    const activeClass = primeraImagen ? 'active' : '';
                    const imagenHTML = `
                        <div class="carousel-item ${activeClass}">
                            <img src="${imagen}" class="d-block w-100 img-fluid border" alt="Imagen del Siniestro" style="max-height: 300px; object-fit: contain;">
                        </div>
                    `;
                    imagenesCarrusel.append(imagenHTML);
                    primeraImagen = false;
                }
            });

            if (imagenesCarrusel.children().length > 0) {
                // Inicializa el carrusel si hay al menos una imagen
                const carouselSiniestro = new bootstrap.Carousel(document.getElementById('carouselSiniestro'));
            } else {
                // Si no hay fotos, muestra un mensaje
                imagenesCarrusel.html('<p class="text-center">No hay imágenes disponibles para este siniestro.</p>');
            }
        }
    </script>
</body>
</html>