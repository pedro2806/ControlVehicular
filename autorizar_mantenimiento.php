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
                        <h1 class = "h3 mb-0 text-black-800">Lista de Mantenimientos</h1>                        
                    </div>

                    <div class="row">                        
                        <div class="col-12">
                            <div class="btn-group" role="group" aria-label="Opciones de Mantenimiento">
                                <button onclick="cargarMantenimientos('PENDIENTE', 'warning')" type="button" class="btn btn-outline-warning">Por autorizar</button>
                                <button onclick="cargarMantenimientos('AUTORIZADO', 'primary')" type="button" class="btn btn-outline-primary">Autorizados</button>
                                <button onclick="cargarMantenimientos('REALIZADO', 'success')" type="button" class="btn btn-outline-success">Realizados</button>
                            </div>
                        </div>
                    </div>
                    <!-- FORMULARIO DE REGISTRO DE MANTENIMIENTO -->
                    <div class="row">
                        <div class="table-responsive">
                            <table id="tablaMantenimientos" name= "tablaMantenimientos" class="table table-striped table-bordered table-sm">
                                <thead>
                                    <tr>
                                        <th>Solicita</th>
                                        <th>Vehiculo</th>                                        
                                        <th>Fecha Registro</th>
                                        <th>Kilometraje</th>
                                        <th>Tipo de Mantenimiento</th>
                                        <th>Descripción</th>
                                        <th>Estatus</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Las filas se cargarán dinámicamente -->
                                </tbody>
                            </table>
                        </div>
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
    <!-- Modal para ingresar comentario y fecha programada -->
    <div class="modal fade" id="modalMantenimiento" tabindex="-1" aria-labelledby="modalMantenimientoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalMantenimientoLabel">Detalles del Mantenimiento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formModalMantenimiento">
                        <div class="mb-3">
                            <label class="form-label">Fecha Programada:</label>
                            <input type="date" class="form-control" id="fecha_programada" name="fecha_programada" required>
                        </div>
                        <div class="row">                        
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">Folio MESS-OC/RC:</label>
                                    <input type="text" class="form-control" id="folioOC" name="folioOC" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mb-3">
                                    <label class="form-label">Costo OC/RC:</label>
                                    <input type="text" class="form-control" id="costo" name="costo" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="comentario" class="form-label">Comentario:</label>
                            <textarea class="form-control" id="comentario" name="comentario" rows="3" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-outline-success" id="btnGuardarModal">Guardar</button>
                </div>
            </div>
        </div>
    </div>

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
    <!-- Ionicons -->
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

    <script type="text/javascript">
    $(document).ready(function() {
        cargarMantenimientos('PENDIENTE', 'warning'); // Cargar mantenimientos por autorizar al cargar la página
        
        $('#tablaMantenimientos').DataTable({
                destroy: true, // Permitir reinicializar la tabla
                paging: true, // Quitar paginado
                ordering: false, // Quitar orden
                searching: false, // Quitar buscador
                info: false, // Quitar leyendas a pie de tabla
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50, 100],
                language: {
                decimal: ",",
                thousands: ".",
                processing: "Procesando...",
                loadingRecords: "Cargando...",
                zeroRecords: "No se encontraron resultados",
                emptyTable: "No hay datos disponibles en la tabla"
                },
                createdRow: function(row, data, dataIndex) {
                    $(row).css('font-size', '14px'); // Reducir tamaño del texto
                }
            });
        
    });

    // Función para cargar los mantenimientos desde la base de datos
    function cargarMantenimientos(estatus, estiloTabla) {
        const rol = getCookie('rol'); // Obtener el rol del usuario desde las cookies

        $.ajax({
            type: "POST",
            url: "acciones_mantenimiento.php",
            data: { accion: "consultarMantenimientos", estatus: estatus },
            dataType: "json",
            success: function (respuesta) {
                
                var table = $('#tablaMantenimientos').DataTable();                

                table.clear().draw();                            
                respuesta.forEach(function(mantenimiento) { 

                    var botones = "";
                    if (rol == 2 && estatus == "PENDIENTE") { 
                        botones = `
                            <button class="btn btn-outline-success" onclick="autorizarMantenimiento(${mantenimiento.id_mantenimiento})">
                                <ion-icon name="checkmark-outline" style="font-size: 16px;"></ion-icon>
                            </button>
                            <button class="btn btn-outline-danger" onclick="denegarMantenimiento(${mantenimiento.id_mantenimiento})">
                                <ion-icon name="close-outline" class="fs-6"></ion-icon>
                            </button>`;
                    }


                    table.row.add([
                        mantenimiento.nombre_usuario,
                        mantenimiento.modelo+ " " + mantenimiento.placa + " " + mantenimiento.color,                        
                        mantenimiento.fecha_registro,
                        mantenimiento.kilometraje,
                        mantenimiento.tipo_mantenimiento,
                        mantenimiento.descripcion,
                        mantenimiento.VoBo_jefe,                        
                        botones
                    ]).draw(false);
                });
                 // Agregar estilo al thead de la tabla            
                $('#tablaMantenimientos thead').removeClass('table-warning table-primary table-success');
                $('#tablaMantenimientos thead').addClass('table-' + estiloTabla);

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

    // Función para obtener el valor de una cookie
    function getCookie(name) {
        let cookies = document.cookie.split(';');
        for (let i = 0; i < cookies.length; i++) {
            let cookie = cookies[i].trim();
            if (cookie.startsWith(name + '=')) {
                return cookie.substring(name.length + 1);
            }
        }
        return null;
    }

    // Función para autorizar un mantenimiento
    function autorizarMantenimiento(id_mantenimiento) {
        $("#comentario").val("");
        $("#modalMantenimiento").data("id_mantenimiento", id_mantenimiento);
        $("#modalMantenimiento").data("accion", "autorizarMantenimiento");
        $("#modalMantenimientoLabel").text("Autorizar Mantenimiento");
        $("#btnGuardarModal").text("Guardar");

        // Habilitar el campo de fecha para que el usuario pueda ingresarla
        $("#fecha_programada").val("").prop("disabled", false);

        // Mostrar el contenedor del campo de fecha
        $("#fecha_programada").closest(".mb-3").show();

        $("#modalMantenimiento").modal("show");
    }

    // Función para denegar un mantenimiento
    function denegarMantenimiento(id_mantenimiento) {
        $("#comentario").val("");
        $("#modalMantenimiento").data("id_mantenimiento", id_mantenimiento);
        $("#modalMantenimiento").data("accion", "denegarMantenimiento");
        $("#modalMantenimientoLabel").text("Denegar Mantenimiento");
        $("#btnGuardarModal").text("Guardar");

        // Deshabilitar el campo de fecha y asignar la fecha actual
        const now = new Date();
        const fechaActual = now.toISOString().split("T")[0]; 
        $("#fecha_programada").val(fechaActual).prop("disabled", true); 

        // Ocultar el contenedor del campo de fecha
        $("#fecha_programada").closest(".mb-3").hide();

        $("#modalMantenimiento").modal("show");
    }

    // Función para guardar el comentario y la fecha programada
    $("#btnGuardarModal").on("click", function () {
        var id_mantenimiento = $("#modalMantenimiento").data("id_mantenimiento");
        var accion = $("#modalMantenimiento").data("accion");
        var comentario = $("#comentario").val();
        var fecha_programada = $("#fecha_programada").val();
        var folioOC = $("#folioOC").val();
        var costo = $("#costo").val();

        // Validar que la fecha programada no esté vacía si el campo está visible y habilitado
        if ($("#fecha_programada").is(":visible") && !$("#fecha_programada").prop("disabled") && fecha_programada === "") {
            Swal.fire({
            icon: "warning",
            title: "Campo requerido",
            text: "Por favor ingresa la fecha programada.",
            confirmButtonText: "Aceptar"
            });
            return;
        }

        $.ajax({
            type: "POST",
            url: "acciones_mantenimiento",
            data: { accion, id_mantenimiento, comentario, fecha_programada, folioOC, costo },
            success: function (respuesta) {
                Swal.fire({
                    icon: "success",
                    title: accion === "autorizarMantenimiento" ? "¡Autorizado!" : "¡Denegado!",
                    text: "El mantenimiento ha sido actualizado exitosamente.",
                    confirmButtonText: "Aceptar"
                });
                $("#modalMantenimiento").modal("hide"); // Cerrar el modal
                cargarMantenimientos(); // Recargar la tabla
            },
            error: function () {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Hubo un problema al actualizar el mantenimiento.",
                    confirmButtonText: "Aceptar"
                });
            }
        });
    });
    </script>
</body>
</html>