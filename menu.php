<?php
    include 'conn.php';
    if($_COOKIE['noEmpleado'] == '' || $_COOKIE['noEmpleado'] == null){
        echo '<script>window.location.assign("index")</script>';
    }

    // Una sola consulta trae todos los accesos especiales del usuario.
    // Cada item del menu lee de $accesos[...] en vez de hacer un fetch.
    $accesos = [];
    $stmtAcc = $conn->prepare(
        "SELECT opcion FROM mess_rrhh.accesos_especiales
         WHERE noEmpleado = ? AND sistema = 'ctrlVehicular' AND estatus = 1"
    );
    if ($stmtAcc) {
        $noEmpMenu = intval($_COOKIE['noEmpleado']);
        $stmtAcc->bind_param("i", $noEmpMenu);
        $stmtAcc->execute();
        $resAcc = $stmtAcc->get_result();
        while ($r = $resAcc->fetch_assoc()) { $accesos[$r['opcion']] = true; }
        $stmtAcc->close();
    }
    $puedeAutorizarMant = !empty($accesos['autorizaMantenimiento']);
    $puedeVerQR         = !empty($accesos['verQR']);
    $puedeCargarReportes= !empty($accesos['cargarReportes']);
    $muestraAdmin       = $puedeVerQR || $puedeCargarReportes;
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

<!-- Menú Gasolina — solo historial (registro de gas se hace desde QR) -->
<li class="nav-item">
    <a class="nav-link py-2" href="historial_gasolina">
        <i class="fas fa-fw fa-gas-pump"></i>
        <span>Historial de Cargas</span>
    </a>
</li>

    <!-- Menú CheckIn -->
    <?php if (!empty($accesos['verActividades'])): ?>
    <li class="nav-item">
        <a class="nav-link" href="verActividades">
            <i class="fas fa-fw fa-eye"></i>
            <span>Ver Actividades</span>
        </a>
    </li>
    <?php endif; ?>

<!-- Menú Mantenimiento -->
<li class="nav-item">
    <a class="nav-link collapsed py-2" href="#" data-bs-toggle="collapse" data-bs-target="#collapseMantenimiento" aria-expanded="false" aria-controls="collapseMantenimiento">
        <i class="fas fa-fw fa-tools"></i>
        <span>Mantenimiento</span>
    </a>
    <div id="collapseMantenimiento" class="collapse" aria-labelledby="headingMantenimiento" data-bs-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            <a class="collapse-item" href="mantenimiento">Registrar Mantenimiento</a>
            <a class="collapse-item" href="seguimiento_mantenimiento">Seg. Mantenimientos</a>
            <?php if ($puedeAutorizarMant): ?>
                <a class="collapse-item" href="autorizar_mantenimiento">Aut. Mantenimientos</a>
            <?php endif; ?>
        </div>
    </div>
</li>

    <!-- Menú Documentación -->
    <li class="nav-item">
        <a class="nav-link collapsed py-2" href="#" data-bs-toggle="collapse" data-bs-target="#collapseDocumentacion" aria-expanded="true" aria-controls="collapseDocumentacion">
            <i class="fas fa-fw fa-file-alt"></i>
            <span>Documentación</span>
        </a>
        <div id="collapseDocumentacion" class="collapse" aria-labelledby="headingDocumentacion" data-bs-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="documentacion">Registrar Documentación</a>
                <a class="collapse-item" href="seguimiento_documentacion">Ver Documentación</a>
            </div>            
        </div>
    </li>

    <!-- Menú Préstamos -->
    <li class="nav-item">
        <a class="nav-link collapsed py-2" href="#" data-bs-toggle="collapse" data-bs-target="#collapsePrestamos" aria-expanded="true" aria-controls="collapsePrestamos">
            <i class="fas fa-fw fa-car"></i>
            <span>Préstamos</span>
        </a>
        <div id="collapsePrestamos" class="collapse" aria-labelledby="headingPrestamos" data-bs-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="prestamos">Solicitar Préstamo</a>
                <a class="collapse-item" href="autorizar_prestamo">Seguimiento Préstamos</a>
            </div>            
        </div>
    </li>
    
    <!-- Menú CheckList -->
    <li class="nav-item">
        <a class="nav-link collapsed py-2" href="#" data-bs-toggle="collapse" data-bs-target="#collapseCheckIn" aria-expanded="true" aria-controls="collapseCheckIn">
            <i class="fas fa-fw fa-book"></i>
            <span>Check List</span>
        </a>
        <div id="collapseCheckIn" class="collapse" aria-labelledby="headingCheckIn" data-bs-parent="#accordionSidebar">

            <div class="bg-white py-2 collapse-inner rounded">
                <a class="collapse-item" href="checkVehiculo">Registrar / Actualizar</a>
                <a class="collapse-item" href="verifica_checkinVehiculo">Ver Check List</a>
            </div>
        </div>

    </li>

    <?php if ($muestraAdmin): ?>
    <!-- Menú Administración (solo visible si el usuario tiene al menos un acceso) -->
    <li class="nav-item">
        <a class="nav-link collapsed py-2" href="#" data-bs-toggle="collapse" data-bs-target="#collapseAdmin" aria-expanded="false" aria-controls="collapseAdmin">
            <i class="fas fa-fw fa-cog"></i>
            <span>Generar QR</span>
        </a>
        <div id="collapseAdmin" class="collapse" aria-labelledby="headingAdmin" data-bs-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <?php if ($puedeVerQR): ?>
                    <a class="collapse-item" href="generar_qr_vehiculo">
                        <i class="fas fa-fw fa-qrcode"></i> QR Vehículos
                    </a>
                <?php endif; ?>
                <?php if ($puedeCargarReportes): ?>
                    <a class="collapse-item" href="importar_reportes">
                        <i class="fas fa-fw fa-file-csv"></i> Importar Reportes
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </li>
    <?php endif; ?>

    <!-- SALIR -->
    <li class = "nav-item">
        <a class = "nav-link py-2" href = "#" data-bs-toggle = "modal" data-bs-target = "#logoutModalN">
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