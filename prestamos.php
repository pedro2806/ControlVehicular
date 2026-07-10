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
                <div class="container-fluid">    
                    <div class="card shadow-sm border-0">
                        <div class="card-header text-bg-secondary">
                            Solicitud de Préstamo Vehicular
                        </div>
                        <div class="card-body p-2 p-md-2">
                            <form id="formRegistroPrestamo">
                                <input type="hidden" id="fecha" name="fecha">
                                <input type="hidden" id="id_checklist" name="id_checklist">
                                <input type="hidden" id="id_usuario" name="id_usuario" value="<?php echo $_COOKIE['id_usuario']; ?>">

                                <!-- Vehículo + Tipo de uso -->
                                <div class="row mb-3">
                                    <div class="col-lg-4 col-md-6 mb-3 mb-md-0">
                                        <label class="form-label fw-semibold">Vehículo</label>
                                        <select id="id_vehiculo" name="id_vehiculo" class="form-select select2" required>
                                            <option value="">Seleccione...</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-4 col-md-6 mb-3 mb-md-0">
                                        <label class="form-label fw-semibold">Tipo de uso</label>
                                        <select class="form-select" id="visita_vinculada" name="visita_vinculada" required onchange="toggleDato()">
                                            <option value="" disabled selected>Seleccione...</option>
                                            <option value="Entrega">Entrega</option>
                                            <option value="Recoleccion">Recolección</option>
                                            <option value="Prospeccion">Prospección</option>
                                            <option value="Negociacion">Negociación</option>
                                            <option value="Proyecto">Proyecto</option>
                                            <option value="OV">OV</option>
                                            <option value="OT">OT</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-4 col-md-12">
                                        <label class="form-label fw-semibold">Destino</label>
                                        <input type="text" id="destino" name="destino" class="form-control" placeholder="Ciudad / dirección..." required>
                                    </div>
                                    <div class="col-lg-4 col-md-6" id="colDato" style="display:none;">
                                        <label class="form-label fw-semibold" id="labelDato">OV / Cliente / OT / Proyecto</label>
                                        <input type="text" id="dato" name="dato" class="form-control" placeholder="Número o nombre...">
                                    </div>
                                </div>

                                <!-- Bloque de fechas compacto -->
                                <div class="row mb-3">
                                    <div class="col-lg-6 col-md-12">
                                        <label class="form-label fw-semibold"><i class="fas fa-calendar-alt me-1 text-primary"></i>Período del préstamo</label>
                                        <div class="card border-primary">
                                            <div class="card-body py-2 px-3">
                                                <div class="d-flex align-items-center gap-3 flex-wrap">
                                                    <div>
                                                        <small class="text-muted d-block mb-1">Inicio</small>
                                                        <input type="datetime-local" class="form-control form-control-sm" id="fecha_inc_prestamo" name="fecha_inc_prestamo" required>
                                                    </div>
                                                    <i class="fas fa-arrow-right text-primary mt-3"></i>
                                                    <div>
                                                        <small class="text-muted d-block mb-1">Fin</small>
                                                        <input type="datetime-local" class="form-control form-control-sm" id="fecha_fin_prestamo" name="fecha_fin_prestamo" required>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-12">
                                        <label class="form-label fw-semibold">Motivo</label>
                                        <textarea class="form-control" id="motivo" name="motivo" rows="2" placeholder="Describe el motivo del préstamo..." required></textarea>
                                    </div>
                                </div>


                                <div class="text-right mt-3">
                                    <button type="button" class="btn btn-primary px-4" onclick="RegistrarPrestamo()">
                                        <i class="fas fa-save me-1"></i> Guardar solicitud
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
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
    <!-- Core plugin JavaScript-->
    <script src = "vendor/jquery-easing/jquery.easing.min.js"></script>
    <!-- Custom scripts for all pages-->
    <script src = "js/sb-admin-2.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            $('#id_vehiculo')
                .select2({ placeholder: 'Cargando vehículos...', width: '100%', allowClear: true })
                .prop('disabled', true);

            infoVehiculos();

            const now = new Date();
            $("#fecha").val(now.toISOString().split('T')[0]);
        });

        //FUNCION PARA CARGAR INFORMACIÓN DE LOS VEHÍCULOS
        function infoVehiculos() {
            $.ajax({
                type: "POST",
                url: "acciones_siniestro",
                data: { accion: "consultarInventarioGeneral" },
                dataType: "json",
                success: function (respuesta) {
                    var select = $("#id_vehiculo");
                    Array.isArray(respuesta) && respuesta.forEach(function (vehiculo) {
                        var color = vehiculo.tipo === 'AREA' ? 'background-color:#ffeeba;'
                                  : vehiculo.tipo === 'EXTERNO' ? 'background-color:rgb(186,201,255);' : '';
                        select.append(`<option value="${vehiculo.id_vehiculo}" style="${color}">${vehiculo.modelo} - ${vehiculo.placa} - Usr: ${vehiculo.usuario}</option>`);
                    });
                    select.prop('disabled', false)
                          .select2({ placeholder: 'Buscar vehículo...', width: '100%', allowClear: true });
                },
                error: function () {
                    Swal.fire({ icon: "error", title: "Error", text: "Hubo un problema al cargar los datos.", confirmButtonText: "Aceptar" });
                }
            });
        }

        function toggleDato() {
            var tipo = $("#visita_vinculada").val();
            var necesitaDato = ['OV', 'OT', 'Proyecto'].includes(tipo);
            var labels = { OV: 'Número de OV', OT: 'Número de OT', Proyecto: 'Nombre del proyecto' };
            if (necesitaDato) {
                $("#labelDato").text(labels[tipo] || 'OV / Cliente / OT / Proyecto');
                $("#colDato").show();
                $("#dato").prop('required', true);
            } else {
                $("#colDato").hide();
                $("#dato").prop('required', false).val('');
            }
        }

        //FUNCION REGISTRO DEl PRESTAMO
        function RegistrarPrestamo() {            
            var fecha_registro = $("#fecha").val();            
            var fecha_inc_prestamo = $("#fecha_inc_prestamo").val();
            var fecha_fin_prestamo = $("#fecha_fin_prestamo").val();
            var id_usuario = getCookie("id_usuario");
            var id_checklist = $("#id_checklist").val();
            var motivo = $("#motivo").val();
            var accion = "RegistrarPrestamo";
            var tipo_uso = $("#visita_vinculada").val();            
            var destino = $("#destino").val();
            var id_vehiculo = $("#id_vehiculo").val();
            // Validar campos obligatorios generales
            if (!fecha_inc_prestamo || !fecha_fin_prestamo || !id_vehiculo) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campos incompletos',
                    text: 'Por favor, completa todos los campos antes de continuar.',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }
            if (fecha_fin_prestamo <= fecha_inc_prestamo) {
                Swal.fire({ icon: 'warning', title: 'Fechas inválidas', text: 'La fecha de fin debe ser posterior a la de inicio.', confirmButtonText: 'Aceptar' });
                return;
            }

            $.ajax({
                type: "POST",
                url: "acciones_prestamos",
                data: { fecha_registro, fecha_inc_prestamo, fecha_fin_prestamo, id_usuario, id_checklist, motivo, accion, destino, id_vehiculo, tipo_uso },
                dataType: "json",
                success: function (respuesta) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: 'Solicitud de prestamo registrado exitosamente.',
                        timer: 3000,
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        // Ejecutar correoPrestamo.php antes de redirigir
                        /*$.ajax({
                            type: "POST",
                            url: "correoPrestamo.php",
                            data: { },
                            complete: function() {
                                window.location.replace("autorizar_prestamo");
                            }
                        });*/
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

        //FUNCION PARA LEER COOKIES
    </script>
</body>
</html>