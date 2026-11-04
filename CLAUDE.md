# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Entorno de desarrollo

No hay build system. El servidor es WAMP64 (Apache + PHP 8.3 + MySQL 8.4). Los archivos se editan directamente y Apache los sirve en `http://localhost/ControlVehicular/`. No hay compilación, transpilación ni gestor de paquetes.

Para probar cambios: recargar el navegador. Para errores PHP: revisar `C:\wamp64\logs\php_error.log`.

### Producción vs local

| | Producción (`messbook.com.mx`, cPanel) | Local (WAMP) |
|---|---|---|
| PHP | 8.2, `display_errors` apagado (un fatal sale como HTTP 500 vacío) | 8.3.28 |
| `sql_mode` | `STRICT_TRANS_TABLES` | vacío (`my.ini` de mysql8.4.7, línea 80) |
| Datos | los reales | copia atrasada |

Por el `sql_mode`, muchos bugs no se reproducen en local: MySQL permisivo convierte `''` a 0 en columnas int y acepta fechas vacías; producción los rechaza, y desde PHP 8.1 mysqli lanza excepción en vez de devolver false (así se cayó el checklist el 04/09/2026: `checklist_documentacion.si_no` es `int NOT NULL` y recibía `''`). Antes de dar por bueno un cambio que escriba en la BD:
```sql
SET GLOBAL sql_mode='STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION';  -- se pierde al reiniciar MySQL
```

### Verificar sin navegador

- **Lint PHP:** `/c/wamp64/bin/php/php8.3.28/php.exe -l archivo.php` (es la versión que carga Apache; hay varias instaladas).
- **MySQL:** `/c/wamp64/bin/mysql/mysql8.4.7/bin/mysql.exe -u root mess_control_vehicular -e "..."`. El `mariadb11.4.9` instalado no es el que usa la app.
- **Endpoints:** como la auth es por cookies, `curl --cookie "noEmpleado=...; id_usuario=...; id_usuarioL=...; rol=1" -d "accion=..." http://localhost/ControlVehicular/acciones_x.php`.
- **Datos de producción:** la BD de producción no se alcanza directo, pero sus endpoints sí: el mismo `curl` contra `https://messbook.com.mx/ControlVehicular/...` con las cookies de alguien con `verTodosVehiculo`. **Solo acciones de lectura** (`obtener*`, `consultar*`, `llena*`); nunca `checkInQR`, `registraGas`, `Captura*` ni nada que escriba.
- **JS embebido en una vista:** extraer el bloque y quitar los tags PHP con `perl -0pe 's/<\?(php|=).*?\?>/0/gs'` antes de `node --check` (un `sed` con `[^?]*` falla por el `??` de PHP). En esta máquina no hay `python`; para procesar JSON usar `node`.
- **Pruebas de navegador headless:** abortar con `page.route` las peticiones que contengan `checkInQR`, `registrarPrestamoQR`, `registraGas` o `calcular_ruta` antes de pulsar cualquier botón de guardar. La BD local guarda de verdad (el 22/10/2026 una prueba dejó 3 check-ins en `actividad_vehiculo`, `kilometrajes`, `km_calculados` y `reporte_km`).
- **Datos de prueba:** los vehículos sembrados llevan placa con prefijo `XXX` (`inventario.placa` es `varchar(9)`); se limpian con `placa LIKE 'XXX%'`.

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

`conn.php` está en `.gitignore` pero sigue trackeado (se commiteó antes). No cambiarle las credenciales: las de producción también conectan en local, y un commit con `root` rompería producción.

### Identificadores de usuario

Hay tres números distintos y es fácil cruzarlos:

| Campo | Qué es |
|---|---|
| `usuarios.id` | PK autoincremental de la tabla `usuarios` de CV. **Nadie lo referencia**; no confundirlo con `id_usuario`. |
| `usuarios.id_usuario` | El id de RRHH (`mess_rrhh.usuarios.id_usuario`). Es el de la cookie `id_usuarioL` y al que apuntan `inventario.id_usuario`, `inventario.id_us_asignado`, `actividad_vehiculo.id_usuario`, `solicitudes_gas.id_usuario`, etc. |
| `noEmpleado` | Número de empleado. Es el de la cookie `noEmpleado` y el de `accesos_especiales`. |

**`id_usuario` no es único**, ni en `usuarios` de CV ni en `mess_rrhh.usuarios`. Un `LEFT JOIN usuarios u ON u.id_usuario = x.id_usuario` duplica filas; emparejar con la fila de mayor id:
```sql
LEFT JOIN usuarios u ON u.id = (SELECT MAX(u2.id) FROM usuarios u2 WHERE u2.id_usuario = x.id_usuario)
```
En RRHH hay ids compartidos por dos personas (112, 141, 376, 539 y el relleno 99999; pendiente de corregir en RRHH, no en código). Quienes comparten id ven los vehículos y registros del otro, y el nombre que se muestra puede ser el de la otra persona. El caso de 112 (Edgar Galván 342 / Edgar Emmanuel Alvarez 502) además tiene en CV la fila `usuarios.id = 105` con id_usuario 112 y el noEmpleado 502 de Emmanuel.

### Reglas de datos

- **Vehículos solo activos:** toda consulta que liste vehículos o registros ligados a uno (check-ins, cargas, checklists) filtra `inventario.estatus = 'Activo'`, también para quien tiene `verTodosVehiculo`. La única excepción es la pantalla Vehículos (`vehiculos.php`), que tiene su apartado de inactivos. El valor real es `'Activo '` con espacio, pero la colación es PAD SPACE y `= 'Activo'` empata.
- **Préstamos sin check-out:** un préstamo `EN CURSO` no vence por tiempo; lo cierra el siguiente check-in de ese vehículo, sea de quien sea (`cerrarPrestamosDelVehiculo()` en `includes/api_bootstrap.php`). El `fecha_fin = NOW() + 1 DAY` de `registrarPrestamoQR` es solo relleno. Los `AUTORIZADO`/`PENDIENTE*` cuya fecha ya pasó se pasan a `CANCELADO` (`cancelarPrestamosNoUsados()`). No proponer cierres por tiempo ni flujos de check-out.
- **Último km de un vehículo:** sale de `actividad_vehiculo`, `carga_gasolina` y `kilometrajes`, la lectura más reciente de las tres (`ultimaLecturaKm()` / `obtenerUltimoKM()` en `includes/api_bootstrap.php`). Un km mal capturado en cualquiera de ellas bloquea las siguientes capturas, así que al corregirlo hay que buscarlo en las tres.
- **KM Mess y KM personales** (regla del negocio, 04/11/2026: "todo lo registrado en QRide es trabajo Mess; los días sin check-in son uso personal"): el km de un check-in es lo que avanzó el odómetro desde el check-in anterior del vehículo; si entre los dos hubo al menos un día completo sin check-ins es personal, si no es Mess, traiga lo que traiga en `ot`. Mess + personales = odómetro. Una sola definición, `sqlKmMessCheckin()` en `calcular_ruta.php`, que usan el panel del mapa y las tablas de Power BI; no recalcularlo por otro lado. La ruta INEGI ya no entra en el cálculo. Las OT/OV se reconocen aunque traigan texto pegado o `MESS-OV-` (`resolverOvOt()` en PHP y `sqlCheckinOvOt()` en SQL, con las mismas expresiones).

## Diseño: MESS Design System (Bootstrap 5.3)

Desde el rediseño de julio 2026 el stack visual es **solo Bootstrap 5.3** (`data-bs-toggle`, `data-bs-target`) + Font Awesome 6 + `css/mess-ds.css`:

- `css/mess-ds.css` — design system MESS (portado de `Tickets/css/estilos.css`). Tokens CSS en `:root` y `body.theme-dark` (dark mode). **Nunca hex hardcoded en componentes nuevos — usar `var(--accent)`, `var(--text)`, etc.**
- `js/mess-ds.js` — toggle del sidebar y del tema (clave localStorage `mess-theme`, compartida entre sistemas MESS). Se carga desde `encabezado.php`.
- El anti-flash del tema oscuro vive al inicio de `menu.php`.
- Se conserva la estructura DOM de SB-Admin-2 (`#wrapper > #accordionSidebar + #content-wrapper > #content > .topbar`), pero `sb-admin-2.css/js` ya **no** se cargan. Hay una capa de compatibilidad BS4 en mess-ds.css (`ml-*`/`mr-*`, `font-weight-bold`, `text-gray-*`); no usar esas clases en código nuevo.
- `<head>` estándar de cada vista: fuente Nunito + Bootstrap 5.3.3 CDN + FA 6.5.1 CDN + `css/mess-ds.css`. El JS de Bootstrap, SweetAlert2, jQuery y `app.css` los inyecta `encabezado.php`.

Los modales que se definen en `encabezado.php` usan **BS5**.

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

El **Mapa de ubicaciones** (`mapa_ubicaciones.php` + `acciones_mapa.php`) usa el acceso especial `verMapaGPS` (19, 183, 191, 276 y 523); lo validan el menú, la página y el endpoint. Reemplazó al Mapa GPS en la retro del 26/10/2026: `mapa_gps.php` solo redirige, y se borraron `obtener_ubicaciones.php` y `obtener_vehiculos_usuario.php`, que respondían sin sesión. Antes el acceso estaba hardcodeado a `noEmpleado` IN ('19', '183', '276', '191') en `menu.php`.

<!-- markitdown:inicio — sección común a los repos MESS (2026-10-28). Si la cambias, cámbiala en todos. -->
## Leer documentos (PDF, Word, Excel, PowerPoint) con MarkItDown

Cuando tengas que leer un documento binario —del repo (manuales, formatos, plantillas) o uno que te pase el usuario— conviértelo a Markdown con [MarkItDown](https://github.com/microsoft/markitdown) en lugar de intentar abrirlo directo:

```bash
markitdown "Manual Planeacion.pdf"                # imprime el Markdown; léelo de la salida
markitdown "Manual.docx" -o "$TEMP/manual.md"     # si es largo: a un temporal y léelo por partes
```

- Si el comando no existe: `py -m pip install "markitdown[pdf,docx,pptx,xlsx,xls,outlook]"` y reintenta (si `markitdown` no quedó en el PATH, usa `py -m markitdown archivo`). No instales `markitdown[all]`: tarda mucho y trae dependencias de Azure que no usamos.
- Sirve para: PDF con texto, DOCX, PPTX, XLSX/XLS, HTML, CSV, JSON, XML, EPUB, MSG de Outlook y ZIP (convierte cada archivo de adentro).

**Reglas**

1. **Solo para leer y entender.** MarkItDown no es dependencia de este sistema: no lo llames desde PHP ni lo metas en un flujo en tiempo real. La ingesta de documentos para IA vive en MESS-DOC.
2. **Nunca guardes el `.md` generado dentro de esta carpeta**: Apache la sirve y quedaría público. Usa `$TEMP` (`%TEMP%` en cmd).
3. **Excel con cifras → no.** MarkItDown aplana las hojas, mete `NaN` y `Unnamed: n` y pierde los tipos. Para datos numéricos usa pandas o consulta MySQL directo.
4. **PDF que sale vacío o casi vacío** = probablemente escaneado (imagen). Avísalo; no inventes el contenido.
5. Las imágenes incrustadas salen como `![](data:...)`: ignóralas, MarkItDown no las describe.
6. Es de una sola vía: para **editar** un .docx/.xlsx/.pptx no sirve.
<!-- markitdown:fin -->
