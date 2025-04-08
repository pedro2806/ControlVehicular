<?php
    
    include 'conn.php';
    if($_COOKIE['noEmpleado'] == '' || $_COOKIE['noEmpleado'] == null){
        echo '<script>window.location.assign("index")</script>';http://localhost/incidencias/saladejuntas/inicio
    }
?>
<!-- Sidebar -->
<ul class = "navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id = "accordionSidebar">

<!-- Sidebar - Brand -->
<a class = "sidebar-brand d-flex align-items-center justify-content-center" href = "inicio">
    <div class = "sidebar-brand-icon rotate-n-1">
        <img class = "sidebar-card-illustration mb-2" src = "img/MESS_07_CuboMess_2.png" width = "40">
    </div>
    <div class = ""></div>
</a>

<!-- Divider -->
<hr class = "sidebar-divider my-0">

<!-- Nav Item - Dashboard -->
<li class = "nav-item active">
    <a class = "nav-link" href = "inicio">
        <i class = "fas fa-fw fa-home"></i>
        <span>Inicio</span></a>
</li>

<!-- Divider -->
<hr class = "sidebar-divider">

<!-- Heading -->
<div class = "sidebar-heading">
    <span class="badge text-xl-white">
        Opciones
    </span>
</div>

<!-- Menú Principal -->
<li class = "nav-item">
    <a class = "nav-link collapsed" href = "#" data-toggle = "collapse" data-target = "#collapseTwo" aria-expanded = "true" aria-controls = "collapseTwo">
        <i class = "fas fa-fw fa-cog"></i>
        <span>Solicitudes</span>
    </a>
    <div id = "collapseTwo" class = "collapse" aria-labelledby = "headingTwo" data-parent = "#accordionSidebar">
        <div class = "bg-white py-1 collapse-inner rounded">
            <h6 class = "collapse-header">Mis solicitudes:</h6>
            <a class = "collapse-item" href = "nuevasolicitud">Nueva solicitud</a>
            <a class = "collapse-item" href = "solicitudestatus">Estatus de mis solicitudes</a>
        <?php
            include 'conn.php';
            $noEmp = $_COOKIE['noEmpleado'];
                                                            
            $Qrol = "SELECT rol FROM usuarios WHERE noEmpleado = $noEmp";
            $resRol= mysqli_query( $conn, $Qrol ) or die (mysqli_error($conn));
            
            While ($row = mysqli_fetch_array($resRol)){
                $rol = utf8_encode($row["rol"]);
            }
            if(($rol == 3) || ($noEmp ==  276 || $noEmp ==  244 )){
        
                echo '<a class = "collapse-item" href = "solicitudempleado">Solicitudes para revisar</a>';
                if ($noEmp ==  19) {  
                     echo '<a class = "collapse-item" href = "calendarioVacaciones">Calendarios de Vacaciones</a>';
                }else{
                    echo '<a class = "collapse-item" href = "calendarioVacacionesJefes">Calendarios de Vacaciones</a>';
                }
            }
        ?>
        </div>
    </div>
</li>
<!-- Menú ADMINISTRACION -->
<?php
$noEmp = $_COOKIE['noEmpleado'];
if ($noEmp == 183 || $noEmp ==  276 || $noEmp == 403 || $noEmp ==  523){
?>
    <li class = "nav-item">
        <a class = "nav-link collapsed" href = "#" data-toggle = "collapse" data-target = "#collapseUtilities"
            aria-expanded = "true" aria-controls = "collapseUtilities">
            <i class = "fas fa-fw fa-wrench"></i>
            <span>Administración</span>
        </a>
        <div id = "collapseUtilities" class = "collapse" aria-labelledby = "headingUtilities"
            data-parent = "#accordionSidebar">
            <div class = "bg-white py-2 collapse-inner rounded">
                <a class = "collapse-item" href = "personal">Personal</a>
                <a class = "collapse-item" href = "areaspuestos">Areas/Puestos/Regiones</a>            
                <a class = "collapse-item" href = "listavacaciones">Lista de Vacaciones</a>
                <a class = "collapse-item" href = "calendarioVacaciones">Calendario de Vacaciones</a>
                <a class = "collapse-item" href = "mandarNomina">Control pagos nómina</a>
                
            </div>
        </div>
    </li>
<?php
}            
?>
<!-- Menú Sala De Juntas -->
    <li class="nav-item">
        <a class="nav-link" href="saladejuntas/">
            <i class="fas fa-fw fa-calendar-day"></i>
            <span>Sala de Juntas</span>
        </a>
    </li>
<!-- Boton Cambiar Contraseña -->
<button type="button" class="nav-item" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
    Cambiar Contraseña
</button>
<!-- Divider -->
<hr class = "sidebar-divider d-none d-md-block">

<!-- Sidebar Toggler (Sidebar) -->
<div class = "text-center d-none d-md-inline">
    <button class = "rounded-circle border-0" id = "sidebarToggle"></button>
</div>

<!-- Sidebar Message 
<div class = "sidebar-card d-none d-lg-flex">
    <img class = "sidebar-card-illustration mb-2" src = "img/undraw_rocket.svg" alt = "...">
    <p class = "text-center mb-2"><strong>SB Admin Pro</strong> is packed with premium features, components, and more!</p>
    <a class = "btn btn-success btn-sm" href = "https://startbootstrap.com/theme/sb-admin-pro">Upgrade to Pro!</a>
</div>-->

</ul>
<!-- End of Sidebar -->