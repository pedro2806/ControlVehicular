# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Entorno de desarrollo

No hay build system. El servidor es WAMP64 (Apache + PHP 7.x + MySQL). Los archivos se editan directamente y Apache los sirve en `http://localhost/ControlVehicular/`. No hay compilación, transpilación ni gestor de paquetes.

Para probar cambios: recargar el navegador. Para errores PHP: revisar `C:\wamp64\logs\php_error.log`.

## Arquitectura de páginas

Cada funcionalidad sigue este patrón de 2 archivos:
- `pagina.php` — vista HTML pura. **No incluye `conn.php` ni ejecuta queries.** Todo acceso a BD se hace vía AJAX.
- `acciones_pagina.php` — endpoint AJAX. Recibe `$_POST['accion']`, ejecuta queries y devuelve JSON.

Todas las vistas incluyen al inicio:
```php
<?php include 'menu.php'; ?>   // sidebar + auth check
<?php include 'encabezado.php'; // topbar + modales globales (check-in, gasolina, actividades)
```

## Autenticación

**No usa `$_SESSION`**. Todo está en cookies:

| Cookie | Contenido |
|--------|-----------|
| `noEmpleado` | ID del empleado (primary auth check) |
| `id_usuarioL` | ID interno de usuario en BD |
| `nombredelusuarioL` | Nombre completo |
| `rol` | 1=usuario, 2=gerente, 3=jefe |
| `navSesion` | `'Navegador'` o `'appMovil'` |
| `gps` | `'activo'` o `'inactivo'` |

Guard estándar al inicio de cada archivo de vista:
```php
if ($_COOKIE['noEmpleado'] == '' || $_COOKIE['noEmpleado'] == null) {
    echo '<script>window.location.assign("index")</script>';
}
```

## Base de datos

`conn.php` crea **dos conexiones** — la procedimental (`mysqli_connect`) y luego la OO (`new mysqli`). La variable `$conn` queda apuntando a la **OO**, que es la activa. Ignorar la primera.

- DB principal: `mess_control_vehicular`
- Tabla de permisos especiales: `mess_rrhh.accesos_especiales` (cross-DB join a la BD de RRHH)

Para permisos especiales, el campo `opcion` puede ser `'verTodosVehiculo'`, `'verQR'`, etc.

## Bootstrap: mezcla BS4 + BS5

El proyecto carga **dos versiones simultáneamente**:
- `vendor/bootstrap/` — Bootstrap 4 (SB-Admin-2 theme). Usa `data-toggle`, `data-target`.
- CDN en `<head>` de la mayoría de páginas — Bootstrap 5. Usa `data-bs-toggle`, `data-bs-target`.

Antes de agregar un componente, identificar qué versión usa la página. Los modales que se definen en `encabezado.php` usan **BS5**.

**Problema conocido — backdrop residual:** Cuando un modal BS5 se cierra con `.hide()` y a continuación se abre un SweetAlert2, el backdrop queda bloqueando la pantalla. Fix:
```javascript
modalEl.addEventListener('hidden.bs.modal', function () {
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    Swal.fire({ ... });
}, { once: true });
bootstrap.Modal.getInstance(modalEl).hide();
```

## Override de funciones de encabezado.php

`encabezado.php` define funciones JS globales (`cargarVehiculos`, `validarActividadesPendientes`, etc.) que aplican lógica del usuario logueado. Para sobreescribir su comportamiento en una página específica, redefinir la función al final del `<script>` de esa página — JS usa la última definición.

## Reglas en callbacks AJAX

Siempre verificar `Array.isArray(data)` antes de `.forEach()` en callbacks AJAX que esperan arrays. Las acciones_*.php devuelven un objeto de error cuando algo falla, y `[].forEach` sobre un objeto crashea silenciosamente.

## Fotos e imágenes

- Estructura de carpetas: `img_control_vehicular/{PLACA}/{actividad}/`
- El campo `foto_general` de la tabla `inventario` guarda rutas relativas. Algunos registros tienen rutas incorrectas. Siempre usar `onerror` en `<img>` para mostrar placeholder:
  ```html
  <img src="<?= $foto ?>" onerror="this.src='img/sin_foto.png'">
  ```
- Las fotos se comprimen antes de guardar con `reducirPesoImagen()` (JPEG 75%, PNG nivel 7).

## SQL y sentencias preparadas

El código antiguo usa concatenación directa (deuda técnica conocida). **Todo código nuevo debe usar sentencias preparadas** con `$conn->prepare()` / `bind_param()`. El charset debe forzarse con `mysqli_set_charset($conn, "utf8mb4")` al inicio de cada acciones_*.php.

## Módulo QR vehicular

`qr_vehiculo.php` es una landing page sin queries directas. El vehículo viene por `?v={id_vehiculo}`. Cuando se abren modales de check-in/gasolina desde esta página, el vehículo escaneado debe pre-seleccionarse aunque no esté asignado al usuario:

```javascript
var enLista = lista.some(v => v.id_vehiculo == vehiculoQR.id);
if (!enLista && vehiculoQR.id) lista.unshift({ id_vehiculo: vehiculoQR.id, ... });
select.val(vehiculoQR.id);
```

## Accesos especiales

Para dar acceso a funciones restringidas:
```sql
INSERT INTO mess_rrhh.accesos_especiales (noEmpleado, sistema, opcion, estatus)
VALUES ({noEmpleado}, 'ctrlVehicular', '{opcion}', 1);
```

El acceso GPS está hardcodeado a `noEmpleado` IN ('19', '183', '276', '191') en `menu.php`.
