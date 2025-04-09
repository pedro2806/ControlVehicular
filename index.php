<!DOCTYPE html>
<html lang = "sp">
<head>
    <meta charset = "utf-8">
    <meta http-equiv = "X-UA-Compatible" content = "IE = edge">
    <meta name = "viewport" content = "width = device-width, initial-scale = 1, shrink-to-fit = no">
    <meta name = "description" content = "">
    <meta name = "author" content = "">

    <title>MESS - Control Vehicular</title>

    <!-- Custom fonts for this template-->
    <link href = "vendor/fontawesome-free/css/all.min.css" rel = "stylesheet" type = "text/css">    
    <!-- Custom styles for this template-->
    <link href = "css/sb-admin-2.min.css" rel = "stylesheet">
</head>
<body class = "bg-gradient-primary">
    <div class = "container">
        <div class = "row justify-content-center">
            <div class = "col-xl-10 col-lg-12 col-md-9">
                <div class = "card o-hidden border-0 shadow-lg my-5">
                    <div class = "card-body p-0">
                        <div class = "row justify-content-center">                                                        
                            <div class = "p-0 text-center">                                    
                                <img src = "img/MESS_05_Imagotipo.svg" alt = "Logo MESS" width = "300px">
                            </div>                            
                        </div>      
                        <div class = "row">
                            <!--LOGIN-->
                            <div class = "col-sm-2"></div>
                            <div class = "col-sm-8 d-flex flex-column align-items-center">
                                <div class = "p-0 w-100">
                                    <div class = "text-center">
                                        <b>
                                            Control Vehicular
                                        </b>
                                        <h1 class = "h4 text-gray-900 mb-4">Bienvenido</h1>
                                    </div>
                                    <form class = "user" method = "POST">
                                        <div class = "form-group">
                                            <input type = "text" class = "form-control form-control-user" id = "InputEmail" name = "InputEmail" aria-describedby = "emailHelp" placeholder = "Usuario">
                                            <span>@mess.com.mx</span>
                                        </div>
                                        <div class = "form-group">
                                            <input type = "password" class = "form-control form-control-user" id = "InputPassword" name = "InputPassword" placeholder = "Contraseña">
                                        </div>
                                        <div class = "form-group">
                                            <div class = "custom-control custom-checkbox small">
                                                <input type = "checkbox" class = "custom-control-input" id = "customCheck">
                                                <label class = "custom-control-label" for = "customCheck">Recordar usuario y contraseña</label>
                                            </div>
                                        </div>
                                        <div class="text-center">
                                            <input class = "btn btn-primary" type = "submit" name = "btningresar" value = "   Acceder   "/>
                                        </div>
                                        <!--<a class = "small" href = "forgot-password">Olvide mi contraseña</a>-->
                                        <br>
                                        <br>
                                        <br>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!--BARRA DE SOPORTE-->
                        <div class="row">  
                            <div class = "col-lg-12 mx-auto">
                                <center>
                                    <p class="alert alert-info" style="font-size: 0.9em;">
                                        Soporte del sistema:
                                        <a href="mailto:sebastian.gutierrez@mess.com.mx">sebastian.gutierrez@mess.com.mx</a> 
                                        o 
                                        <a href="mailto:pedro.martinez@mess.com.mx">pedro.martinez@mess.com.mx</a>
                                    </p>
                                </center>
                            </div>          
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
        session_start();
        if(isset($_SESSION['nombredelusuario']))
        {
            header('location: inicio');
        }

        if(isset($_POST['btningresar']))
        {        
            include 'conn.php';
            
            $usuario = $_POST['InputEmail'];
            $pass = $_POST['InputPassword'];
            $usuario = explode('@', $usuario)[0];
            
            $Qempresas  =  "SELECT  *, TIMESTAMPDIFF(YEAR,fechaIngreso,CURDATE()) AS antiguedad, rol FROM usuarios WHERE usuario  = '".$usuario."@mess.com.mx' and password  =  '".$pass."' AND estatus = 1";
            $res2 = mysqli_query($conn, $Qempresas);
            
            if (!$res2) {
                die("Error in query execution: " . mysqli_error($conn));
            }
            
            $nr = mysqli_num_rows($res2);
            
            if ($nr > 0) {
                while ($row2 = mysqli_fetch_array($res2)) {
                    $nombreEmpleado = $row2["nombre"];
                    $noEmpleado = $row2["noEmpleado"];
                    $antiguedad = $row2["antiguedad"];
                    $diasD = $row2["diasdisponibles"];
                    $rol = $row2["rol"];
                }
            }
    
            if($nr == 1)
            {            
                
                echo '<script>document.cookie = "antiguedad='.$antiguedad.';expires=" + new Date(Date.now() + 99900000).toUTCString() + ";SameSite=Lax;";</script>';
                echo '<script>document.cookie = "nombredelusuario='.$nombreEmpleado.';expires=" + new Date(Date.now() + 99900000).toUTCString() + ";SameSite=Lax;";</script>';
                echo '<script>document.cookie = "noEmpleado='.$noEmpleado.';expires=" + new Date(Date.now() + 99900000).toUTCString() + ";SameSite=Lax;";</script>';
                echo '<script>document.cookie = "diasD='.$diasD.';expires=" + new Date(Date.now() + 99900000).toUTCString() + ";SameSite=Lax;";</script>';
                echo '<script>document.cookie = "rol='.$rol.';expires=" + new Date(Date.now() + 99900000).toUTCString() + ";SameSite=Lax;";</script>';
                
                
                
                   
                echo '<script>window.location.assign("inicio")</script>';
                
            }
            else if ($nr  ==  0)
            {
                echo '<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>';
                echo '<script>swal("Usuario o contraseña incorrectos!", "Vuelve a intentar!", "error");</script>';
                
            }
            
        }
    ?>
    <script src = "https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <script src = "vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src = "js/sb-admin-2.min.js"></script>    

    <script>
    $(document).ready(function () {
        
    });        
    </script>
</body>
</html>