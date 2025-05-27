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
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    
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
                    <!-- Content Row -->
                    <div class="row">
                        <div class="col-xl-12 col-lg-12">
                            <h1 class="h3 mb-0 text-black-800">Historial de Siniestros</h1>
                            <br>
                            <!-- CONTENEDOR INFO AUTO -->                            
                            <div id="placaSeleccionada" class="alert alert-info" style="display: none;"></div> 
                            <button id="btnCambiarVehiculo" class="btn btn-outline-primary" style="display: none;" onclick="cambiarVehiculo()">Cambiar Vehículo</button>    
                            <div class="card shadow mb-4">
                                <!-- Tabla de Vehículos -->
                                <div class="card-body">
                                    <div class="table-responsive" style="overflow-x: auto;">
                                        <table class="table table-striped table-bordered table-sm" id="TablaInventario" width="100%" cellspacing="0">
                                            <thead></thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                    <div class="table-responsive" style="overflow-x: auto; display: none;">
                                        <table class="table table-striped table-bordered" id="TablaRegistrosSiniestros" width="100%" cellspacing="0">
                                            <thead></thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                            <div id="detalleSiniestroContainer"></div>
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
    <!-- API GPS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <script type="text/javascript">
        $(document).ready(function() {
            // Inicializar DataTables
            var TablaInventario = $('#TablaInventario').DataTable({
                data: [], // Inicialmente vacío
                columns: [
                    { title: "Placa" },
                    { title: "Modelo" },
                    { title: "Color" },
                    { title: "Año" },
                    { title: "Asignado" },
                    { title: "Acciones" }
                ],
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
                },                
                createdRow: function(row, data, dataIndex) {
                    $(row).css('font-size', '12px'); // Reducir tamaño del texto
                }
            });
            // Inicializar DataTables para la tabla de registros
            var TablaRegistrosSiniestros = $('#TablaRegistrosSiniestros').DataTable({
                data: [], // Inicialmente vacío
                columns: [
                    { title: "Fecha" },
                    { title: "Origen" },
                    { title: "Destino" },
                    { title: "Empresa" },
                    { title: "Servicio" },
                    { title: "Acciones" }
                ],
                paging: true,
                pageLength: 5,
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
                },                
                createdRow: function(row, data, dataIndex) {
                    $(row).css('font-size', '12px'); // Reducir tamaño del texto
                }
            });
            cargarVehiculos(TablaInventario);
        });

        // Cargar Vehículos
        function cargarVehiculos(TablaInventario) {
            $.ajax({
                type: "POST",
                url: 'acciones_ver_registros',
                data: {accion: 'ver_inventario'},
                dataType: "json",
                success: function(respuesta) {
                    TablaInventario.clear(); 

                    respuesta.forEach(function (vehiculo) {
                        var botones = `
                            <button class="btn btn-outline-warning" onclick="inventario('${vehiculo.id_vehiculo}')">
                                <i class="fas fa-eye"></i>
                            </button>
                            `;
                        
                        var fila = [
                            `<i class="fas fa-car"></i> ${vehiculo.placa}</strong>`,
                            `${vehiculo.modelo} - ${vehiculo.marca}`,
                            `${vehiculo.color}`,
                            `${vehiculo.anio}`,
                            `${vehiculo.usuario}`,
                            `<center>
                                <button class="btn btn-outline-warning btn-sm" onclick="seleccionarVehiculo('${vehiculo.id_vehiculo}', '${vehiculo.placa}' , '${vehiculo.modelo}', '${vehiculo.marca}', '${vehiculo.anio}', '${vehiculo.color}', '${vehiculo.usuario}')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </center>`
                        ];
                        TablaInventario.row.add(fila);
                    });

                    TablaInventario.draw(); // Redibujar tabla con los nuevos datos
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

        //FUNCION PARA MANEJAR EL BOTÓN "CHECK"
        function seleccionarVehiculo(id_vehiculo, placa, modelo, marca, color) {
            $("#placaSeleccionada")
                .html(`
                    <div style="display: flex; justify-content: space-between; font-weight: bold;">
                        <span>Placa:<span id="placaVehiculo" style="font-weight: normal;">${placa}</span></span>
                        <span>Modelo:<span style="font-weight: normal;">${modelo}</span></span>
                        <span>Marca:<span style="font-weight: normal;">${marca}</span></span>
                        <span>Color:<span style="font-weight: normal;">${color}</span></span>
                    </div>
                `).show();
            // Guardar el ID del vehículo seleccionado en un campo oculto
            $("#id_vehiculo").val(id_vehiculo);
            $("#btnCambiarVehiculo").show();
            $("#TablaInventario").closest(".table-responsive").hide();
            $("#TablaRegistrosSiniestros").closest(".table-responsive").show();
            siniestrosXvehiculo(id_vehiculo);
        }
        //FUNCION PARA CAMBIAR DE VEHÍCULO
        function cambiarVehiculo() {
            $("#placaSeleccionada").hide();
            $("#btnCambiarVehiculo").hide();
            $("#TablaInventario").closest(".table-responsive").show();
            $("#TablaRegistrosSiniestros").closest(".table-responsive").hide();
        }
        
        //FUNCION PARA VER LOS VEHICULOS
        function inventario(id_vehiculo) {
            $.ajax({
                type: "POST",
                url: 'acciones_ver_registros',
                data: {accion: 'siniestrosVehiculo', id_vehiculo: id_vehiculo},
                dataType: "json",
                success: function(respuesta) {
                    if (respuesta.error) {
                        Swal.fire({
                            icon: "success",
                            title: "Detalles del Siniestro",
                            text: inventario(respuesta.id_vehiculo),
                            confirmButtonText: "Aceptar"
                        });
                    } else {
                        swal.fire({
                            title: "Error",
                            text: "No se pudo cargar la información del siniestro.",
                            icon: "error",
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

        //FUNCION PARA VER LOS DETALLES DEL SINIESTRO
        function siniestrosXvehiculo(id_vehiculo) {
            $.ajax({
                type: "POST",
                url: 'acciones_ver_registros', 
                data: { accion: 'verSiniestrosXVehiculo', id_vehiculo}, 
                dataType: "json",
                success: function(respuesta) {
                    var TablaRegistrosSiniestros = $('#TablaRegistrosSiniestros').DataTable();
                    TablaRegistrosSiniestros.clear();

                    respuesta.forEach(function(siniestro) {
                        var fila = [
                            `${siniestro.fecha_registro}`,
                            `${siniestro.origen}`,
                            `${siniestro.destino}`,
                            `${siniestro.empresa}`,
                            `${siniestro.servicio}`,
                            `<center>
                                <button class="btn btn-outline-warning btn-sm" onclick='mostrarDetalleSiniestro(${JSON.stringify(siniestro)})'>
                                    <i class="fas fa-eye"></i> 
                                </button>
                            </center>`
                        ];
                        TablaRegistrosSiniestros.row.add(fila);
                    });
                    TablaRegistrosSiniestros.draw();
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "No se pudo cargar la información de los siniestros.",
                        confirmButtonText: "Aceptar"
                    });
                }
            });
        }

        //FUNCION PARA MOSTRAR EL DETALLE DEL SINIESTRO
        function mostrarDetalleSiniestro(siniestro) {
            // Crear la tarjeta con los detalles del siniestro
            var tarjeta = `
                <div class="card shadow mb-4">
                    <div class="card-header py-3 text-bg-primary">
                        <h6 class="m-0 font-weight-bold text-black">Detalle del Siniestro</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <p><strong>Fecha:</strong> ${siniestro.fecha || 'N/A'}</p>
                            </div>
                            <div class="col-md-3">
                                <p><strong>Hora:</strong> ${siniestro.hora || 'N/A'}</p>
                            </div>
                            <div class="col-md-3">
                                <p><strong>Kilometraje:</strong> ${siniestro.kilometraje || 'N/A'}</p>
                            </div>
                            <div class="col-md-3">
                                <p><strong>Gasolina:</strong> ${siniestro.gasolina || 'N/A'}</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <p><strong>Contacto:</strong> ${siniestro.contacto || 'N/A'}</p>
                            </div>
                            <div class="col-md-3">
                                <p><strong>Descripción:</strong> ${siniestro.descripcion || 'N/A'}</p>
                            </div>
                            <div class="col-md-3">
                                <p><strong>Partes Dañadas:</strong> ${siniestro.partes_dañadas || 'N/A'}</p>
                            </div>
                            <div class="col-md-3">
                                <p><strong>Ubicación del Vehículo:</strong> ${siniestro.ubicacion_vehiculo || 'N/A'}</p>
                            </div>
                        </div>
                        <div class="row">
                            <!-- Mapa y Carrusel en la misma fila -->
                            <div class="col-md-6">
                                <p><strong>Coordenadas:</strong></p>
                                <div id="map" style="height: 200px; width: 100%;"></div> <!-- Div para el mapa -->
                            </div>
                            <div class="col-md-6">
                                <div id="imagenesCarruselSiniestro" class="carousel slide mt-3" data-bs-ride="carousel">
                                    <div class="carousel-inner">
                                        <!-- Las imágenes se agregarán dinámicamente aquí -->
                                    </div>
                                    <button class="carousel-control-prev" type="button" data-bs-target="#imagenesCarruselSiniestro" data-bs-slide="prev">
                                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">Anterior</span>
                                    </button>
                                    <button class="carousel-control-next" type="button" data-bs-target="#imagenesCarruselSiniestro" data-bs-slide="next">
                                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                        <span class="visually-hidden">Siguiente</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Mostrar la tarjeta en un contenedor específico
            $('#detalleSiniestroContainer').html(tarjeta);

            // Llamar a la función para agregar las imágenes al carrusel
            mostrarImagenSiniestro(siniestro.imagenes);

            // Llamar a la función para mostrar el mapa
            mostrarMapa(siniestro.coordenadas);
        }

        //Mostrar img del Siniestro
        function mostrarImagenSiniestro(imagenes) {
            const imagenesCarrusel = $("#imagenesCarruselSiniestro .carousel-inner");
            imagenesCarrusel.empty();

            if (Array.isArray(imagenes) && imagenes.length > 0) {
                let primeraImagen = true;

                imagenes.forEach(imagen => {
                    if (imagen) {
                        const activeClass = primeraImagen ? 'active' : '';
                        const imagenHTML = `
                            <div class="carousel-item ${activeClass}">
                                <img src="${imagen}" class="d-block w-100 img-fluid border" alt="Imagen del siniestro" style="max-height: 300px; object-fit: contain;">
                            </div>
                        `;
                        imagenesCarrusel.append(imagenHTML);
                        primeraImagen = false;
                    }
                });
            } else {
                // Si no hay imágenes, muestra un mensaje
                imagenesCarrusel.html('<p class="text-center">No hay imágenes disponibles para este siniestro.</p>');
            }
        }

        // Inicializar el mapa
        function mostrarMapa(coordenadas) {
            // Verifica si las coordenadas están disponibles
            if (!coordenadas || coordenadas === 'N/A') {
                $('#map').html('<p class="text-center">No hay coordenadas disponibles para este siniestro.</p>');
                return;
            }

            // Divide las coordenadas en latitud y longitud
            const [lat, lng] = coordenadas.split(',').map(coord => parseFloat(coord.trim()));

            // Inicializa el mapa
            const mapa = L.map('map').setView([lat, lng], 13); // Zoom inicial en las coordenadas

            // Agrega el mapa base (OpenStreetMap)
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(mapa);

            // Agrega un marcador en las coordenadas
            L.marker([lat, lng]).addTo(mapa)
                .bindPopup('Ubicación del siniestro')
                .openPopup();
        }
    </script>
</body>
</html>