function getCookie(name) {
    var cookies = new URLSearchParams(document.cookie.replace(/; /g, '&'));
    return cookies.get(name) || undefined;
}
var leerCookie = getCookie;

function comprimirImagen(file, maxAncho, calidad) {
    maxAncho = maxAncho || 1280;
    calidad = calidad || 0.7;
    return new Promise(function (resolve) {
        var reader = new FileReader();
        reader.onload = function (e) {
            var img = new Image();
            img.onload = function () {
                var canvas = document.createElement('canvas');
                var ratio = Math.min(maxAncho / img.width, 1);
                canvas.width = img.width * ratio;
                canvas.height = img.height * ratio;
                var ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                canvas.toBlob(function (blob) { resolve(blob); }, 'image/jpeg', calidad);
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });
}
