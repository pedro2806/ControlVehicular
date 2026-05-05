<?php
    
    include 'conn.php';
    if($_COOKIE['noEmpleado'] == '' || $_COOKIE['noEmpleado'] == null){
        echo '<script>window.location.assign("index")</script>';
    }    
?>
<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="inicio">
        <div class="sidebar-brand-icon rotate-n-1">
            <img class="sidebar-card-illustration mb-0" src="img/MESS_07_CuboMess_2.png" width="40">
        </div>
    </a>

    <!-- Nav Item - Dashboard -->
    <li class="nav-item active">
        <a class="nav-link py-2" href="inicio">
            <i class="fas fa-fw fa-home"></i>
            <span>Inicio</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">

    <!-- Heading 
    <div class="sidebar-heading">
        <span class="badge text-xl-white">Menú</span>
    </div>
    -->
    <!-- Menú Siniestro -->
    <li class="nav-item btn-warning active">
        <a class="nav-link py-2" href="siniestros" style="font-size: 1rem;">
            <i class="fas fa-fw fa-car-crash"></i>
            <span style="font-size: 1.1rem;">Siniestro</span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link py-2" href="seguimiento_siniestro">
            <i class="fas fa-fw fa-car-crash"></i>
            <span>Hist. Siniestros</span>
        </a>
    </li>

    <hr class="sidebar-divider">
    <!-- Menú CheckIn -->

    <?php if ($_COOKIE['navSesion'] == 'Navegador'){ 
        if ($_COOKIE['gps'] == 'activo'){
            ?>
                <li class="nav-item btn-success active">
                    <a class="nav-link py-2">
                        <i class="far fa-fw fa-check-square"></i>
                        <span>
                            <span style="color:white; font-size: 1rem;">CheckIn desde la app</span>
                        </span>
                    </a>
                </li>
            <?php
        }
        else{ ?>
        <li class="nav-item btn-success active">
            <a class="nav-link py-2" href="#" onclick="validarActividadesPendientes()">
                <i class="far fa-fw fa-check-square"></i>
                <span>
                    <span style="color:blue; font-size: 1rem;">CheckIn</span> / <span style="color:white; font-size: 1rem;">CheckOut</span>
                </span>
            </a>
        </li>
    <?php }}
    else{
        ?>
        <li class="nav-item btn-success active">
            <a class="nav-link py-2" href="#" onclick="validarActividadesPendientes()">
                <i class="far fa-fw fa-check-square"></i>
                <span>
                    <span style="color:blue; font-size: 1rem;">CheckIn</span> / <span style="color:white; font-size: 1rem;">CheckOut</span>
                </span>
            </a>
        </li>
    <?php
    }?>

<!-- Menú Gasolina -->        
<li class="nav-item">    
    <a class="nav-link collapsed py-2" href="#" data-toggle="collapse" data-target="#collapseGasolina" aria-expanded="false" aria-controls="collapseGasolina">
        <i class="fas fa-fw fa-gas-pump"></i>
        <span>Gasolina</span>
    </a>        
    <div id="collapseGasolina" class="collapse" aria-labelledby="headingGasolina" data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">            
            <a class="collapse-item" href="#" data-toggle="modal" data-target="#capturaGasModal" onclick="cargarVehiculos('vehiculoAsignadoGas')">
                <i class="fas fa-fw fa-gas-pump"></i>
                <span>Registrar Gasolina</span>
            </a>
            <a class="collapse-item" href="historial_gasolina">
                <i class="fas fa-fw fa-history"></i>
                <span>Historial de Cargas</span>
            </a>
        </div>
    </div>
</li>

    <!-- Menú CheckIn -->
    <li class="nav-item">
        <a class="nav-link" href="verActividades">
            <i class="fas fa-fw fa-eye"></i>
            <span>Ver Actividades</span>
        </a>
    </li>

<!-- Menú Mantenimiento -->
<li class="nav-item">
    <a class="nav-link collapsed py-2" href="#" data-toggle="collapse" data-target="#collapseMantenimiento" aria-expanded="false" aria-controls="collapseMantenimiento">
        <i class="fas fa-fw fa-tools"></i>
        <span>Mantenimiento</span>
    </a>
    <div id="collapseMantenimiento" class="collapse" aria-labelledby="headingMantenimiento" data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            <a class="collapse-item" href="mantenimiento">Registrar Mantenimiento</a>
            <a class="collapse-item" href="seguimiento_mantenimiento">Seg. Mantenimientos</a>
            <?php if (isset($_COOKIE['rol']) && $_COOKIE['rol'] == 2): ?>
                <a class="collapse-item" href="autorizar_mantenimiento">Aut. Mantenimientos</a>
            <?php endif; ?>
        </div>
    </div>
</li>

    <!-- Menú Documentación -->
    <li class="nav-item">
        <a class="nav-link collapsed py-2" href="#" data-toggle="collapse" data-target="#collapseDocumentacion" aria-expanded="true" aria-controls="collapseDocumentacion">
            <i class="fas fa-fw fa-file-alt"></i>
            <span>Documentación</span>
        </a>
        <div id="collapseDocumentacion" class="collapse" aria-labelledby="headingDocumentacion" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="documentacion">Registrar Documentación</a>
                <a class="collapse-item" href="seguimiento_documentacion">Ver Documentación</a>
            </div>            
        </div>
    </li>

    <!-- Menú Préstamos -->
    <li class="nav-item">
        <a class="nav-link collapsed py-2" href="#" data-toggle="collapse" data-target="#collapsePrestamos" aria-expanded="true" aria-controls="collapsePrestamos">
            <i class="fas fa-fw fa-car"></i>
            <span>Préstamos</span>
        </a>
        <div id="collapsePrestamos" class="collapse" aria-labelledby="headingPrestamos" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="prestamos">Solicitar Préstamo</a>
                <a class="collapse-item" href="autorizar_prestamo">Seguimiento Préstamos</a>
            </div>            
        </div>
    </li>
    
    <!-- Menú CheckList -->
    <li class="nav-item">
        <a class="nav-link collapsed py-2" href="#" data-toggle="collapse" data-target="#collapseCheckIn" aria-expanded="true" aria-controls="collapseCheckIn">
            <i class="fas fa-fw fa-book"></i>
            <span>Check List</span>
        </a>
        <div id="collapseCheckIn" class="collapse" aria-labelledby="headingCheckIn" data-parent="#accordionSidebar">
        
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="checkVehiculo">Registrar / Actualizar</a>
                <a class="collapse-item" href="verifica_checkinVehiculo">Ver Check List</a>
            </div>            
        </div>
    
    </li>

    <!-- SALIR -->
    <li class = "nav-item">
        <a class = "nav-link py-2" href = "#" data-toggle = "modal" data-target = "#logoutModalN">
            <i class = "fas fa-sign-out-alt text-gray-100"></i>
            Salir
        </a>
    </li>

    <?php if ($_COOKIE['noEmpleado'] == '19' || $_COOKIE['noEmpleado'] == '183' || $_COOKIE['noEmpleado'] == '276' || $_COOKIE['noEmpleado'] == '191'): ?>
        <!-- Menú Mapa GPS Tracking -->
        <li class="nav-item">
            <a class="nav-link py-2" href="mapa_gps">
                <i class="fas fa-fw fa-map"></i>
                <span>Mapa GPS</span>
            </a>
        </li>
    <?php endif; ?>

    <!-- Divider -->
    <hr class="sidebar-divider d-none d-md-block">
    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>
</ul>
<!-- End of Sidebar -->