<!DOCTYPE html>
<html lang = "sp">
<head>
    <meta charset = "utf-8">
    <meta http-equiv = "X-UA-Compatible" content = "IE = edge">
    <meta name = "viewport" content = "width = device-width, initial-scale = 1, shrink-to-fit = no">
    <meta name = "description" content = "">
    <meta name = "author" content = "">

    <title>MESS - Control Vehicular</title>

    <!-- MESS Design System -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    <link href="css/login.css" rel="stylesheet">
</head>
<body>    
    <div class = "fb-container">
        <div class = "fb-content">
            <div class = "fb-inner">
                <!--LOGIN-->
                <div class="fb-left">
                    <img src="img/QRide_grande.png" alt="Messbook" class="fb-logo-img">
                    <h2 class="fb-tagline" style="font-size: 18px !important;">
                        
                    </h2>
                </div>
                <div class = "fb-right">                                
                    <div class="fb-card">
                        <div class="fb-card-body">
                            <div class="fb-card-title">Iniciar sesión con tu cuenta de Messbook</div>
                            
                            <form class = "user" method = "POST">
                                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_GET['redirect'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="fb-input-group">
                                    <i class="fas fa-user fb-input-icon"></i>
                                    <input type="text" class="fb-input" id="InputEmail" name="InputEmail" aria-describedby="emailHelp" placeholder="Correo electrónico">
                                    <div class="fb-domain-text"></div>
                                </div>
                                
                                <div class="fb-input-group">
                                    <i class="fas fa-lock fb-input-icon"></i>
                                    <input type="password" class="fb-input" id="InputPassword" name="InputPassword" placeholder="Contraseña">
                                </div>
                                <div class="fb-check-wrap">
                                    <div class="form-check small">
                                        <input type="checkbox" class="form-check-input" id="customCheck">
                                        <label class="form-check-label" for="customCheck">Recordar mis datos</label>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <input class = "fb-login-btn" type = "submit" name = "btningresar" value = "   Acceder   "/>
                                </div>
                                <!--<a class = "small" href = "forgot-password">Olvide mi contraseña</a>-->
                                <br>
                                <br>
                                <br>
                            </form>                                
                        </div>
                    </div>
                </div>
            </div>                        
        </div>
        <div class="fb-footer">
            <div class="fb-footer-inner">
               
                
                <img src="../loginMaster/img/mess-desarrollo-b1.png" alt="Grupo Mess" class="fb-footer-logo">
                
                <div class="fb-footer-links">
                   Business Intelligence | Messbook ©️ <?php echo date("Y"); ?>
                </div>
            </div>
        </div>
    </div>            
    <?php
        if(isset($_COOKIE['noEmpleado']) && $_COOKIE['noEmpleado'] != '')
        {
           // header('location: inicio');
        }

        if(isset($_POST['btningresar']))
        {        
            include 'conn.php';
            
            $usuario = $_POST['InputEmail'];
            $pass = $_POST['InputPassword'];
            $usuario = explode('@', $usuario)[0];
            
            $usuarioEsc = mysqli_real_escape_string($conn, $usuario . '@mess.com.mx');
            $passEsc    = mysqli_real_escape_string($conn, $pass);
            $Qempresas  = "SELECT cv.*,
                                  TRIM(CONCAT(IFNULL(rrhh.nombres,''), ' ', IFNULL(rrhh.apellidos,''))) AS nombre_completo
                           FROM usuarios cv
                           LEFT JOIN mess_rrhh.usuarios rrhh ON rrhh.noEmpleado = cv.noEmpleado
                           WHERE cv.usuario = '$usuarioEsc' AND cv.password = '$passEsc' AND cv.estatus = 1";
            $res2 = mysqli_query($conn, $Qempresas);

            if (!$res2) {
                die("Error in query execution: " . mysqli_error($conn));
            }

            $nr = mysqli_num_rows($res2);

            if ($nr > 0) {
                while ($row2 = mysqli_fetch_array($res2)) {
                    $nombreEmpleado = ($row2["nombre_completo"] !== '') ? $row2["nombre_completo"] : $row2["nombre"];
                    $noEmpleado = $row2["noEmpleado"];
                    $id_usuario = $row2["id_usuario"];
                    $rol = $row2["rol"];
                    $gps = $row2["gps"];
                }
            }


            
            if($nr == 1)
            {
                 // $fila es el resultado de tu query SQL
                $passwordIngresado = $pass;
                $hashAlmacenado = $fila['hash_almacenado'];

                if (password_verify($passwordIngresado, $hashAlmacenado)) {
                    // ¡Contraseña correcta! Iniciar sesión.
                } else {
                    // Contraseña incorrecta.
                }

                if ($gps == "1") {                    
                    echo '<script>document.cookie = "gps=activo; expires=" + new Date(Date.now() + 99900000).toUTCString() + ";SameSite=Lax;";</script>';
                } else {                    
                    echo '<script>document.cookie = "gps=inactivo; expires=" + new Date(Date.now() + 99900000).toUTCString() + ";SameSite=Lax;";</script>';
                }

                echo '<script>document.cookie = "id_usuario='.$id_usuario.';expires=" + new Date(Date.now() + 99900000).toUTCString() + ";SameSite=Lax;";</script>';
                echo '<script>document.cookie = "nombredelusuario='.$nombreEmpleado.';expires=" + new Date(Date.now() + 99900000).toUTCString() + ";SameSite=Lax;";</script>';
                echo '<script>document.cookie = "noEmpleado='.$noEmpleado.';expires=" + new Date(Date.now() + 99900000).toUTCString() + ";SameSite=Lax;";</script>';
                echo '<script>document.cookie = "rol='.$rol.';expires=" + new Date(Date.now() + 99900000).toUTCString() + ";SameSite=Lax;";</script>';
                
                if($_GET['status'] == '1')
                {
                    echo '<script>document.cookie = "navSesion=appMovil; expires=" + new Date(Date.now() + 99900000).toUTCString() + ";SameSite=Lax;";</script>';
                }else{
                    echo '<script>document.cookie = "navSesion=Navegador; expires=" + new Date(Date.now() + 99900000).toUTCString() + ";SameSite=Lax;";</script>';
                }

                $redirect = $_POST['redirect'] ?? '';
                // Solo permitir rutas relativas (sin protocolo ni doble slash)
                $destino = (!empty($redirect) && !preg_match('/^(https?:)?\/\//i', $redirect))
                    ? $redirect
                    : 'inicio';
                echo '<script>window.location.assign("' . addslashes($destino) . '")</script>';
            }
            else if ($nr  ==  0)
            {
                echo '<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>';
                echo '<script>swal("Usuario o contraseña incorrectos!", "Vuelve a intentar!", "error");</script>';
            }
            
        }
    ?>
    <script src = "https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    $(document).ready(function () {
        //validarNavegadorChrome();    
    });        

    function validarNavegadorChrome() {
        var ua = navigator.userAgent;
        var isChromeDesktop = /Chrome/.test(ua) && /Google Inc/.test(navigator.vendor) && !/Edg/.test(ua) && !/OPR|Opera/.test(ua) && !/Brave/.test(ua);
        var isChromeMobile = /CriOS/.test(ua) && /Mobile/.test(ua);
        var isSafariMobile = /Safari/.test(ua) && /Mobile/.test(ua) && !/CriOS/.test(ua);
        var isAndroidWebView = /\bwv\b/.test(ua) || /Android.*Version\/[\d.]+/.test(ua);

        if (isChromeDesktop || isChromeMobile) {
            
        } else if (isSafariMobile) {
            // Safari en iOS
            Swal.fire({
                icon: 'warning',
                title: 'Navegador no recomendado',
                text: 'Estás usando Safari en un dispositivo móvil. Por favor, utiliza Google Chrome para una mejor experiencia.',
                footer: '<a href="navegadorValido.php">¿Por qué no puedo usar otro navegador?</a>'
            }).then(() => {
                window.location.href = "navegadorValido.php";
            });
        } else if (isAndroidWebView) {
            // WebView Android
            Swal.fire({
                icon: 'warning',
                title: 'Navegador no compatible',
                text: 'Estás usando un navegador no compatible. Por favor, utiliza Google Chrome.',
                footer: '<a href="navegadorValido.php">¿Por qué no puedo usar otro navegador?</a>'
            }).then(() => {
                window.location.href = "navegadorValido.php";
            });
        } else {
            // Otros navegadores
            Swal.fire({
                icon: 'warning',
                title: 'Navegador no compatible',
                text: 'Este sistema solo puede ser utilizado en Google Chrome.',
                footer: '<a href="navegadorValido.php">¿Por qué no puedo usar otro navegador?</a>'
            }).then(() => {
                window.location.href = "navegadorValido.php";
            });
        }
    }

    
    </script>
</body>
</html>