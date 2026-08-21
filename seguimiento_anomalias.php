<?php
session_start();
require_once __DIR__ . '/includes/sesion_cookies.php';
include 'conn.php';
if ($_COOKIE['noEmpleado'] == '' || $_COOKIE['noEmpleado'] == null) {
    echo '<script>window.location.assign("index")</script>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Control Vehicular - Anomalías</title>
    <!-- MESS Design System -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="css/mess-ds.css" rel="stylesheet">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include 'menu.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'encabezado.php'; ?>
                <div class="container-fluid">

                    <div class="d-sm-flex align-items-center justify-content-between mb-3">
                        <h1 class="h3 mb-0 text-black-800">Historial de Anomalías</h1>
                        <span class="badge bg-secondary fs-6" id="badgeTotal"></span>
                    </div>

                    <!-- Filtros: mismos que el historial de siniestros -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-body py-3">
                            <div class="row g-2 align-items-end">
                                <div class="col-6 col-md-3">
                                    <label for="fDesde" class="form-label mb-1 small fw-semibold">Desde</label>
                                    <input type="date" class="form-control form-control-sm" id="fDesde">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label for="fHasta" class="form-label mb-1 small fw-semibold">Hasta</label>
                                    <input type="date" class="form-control form-control-sm" id="fHasta">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label for="fVehiculo" class="form-label mb-1 small fw-semibold">Vehículo</label>
                                    <select class="form-select form-select-sm" id="fVehiculo">
                                        <option value="">Todos</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-2 d-grid">
                                    <button class="btn btn-outline-secondary btn-sm" id="btnLimpiarFiltros">
                                        <i class="fas fa-eraser me-1"></i> Limpiar
                                    </button>
                                </div>
                            </div>
                            <div class="row g-2 mt-1">
                                <div class="col-12">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                        <input type="text" class="form-control border-start-0" id="buscadorFeed"
                                               placeholder="Buscar por placa, usuario o descripción...">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="feedAnomalias" class="row"></div>

                    <div id="noResultados" class="text-center text-muted py-5" style="display:none;">
                        <i class="fas fa-triangle-exclamation fa-3x mb-3 d-block"></i>
                        <p class="mb-0">No se encontraron anomalías.</p>
                    </div>
                </div>
            </div>
            <footer class="sticky-footer bg-white">
                <div class="container my-auto"><div class="copyright text-center my-auto">
                    <span>Copyright &copy; MESS <?php echo date("Y"); ?></span>
                </div></div>
            </footer>
        </div>
    </div>

    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

    <!-- Mapa de la ubicación. Mismo criterio que en ver_checkins.php: se abre dentro de
         la vista para no perder los filtros, con el embed de Google Maps (sin API key).
         El iframe se rellena al abrir, no al pintar la lista. -->
    <div class="modal fade" id="modalMapa" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title" id="modalMapaTitulo">Ubicación de la anomalía</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-0">
                    <iframe id="mapaFrame" src="" style="border:0; width:100%; height:60vh;"
                            loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
                <div class="modal-footer py-2">
                    <span class="small text-muted me-auto" id="modalMapaCoords"></span>
                    <a id="modalMapaAbrir" href="#" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-up-right-from-square me-1"></i> Abrir en Google Maps
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Foto de la anomalía a tamaño completo -->
    <div class="modal fade" id="modalFoto" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title" id="modalFotoTitulo">Anomalía</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body text-center p-2">
                    <img id="modalFotoImg" src="" alt="" style="max-width:100%; max-height:70vh; border-radius:8px;">
                    <div id="modalFotoError" class="text-muted py-5" style="display:none;">
                        <i class="fas fa-image fa-3x d-block mb-2"></i>
                        No se pudo cargar la imagen.
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
    function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }

    /** Marcador para cuando no hay foto o la ruta guardada ya no resuelve. */
    function marcadorSinFoto() {
        return '<div class="rounded me-3 bg-body-secondary d-flex flex-column align-items-center justify-content-center flex-shrink-0"'
             + ' style="width:84px; height:84px;" title="Sin foto">'
             + '<i class="fas fa-image text-muted"></i>'
             + '<span class="text-muted" style="font-size:.65rem;">sin foto</span></div>';
    }

    function fmtFecha(f) {
        if (!f) return '';
        var p = String(f).split(/[- :]/);
        return p[2] + '/' + p[1] + '/' + p[0] + (p[3] ? ' ' + p[3] + ':' + (p[4] || '00') : '');
    }

    /** Aplica los cuatro filtros sobre las tarjetas ya cargadas. */
    function filtrarFeed() {
        var busqueda = ($('#buscadorFeed').val() || '').toLowerCase().trim();
        var desde    = $('#fDesde').val();
        var hasta    = $('#fHasta').val();
        var vehiculo = $('#fVehiculo').val();

        var visibles = 0;
        $('.feed-card').each(function () {
            var $c = $(this), ok = true;

            if (busqueda && String($c.data('busqueda') || '').indexOf(busqueda) === -1) ok = false;

            // Las fechas se comparan como texto AAAA-MM-DD: en ese formato el orden
            // alfabético coincide con el cronológico.
            var fecha = String($c.data('fecha') || '');
            if (ok && desde && (!fecha || fecha < desde)) ok = false;
            if (ok && hasta && (!fecha || fecha > hasta)) ok = false;

            if (ok && vehiculo && String($c.data('vehiculo')) !== String(vehiculo)) ok = false;

            $c.toggle(ok);
            if (ok) visibles++;
        });

        $('#noResultados').toggle(visibles === 0);
        $('#badgeTotal').text(visibles + (visibles === 1 ? ' anomalía' : ' anomalías'));
    }

    /** Llena el selector con los vehículos que realmente aparecen en el feed. */
    function llenarFiltroVehiculos(lista) {
        var vistos = {}, $sel = $('#fVehiculo');
        lista.forEach(function (a) {
            if (!a.id_vehiculo || vistos[a.id_vehiculo]) return;
            vistos[a.id_vehiculo] = true;
            $sel.append($('<option>').val(a.id_vehiculo).text((a.placa || 'S/P') + ' - ' + (a.modelo || '')));
        });
    }

    function renderFeed(items) {
        var $cont = $('#feedAnomalias').empty();
        if (!items.length) {
            $('#noResultados').show();
            $('#badgeTotal').text('0 anomalías');
            return;
        }
        $('#noResultados').hide();
        llenarFiltroVehiculos(items);

        items.forEach(function (a) {
            var desc = a.descripcion || '';
            // La descripción se muestra COMPLETA: la tabla anomalias no guarda nada más
            // (vehículo, usuario, descripción, foto y fecha), así que no hay vista de
            // detalle donde leer el resto si se recortara.
            var descHtml = desc ? esc(desc) : '<em class="text-muted">Sin descripción</em>';
            var busqueda = ((a.placa || '') + ' ' + (a.nombre_usuario || '') + ' ' + desc).toLowerCase();

            // Si la imagen no carga se sustituye por el mismo marcador que se usa cuando
            // no hay foto. No se apunta a img/sin_foto.png (la convención del proyecto)
            // porque ese archivo NO existe: el respaldo quedaba igual de roto.
            var foto = a.foto_ruta
                ? '<img src="' + esc(a.foto_ruta) + '" class="rounded me-3 anomalia-foto" style="width:84px; height:84px; object-fit:cover; cursor:pointer;"'
                    + ' data-foto="' + esc(a.foto_ruta) + '" data-titulo="' + esc((a.placa || '') + ' · ' + fmtFecha(a.fecha)) + '"'
                    + ' onerror="this.onerror=null; this.outerHTML=marcadorSinFoto();">'
                : marcadorSinFoto();

            $cont.append(
                '<div class="col-lg-6 col-12 mb-3 feed-card" data-busqueda="' + esc(busqueda) + '"'
                + ' data-fecha="' + esc(a.fecha_dia || '') + '" data-vehiculo="' + esc(a.id_vehiculo || '') + '">'
                + '  <div class="card shadow-sm h-100 border-0"><div class="card-body d-flex">'
                +      foto
                + '    <div class="flex-grow-1 min-w-0">'
                + '      <h5 class="mb-0 text-primary fw-bold"><i class="fas fa-car me-1"></i>' + esc(a.placa || 'S/P') + '</h5>'
                + '      <small class="text-muted d-block">' + esc([a.marca, a.modelo, a.color].filter(Boolean).join(' · ')) + '</small>'
                + '      <div class="small mt-2">' + descHtml + '</div>'
                + '      <div class="small text-muted mt-2">'
                + '        <i class="fas fa-user me-1"></i>' + esc(a.nombre_usuario || 'S/R')
                + '        <span class="ms-3"><i class="fas fa-calendar me-1"></i>' + fmtFecha(a.fecha) + '</span>'
                +          (a.coordenadas
                              ? '        <button type="button" class="btn btn-link btn-sm p-0 ms-3 align-baseline btn-mapa"'
                                + ' data-coords="' + esc(a.coordenadas) + '"'
                                + ' data-titulo="' + esc((a.placa || '') + ' · ' + fmtFecha(a.fecha)) + '"'
                                + ' title="' + esc(a.coordenadas) + '"><i class="fas fa-map-marker-alt me-1"></i>Ubicación</button>'
                              : '')
                + '      </div>'
                + '    </div>'
                + '  </div></div>'
                + '</div>'
            );
        });
    }

    function cargar() {
        $('#feedAnomalias').html('<div class="col-12 text-center py-5"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i></div>');
        $.ajax({
            url: 'acciones_anomalias.php',
            type: 'POST',
            dataType: 'json',
            data: { accion: 'obtenerFeedAnomalias' }
        }).done(function (resp) {
            // Array.isArray antes de iterar: las acciones_*.php devuelven un objeto de
            // error cuando algo falla, y recorrerlo rompe en silencio.
            renderFeed(Array.isArray(resp) ? resp : []);
            filtrarFeed();
        }).fail(function () {
            $('#feedAnomalias').html('<div class="col-12 text-center text-danger py-5"><i class="fas fa-exclamation-circle fa-2x"></i><p class="mt-2">Error al cargar las anomalías.</p></div>');
        });
    }

    $(document).ready(function () {
        cargar();
        $('#buscadorFeed').on('input', filtrarFeed);
        $('#fDesde, #fHasta, #fVehiculo').on('change', filtrarFeed);
        $('#btnLimpiarFiltros').on('click', function () {
            $('#fDesde, #fHasta, #buscadorFeed').val('');
            $('#fVehiculo').val('');
            filtrarFeed();
        });

        $('#modalFotoImg').on('error', function () {
            $(this).hide();
            $('#modalFotoError').show();
        });

        $(document).on('click', '.btn-mapa', function () {
            var coords = $(this).data('coords');
            $('#mapaFrame').attr('src', 'https://maps.google.com/maps?q=' + encodeURIComponent(coords) + '&z=16&output=embed');
            $('#modalMapaTitulo').text($(this).data('titulo') || 'Ubicación de la anomalía');
            $('#modalMapaCoords').text(coords);
            $('#modalMapaAbrir').attr('href', 'https://www.google.com/maps?q=' + encodeURIComponent(coords));
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalMapa')).show();
        });

        // Se descarga el mapa al cerrar: si no, el iframe sigue vivo en segundo plano.
        document.getElementById('modalMapa').addEventListener('hidden.bs.modal', function () {
            $('#mapaFrame').attr('src', '');
        });

        $(document).on('click', '.anomalia-foto', function () {
            $('#modalFotoError').hide();
            $('#modalFotoImg').show().attr('src', $(this).data('foto'));
            $('#modalFotoTitulo').text($(this).data('titulo') || 'Anomalía');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalFoto')).show();
        });
    });
</script>
</body>
</html>
