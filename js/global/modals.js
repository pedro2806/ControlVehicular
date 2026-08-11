function verificarPermisoUbicacion() {
    if (!navigator.geolocation) return;
    var banner = document.getElementById('avisoUbicacion');
    if (!banner) return;
    var titulo = document.getElementById('avisoUbicacionTitulo');
    var msg    = document.getElementById('avisoUbicacionMsg');
    var btn    = document.getElementById('btnAceptarUbicacion');

    // El permiso del SITIO en el navegador y el GPS del teléfono son cosas distintas:
    // activar la ubicación del celular no desbloquea un sitio que ya fue denegado. El
    // texto de 'denied' lo dice explícitamente porque es la confusión más común.
    var TEXTOS = {
        denied: ['Ubicación bloqueada', 'Activar el GPS del teléfono no basta: la ubicación está bloqueada para este sitio en el navegador. Permítela desde el candado de la barra de direcciones (Ajustes del sitio → Ubicación) y pulsa "Volver a comprobar".'],
        prompt: ['Habilita el acceso a tu ubicación', 'Para el correcto funcionamiento de Control Vehicular necesitamos acceso a tu ubicación y cookies. Acepta los permisos cuando el navegador te lo solicite.']
    };

    var estadoActual = 'prompt';

    function aplicar(state) {
        estadoActual = state;
        if (state === 'granted') {
            localStorage.setItem('cv_ubicacion_aceptada', '1');
            banner.classList.add('d-none');
            return;
        }
        if (state === 'prompt' && localStorage.getItem('cv_ubicacion_aceptada')) {
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

    function consultar() {
        if (navigator.permissions && navigator.permissions.query) {
            navigator.permissions.query({ name: 'geolocation' }).then(function (status) {
                aplicar(status.state);
                status.onchange = function () { aplicar(status.state); };
            }).catch(function () { aplicar('prompt'); });
        } else {
            // Safari/iOS no implementa permissions.query para geolocation.
            aplicar('prompt');
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
        text: 'No pudimos obtener tu ubicación. Habilita el GPS y el permiso de ubicación del navegador e inténtalo de nuevo.',
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
