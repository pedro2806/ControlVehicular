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
                        <h1 class = "h3 mb-0 text-gray-800">Registro de Siniestro</h1>                        
                    </div>
                    <!-- TABLA DE VEHICULOS -->
                    <div class="container">
                        <h3>Inventario de Vehículos</h3>
                        <table id="tablaInventario" class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Placa</th>
                                    <th>Modelo</th>
                                    <th>Color</th>
                                    <th>Año</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Las filas se cargarán dinámicamente -->
                            </tbody>
                        </table>
                    </div>

                    <form id="formRegistroSiniestro">
                        <!-- Content Row -->
                        <div class = "row">
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Fecha:</label>
                                <input type = "date" class = "form-control" id = "fecha" name = "fecha" required>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Hora:</label> 
                                <input type = "time" class = "form-control" id = "hora" name = "hora" required>       
                            </div>
                            <br>
                            <br>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Tipo de Vehiculo:</label> 
                                <select class="form-control" id="tipo_carro" name="tipo_carro" required onchange="mostrarCampoDueno()">
                                    <option value="0">Seleccione...</option>
                                    <option value="Propio">A mi nombre</option>
                                    <option value="Prestado">Prestado</option>
                                </select>       
                            </div>
                            <br>
                            <br>
                            <!-- Campo adicional para el nombre del dueño -->
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6" id="campo_dueno" style="display: none;">
                                <label>Nombre del Dueño:</label>
                                <input type="text" class="form-control" id="nombre_dueno" name="nombre_dueno">
                            </div>
                        </div>
                        <br>
                        <!-- Content Row -->
                        <h1 class = "h5 mb-0 text-gray-800">Ubicacion</h1>
                        <br>
                        <div class="row">
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Origen:</label>  
                                <input class = "form-control" id = "origen" name = "origen"  required>    
                            </div>
                            <br>
                            <br>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Destino:</label>
                                <input type = "text" class = "form-control" id = "destino" name = "destino" required>
                            </div>
                            <br>
                            <br>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Lugar:</label>  
                                <input class = "form-control" id = "lugar" name = "lugar">
                            </div>
                            <br>
                            <br>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Empresa:</label>
                                <input type = "text" class = "form-control" id = "empresa" name = "empresa" oninput="this.value = this.value.toUpperCase()">
                            </div>
                            <br>
                            <br>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Servicio:</label>
                                <input type = "text" class = "form-control" id = "servicio" name = "servicio">
                            </div>
                            <br>
                            <br>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Coordenadas:</label>  
                                <input class = "form-control" id = "coordenadas" name = "coordenadas" type="text" pattern="^-?[0-9]+(\.[0-9]+)?,\s*-?[0-9]+(\.[0-9]+)?$" placeholder="Ej: 19.4326, -99.1332" required>
                            </div>
                        </div>
                        <br>
                        <!-- Content Row -->
                        <div class = "row">
                            <h1 class = "h5 mb-0 text-gray-800">Detalles del Automovil</h1>
                            <br>
                            <br>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Kilometraje:</label>  
                                <input class = "form-control" id = "kilometraje" name = "kilometraje" type="number" min="0" required>
                            </div>
                            <br>
                            <br>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Gasolina:</label>
                                <select class = "form-control" id = "gasolina" name = "gasolina" required>
                                    <option value = "0">Seleccione...</option>
                                    <option value = "1/8">1/8</option>
                                    <option value = "2/8">2/8</option>
                                    <option value = "3/8">3/8</option>    
                                    <option value = "4/8">4/8</option>
                                    <option value = "5/8">5/8</option>
                                    <option value = "6/8">6/8</option>
                                    <option value = "7/8">7/8</option>
                                    <option value = "8/8">8/8</option>
                                </select>  
                            </div>
                            <br>
                            <br>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Ubicacion del Vehiculo:</label>  
                                <input class = "form-control" id = "ubicacion" name = "ubicacion" required>
                            </div>
                            <br>
                            <br>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Partes Dañadas:</label>  
                                <input class = "form-control" id = "daños" name = "daños" required>
                            </div>
                            <br>
                            <br>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Contacto:</label>  
                                <input class = "form-control" id = "contacto" name = "contacto" type="tel" required>
                            </div>
                            <br>
                            <br>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Descripción:</label>
                                <textarea class = "form-control" id = "descripcion" name = "descripcion" required></textarea>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label>Foto del Siniestro:</label>
                                <input 
                                    type="file" 
                                    class="form-control" 
                                    id="foto" 
                                    name="foto" 
                                    accept="image/*" 
                                    capture="environment" 
                                    ><!--required-->
                            </div>
                        </div>
                        <br>
                        <center>
                            <button type="button" class="btn btn-outline-success" onclick="RegistrarSiniestro()">Confirmar</button>
                        </center>
                    </form>
                    <br>
                </div>
            </div>
            <footer class = "sticky-footer bg-white">
                <div class = "container my-auto">
                    <div class = "copyright text-center my-auto">
                        <span>Copyright &copy; MESS@2025</span>
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
        });
        
         // FUNCION PARA CARGAR INFORMACIÓN DE LOS VEHÍCULOS
        function infoVehiculos() {
            $.ajax({
                type: "POST",
                url: "acciones_siniestro",
                data: { accion: "consultarInventario" }, 
                dataType: "json",
                success: function (respuesta) {
                    var tabla = $("#tablaInventario");
                    tabla.empty(); // Limpiar la tabla antes de cargar los datos

                    // Recorrer los datos y agregarlos a la tabla
                    respuesta.forEach(function (vehiculo) {
                        var fila = `
                            <tr>
                                <td>${vehiculo.placa}</td>
                                <td>${vehiculo.modelo}</td>
                                <td>${vehiculo.color}</td>
                                <td>${vehiculo.anio}</td>
                                <td>
                                    <button class="btn btn-success btn-sm" onclick="seleccionarVehiculo('${vehiculo.placa}')">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                        tabla.append(fila);
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
        function seleccionarVehiculo(placa) {
            Swal.fire({
                icon: "success",
                title: "Vehículo seleccionado",
                text: `Has seleccionado el vehículo con placa: ${placa}`,
                confirmButtonText: "Aceptar"
            });
        }

        //FUNCION REGISTRO DE SINIESTRO
        function RegistrarSiniestro() {
            var fecha = $("#fecha").val();
            var hora = $("#hora").val();
            var origen = $("#origen").val();
            var destino = $("#destino").val(); 
            var lugar = $("#lugar").val();
            var empresa = $("#empresa").val();
            var servicio = $("#servicio").val();
            var coordenadas = $("#coordenadas").val();
            var kilometraje = $("#kilometraje").val();
            var gasolina = $("#gasolina").val();
            var ubicacion = $("#ubicacion").val();
            var daños = $("#daños").val();
            var contacto = $("#contacto").val();
            var descripcion = $("#descripcion").val();
            var tipo_carro = $("#tipo_carro").val();
            var nombre_dueno = $("#nombre_dueno").val(); 
            var noEmpleado = "<?php echo $_COOKIE['noEmpleado']; ?>"; // Obtener el valor de la cookie
            var accion = "registroSiniestro";
            
            // Validar que los campos requeridos no estén vacíos
            if (!fecha || !hora || !origen || !destino || !kilometraje || !gasolina || !ubicacion || !daños || !contacto || !descripcion || tipo_carro === "Prestado" && !nombre_dueno) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Campos incompletos',
                    text: 'Por favor, completa todos los campos requeridos antes de enviar.',
                    confirmButtonText: 'Aceptar'
                });
                return; // Detener la ejecución si hay campos vacíos
            }
            
            $.ajax({
                type: "POST",
                url: "acciones_siniestro",
                data: { fecha, hora, origen, destino, lugar, empresa, servicio, coordenadas, 
                        kilometraje, gasolina, ubicacion, daños, contacto, descripcion, tipo_carro, 
                        nombre_dueno, noEmpleado, accion},
                dataType: 'json', 
                success: function (respuesta) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: 'Siniestro registrado exitosamente.',
                        confirmButtonText: 'Aceptar'
                    });
                    //document.getElementById("formRegistroSiniestro").reset(); // Limpiar el formulario
                },
                error: function () {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Hubo un problema al registrar el siniestro.',
                        confirmButtonText: 'Aceptar'
                    });
                }
            });
        } 

        //FUNCION PARA MOSTRAR CAMPO DEL DUEÑO DEL VEHICULO
        function mostrarCampoDueno() {
            var tipo_carro = $("#tipo_carro").val();
            var campo_dueno = document.getElementById("campo_dueno"); // Seleccionar el elemento directamente

            if (tipo_carro === "Prestado") {
                campo_dueno.style.display = "block"; // Mostrar el campo
                $("#nombre_dueno").attr("required", true); // Hacer el campo obligatorio
            } else {
                campo_dueno.style.display = "none"; // Ocultar el campo
                $("#nombre_dueno").removeAttr("required"); // Quitar la obligatoriedad
            }
        }
    </script>
</body>
</html>