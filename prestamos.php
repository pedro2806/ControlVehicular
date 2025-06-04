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
                        <h1 class = "h3 mb-0 text-black-800">Solicitud de Prestamo Vehicular</h1>                        
                    </div>
                    <!-- FORMULARIO DEL PRESTAMO-->
                    <form id="formRegistroPrestamo">
                        <!-- Content Row -->
                        <div class = "row">
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Fecha Registro:</label>
                                <input type = "date" class = "form-control" id = "fecha" name = "fecha" readonly>
                            </div>
                            <br>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Contacto Tel:</label>  
                                <input class="form-control" id="contacto" name="contacto" type="tel" required>
                            </div>
                            <br>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Fecha Inicio Prestamo:</label>
                                <input type="datetime-local" class="form-control" id="fecha_inc_prestamo" name="fecha_inc_prestamo" required>
                            </div>
                            <br>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Fecha Fin Prestamo:</label>
                                <input type="datetime-local" class="form-control" id="fecha_fin_prestamo" name="fecha_fin_prestamo" required>
                            </div>
                            <br>
                        </div>
                        <div class = "row">
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Tipo de uso:</label>
                                <select class="form-select" id="visita_vinculada" name="visita_vinculada" required>
                                    <option value="" disabled selected>Seleccione una opción</option>
                                    <option value="Entrega">Entrega</option>
                                    <option value="Recoleccion">Recolección</option>
                                    <option value="Prospeccion">Prospección</option>
                                    <option value="Negociacion">Negociación</option>
                                    <option value="Proyecto">Proyecto</option>
                                    <option value="OV">OV</option>
                                    <option value="OT">OT</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>OV/Cliente/OT/Proyecto:</label>
                                <input type="text" id = "dato" name = "dato" class="form-control" required>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Destino:</label>
                                <input type="text" id = "destino" name = "destino" class="form-control" required>
                            </div>
                        </div>
                        <br>
                        <div class = "row">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                                <label>Motivo:</label>
                                <textarea class = "form-control" id = "motivo" name = "motivo" required></textarea>
                            </div>
                        </div>
                        <br>
                        <input type="hidden" id = "id_checklist" name = "id_checklist">
                        <input type="hidden" id = "id_usuario" name = "id_usuario" value="<?php echo $_SESSION['id_usuario']; ?>">
                        <center>
                            <button type="button" class="btn btn-outline-success" onclick="RegistrarPrestamo()">Guardar</button>
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
            // Llenar automáticamente los campos de fecha y hora
            const now = new Date();
            const fecha = now.toISOString().split('T')[0]; // Formato YYYY-MM-DD
            const hora = now.toTimeString().split(' ')[0].slice(0, 5); // Formato HH:MM

            $("#fecha").val(fecha); // Establecer la fecha actual
            $("#hora").val(hora); // Establecer la hora actual

        });

        //FUNCION PARA CARGAR INFORMACIÓN DE LOS VEHÍCULOS
        function infoVehiculos() {
            $.ajax({
                type: "POST",
                url: "acciones_siniestro",
                data: { accion: "consultarInventario" },
                dataType: "json",
                success: function (respuesta) {
                    var select = $("#id_vehiculo");
                    select.find("option:not(:first)").remove();
                    respuesta.forEach(function (vehiculo) {
                        var option = `<option value="${vehiculo.id_vehiculo}" data-checklist="${vehiculo.id_checklist}">${vehiculo.modelo} - ${vehiculo.placa}</option>`;
                        select.append(option);
                    });

                    // Asignar el id_checklist al campo oculto cuando se selecciona un vehículo
                    select.on("change", function () {
                        var selectedOption = $(this).find(":selected");
                        var idChecklist = selectedOption.data("checklist");
                        $("#id_checklist").val(idChecklist); // Asignar el valor al campo oculto
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

        //FUNCION REGISTRO DEl PRESTAMO
        function RegistrarPrestamo() {
            var fecha_registro = $("#fecha").val();
            var contacto = $("#contacto").val();
            var fecha_inc_prestamo = $("#fecha_inc_prestamo").val();
            var fecha_fin_prestamo = $("#fecha_fin_prestamo").val();            
            var id_usuario = getCookie("id_usuario");
            var id_checklist = $("#id_checklist").val();
            var motivo = $("#motivo").val();
            var accion = "RegistrarPrestamo";
            var tipo_uso = $("#visita_vinculada").val();
            var detalle_tipo_uso = $("#dato").val();
            var destino = $("#destino").val();

            // Validar campos obligatorios generales
            if (!contacto || !fecha_inc_prestamo || !fecha_fin_prestamo) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Algun campo no seleccionado',
                    text: 'Por favor, completa el formulario antes de continuar.',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            $.ajax({
                type: "POST",
                url: "acciones_prestamos",
                data: { fecha_registro, contacto, fecha_inc_prestamo, fecha_fin_prestamo, id_usuario, id_checklist, motivo, accion, detalle_tipo_uso, tipo_uso, destino },
                dataType: "json",
                success: function (respuesta) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: 'Solicitud de prestamo registrado exitosamente.',
                        timer: 3000,
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        $("#formRegistroPrestamo")[0].reset();
                        window.location.replace("autorizar_prestamo");
                    });
                },
                error: function (xhr, status, error) {                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Hubo un problema al registrar el prestamo.',
                        confirmButtonText: 'Aceptar'
                    });
                }
            });
        }
    </script>
</body>
</html>