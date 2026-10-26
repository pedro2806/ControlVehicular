function getCookie(name) {
    var cookies = new URLSearchParams(document.cookie.replace(/; /g, '&'));
    return cookies.get(name) || undefined;
}
var leerCookie = getCookie;

/**
 * Reduce una imagen antes de subirla. Resuelve con el Blob comprimido, o con null si no
 * se pudo comprimir (quien llama decide si manda el original).
 *
 * IMPORTANTE: esta promesa SIEMPRE se resuelve. Antes no tenía onerror ni límite de
 * tiempo, así que un archivo que el navegador no lograba decodificar (típico con los HEIC
 * del iPhone, o una foto corrupta) dejaba la promesa colgada para siempre. Cualquier
 * código que esperara a la compresión antes de enviar se quedaba esperando en silencio:
 * ni guardaba ni avisaba.
 */
/**
 * Diferencia de km contra el último registro a partir de la cual se pide confirmar.
 * Un crédito de gasolina rinde del orden de 1,500 km, así que un salto mayor entre dos
 * capturas ya es raro; un dígito de más da saltos de cientos de miles.
 */
var UMBRAL_SALTO_KM = 2000;

/**
 * Pide confirmar el kilometraje cuando supera por mucho al último registrado.
 *
 * Un dígito de más (2,697,793 en vez de 269,793) se guardaba sin aviso y, como el km no
 * puede bajar del último registrado, el vehículo quedaba bloqueado hasta que alguien
 * corrigiera la BD a mano (ticket #4420). No bloquea: un salto grande puede ser real
 * (semanas sin capturar), así que solo pregunta.
 *
 * Llama a continuar() si no hace falta preguntar o si el usuario confirma; alCorregir()
 * (opcional) si elige corregir.
 */
function confirmarSaltoKm(kmNuevo, kmUltimo, continuar, alCorregir) {
    var nuevo = parseInt(kmNuevo, 10);
    var ultimo = parseInt(kmUltimo, 10);
    if (!(ultimo > 0) || isNaN(nuevo) || nuevo - ultimo <= UMBRAL_SALTO_KM) {
        continuar();
        return;
    }
    var fmt = function (n) { return n.toLocaleString('es-MX'); };
    Swal.fire({
        icon: 'warning',
        title: '¿El kilometraje es correcto?',
        html: 'Capturaste <b>' + fmt(nuevo) + ' km</b> y el último registro del vehículo es <b>'
            + fmt(ultimo) + ' km</b> (' + fmt(nuevo - ultimo) + ' km más).'
            + '<br>Revisa que no tenga un dígito de más.',
        showCancelButton: true,
        confirmButtonText: 'Sí, es correcto',
        cancelButtonText: 'Corregir',
        reverseButtons: true,
        focusCancel: true,
        // Sin esto SweetAlert devuelve el foco al botón de guardar y le gana a alCorregir,
        // que quiere dejarlo en el campo de km para corregirlo.
        returnFocus: false
    }).then(function (r) {
        if (r.isConfirmed) continuar();
        else if (typeof alCorregir === 'function') alCorregir();
    });
}

function comprimirImagen(file, maxAncho, calidad) {
    maxAncho = maxAncho || 1280;
    calidad = calidad || 0.7;
    return new Promise(function (resolve) {
        var terminado = false;
        function terminar(blob) {
            if (terminado) return;
            terminado = true;
            clearTimeout(temporizador);
            resolve(blob || null);
        }

        // Red de seguridad final: si algún paso no dispara ningún evento (ni load ni
        // error), a los 15 s se sigue adelante sin comprimir en vez de colgarse.
        var temporizador = setTimeout(function () { terminar(null); }, 15000);

        var reader = new FileReader();
        reader.onerror = function () { terminar(null); };
        reader.onload = function (e) {
            var img = new Image();
            img.onerror = function () { terminar(null); };
            img.onload = function () {
                try {
                    var canvas = document.createElement('canvas');
                    var ratio = Math.min(maxAncho / img.width, 1);
                    canvas.width = img.width * ratio;
                    canvas.height = img.height * ratio;
                    var ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                    canvas.toBlob(function (blob) { terminar(blob); }, 'image/jpeg', calidad);
                } catch (err) {
                    // Safari lanza aquí con imágenes muy grandes (límite de memoria de canvas).
                    terminar(null);
                }
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });
}
