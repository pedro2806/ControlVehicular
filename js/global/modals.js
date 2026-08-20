function verificarPermisoUbicacion() {
    if (!navigator.geolocation) return;
    var banner = document.getElementById('avisoUbicacion');
    if (!banner) return;
    var titulo = document.getElementById('avisoUbicacionTitulo');
    var msg    = document.getElementById('avisoUbicacionMsg');
    var btn    = document.getElementById('btnAceptarUbicacion');

    // Mensajes cortos y sin recetas de ajustes del sistema: esas instrucciones cambian
    // por navegador y versión, y suelen apuntar a interruptores que el usuario no
    // siempre puede tocar. Aquí solo se dice qué falta; lo accionable desde la página es
    // el botón, y de eso se encarga aplicar().
    var TEXTOS = {
        denied: ['Se necesita la ubicación activada',
                 'Permite el acceso a tu ubicación para poder usar Control Vehicular.'],
        prompt: ['Se necesita la ubicación activada',
                 'Acepta el permiso de ubicación cuando el navegador te lo solicite.']
    };

    var estadoActual = 'prompt';

    // localStorage puede lanzar (Safari en navegación privada, bloqueo de datos de sitio
    // en el navegador de una app). Antes el setItem iba ANTES de ocultar el banner, así
    // que al lanzar dejaba el aviso puesto aunque el permiso estuviera concedido.
    function recordar(valor) {
        try { localStorage.setItem('cv_ubicacion_aceptada', valor); } catch (e) {}
    }
    function recordado() {
        try { return localStorage.getItem('cv_ubicacion_aceptada'); } catch (e) { return null; }
    }

    function aplicar(state) {
        estadoActual = state;
        if (state === 'granted') {
            banner.classList.add('d-none');   // ocultar primero: el storage puede fallar
            recordar('1');
            return;
        }
        if (state === 'prompt' && recordado()) {
            banner.classList.add('d-none');
            return;
        }
        var t = TEXTOS[state] || TEXTOS.prompt;
        titulo.textContent = t[0];
        msg.textContent    = t[1];
        if (btn) {
            // Denegado no se puede volver a preguntar por JS: el navegador ya no muestra
            // el diálogo. Antes el botón se ocultaba y el usuario quedaba sin salida;
            // ahora ofrece releer el permiso después de cambiarlo en los ajustes.
            btn.innerHTML = (state === 'denied')
                ? '<i class="fas fa-rotate-right me-1"></i> Volver a comprobar'
                : '<i class="fas fa-check me-1"></i> Habilitar ubicación';
            btn.classList.remove('d-none');
        }
        banner.classList.remove('d-none');
    }

    /**
     * Sondea el permiso real. permissions.query es una OPTIMIZACIÓN (lee el estado sin
     * abrir el diálogo y avisa de cambios), pero la FUENTE DE VERDAD es getCurrentPosition,
     * que funciona en todos los navegadores.
     *
     * Este era el fallo de raíz del aviso que no se iba en iPhone: Safari no implementa
     * permissions.query para geolocation, así que se caía al respaldo y daba 'prompt'
     * SIN preguntarle nunca a la API de ubicación. Con el permiso concedido o no, el
     * resultado era el mismo y el aviso se quedaba puesto.
     *
     * No añade diálogos extra: encabezado.php ya llama a obtenerCoordenadas() en cada
     * carga, que hace su propio getCurrentPosition.
     */
    function sondear() {
        navigator.geolocation.getCurrentPosition(
            function () { aplicar('granted'); },
            function (err) {
                // Solo el código 1 (PERMISSION_DENIED) significa bloqueo. Los códigos 2 y 3
                // (sin señal / se agotó el tiempo) son fallos de GPS, no de permiso: decir
                // "bloqueada" ahí manda al usuario a revisar ajustes que están bien.
                if (err && err.code === 1) aplicar('denied');
                else                       aplicar('prompt');
            },
            { timeout: 8000, maximumAge: 60000 }
        );
    }

    function consultar() {
        if (navigator.permissions && navigator.permissions.query) {
            navigator.permissions.query({ name: 'geolocation' }).then(function (status) {
                if (status.state === 'granted' || status.state === 'denied') {
                    aplicar(status.state);
                } else {
                    sondear();   // 'prompt' no distingue: preguntar de verdad
                }
                status.onchange = function () { aplicar(status.state); };
            }).catch(sondear);
        } else {
            sondear();   // Safari/iOS: no hay permissions.query, la API real decide
        }
    }

    consultar();

    // Al volver a la pestaña hay que reconsultar: es justo lo que hace el usuario tras
    // cambiar el permiso en los ajustes del navegador, y ahí status.onchange no siempre
    // llega (página congelada en segundo plano o restaurada del bfcache). Solo se
    // reconsulta si el aviso está visible, para no gastar consultas cuando ya está todo bien.
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && !banner.classList.contains('d-none')) consultar();
    });
    window.addEventListener('pageshow', function (e) {
        if (e.persisted && !banner.classList.contains('d-none')) consultar();
    });

    if (btn) {
        btn.addEventListener('click', function () {
            if (estadoActual === 'denied') {
                consultar();
                return;
            }
            navigator.geolocation.getCurrentPosition(
                function ()    { aplicar('granted'); },
                function (err) { if (err && err.code === err.PERMISSION_DENIED) aplicar('denied'); },
                { timeout: 8000, maximumAge: 0 }
            );
        });
    }
}

function obtenerCoordenadas() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function (position) {
                var lat = position.coords.latitude.toFixed(6);
                var lon = position.coords.longitude.toFixed(6);
                $("#coordenadasCheck").val(lat + ", " + lon);
            },
            function (error) {}
        );
    }
}

function obtenerUbicacionObligatoria() {
    return new Promise(function (resolve, reject) {
        if (!navigator.geolocation) { reject(); return; }
        navigator.geolocation.getCurrentPosition(
            function (pos) {
                resolve({
                    lat: parseFloat(pos.coords.latitude.toFixed(6)),
                    lng: parseFloat(pos.coords.longitude.toFixed(6))
                });
            },
            function () { reject(); },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    });
}

function avisoUbicacionObligatoria() {
    Swal.fire({
        icon: 'error',
        title: 'Ubicación obligatoria',
        text: 'No pudimos obtener tu ubicación, y es obligatoria para continuar.',
        confirmButtonText: 'Entendido'
    });
}

function agregarInputImagen(containerId, inputName, maxExtra) {
    var contenedor = document.getElementById(containerId);
    var inputs = contenedor.querySelectorAll('input[type="file"]');
    maxExtra = maxExtra || 3;
    if (inputs.length < maxExtra) {
        var nuevoInput = document.createElement('input');
        nuevoInput.type = 'file';
        nuevoInput.className = 'form-control mt-1';
        nuevoInput.name = inputName;
        nuevoInput.accept = '.jpg,.jpeg,.png';
        contenedor.appendChild(nuevoInput);
    } else {
        Swal.fire({ icon: 'warning', title: 'Límite alcanzado', text: 'Solo puedes subir hasta ' + (maxExtra + 1) + ' imágenes.' });
    }
}
