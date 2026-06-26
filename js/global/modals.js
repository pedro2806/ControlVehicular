function verificarPermisoUbicacion() {
    if (!navigator.geolocation) return;
    var banner = document.getElementById('avisoUbicacion');
    if (!banner) return;
    var titulo = document.getElementById('avisoUbicacionTitulo');
    var msg    = document.getElementById('avisoUbicacionMsg');
    var btn    = document.getElementById('btnAceptarUbicacion');

    var TEXTOS = {
        denied: ['Ubicación bloqueada', 'Tu navegador tiene bloqueada la ubicación para este sitio.'],
        prompt: ['Habilita el acceso a tu ubicación', 'Para el correcto funcionamiento de Control Vehicular necesitamos acceso a tu ubicación y cookies. Acepta los permisos cuando el navegador te lo solicite.']
    };

    function aplicar(state) {
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
        banner.classList.remove('d-none');
    }

    if (navigator.permissions && navigator.permissions.query) {
        navigator.permissions.query({ name: 'geolocation' }).then(function (status) {
            aplicar(status.state);
            status.onchange = function () { aplicar(status.state); };
        }).catch(function () { aplicar('prompt'); });
    } else {
        aplicar('prompt');
    }

    if (btn) {
        btn.addEventListener('click', function () {
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
