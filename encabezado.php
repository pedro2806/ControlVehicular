<!-- Topbar -->
<nav class="navbar navbar-expand navbar-light bg-white topbar mb-2 static-top shadow">
    <!-- Enlace a Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Enlace a Bootstrap JS (necesario para el funcionamiento del modal) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Enlace a FontAwesome para los íconos (si usas íconos) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- CSS personalizado -->
    <link href="css/app.css" rel="stylesheet">

    <!-- Sidebar Toggle (Topbar) -->
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>

    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-0">
        <img src="img/QRide_grande.png" height="36" alt="QRide" style="max-width:160px;object-fit:contain;">
    </div>

    <!-- Topbar Navbar -->
    <ul class="navbar-nav ml-auto">
        <div class="topbar-divider d-none d-sm-block"></div>
        <!-- Nav Item - User Information -->
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="mr-0 d-lg-inline text-gray-600" style="font-size: 0.85em;">
                    <?php echo ' '.($_COOKIE['nombredelusuarioL'] ?? $_COOKIE['nombredelusuario'] ?? '').' ';?>
                </span>
                <img class="img-profile rounded-circle" src="img/undraw_profile.svg" style="width: 100%;">
            </a>
            <input type="hidden" id="coordenadasCheck" name="coordenadasCheck">
            <input type="hidden" id="placaElegida" name="placaElegida">
            <input type="hidden" class="form-control" id="PidPrestamo" name="PidPrestamo">
            <!-- Dropdown - User Information -->
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModalN">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                    Salir
                </a>
            </div>
        </li>
    </ul>

    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModalN" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content border-left-danger">
                <div class="modal-header">
                    <h4 class="modal-title" id="exampleModalLabel"> Cerrar sesión </h4>
                    <button class="close" type="button" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">X</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h5><b>¿Estas seguro?</b></h5>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-warning" type="button" data-bs-dismiss="modal">Cancelar</button>
                    <a class="btn btn-danger" href="logout">Salir</a>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL PARA CAPTURAR KM DE UN AUTO -->
    <div class="modal fade" id="capturaKmModal" tabindex="-1" aria-labelledby="capturaKmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="capturaKmModalLabel">Check In Uso Vehiculo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="formCapturaKm">
                        <div class="mb-2">
                            <label for="vehiculoAsignado" class="form-label">Vehículo Asignado</label>
                            <select class="form-select" id="vehiculoAsignado" name="vehiculoAsignado" onchange="verPlaca('vehiculoAsignado', 'kmActual')" required>
                                <option value="">Seleccione un vehículo</option>                                
                            </select>                            
                        </div>
                        <div class="mb-2 row g-2">
                            <div class="col-6 col-md-6">
                                <label>Tipo de uso:</label>
                                <select class="form-select" id="tipoServicio" name="tipoServicio" onchange="cambiaLabelTServicio()" required>
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
                            <div class="col-6 col-md-6">
                                <label id="labelTServicio" name="labelTServicio" class="form-label">OT Relacionada</label>
                                <input type="text" class="form-control" id="otRelacionada" name="otRelacionada" required>
                            </div>                            
                        </div>
                        <div class="mb-2 row g-2">
                            <div class="col-4 col-md-4">
                                <label id="KmActual" for="kmActual" class="form-label">Km Actual</label>
                                <input type="number" class="form-control" id="kmActual" name="kmActual" min="0" required>
                            </div>
                            <div class="col-4 col-md-4">
                                <label id="GasActual" for="gasActual" class="form-label">Gas. Actual</label>                                
                                <select class = "form-select" id = "gasActual" name = "gasActual">
                                    <option value = "">Seleccione...</option>
                                    <option value = "SD">Sin Datos</option>
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
                            <div class="col-4 col-md-4">
                                <label class="form-label" id="labelpatronRelacionado" name="labelpatronRelacionado">Patron</label>
                                <input type="text" class="form-control" id="patronRelacionado" name="patronRelacionado" required>
                            </div>
                        </div>
                        <div class="mb-2 row g-2">
                            <div class="col-12 col-md-12">
                                <label id="Notas" for="notasCheckin" class="form-label">Notas</label>
                                <textarea name="notasCheckin" id="notasCheckin" class="form-control" rows="2" cols="5"></textarea>
                            </div>
                        </div>
                        <?php
                        $autorizados = ['42', '290', '492', '502', '183', '276'];
                        if (in_array($_COOKIE['noEmpleado'], $autorizados)):
                        ?>
                            <div class="mb-2 row g-2">
                                <div class="col-6 col-md-6">
                                    <label id="Ruta" for="ruta" class="form-label">Ruta</label>
                                    <select class="form-select" id="ruta" name="ruta" required>
                                        <option value="">Seleccione una ruta</option>
                                        <option value="Ruta 1">Ruta 1</option>
                                        <option value="Ruta 2">Ruta 2</option>
                                        <option value="Ruta 3">Ruta 3</option>
                                        <option value="Ruta 4">Ruta 4</option>
                                    </select>
                                </div>
                                <div class="col-6 col-md-6">
                                    <label id="CostoOvs" for="costoOv" class="form-label">Costo Ovs.</label>
                                    <input type="text" class="form-control" id="costoOv" name="costoOv">
                                </div>
                            </div>
                    <?php endif; ?>
                        <div class="mb-2 row g-2">
                            <div class="col-12 col-md-12">
                                <label id="Img" for="imgCheckin" class="form-label">Img.</label>
                                <input type="file" class="form-control" id="imgCheckin" name="imgCheckin[]" accept=".jpg,.jpeg,.png">
                                <div id="contenedorImgCheckin"></div>
                                <button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="agregarInputImagen()">Agregar otra imagen</button>
                            </div>    
                        </div>
                        <div id="msgKm" class="form-text text-danger"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="guardarCheckIn()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL PARA VER ACTIVIDADES PENDIENTES -->
    <div class="modal fade" id="actividadesPendientesModal" tabindex="-1" aria-labelledby="actividadesPendientesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="actividadesPendientesModalLabel">Actividades Pendientes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div id="actividadesPendientesContent">
                        <div class="text-center">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Título</th>
                                        <th>Descripción</th>
                                        <th>Vehículo</th>                                    
                                    </tr>
                                </thead>
                                <tbody id="tablaActividadesPendientes">                                    
                                </tbody>
                            </table>
                            <form id="formNuevaActividadPendiente" class="mb-3">                                
                                <div class="row g-2 mt-2">

                                    <div class="col-md-3">
                                        <label for="kmActualNuevo" class="form-label">Km Actual</label>
                                        <input type="number" class="form-control" id="kmActualNuevo" name="kmActualNuevo" min="0" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="gasActualNuevo" class="form-label">Gas. Actual</label>
                                        <select class = "form-select" id = "gasActualNuevo" name = "gasActualNuevo">
                                            <option value = "">Seleccione...</option>
                                            <option value = "SD">Sin Datos</option>
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
                                    <div class="col-md-6">
                                        <label for="notasCheckinNuevo" class="form-label">Notas</label>
                                        <textarea name="notasCheckinNuevo" id="notasCheckinNuevo" class="form-control" rows="1"></textarea>
                                    </div>
                                </div>
                                <?php
                                    $autorizados = ['42', '290', '492', '502', '183', '276'];
                                    if (in_array($_COOKIE['noEmpleado'], $autorizados)):
                                    ?>
                                        <div class="mb-2 row g-2">
                                            <div class="col-6 col-md-6">
                                                <label id="ruta" for="ruta" class="form-label">Ruta</label>
                                                <select class="form-select" id="rutaNuevo" name="rutaNuevo" required>
                                                    <option value="">Seleccione una ruta</option>
                                                    <option value="Ruta 1">Ruta 1</option>
                                                    <option value="Ruta 2">Ruta 2</option>
                                                    <option value="Ruta 3">Ruta 3</option>
                                                    <option value="Ruta 4">Ruta 4</option>
                                                </select>
                                            </div>
                                            <div class="col-6 col-md-6">
                                                <label for="kmActual" class="form-label">Costo Ovs.</label>
                                                <input type="text" class="form-control" id="costoOvNuevo" name="costoOvNuevo">
                                            </div>
                                        </div>
                                <?php endif; ?>
                                <div class="row g-2 mt-2">
                                    <div class="col-12 col-md-12 alert alert-primary me-0" id="divFinalizarPrestamo" name="divFinalizarPrestamo">
                                        <div class="form-check form-switch d-flex align-items-center mb-2" style="font-size: 1.1em;">
                                            <input class="form-check-input me-4" type="checkbox" id="finalizarPrestamo" name="finalizarPrestamo" style="width:2.5em; height:1.5em;">
                                            <label class="form-check-label fw-bold text-primary" for="finalizarPrestamo" style="margin-left: 1.5em;">                                                
                                                Marca esta casilla si vas a <span class="text-success">finalizar tu préstamo</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-2 mt-2">
                                    <div class="col-12 col-md-12">                                        
                                        <input type="file" class="form-control" id="imgCheckinNuevo" name="imgCheckinNuevo[]" accept=".jpg,.jpeg,.png,.pdf">
                                        <div id="contenedorImgCheckout"></div>
                                        <button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="agregarInputImagenOut()">Agregar otra imagen</button>
                                    </div>                                      
                                    <input type="hidden" class="form-control" id="PidVehiculo" name="PidVehiculo">
                                    <input type="hidden" class="form-control" id="PtipoActividad" name="¨PtipoActividad">
                                    <input type="hidden" class="form-control" id="Ppatron" name="Ppatron">
                                    <input type="hidden" class="form-control" id="Pot" name="Pot">
                                </div>
                                <div class="mt-2" id ="cerrarActividadPendiente">                                    
                                </div>
                            </form>                            
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL PARA CAPTURAR GASOLINA -->
    <div class="modal fade" id="capturaGasModal" tabindex="-1" aria-labelledby="capturaGasModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="capturaGasModalLabel">Captura de Gasolina</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="formCapturaGas">
                        <div class="mb-2">
                            <label for="vehiculoAsignadoGas" class="form-label">Vehículo Asignado</label>
                            <select class="form-select" id="vehiculoAsignadoGas" name="vehiculoAsignadoGas" onchange="verPlaca('vehiculoAsignadoGas', 'kmActualGas', 'saldo')" required>
                                <option value="">Seleccione un vehículo</option>                                
                            </select>                           
                        </div>
                        <div class="mb-2 row g-2">    
                            <div class="col-4 col-md-4">
                                <label for="monto" class="form-label">Monto</label>
                                <input type="text" class="form-control" id="monto" name="monto" placeholder="$00.00" readonly required>
                            </div>
                        
                            <div class="col-4 col-md-4">
                                <label for="pagos" class="form-label">Pagos</label>
                                <input type="text" class="form-control" id="pagos" name="pagos" placeholder="$00.00" onblur="calcularSaldo()" required>
                            </div>

                            <div class="col-4 col-md-4">
                                <label for="saldo" class="form-label">Saldo</label>
                                <input type="number" class="form-control" id="saldo" name="saldo" placeholder="$00.00" readonly required>
                            </div>
                        </div>
                        <div class="mb-2 row g-2">
                            <div class="col-4 col-md-6">
                                <label for="fechaCarga" class="form-label">Fecha de Carga</label>
                                <input type="datetime-local" class="form-control" id="fechaCarga" name="fechaCarga" value= "" required>
                            </div>
                            <div class="col-4 col-md-6">
                                <label id="kmActual" for="kmActual" class="form-label">Km Actual</label>
                                <input type="number" class="form-control" id="kmActualGas" name="kmActualGas" min="0" required>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="registrarGas()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    </nav>
<!-- End of Topbar -->

<!-- Aviso global de permisos de ubicacion -->
<div id="avisoUbicacion" class="alert alert-warning alert-dismissible fade show mb-2 mx-3 d-none" role="alert" style="border-left: 4px solid #f6c23e;">
    <div class="d-flex align-items-start gap-2">
        <i class="fas fa-map-marker-alt mt-1"></i>
        <div class="flex-grow-1">
            <div class="fw-semibold" id="avisoUbicacionTitulo">Habilita el acceso a tu ubicación</div>
            <div class="small mb-1" id="avisoUbicacionMsg">
                Para el correcto funcionamiento de Control Vehicular necesitamos acceso a tu ubicación y cookies. Acepta los permisos cuando el navegador te lo solicite.
            </div>
            <button type="button" class="btn btn-sm btn-warning fw-semibold mt-1" id="btnAceptarUbicacion">
                <i class="fas fa-check me-1"></i> Habilitar ubicación
            </button>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
<script src="js/global/utils.js"></script>
<script src="js/global/modals.js"></script>
<script src="js/global/vehiculos.js"></script>
<script src="js/global/gas.js"></script>
<script type="text/javascript">
    $(document).ready(function () {
        obtenerCoordenadas();
        verificarPermisoUbicacion();
    });
    window.addEventListener('load', function () {
        var ahora = new Date();
        var fechaCargaInput = document.getElementById("fechaCarga");
        if (fechaCargaInput) fechaCargaInput.value = ahora.toISOString().slice(0,16);
        var cookieValue = getCookie("noEmpleado");
        if (cookieValue) {
            var noEmpleadoInput = document.getElementById("noEmpleado");
            if (noEmpleadoInput) noEmpleadoInput.value = cookieValue;
        }
    });
</script>

