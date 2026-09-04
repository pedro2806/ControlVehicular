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
