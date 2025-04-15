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
                        <h1 class = "h3 mb-0 text-black-800">Lista de Mantenimiento</h1>                        
                    </div>

                    <!-- CONTENEDOR INFO AUTO -->
                    <div id="placaSeleccionada" class="alert alert-info" style="display: none;"></div> 
                    <button id="btnCambiarVehiculo" class="btn btn-outline-primary" style="display: none;" onclick="cambiarVehiculo()">Cambiar Vehículo</button>

                    <!-- FORMULARIO DE REGISTRO DE MANTENIMIENTO -->
                    <div class="container">
                        <h3 class="h5 mb-0 text-black" style="font-weight: bold;">Lista de Mantenimiento</h3>
                        <table id="tablaMantenimientos" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Placa</th>
                                    <th>Fecha Registro</th>
                                    <th>Kilometraje</th>
                                    <th>Tipo de Mantenimiento</th>
                                    <th>Descripción</th>
                                    <th>Estado</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Las filas se cargarán dinámicamente -->
                            </tbody>
                        </table>
                    </div>
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
        cargarMantenimientos();
        $("#tablaMantenimientos").DataTable({
            destroy: true,
            paging: true,
            ordering: true,
            searching: true,
            info: true,
            language: {
                decimal: ",",
                thousands: ".",
                processing: "Procesando...",
                loadingRecords: "Cargando...",
                zeroRecords: "No se encontraron resultados",
                emptyTable: "No hay datos disponibles en la tabla"
            }
        });
    });

    // Función para cargar los mantenimientos desde la base de datos
    function cargarMantenimientos() {
        $.ajax({
            type: "POST",
            url: "acciones_mantenimiento.php",
            data: { accion: "consultarMantenimientos" },
            dataType: "json",
            success: function (respuesta) {
                var tabla = $("#tablaMantenimientos tbody");
                tabla.empty(); // Limpiar la tabla antes de llenarla
                respuesta.forEach(function (mantenimiento) {
                    var fila = `
                        <tr>
                            <td>${mantenimiento.id_mantenimiento}</td>
                            <td>${mantenimiento.placa}</td>
                            <td>${mantenimiento.fecha_registro}</td>
                            <td>${mantenimiento.kilometraje}</td>
                            <td>${mantenimiento.tipo_mantenimiento}</td>
                            <td>${mantenimiento.descripcion}</td>
                            <td>${mantenimiento.VoBo_jefe}</td>
                            <td>
                                <button id="btnAutorizar" class="btn btn-outline-success" onclick="autorizarMantenimiento()">Autorizar</button>
                                <button id="btnDenegar" class="btn btn-outline-danger" onclick="denegarMantenimiento()">Denegar</button>
                            </td>
                        </tr>`;
                    tabla.append(fila);
                });
            },
            error: function () {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Hubo un problema al cargar los mantenimientos.",
                    confirmButtonText: "Aceptar"
                });
            }
        });
    }

    // Función para autorizar un mantenimiento
    function autorizarMantenimiento() {
        Swal.fire({
            title: "¿Estás seguro?",
            text: "¿Deseas autorizar el mantenimiento seleccionado?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Sí, autorizar",
            cancelButtonText: "Cancelar"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: "acciones_mantenimiento",
                    data: { accion: "autorizarMantenimiento" },
                    success: function (respuesta) {
                        Swal.fire({
                            icon: "success",
                            title: "¡Autorizado!",
                            text: "El mantenimiento ha sido autorizado.",
                            confirmButtonText: "Aceptar"
                        });
                        cargarMantenimientos(); 
                    },
                    error: function () {
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: "Hubo un problema al autorizar el mantenimiento.",
                            confirmButtonText: "Aceptar"
                        });
                    }
                });
            }
        });
    }

    // Función para denegar un mantenimiento
    function denegarMantenimiento() {
        Swal.fire({
            title: "¿Estás seguro?",
            text: "¿Deseas denegar el mantenimiento seleccionado?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Sí, denegar",
            cancelButtonText: "Cancelar"
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: "acciones_mantenimiento",
                    data: { accion: "denegarMantenimiento" },
                    success: function (respuesta) {
                        Swal.fire({
                            icon: "success",
                            title: "¡Denegado!",
                            text: "El mantenimiento ha sido denegado.",
                            confirmButtonText: "Aceptar"
                        });
                        cargarMantenimientos(); 
                    },
                    error: function () {
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: "Hubo un problema al denegar el mantenimiento.",
                            confirmButtonText: "Aceptar"
                        });
                    }
                });
            }
        });
    }
    </script>
</body>
</html>