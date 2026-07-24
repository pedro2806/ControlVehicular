<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=no">
	<title>MESS</title>
	<script>
		// Cierre de sesión: borra las cookies y redirige. Sin jQuery ni getCookie
		// (antes fallaba con ReferenceError y dejaba la pantalla en blanco).
		(function () {
			var kill = "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/";
			[
				"antiguedad", "diasD", "noEmpleado", "nombredelusuario",
				"nombredelusuarioL", "rol", "SesionLogin", "id_usuario",
				"id_usuarioL", "navSesion", "gps"
			].forEach(function (c) {
				document.cookie = c + kill;
			});
			window.location.replace("../loginMaster/inicio");
		})();
	</script>
</head>
<body></body>
</html>
