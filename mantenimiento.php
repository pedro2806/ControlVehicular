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
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        @keyframes pulso-vehiculo {
            0%   { box-shadow: 0 0 0 0 rgba(78, 115, 223, 0.7); border-color: var(--accent); }
            70%  { box-shadow: 0 0 3px 3px rgba(78, 115, 223, 0); border-color: var(--accent); }
            100% { box-shadow: 0 0 0 0 rgba(78, 115, 223, 0); border-color: var(--accent); }
        }
        .select2-selection.select-pulso {
            animation: pulso-vehiculo 1.4s ease-in-out infinite;
            border: 2px solid var(--accent) !important;
        }
    </style>
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
                        <h1 class = "h3 mb-0 text-black-800">Registro de Mantenimiento</h1>                        
                    </div>
                    <!-- FORMULARIO DEL MANTENIMIENTO -->
                    <form id="formRegistroMantenimiento">
                        <input type="hidden" id="fecha" name="fecha">
                        <input type="hidden" id="hora" name="hora">
                        <input type="hidden" id="id_vehiculo" name="id_vehiculo">
                        <span id="placaVehiculo" style="display:none;"></span>

                        <!-- Detalles del Servicio -->
                        <h1 class="h5 mb-2 text-black" style="font-weight: bold;">Detalles del Servicio</h1>
                        <div class="row mb-3">
                            <div class="col-lg-3 col-md-6 col-sm-6 col-12 mb-2">
                                <label>Vehículo:</label>
                                <select class="form-select" id="vehiculo_select" name="vehiculo_select" required>
                                    <option value="">Seleccione...</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-12 mb-2">
                                <label>Tipo de Servicio:</label>
                                <select class="form-select" id="servicio" name="servicio" required>
                                    <option value="">Seleccione...</option>
                                    <option value="Preventivo">Preventivo</option>
                                    <option value="Correctivo">Correctivo</option>
                                    <option value="Verificacion">Verificación</option>
                                    <option value="Cambio de llantas">Cambio de llantas</option>
                                </select>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-12 mb-2">
                                <label>Proveedor:</label>
                                <input class="form-control" id="proveedor" name="proveedor" type="text" placeholder="Nombre del proveedor">
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-12 mb-2">
                                <label>Contacto del proveedor:</label>
                                <input class="form-control" id="contacto_proveedor" name="contacto_proveedor" type="text" placeholder="Teléfono o correo">
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col-12">
                                <label>Descripción:</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required></textarea>
                            </div>
                        </div>

                        <!-- Info llantas (visible solo con Cambio de llantas) -->
                        <div id="info_llantas" class="alert alert-info py-2 mb-3" style="display:none;">
                            <strong><i class="fas fa-circle-info"></i> Último registro de llantas:</strong>
                            <span class="ms-3">Rin: <strong id="llantas_rin">—</strong></span>
                            <span class="ms-3">Medidas: <strong id="llantas_medidas">—</strong></span>
                        </div>

                        <div class="text-center">
                            <button type="button" class="btn btn-primary" onclick="RegistrarMantenimiento()">Guardar</button>
                        </div>
                    </form>
                    <br>
                </div>
            </div>
            <footer class = "sticky-footer bg-white">
                <div class = "container my-auto">
                    <div class = "copyright text-center my-auto">
                        <span>Copyright &copy; MESS<?php echo date("Y"); ?></span>
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
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            // Inicializar Select2 de inmediato en estado "cargando" para evitar el salto visual
            $('#vehiculo_select')
                .select2({ placeholder: 'Cargando vehículos...', width: '100%', allowClear: true })
                .prop('disabled', true);
            $('#vehiculo_select').next('.select2-container').find('.select2-selection').addClass('select-pulso');
            $('#vehiculo_select').on('change', function() { alSeleccionarVehiculo(this); });

            infoVehiculos();
            cargarUsuarios();
            const now = new Date();
            const fecha = now.toISOString().split('T')[0];
            const hora = now.toTimeString().split(' ')[0].slice(0, 5);
            $("#fecha").val(fecha);
            $("#hora").val(hora);
        });
        
        // FUNCION PARA CARGAR INFORMACIÓN DE LOS VEHÍCULOS
        function infoVehiculos() {
            $.ajax({
                type: "POST",
                url: "acciones_siniestro",
                data: { accion: "consultarInventario" },
                dataType: "json",
                success: function (respuesta) {
                    var select = $("#vehiculo_select");
                    Array.isArray(respuesta) && respuesta.forEach(function (v) {
                        select.append(`<option value="${v.id_vehiculo}" data-placa="${v.placa}">${v.placa} - ${v.modelo} ${v.marca}</option>`);
                    });
                    select.prop('disabled', false)
                          .select2({ placeholder: 'Buscar vehículo...', width: '100%', allowClear: true });
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

        $("#servicio").on("change", function() {
            $("#info_llantas").hide();
            if ($(this).val() !== "Cambio de llantas") return;

            var id_vehiculo = $("#id_vehiculo").val();
            if (!id_vehiculo) {
                Swal.fire({ icon: "warning", title: "Vehículo requerido", text: "Selecciona primero el vehículo.", confirmButtonText: "Aceptar" });
                $(this).val("");
                return;
            }

            $.ajax({
                type: "POST",
                url: "acciones_mantenimiento",
                data: { accion: "infoLlantas", id_vehiculo: id_vehiculo },
                dataType: "json",
                success: function(r) {
                    function tieneValor(v) {
                        return v && v.trim() !== '' && v.trim().toUpperCase() !== 'S/R';
                    }
                    if (r.found && tieneValor(r.no_rin) && tieneValor(r.medidas)) {
                        $("#llantas_rin").text(r.no_rin);
                        $("#llantas_medidas").text(r.medidas);
                        $("#info_llantas").show();
                    } else {
                        Swal.fire({
                            icon: "warning",
                            title: "Sin datos de llantas",
                            text: "No se encontró información de rin y medidas para este vehículo. Por favor llena el checklist de llantas antes de registrar este mantenimiento.",
                            showCancelButton: true,
                            confirmButtonText: "Ir al Checklist",
                            cancelButtonText: "Cerrar",
                            confirmButtonColor: "#4e73df"
                        }).then(function(result) {
                            if (result.isConfirmed) {
                                window.location.href = "checkVehiculo";
                            }
                        });
                    }
                }
            });
        });

        function alSeleccionarVehiculo(sel) {
            var opt = sel.options[sel.selectedIndex];
            $("#id_vehiculo").val(opt.value);
            $("#placaVehiculo").text($(opt).data("placa") || "");
            $("#proveedor").val("");
            $("#contacto_proveedor").val("");
            $("#info_llantas").hide();
            var $sel2 = $("#vehiculo_select").next('.select2-container').find('.select2-selection');
            if (opt.value) {
                $sel2.removeClass("select-pulso");
            } else {
                $sel2.addClass("select-pulso");
            }
        }
        
        // Cargar la lista de usuarios
        function cargarUsuarios() {
            $.ajax({
                type: "POST",
                url: "acciones_mantenimiento",
                data: { accion: "consultarUsuarios" },
                dataType: "json",
                success: function (respuesta) {
                    var select = $("#id_dueno");
                    select.empty(); // Limpiar las opciones existentes
                    select.append('<option value="">Seleccione un propietario...</option>'); // Opción por defecto
                    Array.isArray(respuesta) && respuesta.forEach(function (usuario) {
                        select.append(`<option value="${usuario.id_usuario}">${usuario.nombre}</option>`);
                    });
                },
                error: function () {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "Hubo un problema al cargar la lista de usuarios.",
                        confirmButtonText: "Aceptar"
                    });
                }
            });
        }
        
       

        //FUNCION REGISTRO DE MANTENIMIENTO
        function RegistrarMantenimiento() {
            var id_vehiculo = $("#id_vehiculo").val();
            var fecha_registro = $("#fecha").val();
            var tipo_mantenimiento = $("#servicio").val();
            var descripcion = $("#descripcion").val();
            var solicitante = getCookie("id_usuario");
            var proveedor = $("#proveedor").val();
            var contacto_proveedor = $("#contacto_proveedor").val();
            var placa = $("#placaVehiculo").text().trim();

            var camposFaltantes = [];
            if (!placa) camposFaltantes.push("Vehículo seleccionado");
            if (!tipo_mantenimiento) camposFaltantes.push("Tipo de servicio");
            if (!descripcion) camposFaltantes.push("Descripción");

            if (camposFaltantes.length > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campos incompletos',
                    html: `<p>Por favor, completa los siguientes campos:</p><ul>${camposFaltantes.map(c => `<li>${c}</li>`).join('')}</ul>`,
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            var $btn = $('button[onclick="RegistrarMantenimiento()"]');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando...');

            $.ajax({
                type: "POST",
                url: "acciones_mantenimiento",
                data: { accion: "RegistrarMantenimiento", id_vehiculo, fecha_registro,
                        tipo_mantenimiento, descripcion, solicitante, proveedor, contacto_proveedor },
                dataType: 'json',
                success: function (respuesta) {
                    if (!respuesta.success) {
                        $btn.prop('disabled', false).html('Guardar');
                        Swal.fire({ icon: 'error', title: 'Error', text: respuesta.message, confirmButtonText: 'Aceptar' });
                        return;
                    }
                    // Enviar correo sin esperar respuesta
                    $.ajax({ type: "POST", url: "correoMantenimiento.php", data: {} });
                    window.location.replace("seguimiento_mantenimiento");
                },
                error: function () {
                    $btn.prop('disabled', false).html('Guardar');
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Hubo un problema al registrar el mantenimiento.', confirmButtonText: 'Aceptar' });
                }
            });
        }

        //FUNCION PARA MOSTRAR CAMPO DEL DUEÑO DEL VEHICULO
        function mostrarCampoDueno() {
            var tipo_carro = $("#tipo_carro").val();
            var campo_dueno = $("#campo_dueno");

            if (tipo_carro === "Prestado" || tipo_carro === "Asignado") {
                campo_dueno.show(); // Mostrar el campo
                $("#id_dueno").attr("required", true); // Hacer el campo obligatorio
            } else {
                campo_dueno.hide(); // Ocultar el campo
                $("#id_dueno").removeAttr("required"); // Quitar la obligatoriedad
            }
        }

        // Función para denegar un mantenimiento
        function denegarMantenimiento(id_mantenimiento) {
            // Guardar el ID del mantenimiento en un atributo del modal
            $("#modalMantenimiento").data("id_mantenimiento", id_mantenimiento);
            $("#modalMantenimiento").data("accion", "denegarMantenimiento");
            $("#modalMantenimientoLabel").text("Denegar Mantenimiento");
            $("#btnGuardarModal").text("Guardar");

            // Deshabilitar el campo de fecha y asignar la fecha actual
            const now = new Date();
            const fechaActual = now.toISOString().split("T")[0]; // Formato YYYY-MM-DD
            $("#fecha_programada").val(fechaActual).prop("disabled", true); // Asignar fecha y deshabilitar el campo

            // Ocultar el campo de fecha de registro
            $("#fecha_programada").closest(".form-group").hide();
            new bootstrap.Modal(document.getElementById('modalMantenimiento')).show();
        }
    </script>
</body>
</html>