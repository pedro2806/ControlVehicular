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
                    <!-- Pestañas de navegación -->
                    <ul class="nav nav-tabs">
                        <li class="nav-item">
                            <a class="btn btn-outline-warning" id="tabPendientes" data-bs-toggle="tab" href="#pendientes">Pendientes</a>
                        </li>                        
                        <li class="nav-item">
                            <a class="btn btn-outline-info" id="tabAutorizaAsignadoOtraArea" data-bs-toggle="tab" href="#AutorizaAsignadoOtraArea">Asignado otra area</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-outline-success" id="tabAutorizados" data-bs-toggle="tab" href="#autorizados">Autorizados</a>
                        </li>
                        <!-- Nueva pestaña para Devolución -->
                        <li class="nav-item">
                            <a class="btn btn-outline-primary" id="tabDevolucion" data-bs-toggle="tab" href="#devolucion">Devolución</a>
                        </li>
                        <!-- Nueva pestaña para Terminados -->
                        <li class="nav-item">
                            <a class="btn btn-outline-danger" id="tabTerminados" data-bs-toggle="tab" href="#terminados">Terminados</a>
                        </li>                        
                    </ul>
                    <!-- Contenedor de las tablas -->
                    <div class="tab-content mt-3">
                        <!-- Tabla de préstamos pendientes -->
                        <div class="tab-pane fade show active" id="pendientes">
                            <div class="table-responsive">
                                <table id="tablaPrestamos" class="table table-striped table-bordered">
                                    <thead class="table-warning">
                                        <tr>
                                            <th>Solicita</th>
                                            <th>Inicio Préstamo</th>    
                                            <th>Fin Préstamo</th>
                                            <th>Tipo de Uso</th>
                                            <th>Detalle del Uso</th>
                                            <th>Motivo</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Las filas se cargarán dinámicamente -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!-- Tabla de préstamos autorizados para otra área -->
                        <div class="tab-pane fade show" id="AutorizaAsignadoOtraArea">
                            <div class="table-responsive">
                                <table id="tablaPrestamosOtraArea" class="table table-striped table-bordered">
                                    <thead class="table-info">
                                        <tr>
                                            <th>Solicita</th>
                                            <th>Inicio Préstamo</th>    
                                            <th>Fin Préstamo</th>
                                            <th>Tipo de Uso</th>
                                            <th>Detalle del Uso</th>
                                            <th>Motivo</th>
                                            <th>Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Las filas se cargarán dinámicamente -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!-- Tabla de préstamos autorizados -->
                        <div class="tab-pane fade" id="autorizados">
                            <div class="table-responsive">
                                <table id="tablaAutorizados" class="table table-striped table-bordered">
                                    <thead class="table-success">
                                        <tr>
                                            <th>Solicita</th>
                                            <th>Fecha Entrega</th>
                                            <th>Vehiculo</th>
                                            <th>Notas del Jefe</th>
                                            <th>Tipo de Uso</th>
                                            <th>Detalle del Uso</th>
                                            <th>Iniciar Prestamo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Las filas se cargarán dinámicamente -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!-- Nueva tabla para Devolución -->
                        <div class="tab-pane fade" id="devolucion">
                            <div class="table-responsive">
                                <table id="tablaDevolucion" class="table table-striped table-bordered">
                                    <thead class="table-primary">
                                        <tr>
                                            <th>Fecha Inicio</th>    
                                            <th>Vehículo</th>
                                            <th>Tipo de Uso</th>
                                            <th>Detalle del Uso</th>
                                            <th>Fecha Devolucion</th>
                                            <th>Finalizar Prestamo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Las filas se cargarán dinámicamente -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!-- Nueva tabla para terminados -->
                        <div class="tab-pane fade" id="terminados">
                            <div class="table-responsive">
                                <table id="tablaTerminados" class="table table-striped table-bordered">
                                    <thead class="table-danger">
                                        <tr>
                                            <th>Fecha Inicio</th>    
                                            <th>Vehículo</th>
                                            <th>Tipo de Uso</th>
                                            <th>Detalle del Uso</th>
                                            <th>Fecha Devolucion</th>                                            
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Las filas se cargarán dinámicamente -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <br>
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
                    <h5 class="modal-title" id="modalPrestamoLabel">Aprobar Préstamo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formModalPrestamo">
                        <input type="hidden" class="form-control" id="fecha_registro_asignado" name="fecha_registro_asignado" readonly>
                        <input type="hidden" class="form-control" id="placa" name="placa" readonly>
                        <div class="mb-3">
                            <label>Seleccionar Vehículo:</label>
                            <select id="id_vehiculo" name="id_vehiculo" class="form-select" onchange="actualizarInfoVehiculo(this.value)" required>
                                <option value="">Seleccione...</option>
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
                                <input type="hidden" class="form-control" id="tipoP" name="tipoP" readonly>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>Fecha de Entrega:</label>
                            <input type="datetime-local" class="form-control" id="fecha_entrega" name="fecha_entrega" required>
                        </div>
                        <div class="mb-3">
                            <label>Notas:</label>
                            <textarea class="form-control" id="notas_jefe" name="notas_jefe" required></textarea>
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
                        <input type="hidden" class="form-control" id="fecha_registro_denegar" name="fecha_registro_denegar" readonly>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-outline-danger" id="btnDenegarModal" onclick="denegarPrestamo()">Denegar</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal para Inicio Préstamo -->
    <div class="modal fade" id="modalInicioPrestamo" tabindex="-1" aria-labelledby="modalInicioPrestamoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalInicioPrestamoLabel">Inicio Préstamo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formInicioPrestamo" enctype="multipart/form-data">
                        <input type="hidden" class="form-control" id="id_vehiculo" name="id_vehiculo">
                        <input type="hidden" class="form-control" id="placa_ini" name="placa_ini">
                        <input type="hidden" class="form-control" id="id_prestamo" name="id_prestamo"> 
                        <div class="mb-3">
                            <label class="form-label">Fecha de Entrega:</label>
                            <input type="datetime-local" class="form-control" id="fecha_entrega_inicio" name="fecha_entrega_inicio" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kilómetros Inicio:</label>
                            <input type="number" class="form-control" id="km_inicio" name="km_inicio" required>
                        </div>
                        <div class="mb-3">
                            <label>Gasolina:</label>
                            <select class="form-select" id="gasolina_inicio" name="gasolina_inicio">
                                <option value="">Seleccione...</option>
                                <option value="SD">Sin Datos</option>
                                <option value="1/8">1/8</option>
                                <option value="2/8">2/8</option>
                                <option value="3/8">3/8</option>
                                <option value="4/8">4/8</option>
                                <option value="5/8">5/8</option>
                                <option value="6/8">6/8</option>
                                <option value="7/8">7/8</option>
                                <option value="8/8">8/8</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notas:</label>
                            <textarea class="form-control" id="notas_entrega" name="notas_entrega" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Foto:</label>
                            <input type="file" class="form-control" id="fotos_inicio" name="fotos_inicio[]" multiple accept="image/*">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Patron:</label>
                            <input type="text" class="form-control" id="Ppatronp" name="Ppatronp">
                        </div>
                        

                        <input type="hidden" class="form-control" id="PidPrestamop" name="PidPrestamop">
                        <input type="hidden" class="form-control" id="PidVehiculop" name="PidVehiculop">
                        <input type="hidden" class="form-control" id="PtipoActividadp" name="PtipoActividadp">                        
                        <input type="hidden" class="form-control" id="Potp" name="Potp">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-outline-success" id="btnInicioPrestamo" onclick="iniciarPrestamo()">Iniciar</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal para Finalizar Préstamo -->
    <div class="modal fade" id="modalFinalizarPrestamo" tabindex="-1" aria-labelledby="modalFinalizarPrestamoLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalFinalizarPrestamoLabel">Finalizar Préstamo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formFinalizarPrestamo" enctype="multipart/form-data">
                        <div class="mb-3">
                            <input type="hidden" class="form-control" id="fecha_entrega_final" name="fecha_entrega_final">
                            <input type="hidden" class="form-control" id="id_vehiculo" name="id_vehiculo">
                            <input type="hidden" class="form-control" id="placa" name="placa">
                            <input type="hidden" class="form-control" id="id_prestamo" name="id_prestamo"> 
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kilómetros Final:</label>
                            <input type="number" class="form-control" id="km_fin" name="km_fin" required>
                        </div>
                        <div class="mb-3">
                            <label>Gasolina:</label>
                            <select class="form-select" id="gasolina_fin" name="gasolina_fin">
                                <option value="">Seleccione...</option>
                                <option value="SD">Sin Datos</option>
                                <option value="1/8">1/8</option>
                                <option value="2/8">2/8</option>
                                <option value="3/8">3/8</option>
                                <option value="4/8">4/8</option>
                                <option value="5/8">5/8</option>
                                <option value="6/8">6/8</option>
                                <option value="7/8">7/8</option>
                                <option value="8/8">8/8</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quién recibió el auto:</label>
                            <select id="id_recibe" name="id_recibe" class="form-select" required>
                                <option value="">Seleccione</option>
                                <!-- Las opciones se cargarán dinámicamente -->
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notas:</label>
                            <textarea class="form-control" id="notas_devolucion" name="notas_devolucion" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Foto:</label>
                            <input type="file" class="form-control" id="fotos_final" name="fotos_final[]" multiple accept="image/*">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-outline-success" id="btnFinalizarPrestamo" onclick="finalizarPrestamo()">Finalizar</button>
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
        //Variables Globales
        const now = new Date();
        const fecha = now.toISOString().split('T')[0]; 
        const hora = now.toTimeString().split(' ')[0].slice(0, 5);
    $(document).ready(function() {
        infoVehiculos()
        cargarPrestamos();
        cargarPrestamosAutorizados();
        cargarPrestamosDevolucion();
        cargarPrestamosTerminados();
        cargarPrestamosOtraArea();

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
            }
        });
        $("#tablaAutorizados").DataTable({
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
            }
        });
        $("#tablaDevolucion").DataTable({
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
            }
        });
        $("#tablaTerminados").DataTable({
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
            }
        });
        
        $("#tablaPrestamosOtraArea").DataTable({
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
            }
        });

        $("#fecha").val(fecha); 
        $("#hora").val(hora);
    });
    
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

    // Función para cargar préstamos pendientes
    function cargarPrestamos() {
        const rol = getCookie('rol'); 
        $.ajax({
            type: "POST",
            url: "acciones_prestamos",
            data: { accion: "consultarPrestamos" },
            dataType: "json",
            success: function (respuesta) {
                var tablaPendientes = $("#tablaPrestamos tbody");
                tablaPendientes.empty();
                respuesta.forEach(function (prestamo) {
                    if (prestamo.estatus === "PENDIENTE") {
                        var botones = `
                            <button class="btn btn-outline-success" onclick="abrirModalAutoriza(${prestamo.id_prestamo}, 2)">
                                <ion-icon name="checkmark-outline" style="font-size: 16px;"></ion-icon>
                            </button>
                            <button class="btn btn-outline-danger" onclick="abrirModalDenegar(${prestamo.id_prestamo})">
                                <ion-icon name="close-outline" class="fs-6"></ion-icon>
                            </button>`;
                        
                        var fila = `
                            <tr>
                                <td>${prestamo.nombre_usuario}</td>
                                <td>${prestamo.fecha_inc_prestamo}</td>
                                <td>${prestamo.fecha_fin_prestamo}</td>
                                <td>${prestamo.tipo_uso}</td>
                                <td>${prestamo.detalle_tipo_uso}</td>
                                <td>${prestamo.motivo_us}</td>
                                <td>${botones}</td>
                            </tr>`;
                        tablaPendientes.append(fila);
                    }
                });
                if (rol != 3) {
                    $("#tablaPrestamos th:last-child, #tablaPrestamos td:last-child").hide(); // Ocultar columna "Acción"
                } else {
                    $("#tablaPrestamos th:last-child, #tablaPrestamos td:last-child").show(); // Mostrar columna "Acción"
                }
            },
            error: function () {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Hubo un problema al cargar los préstamos pendientes.",
                    confirmButtonText: "Aceptar"
                });
            }
        });
    }


    // Función para cargar préstamos autorizados para otra área    
    function cargarPrestamosOtraArea() {
        const rol = getCookie('rol'); 
        $.ajax({
            type: "POST",
            url: "acciones_prestamos",
            data: { accion: "consultarPrestamosOtraArea" },
            dataType: "json",
            success: function (respuesta) {
                var tablaPendientesOtraArea = $("#tablaPrestamosOtraArea tbody");
                var tipoU = '';
                tablaPendientesOtraArea.empty();
                respuesta.forEach(function (prestamo) {
                    if (prestamo.estatus === "PENDIENTEAREA") {
                        var botones = `
                            <button class="btn btn-outline-success" onclick="abrirModalAutoriza(${prestamo.id_prestamo}, 1)">
                                <ion-icon name="checkmark-outline" style="font-size: 16px;"></ion-icon>
                            </button>
                            <button class="btn btn-outline-danger" onclick="abrirModalDenegar(${prestamo.id_prestamo})">
                                <ion-icon name="close-outline" class="fs-6"></ion-icon>
                            </button>`;
                        
                        var fila = `
                            <tr>
                                <td>${prestamo.nombre_usuario}</td>
                                <td>${prestamo.fecha_inc_prestamo}</td>
                                <td>${prestamo.fecha_fin_prestamo}</td>
                                <td>${prestamo.tipo_uso}</td>
                                <td>${prestamo.detalle_tipo_uso}</td>
                                <td>${prestamo.motivo_us}</td>
                                <td>${
                                    (rol == 3 && prestamo.source_type == '1')
                                        ? botones
                                        : '<span class="text-muted">Por autorizar</span>'
                                }</td>
                            </tr>`;
                        tablaPendientesOtraArea.append(fila);
                        tipoU = prestamo.tipoU; // Guardar el tipo de uso del último préstamo
                    }
                });
                
                /*if (rol == 3 && tipoU == '1') {
                    $("#tablaPrestamosOtraArea th:last-child, #tablaPrestamosOtraArea td:last-child").show(); // Mostrar columna "Acción"                    
                } else {
                    $("#tablaPrestamosOtraArea th:last-child, #tablaPrestamosOtraArea td:last-child").hide(); // Ocultar columna "Acción"
                }*/
            },
            error: function () {
                /*Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Hubo un problema al cargar los préstamos pendientes otra area.",
                    confirmButtonText: "Aceptar"
                });*/
            }
        });
    }

    // Nueva función para cargar préstamos autorizados
    function cargarPrestamosAutorizados() {
        $.ajax({
            type: "POST",
            url: "acciones_prestamos",
            data: { accion: "actualizarPrestamo" },
            dataType: "json",
            success: function (respuesta) {
                var tablaAutorizados = $("#tablaAutorizados tbody");
                tablaAutorizados.empty(); // Limpiar la tabla antes de agregar nuevas filas
                respuesta.forEach(function (prestamo) {
                    
                    if (prestamo.accion == "VERIFICAR") {
                        var botones = `
                            <button class="btn btn-outline-success" onclick="abrirModalInicio(${prestamo.id_prestamo}, '${prestamo.placa}', '${prestamo.fecha_entrega}', ${prestamo.id_vehiculo}, '${prestamo.detalle_tipo_uso}', '${prestamo.tipo_uso}')">
                                <ion-icon name="checkmark-outline" style="font-size: 16px;"></ion-icon>
                            </button>`;
                    }
                    else{
                        var botones = 'Prestamo a otra area';
                    }
                        
                        var fila = `
                            <tr>
                                <td>${prestamo.nombre_usuario}</td>
                                <td>${prestamo.fecha_entrega}</td>
                                <td>${prestamo.placa} - ${prestamo.modelo}</td>
                                <td>${prestamo.notas_jefe}</td>
                                <td>${prestamo.tipo_uso}</td>
                                <td>${prestamo.detalle_tipo_uso}</td>
                                <td>${botones}</td>
                            </tr>`;
                        tablaAutorizados.append(fila);
                    
                });
            },
            error: function () {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Hubo un problema al cargar los préstamos autorizados.",
                    confirmButtonText: "Aceptar"
                });
            }
        });
    }

    // Nueva función para cargar préstamos en devolución
    function cargarPrestamosDevolucion() {
        $.ajax({
            type: "POST",
            url: "acciones_prestamos",
            data: { accion: "consultarPrestamosEnCurso" }, // Cambia la acción según sea necesario
            dataType: "json",
            success: function (respuesta) {
                var tablaDevolucion = $("#tablaDevolucion tbody");
                tablaDevolucion.empty(); // Limpiar la tabla antes de agregar nuevas filas
                respuesta.forEach(function (prestamo) {
                    if (prestamo.estatus === "EN CURSO") {
                        var botones = `
                            <button class="btn btn-outline-success" onclick="abrirModalFinalizar(${prestamo.id_prestamo}, '${prestamo.placa}')">
                                <ion-icon name="checkmark-outline" style="font-size: 16px;"></ion-icon>
                            </button>`;
                        var fila = `
                            <tr>
                                <td>${prestamo.fecha_inc_prestamo}</td>    
                                <td>${prestamo.placa} - ${prestamo.modelo}</td>
                                <td>${prestamo.tipo_uso}</td>
                                <td>${prestamo.detalle_tipo_uso}</td>
                                <td>${prestamo.fecha_fin_prestamo}</td>
                                <td>${botones}</td>
                            </tr>`;
                        tablaDevolucion.append(fila);
                    }
                });
            },
            error: function () {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Hubo un problema al cargar los préstamos en devolución.",
                    confirmButtonText: "Aceptar"
                });
            }
        });
    }

    // Nueva función para cargar préstamos terminados
    function cargarPrestamosTerminados() {
        $.ajax({
            type: "POST",
            url: "acciones_prestamos",
            data: { accion: "consultarPrestamosTerminados" }, // Cambia la acción según sea necesario
            dataType: "json",
            success: function (respuesta) {
                var tablaTerminados = $("#tablaTerminados tbody");
                tablaTerminados.empty(); // Limpiar la tabla antes de agregar nuevas filas
                respuesta.forEach(function (prestamo) {                    
                        var fila = `
                            <tr>
                                <td>${prestamo.fecha_inc_prestamo}</td>    
                                <td>${prestamo.placa} - ${prestamo.modelo}</td>
                                <td>${prestamo.tipo_uso}</td>
                                <td>${prestamo.detalle_tipo_uso}</td>
                                <td>${prestamo.fecha_fin_prestamo}</td>
                            </tr>`;
                        tablaTerminados.append(fila);
                    
                });
            },
            error: function () {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Hubo un problema al cargar los préstamos terminados.",
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
            data: { accion: "consultarInventarioGeneral" },
            dataType: "json",
            success: function (respuesta) {
                var select = $("#id_vehiculo");
                
                respuesta.forEach(function (vehiculo) {
                    // Define el color según el valor de vehiculo.usuario
                    let color = "";
                    switch (vehiculo.id_usuario) {
                        case leerCookie("id_usuario"):
                            color = "background-color: #ffeeba;";
                            break;                        
                        default:
                            color = "background-color:rgb(186, 201, 255);";
                    }
                    var option = `<option value="${vehiculo.id_vehiculo},${vehiculo.tipo}" style="${color}">${vehiculo.modelo} - ${vehiculo.placa} - Usr: ${vehiculo.usuario}</option>`;
                    select.append(option);
                });
            },
            error: function (xhr, status, error) {
                
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Hubo un problema al cargar los datos.",
                    confirmButtonText: "Aceptar"
                });
            }
        });
    }

    // Función para actualizar la información del vehículo MODAL
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
    
    // Función para abrir el modal de denegar préstamo
    function abrirModalDenegar(id_prestamo) {
        // Establecer el ID del préstamo en el botón del modal
        $("#btnDenegarModal").attr("data-id-prestamo", id_prestamo);
        // Mostrar el modal
        abrirModal("modalDenegarPrestamo");
    }

    // Función para autorizar un prestamo
    function abrirModalAutoriza(id_prestamo, tipo) {
        $.ajax({
            type: "POST",
            url: "acciones_prestamos",
            data: { accion: "consultarPrestamosDetalle", id_prestamo},
            dataType: "json",
            success: function (respuesta) {
                const prestamo = respuesta.find(p => p.id_prestamo == id_prestamo);
                if (prestamo) {
                    $("#placa").val(prestamo.placa);
                    $("#modelo").val(prestamo.modelo);
                    $("#color").val(prestamo.color);                    

                    // Si el estatus es 'VALIDAAREA', deshabilita el select de vehículo
                    if (prestamo.estatus === "PENDIENTEAREA") {
                        $("#id_vehiculo").prop("disabled", true);
                    } else {
                        $("#id_vehiculo").prop("disabled", false);
                    }

                    $("#id_vehiculo").val(prestamo.id_vehiculo+",EXTERNO");

                    $("#tipoP").val(tipo);
                    const fechaHora = prestamo.fecha_inc_prestamo.replace(" ", "T");
                    $("#fecha_entrega").val(fechaHora);

                    $("#btnGuardarModal").attr("data-id-prestamo", id_prestamo);                    
                    abrirModal("modalPrestamo");
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "No se encontró información del préstamo.",
                        confirmButtonText: "Aceptar"
                    });
                }
            },
            error: function () {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Hubo un problema al cargar la información del préstamo.",
                    confirmButtonText: "Aceptar"
                });
            }
        });
        //OTRA_AREA
    }

    // Función para abrir el modal de iniciar préstamo
    function abrirModalInicio(id_prestamo, placa, fechaIncPrestamo, id_vehiculo,  detalle_tipo_uso, tipo_uso) {
        $("#PidVehiculop").val(id_vehiculo);    
        $("#Potp").val(detalle_tipo_uso);
        $("#PtipoActividadp").val(tipo_uso);        
        $("#PidPrestamop").val(id_prestamo);
        
        $("#placa_ini").val(placa);
        $("#id_prestamo").val(id_prestamo);
        $("#id_vehiculo").val(id_vehiculo);

        if (fechaIncPrestamo) {
            const fechaInicio = fechaIncPrestamo.replace(" ", "T"); 
            $("#fecha_entrega_inicio").val(fechaInicio);
        } else {
            $("#fecha_entrega_inicio").val(""); 
        }
        abrirModal("modalInicioPrestamo"); 
    }

    // Función para abrir el modal de finalizar préstamo
    function abrirModalFinalizar(id_prestamo, placa) {
        $("#id_prestamo").val(id_prestamo);
        $("#placa").val(placa);
        cargarUsuarios();
        abrirModal("modalFinalizarPrestamo");
    }

    // Función para enviar el formulario de autorización el préstamo
    function autorizarPrestamo(id_prestamo) {
        var id_prestamo = $("#btnGuardarModal").attr("data-id-prestamo");
        var id_vehiculo = $("#id_vehiculo").val();
        var fecha_entrega = $("#fecha_entrega").val();
        var notas_jefe = $("#notas_jefe").val();
        var tipo_uso = $("#tipoP").val();
        var accion = "autorizarPrestamo"; 
        
        $.ajax({
            type: "POST",
            url: "acciones_prestamos",
            data: {
                accion,
                id_prestamo,
                id_vehiculo,
                fecha_entrega,
                notas_jefe,
                tipo_uso
            },
            dataType: "json",
            success: function (respuesta) {
                if (respuesta.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Éxito",
                        timer: 3000,
                        confirmButtonText: "Aceptar"
                    });
                    cargarPrestamos();
                    cargarPrestamosAutorizados();
                    cargarPrestamosDevolucion();
                    cargarPrestamosTerminados();
                    cargarPrestamosOtraArea();
                    cerrarModal("modalPrestamo");
                    document.getElementById("formModalPrestamo").reset();
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

    // Función para enviar el formulario de denegar el préstamo 
    function denegarPrestamo(id_prestamo) {
        var id_prestamo = $("#btnDenegarModal").attr("data-id-prestamo");
        var notas_denegar = $("#notas_denegar").val();

        $.ajax({
            type: "POST",
            url: "acciones_prestamos",
            data: {
                accion: "denegarPrestamo",
                id_prestamo,
                notas_denegar
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
                        cerrarModal("modalDenegarPrestamo");
                        $("#notas_denegar").val("");
                        document.getElementById("formModalDenegarPrestamo").reset();
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

    // Función para enviar el formulario de iniciar el préstamo
    function iniciarPrestamo(id_prestamo) {
        var formData = new FormData();
        formData.append("accion", "iniciarPrestamo");
        formData.append("id_prestamo", $("#id_prestamo").val());
        formData.append("km_inicio", $("#km_inicio").val());
        formData.append("gasolina_inicio", $("#gasolina_inicio").val());
        formData.append("notas_entrega", $("#notas_entrega").val());
        formData.append("placa", $("#placa_ini").val());
        formData.append("id_vehiculo", $("#PidVehiculop").val());
        var fotos = document.getElementById("fotos_inicio").files;

        for (var i = 0; i < fotos.length; i++) {
            formData.append("fotos_inicio[]", fotos[i]);
        }
        $.ajax({
            type: "POST",
            url: "acciones_prestamos",
            data: formData,
            processData: false, 
            contentType: false,
            dataType: "json",
            success: function (respuesta) {
                if (respuesta.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Éxito",
                        text: respuesta.message,
                        timer: 3000,
                        confirmButtonText: "Aceptar"
                    }).then(() => {
                        cargarPrestamos();
                        cargarPrestamosAutorizados();
                        cargarPrestamosDevolucion();
                        cargarPrestamosTerminados();
                        cargarPrestamosOtraArea();
                        cerrarModal("modalInicioPrestamo");
                        document.getElementById("formInicioPrestamo").reset();
                    });
                    checkIn(); // Llamar a la función checkIn después de iniciar el préstamo
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: respuesta.message,
                        confirmButtonText: "Aceptar"
                    });
                }
            },
            error: function (xhr, status, error) {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Hubo un problema al iniciar el préstamo.",
                    confirmButtonText: "Aceptar"
                });
            }
        });
    }

    function checkIn() {            
            // Obtener los valores de los campos del formulario
            var vehiculoAsignado = $('#PidVehiculop').val();
            var otRelacionada = $('#Potp').val();
            var kmActual = $('#km_inicio').val();
            var patronRelacionado = $('#Ppatronp').val();
            var notasCheckin = $('#notas_entrega').val();
            var gasActual = $('#gasolina_inicio').val();            
            var imgCheckin = $('#fotos_inicio')[0].files[0];
            var tipoServicio = $('#PtipoActividadp').val();
            var id_prestamo = $('#PidPrestamop').val();
            // Validar que el tipo de servicio sea válido               
            // Validar que los campos no estén vacíos
            /*if (!vehiculoAsignado || !kmActual || !gasActual || !tipoServicio || !otRelacionada) {
            $('#msgKm').text('Por favor, complete todos los campos obligatorios.');
            return;
            }*/

            var formData = new FormData();
            formData.append('id_prestamo', id_prestamo); // Asignar un ID de préstamo por defecto
            formData.append('accion', 'CapturaCheckIn');
            formData.append('vehiculoAsignado', vehiculoAsignado);
            formData.append('otRelacionada', otRelacionada);
            formData.append('kmActual', kmActual);
            formData.append('patronRelacionado', patronRelacionado);
            formData.append('notasCheckin', notasCheckin);
            formData.append('gasActual', gasActual);
            formData.append('tipoServicio', tipoServicio);

            if (imgCheckin) {
            formData.append('imgCheckin', imgCheckin);
            }

            $.ajax({
            url: 'acciones_kilometraje.php',
            method: 'POST',
            dataType: 'json',
            data: formData,
            processData: false,
            contentType: false,
            success: function (resp) {
                Swal.fire({
                title: "¡Guardado!",
                text: "Kilometraje registrado correctamente.",
                icon: "success",
                timer: 2000,
                timerProgressBar: true
                }).then(function () {
                $('#formCapturaKm')[0].reset();
                $('#capturaKmModal').modal('hide');
                $('#msgKm').text('');
                });
            },
            error: function () {
                $('#msgKm').text('Error al guardar el kilometraje.');
            }
            });
        }
    // Función para enviar el formulario de finalizar el préstamo
    function finalizarPrestamo() {
        var id_prestamo = $("#id_prestamo").val(); 
        var id_recibe = $("#id_recibe").val();
        var fotos = document.getElementById("fotos_final").files;
        var formData = new FormData();
        formData.append("accion", "finalizarPrestamo");
        formData.append("id_prestamo", id_prestamo);
        formData.append("km_fin", $("#km_fin").val());
        formData.append("gasolina_fin", $("#gasolina_fin").val());
        formData.append("notas_devolucion", $("#notas_devolucion").val());
        formData.append("id_recibe", id_recibe);
        formData.append("placa", $("#placa").val());
        formData.append("id_vehiculo", $("#PidVehiculop").val());
        // Agregar las imágenes seleccionadas al FormData
        for (var i = 0; i < fotos.length; i++) {
            formData.append("fotos_final[]", fotos[i]);
        }
        $.ajax({
            type: "POST",
            url: "acciones_prestamos",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function (respuesta) {
                if (respuesta.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Éxito",
                        text: respuesta.message,
                        timer: 3000,
                        confirmButtonText: "Aceptar"
                    }).then(() => {
                        cargarPrestamos();
                        cargarPrestamosAutorizados();
                        cargarPrestamosDevolucion();
                        cargarPrestamosTerminados();
                        cargarPrestamosOtraArea();
                        cerrarModal("modalFinalizarPrestamo");
                        document.getElementById("formFinalizarPrestamo").reset();
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: respuesta.message,
                        confirmButtonText: "Aceptar"
                    });
                }
            },
            error: function (xhr, status, error) {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Hubo un problema al finalizar el préstamo.",
                    confirmButtonText: "Aceptar"
                });
            }
        });
    }

    // Función para cargar los usuarios con rol 3
    function cargarUsuarios() {
        $.ajax({
            type: "POST",
            url: "acciones_prestamos",
            data: { accion: "consultarUsuariosRecibe" },
            dataType: "json",
            success: function (usuarios) {
                var select = $("#id_recibe");
                select.empty();
                select.append('<option value="">Seleccione</option>');
                usuarios.forEach(function (usuario) {
                    select.append(`<option value="${usuario.id_usuario}">${usuario.nombre}</option>`);
                });
            },
            error: function () {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Hubo un problema al cargar los usuarios.",
                    confirmButtonText: "Aceptar"
                });
            }
        });
    }

    // Función para abrir un modal
    function abrirModal(idModal) {
        const modal = new bootstrap.Modal(document.getElementById(idModal));
        modal.show();
    }

    // Función para cerrar un modal
    function cerrarModal(idModal) {
        const modal = bootstrap.Modal.getInstance(document.getElementById(idModal));
        modal.hide();
        if (idModal === "modalFinalizarPrestamo") {
            document.getElementById("formFinalizarPrestamo").reset(); // Limpia el formulario
        }
    }
    /**
     * Lee el valor de una cookie por nombre.
     * @param {string} nombre - El nombre de la cookie.
     * @returns {string|null} El valor de la cookie o null si no existe.
     */
    function leerCookie(nombre) {
        let cookies = document.cookie.split(';');
        for (let i = 0; i < cookies.length; i++) {
            let cookie = cookies[i].trim();
            if (cookie.startsWith(nombre + '=')) {
                return decodeURIComponent(cookie.substring(nombre.length + 1));
            }
        }
        return null;
    }
    </script>
</body>
</html>