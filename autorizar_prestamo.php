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
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
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
                        <h1 class = "h3 mb-0 text-black-800">Lista de Solicitudes</h1>                        
                    </div>
                    <!-- FORMULARIO DE REGISTROS DE PRESTAMOS -->
                    <div class="table-responsive">
                        <table id="tablaPrestamos" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Vehiculo</th>
                                    <th>Inicio Prestamo</th>
                                    <th>Fin Prestamo</th>
                                    <th>Estado</th>
                                    <th>Motivo</th>
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
    <!-- Modal para Autorizar Solicitud -->
    <div class="modal fade" id="modalPrestamo" tabindex="-1" aria-labelledby="modalPrestamoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalPrestamoLabel">Detalles del Prestamo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formModalPrestamo">
                        <div class="mb-3">
                            <input type="hidden" class="form-control" id="autoSeleccionado" name="autoSeleccionado" readonly>
                        </div>
                        <div  class="mb-3">
                            <label>Seleccionar Vehículo:</label>
                            <select id="id_vehiculo" name="id_vehiculo" class="form-select" onchange="actualizarInfoVehiculo(this.value)" required>
                                <option value="">Seleccione un vehículo</option>
                                <!-- Las opciones se cargarán dinámicamente -->
                            </select>
                        </div>
                        <div class="mb-3 d-flex">
                            <div class="me-3" style="flex: 1;">
                                <label class="form-label" for="modelo">Modelo:</label>
                                <input type="text" class="form-control" id="modelo" name="modelo" readonly>
                            </div>
                            <div style="flex: 1;">
                                <label class="form-label" for="color">Color:</label>
                                <input type="text" class="form-control" id="color" name="color" readonly>
                            </div>
                        </div>
                        <div class="mb-3 d-flex">
                            <div class="me-3" style="flex: 1;">
                                <label for="km_inicio">Km Inicio:</label>
                                <input type="text" class="form-control" id="km_inicio" name="km_inicio" required>
                            </div>
                            <div style="flex: 1;">
                                <label for="km_fin">Km Fin:</label>
                                <input type="text" class="form-control" id="km_fin" name="km_fin">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>Fecha Entrega de Vehiculo:</label>
                            <input type="date" class="form-control" id="fecha_entrega" name="fecha_entrega" required>
                        </div>
                        <div class="mb-3">
                            <label>Notas:</label>
                            <textarea class = "form-control" id = "notas_aprobadas" name = "notas_aprobadas" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-outline-success" id="btnGuardarModal" onclick="autorizarPrestamo()">Guardar</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal para Denegar-->
    <div class="modal fade" id="modalDenegarPrestamo" tabindex="-1" aria-labelledby="modalDenegarPrestamoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDenegarPrestamoLabel">Denegar Préstamo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formModalDenegarPrestamo">
                        <div class="mb-3">
                            <label>Notas:</label>
                            <textarea class="form-control" id="notas_denegar" name="notas_denegar" required></textarea>
                        </div>
                        <input type="hidden" class="form-control" id="id_vehiculo_denegar" name="id_vehiculo_denegar" readonly>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-outline-danger" id="btnDenegarModal" onclick="denegarPrestamo()">Denegar</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap core JavaScript-->
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <script src = "vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
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
        cargarPrestamos();
        $("#tablaPrestamos").DataTable({
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
            //Justifica el buscador y la paginación
            dom: '<"d-flex justify-content-between"lf>t<"d-flex justify-content-between"ip>'
        });
        //Llenar automáticamente los campos de fecha y hora
        const now = new Date();
        const fecha = now.toISOString().split('T')[0]; // Formato YYYY-MM-DD
        const hora = now.toTimeString().split(' ')[0].slice(0, 5); // Formato HH:MM
        $("#fecha").val(fecha); // Establecer la fecha actual
        $("#hora").val(hora); // Establecer la hora actual
    });

    // Modificar la función cargarPrestamos
    function cargarPrestamos() {
        const rol = getCookie('rol'); 

        $.ajax({
            type: "POST",
            url: "acciones_prestamos",
            data: { accion: "consultarPrestamos" },
            dataType: "json",
            success: function (respuesta) {
                var tabla = $("#tablaPrestamos tbody");
                tabla.empty(); // Limpiar la tabla antes de llenarla
                // Mostrar u ocultar la columna "Acción" según el rol
                if (rol != 3) {
                    $("#tablaPrestamos th:last-child, #tablaPrestamos td:last-child").hide(); // Ocultar columna "Acción"
                } else {
                    $("#tablaPrestamos th:last-child, #tablaPrestamos td:last-child").show(); // Mostrar columna "Acción"
                }
                respuesta.forEach(function (prestamo) {
                    var botones = "";
                    if (rol == 3) { 
                        botones = `
                            <button class="btn btn-outline-success" onclick="infoSelect(${prestamo.id_prestamo})">
                                <ion-icon name="checkmark-outline" style="font-size: 16px;"></ion-icon>
                            </button>
                            <button class="btn btn-outline-danger" onclick="abrirModalDenegar(${prestamo.id_prestamo}, ${prestamo.id_vehiculo})">
                                <ion-icon name="close-outline" class="fs-6"></ion-icon>
                            </button>`;
                    }
                    var fila = `
                        <tr>
                            <td>${prestamo.modelo} - ${prestamo.placa}</td>
                            <td>${prestamo.fecha_inc_prestamo}</td>
                            <td>${prestamo.fecha_fin_prestamo}</td>
                            <td>${prestamo.estatus}</td>
                            <td>${prestamo.motivo}</td>
                            <td>${botones}</td>
                        </tr>`;
                    tabla.append(fila);
                });
            },
            error: function () {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Hubo un problema al cargar los prestamos.",
                    confirmButtonText: "Aceptar"
                });
            }
        });
    }

    //FUNCION PARA CARGAR INFORMACIÓN DE LOS VEHÍCULOS
    function infoVehiculos() {
        $.ajax({
            type: "POST",
            url: "acciones_siniestro",
            data: { accion: "consultarInventarioCambio" },
            dataType: "json",
            success: function (respuesta) {
                var select = $("#id_vehiculo");
                select.empty();
                respuesta.forEach(function (vehiculo) {
                    var option = `<option value="${vehiculo.id_vehiculo}">${vehiculo.modelo} - ${vehiculo.placa}</option>`;
                    select.append(option);
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

    // Función para autorizar un prestamo
    function infoSelect(id_prestamo) {
        $.ajax({
            type: "POST",
            url: "acciones_prestamos",
            data: { accion: "consultarPrestamos", id_prestamo },
            dataType: "json",
            success: function (respuesta) {
                const prestamo = respuesta.find(p => p.id_prestamo == id_prestamo);
                if (prestamo) {
                    infoVehiculos(prestamo.id_vehiculo);
                    $("#placa").val(prestamo.placa);
                    $("#modelo").val(prestamo.modelo);
                    $("#color").val(prestamo.color);
                    const fechaIncPrestamo = prestamo.fecha_inc_prestamo.split(" ")[0];
                    $("#fecha_entrega").val(fechaIncPrestamo);
                    $("#btnGuardarModal").attr("data-id-prestamo", id_prestamo);
                    var modal = new bootstrap.Modal(document.getElementById("modalPrestamo"));
                    modal.show();
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "No se encontró información del préstamo.",
                        confirmButtonText: "Aceptar"
                    });
                }
            },
            error: function (xhr, status, error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Hubo un problema al cargar la información del préstamo.',
                    confirmButtonText: 'Aceptar'
                });
            }
        });
    }

    // Función para abrir el modal de denegar préstamo
    function abrirModalDenegar(id_prestamo, id_vehiculo) {
        // Establecer el ID del préstamo en el botón del modal
        $("#btnDenegarModal").attr("data-id-prestamo", id_prestamo);

        // Establecer el ID del vehículo en el campo oculto del modal
        $("#id_vehiculo_denegar").val(id_vehiculo);

        // Mostrar el modal
        var modal = new bootstrap.Modal(document.getElementById("modalDenegarPrestamo"));
        modal.show();
    }

    // Función para denegar el préstamo 
    function denegarPrestamo() {
        var id_prestamo = $("#btnDenegarModal").attr("data-id-prestamo");
        var id_vehiculo = $("#id_vehiculo_denegar").val();
        var notas_denegar = $("#notas_denegar").val();

        $.ajax({
            type: "POST",
            url: "acciones_prestamos",
            data: {
                accion: "denegarPrestamo",
                id_prestamo: id_prestamo,
                id_vehiculo: id_vehiculo,
                notas_denegar: notas_denegar
            },
            dataType: "json",
            success: function (respuesta) {
                if (respuesta.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Éxito",
                        text: "Préstamo denegado exitosamente.",
                        timer: 3000,
                        confirmButtonText: "Aceptar"
                    }).then(() => {
                        cargarPrestamos();
                        $("#modalDenegarPrestamo").modal("hide");
                        $("#notas_denegar").val("");
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: respuesta.message || "No se pudo denegar el préstamo.",
                        confirmButtonText: "Aceptar"
                    });
                }
            },
            error: function () {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Hubo un problema al denegar el préstamo.",
                    confirmButtonText: "Aceptar"
                });
            }
        });
    }

    // Función para actualizar la información del vehículo
    function actualizarInfoVehiculo(id_vehiculo) {
        $.ajax({
            type: "POST",
            url: "acciones_prestamos", 
            data: { accion: "obtenerInfoVehiculo", id_vehiculo },
            dataType: "json",
            success: function (respuesta) {
                if (respuesta.success) {
                    $("#modelo").val(respuesta.vehiculo.modelo);
                    $("#color").val(respuesta.vehiculo.color);
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: respuesta.message || "No se pudo cargar la información del vehículo.",
                        confirmButtonText: "Aceptar"
                    });
                }
            },
            error: function () {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Hubo un problema al cargar la información del vehículo.",
                    confirmButtonText: "Aceptar"
                });
            }
        });
    }

    // Función para enviar el formulario de autorización de préstamo a la base de datos
    function autorizarPrestamo() {
        var id_prestamo = $("#btnGuardarModal").attr("data-id-prestamo");
        var fecha_entrega = $("#fecha_entrega").val();
        var id_vehiculo = $("#id_vehiculo").val();
        var km_inicio = $("#km_inicio").val();
        var km_fin = $("#km_fin").val();
        var accion = "autorizarPrestamo";
        var notas_aprobadas = $("#notas_aprobadas").val();
        var id_usuario = getCookie("id_usuario");

        $.ajax({
            type: "POST",
            url: "acciones_prestamos",
            data: {
                id_prestamo: id_prestamo,
                fecha_entrega: fecha_entrega,
                id_vehiculo: id_vehiculo,
                notas_aprobadas: notas_aprobadas,
                km_inicio: km_inicio,
                km_fin: km_fin,
                id_usuario: id_usuario,
                accion: accion
            },
            dataType: "json",
            success: function (respuesta) {
                if (respuesta.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Éxito",
                        text: respuesta.message || "Préstamo autorizado exitosamente.",
                        timer: 3000,
                        confirmButtonText: "Aceptar"
                    }).then(() => {
                        cargarPrestamos(); // Recargar la tabla de préstamos
                        $("#modalPrestamo").modal("hide"); // Cerrar el modal
                        $("#notas_aprobadas").val(""); // Limpiar el campo de notas
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: respuesta.message || "No se pudo autorizar el préstamo.",
                        confirmButtonText: "Aceptar"
                    });
                }
            },
            error: function () {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Hubo un problema al autorizar el préstamo.",
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
    </script>
</body>
</html>