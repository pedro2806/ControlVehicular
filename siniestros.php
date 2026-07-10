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
                        <h1 class = "h3 mb-0 text-black-800">Registro de Siniestro</h1>                        
                    </div>
                    <!-- TABLA DE VEHICULOS -->
                    <div class="container">
                        <table id="tablaInventario" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Placa</th>
                                    <th>Mod/Marca</th>
                                    <th>Color</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Las filas se cargarán dinámicamente -->
                            </tbody>
                        </table>
                    </div>

                    <!-- CONTENEDOR INFO AUTO -->
                    <div class="card shadow-sm mb-3" id="placaSeleccionada" style="display:none;">
                        <div class="card-body py-3 px-3">
                            <div class="d-flex align-items-center">
                                <div style="width:60px;height:60px;border-radius:8px;background:var(--card-soft);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                    <i class="fas fa-car fa-2x text-muted"></i>
                                </div>
                                <div class="ml-3 flex-grow-1">
                                    <h5 class="mb-0 font-weight-bold text-primary" id="infoPlacaSin"></h5>
                                    <span class="text-dark" id="infoModeloMarcaSin"></span><br>
                                    <small class="text-muted" id="infoColorSin"></small>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="cambiarVehiculo()">
                                        <i class="fas fa-exchange-alt me-1"></i> Cambiar Vehículo
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- FORMULARIO DEL SINIESTRO -->
                    <form id="formRegistroSiniestro" style="display: none;">
                        <input type="hidden" id="tipo_carro" name="tipo_carro" value="S/R">
                        <!-- Ubicación -->
                        <h1 class="h5 mb-2 text-gray-800">Ubicación</h1>
                        <div class="row mb-3">
                            <div class="col-lg-3 col-md-6 col-sm-6 col-12 mb-2">
                                <label>Fecha:</label>
                                <input type="date" class="form-control" id="fecha" name="fecha" required>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-12 mb-2">
                                <label>Hora:</label>
                                <input type="time" class="form-control" id="hora" name="hora" required>
                            </div>
                            <div class="col-lg-6 col-md-12 col-sm-12 col-12 mb-2">
                                <label>Lugar del siniestro:</label>
                                <input class="form-control" id="lugar" name="lugar" placeholder="Ej. Blvd. Independencia esq. Av. Universidad" required>
                            </div>
                        </div>
                        <!-- Content Row -->
                        <h1 class="h5 mb-2 text-gray-800">Detalles del Automóvil</h1>
                        <div class="row mb-3">
                            <div class="col-lg-3 col-md-6 col-sm-6 col-12 mb-2">
                                <label>Ubicación del vehículo:</label>
                                <select class="form-select" id="ubicacion" name="ubicacion" required onchange="toggleUbicacionOtro()">
                                    <option value="">Seleccione...</option>
                                    <option value="MESS">MESS</option>
                                    <option value="Mecánico">Mecánico</option>
                                    <option value="Corralón">Corralón</option>
                                    <option value="Otro">Otro</option>
                                </select>
                                <input class="form-control mt-1" id="ubicacion_otro" name="ubicacion_otro" placeholder="Especificar..." style="display:none;">
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-12 mb-2">
                                <label>Partes Dañadas:</label>
                                <input class="form-control" id="daños" name="daños" required>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-12 col-12 mb-2">
                                <label>Descripción:</label>
                                <input type="text" class="form-control" id="descripcion" name="descripcion" required>
                            </div>
                        </div>
                        <div id="contenedorFotos" class="mb-2">
                            <label>Fotos del Siniestro: <small class="text-muted">(opcional, máx. 4)</small></label>
                            <div class="input-group mb-2">
                                <input type="file" class="form-control foto-siniestro" name="foto[]" accept="image/*" capture="environment">
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="btnAgregarFoto" onclick="agregarCampoFoto()">
                            <i class="fas fa-plus"></i> Agregar Foto
                        </button>
                        <input type="hidden" id="coordenadas" name="coordenadas">
                        <input type="hidden" id="id_vehiculo" name="id_vehiculo">
                        <div class="text-center">
                            <button type="button" class="btn btn-primary" onclick="RegistrarSiniestro()">Guardar</button>
                        </div>
                    </form>
                    <br>
                </div>
            </div>
            <footer class = "sticky-footer bg-white">
                <div class = "container my-auto">
                    <div class = "copyright text-center my-auto">
                        <span>Copyright &copy; MESS <?php echo date("Y"); ?></span>
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
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script type="text/javascript">
        $(document).ready(function() {
            infoVehiculos();
            const now = new Date();
            const fecha = now.toISOString().split('T')[0];
            const hora = now.toTimeString().split(' ')[0].slice(0, 5); 
            $("#fecha").val(fecha);
            $("#hora").val(hora); 
            // Obtener coordenadas del usuario
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        const lat = position.coords.latitude.toFixed(6); 
                        const lon = position.coords.longitude.toFixed(6); 
                        $("#coordenadas").val(`${lat}, ${lon}`); // Establecer las coordenadas en el campo
                    }
                );
            }
        });
        
         // FUNCION PARA CARGAR INFORMACIÓN DE LOS VEHÍCULOS
        function infoVehiculos() {
            $.ajax({
                type: "POST",
                url: "acciones_siniestro",
                data: { accion: "consultarInventario" },
                dataType: "json",
                success: function (respuesta) {
                    var tabla = $("#tablaInventario tbody");
                    tabla.empty(); 
                    Array.isArray(respuesta) && respuesta.forEach(function (vehiculo) {
                        var fila = 
                            `<tr>
                                <td><strong><i class="fas fa-car"></i> ${vehiculo.placa}</strong></td>
                                <td><strong>${vehiculo.modelo} ${vehiculo.marca}</strong></td>
                                <td><strong>${vehiculo.color}</strong></td>
                                <td>
                                    <div class="text-center">
                                        <button class="btn btn-outline-success btn-sm" onclick="seleccionarVehiculo('${vehiculo.id_vehiculo}', '${vehiculo.placa}', '${vehiculo.modelo}', '${vehiculo.marca}', '${vehiculo.color}')">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>`;
                        tabla.append(fila);
                    });
                    $("#tablaInventario").DataTable({
                        destroy: true, // Permitir reinicializar la tabla
                        language: {
                            decimal: ",",
                            thousands: ".",
                            processing: "Procesando...",
                            search: "Buscar:",
                            lengthMenu: "Mostrar _MENU_ registros",
                            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                            infoEmpty: "Mostrando 0 a 0 de 0 registros",
                            infoFiltered: "(filtrado de _MAX_ registros totales)",
                            loadingRecords: "Cargando...",
                            zeroRecords: "No se encontraron resultados",
                            emptyTable: "No hay datos disponibles en la tabla",
                            paginate: {
                                first: "Primero",
                                previous: "Anterior",
                                next: "Siguiente",
                                last: "Último"
                            },
                            aria: {
                                sortAscending: ": activar para ordenar la columna de manera ascendente",
                                sortDescending: ": activar para ordenar la columna de manera descendente"
                            }
                        }
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

        // FUNCION PARA MANEJAR EL BOTÓN "CHECK"
        function seleccionarVehiculo(id_vehiculo, placa, modelo, marca, color) {
            $("#infoPlacaSin").text(placa);
            $("#infoModeloMarcaSin").text(modelo + ' ' + marca);
            $("#infoColorSin").text(color);
            $("#placaSeleccionada").show();
            $("#id_vehiculo").val(id_vehiculo);
            $("#tablaInventario").closest(".container").hide();
            $("#formRegistroSiniestro").show();
        }

        // FUNCION PARA CAMBIAR DE VEHICULO
        function cambiarVehiculo() {
            $("#placaSeleccionada").hide();
            $("#tablaInventario").closest(".container").show();
            $("#formRegistroSiniestro").hide();
        }

        function toggleUbicacionOtro() {
            var val = $("#ubicacion").val();
            $("#ubicacion_otro").toggle(val === 'Otro');
            if (val !== 'Otro') $("#ubicacion_otro").val('');
        }

        //FUNCION REGISTRO DE SINIESTRO
        function RegistrarSiniestro() {
            var fecha = $("#fecha").val();
            var hora = $("#hora").val();
            var lugar = $("#lugar").val();
            var ubicacionSel = $("#ubicacion").val();
            var ubicacion = (ubicacionSel === 'Otro') ? ($("#ubicacion_otro").val().trim() || 'Otro') : ubicacionSel;
            var daños = $("#daños").val();
            var descripcion = $("#descripcion").val();
            var tipo_carro = $("#tipo_carro").val();
            var id_vehiculo = $("#id_vehiculo").val();
            var placa = $("#infoPlacaSin").text().trim();

            if (!id_vehiculo) {
                Swal.fire({ icon: 'warning', title: 'Sin vehículo', text: 'Selecciona un vehículo antes de continuar.', confirmButtonText: 'Aceptar' });
                return;
            }
            if (!fecha || !hora || !lugar || !ubicacionSel || !daños || !descripcion) {
                Swal.fire({ icon: 'warning', title: 'Campos incompletos', text: 'Por favor, completa todos los campos requeridos.', confirmButtonText: 'Aceptar' });
                return;
            }

            var $btn = $('button[onclick="RegistrarSiniestro()"]');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Guardando...');

            var formData = new FormData();
            formData.append("id_vehiculo", id_vehiculo);
            formData.append("fecha", fecha);
            formData.append("hora", hora);
            formData.append("lugar", lugar);
            formData.append("coordenadas", $("#coordenadas").val() || '');
            formData.append("ubicacion", ubicacion);
            formData.append("daños", daños);
            formData.append("descripcion", descripcion);
            formData.append("tipo_carro", tipo_carro);
            formData.append("placa", placa);
            formData.append("accion", 'registroSiniestro');

            document.querySelectorAll('input[name="foto[]"]').forEach(function(input) {
                for (var i = 0; i < input.files.length; i++) {
                    formData.append('foto[]', input.files[i]);
                }
            });

            $.ajax({
                type: "POST", url: "acciones_siniestro", data: formData,
                processData: false, contentType: false, dataType: 'json',
                success: function (r) {
                    $btn.prop('disabled', false).html('Guardar');
                    if (r.success) {
                        Swal.fire({ icon: 'success', title: 'Siniestro registrado.', confirmButtonText: 'Aceptar' })
                            .then(function() { location.reload(); });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: r.message, confirmButtonText: 'Aceptar' });
                    }
                },
                error: function () {
                    $btn.prop('disabled', false).html('Guardar');
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Hubo un problema al registrar el siniestro.', confirmButtonText: 'Aceptar' });
                }
            });
        }

        
        //Validar el número máximo de imágenes (4)
        $("#foto").on("change", function () {
            if (this.files.length > 4) {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Solo puedes subir un máximo de 4 imágenes.",
                    confirmButtonText: "Aceptar"
                });
                this.value = ""; // Limpia el campo de archivos
            }
        });

        //FUNCION PARA AGREGAR CAMPO DE FOTO
        function agregarCampoFoto() {
            // Contar cuántos campos de fotos existen actualmente
            var totalFotos = document.querySelectorAll(".foto-siniestro").length;

            // Validar que no se agreguen más de 4 campos en total
            if (totalFotos >= 4) {
                Swal.fire({
                    icon: "error",
                    title: "Límite alcanzado",
                    text: "Solo puedes agregar un máximo de 4 imágenes.",
                    confirmButtonText: "Aceptar"
                });
                return;
            }

            // Crear un nuevo grupo de input y botón eliminar
            var nuevoCampo = `
                <div class="input-group mb-2">
                    <input type="file" class="form-control foto-siniestro" name="foto[]" accept="image/*" capture="environment">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="eliminarCampo(this)">Eliminar</button>
                </div>`;
            
            // Agregar el nuevo campo al contenedor 
            $("#contenedorFotos").append(nuevoCampo);
        }

        //FUNCION PARA ELIMINAR CAMPO DE FOTO
        function eliminarCampo(boton) {
            $(boton).closest(".input-group").remove();
        }

        // Validar el número máximo de imágenes dinámicas (4)
        $("#foto").on("change", ".foto-siniestro", function () {
            var totalFotos = document.querySelectorAll(".foto-siniestro").length;

            if (totalFotos > 4) {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "Asegurate que cada campo tenga una imagen.",
                    confirmButtonText: "Aceptar"
                });
                this.value = ""; // Limpia el campo de archivos
            }
        });
    </script>
</body>
</html>