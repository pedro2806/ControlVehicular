<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Navegador no compatible</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8f9fa;
            color: #333;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: #fff;
            padding: 2em 3em;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        .chrome-logo {
            width: 80px;
            margin-bottom: 1em;
        }
        h1 {
            color: #d32f2f;
        }
    </style>
    <script>
        // Detecta si el navegador es Chrome
        if (!/Chrome/.test(navigator.userAgent) || /Edge|OPR|Brave/.test(navigator.userAgent)) {
            // No es Chrome, mostrar la página
        } else {
            // Es Chrome, redirigir a la página principal
            window.location.href = "index.php";
        }
    </script>
</head>
<body>
    <div class="container">
        <img src="https://www.google.com/chrome/static/images/chrome-logo.svg" alt="Chrome Logo" class="chrome-logo">
        <h1>Navegador no compatible</h1>
        <p>Este sistema solo puede ser utilizado desde el navegador <strong>Google Chrome</strong>.</p>
        <p>Por favor, acceda nuevamente usando Chrome.</p>
        
    </div>
</body>
</html>