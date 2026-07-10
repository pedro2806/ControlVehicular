<?php
session_start();
include 'conn.php';
if ($_COOKIE['noEmpleado'] == '' || $_COOKIE['noEmpleado'] == null) {
    echo '<script>window.location.assign("index")</script>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Control Vehicular</title>
    <!-- MESS Design System -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="css/mess-ds.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include 'menu.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'encabezado.php'; ?>
                <div class="container-fluid">
                    <!-- Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-3">
                        <h1 class="h3 mb-0 text-black-800">Historial de Siniestros</h1>
                        <span class="badge bg-secondary fs-6" id="badgeTotal"></span>
                    </div>

                    <!-- Buscador -->
                    <div class="mb-4">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" class="form-control border-start-0" id="buscadorFeed"
                                   placeholder="Buscar por placa, lugar o descripción...">
                        </div>
                    </div>

                    <!-- Feed -->
                    <div id="feedSiniestros" class="row"></div>

                    <!-- Sin resultados -->
                    <div id="noResultados" class="text-center text-muted py-5" style="display:none;">
                        <i class="fas fa-car-crash fa-3x mb-3"></i>
                        <p class="mb-0">No se encontraron siniestros.</p>
                    </div>
                </div>
            </div>
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; MESS <?php echo date("Y"); ?></span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Modal Detalle -->
    <div class="modal fade" id="modalDetalleSiniestro" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-car-crash me-2"></i>Detalle del Siniestro</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalDetalleBody">
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        var leafletMapModal = null;

        $(document).ready(function () {
            cargarFeed();

            $('#buscadorFeed').on('input', function () {
                filtrarFeed($(this).val().toLowerCase().trim());
            });

            document.getElementById('modalDetalleSiniestro').addEventListener('hidden.bs.modal', function () {
                if (leafletMapModal) { leafletMapModal.remove(); leafletMapModal = null; }
            });
        });

        function cargarFeed() {
            $('#feedSiniestros').html('<div class="col-12 text-center py-5"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i></div>');
            $.ajax({
                type: "POST",
                url: "acciones_siniestro",
                data: { accion: "obtenerFeedSiniestros" },
                dataType: "json",
                success: function (data) {
                    var items = Array.isArray(data) ? data : [];
                    renderFeed(items);
                    var total = items.length;
                    $('#badgeTotal').text(total + ' siniestro' + (total !== 1 ? 's' : ''));
                },
                error: function () {
                    $('#feedSiniestros').html('<div class="col-12 text-center text-danger py-5"><i class="fas fa-exclamation-circle fa-2x"></i><p class="mt-2">Error al cargar los siniestros.</p></div>');
                }
            });
        }

        function renderFeed(items) {
            var container = $('#feedSiniestros');
            container.empty();

            if (items.length === 0) {
                $('#noResultados').show();
                return;
            }
            $('#noResultados').hide();

            items.forEach(function (s) {
                var desc = s.descripcion || '';
                var descCorta = desc.length > 130 ? desc.substring(0, 130) + '…' : (desc || '<em class="text-muted">Sin descripción</em>');
                var fotoBadge = parseInt(s.num_fotos) > 0
                    ? `<span class="badge bg-light text-dark border me-2"><i class="fas fa-camera me-1 text-secondary"></i>${s.num_fotos}</span>`
                    : '';
                var fechaDisplay = s.fecha || (s.fecha_registro ? s.fecha_registro.split(' ')[0] : '');
                var busqueda = ((s.placa || '') + ' ' + (s.lugar || '') + ' ' + desc).toLowerCase();

                var card = `
                    <div class="col-lg-6 col-12 mb-3 feed-card" data-busqueda="${busqueda.replace(/"/g, '&quot;')}">
                        <div class="card shadow-sm h-100 border-0">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h5 class="mb-0 text-primary fw-bold">
                                            <i class="fas fa-car me-1"></i>${s.placa}
                                        </h5>
                                        <small class="text-muted">${s.modelo} ${s.marca} &middot; ${s.color}</small>
                                    </div>
                                    <div class="text-end text-muted small">
                                        <div><i class="fas fa-calendar-alt me-1"></i>${fechaDisplay}</div>
                                        ${s.hora ? `<div><i class="fas fa-clock me-1"></i>${s.hora}</div>` : ''}
                                    </div>
                                </div>
                                <p class="mb-1">
                                    <i class="fas fa-map-marker-alt me-1 text-danger"></i>
                                    <strong>${s.lugar || 'Sin lugar registrado'}</strong>
                                </p>
                                <p class="text-muted small mb-3">${descCorta}</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>${fotoBadge}${s.ubicacion_vehiculo ? `<span class="badge bg-light text-dark border"><i class="fas fa-map-pin me-1 text-muted"></i>${s.ubicacion_vehiculo}</span>` : ''}</div>
                                    <button class="btn btn-sm btn-primary" onclick="verDetalle(${s.id_siniestro})">
                                        <i class="fas fa-eye me-1"></i> Ver detalle
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                container.append(card);
            });
        }

        function filtrarFeed(busqueda) {
            if (!busqueda) {
                $('.feed-card').show();
                $('#noResultados').hide();
                return;
            }
            var visibles = 0;
            $('.feed-card').each(function () {
                var texto = $(this).data('busqueda') || '';
                if (texto.indexOf(busqueda) !== -1) { $(this).show(); visibles++; }
                else { $(this).hide(); }
            });
            $('#noResultados').toggle(visibles === 0);
        }

        function verDetalle(id_siniestro) {
            $('#modalDetalleBody').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i></div>');
            var modalEl = document.getElementById('modalDetalleSiniestro');
            var modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modal.show();

            $.ajax({
                type: "POST",
                url: "acciones_siniestro",
                data: { accion: "obtenerDetallesSiniestro", id_siniestro: id_siniestro },
                dataType: "json",
                success: function (s) {
                    if (s.error) {
                        $('#modalDetalleBody').html('<p class="text-danger">Error al cargar el siniestro.</p>');
                        return;
                    }

                    var fotosHtml = '';
                    if (Array.isArray(s.imagenes) && s.imagenes.length > 0) {
                        var items = s.imagenes.map(function (img, i) {
                            return `<div class="carousel-item ${i === 0 ? 'active' : ''}">
                                <img src="${img}" class="d-block w-100" style="max-height:280px;object-fit:contain;"
                                     onerror="this.src='img/sin_foto.png'">
                            </div>`;
                        }).join('');
                        fotosHtml = `<div id="carruselDetalle" class="carousel slide" data-bs-ride="carousel">
                            <div class="carousel-inner">${items}</div>
                            <button class="carousel-control-prev" type="button" data-bs-target="#carruselDetalle" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon"></span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#carruselDetalle" data-bs-slide="next">
                                <span class="carousel-control-next-icon"></span>
                            </button>
                        </div>`;
                    } else {
                        fotosHtml = '<p class="text-muted small mb-0"><i class="fas fa-image me-1"></i>Sin fotos registradas.</p>';
                    }

                    var tieneCoords = s.coordenadas && s.coordenadas.trim() !== '';
                    var mapHtml = tieneCoords
                        ? '<div id="mapDetalle" style="height:220px;border-radius:8px;"></div>'
                        : '<p class="text-muted small mb-0"><i class="fas fa-map-marker-alt me-1"></i>Sin coordenadas registradas.</p>';

                    var fechaDisplay = s.fecha || (s.fecha_registro ? s.fecha_registro.split(' ')[0] : 'N/A');

                    var body = `
                        <div class="row mb-2">
                            <div class="col-6"><p class="mb-1"><strong>Placa:</strong> ${s.placa}</p></div>
                            <div class="col-6"><p class="mb-1"><strong>Vehículo:</strong> ${s.modelo} ${s.marca}</p></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6"><p class="mb-1"><strong>Fecha:</strong> ${fechaDisplay}</p></div>
                            <div class="col-6"><p class="mb-1"><strong>Hora:</strong> ${s.hora || 'N/A'}</p></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6"><p class="mb-1"><strong>Lugar del siniestro:</strong> ${s.lugar || 'N/A'}</p></div>
                            <div class="col-6"><p class="mb-1"><strong>Vehículo ubicado en:</strong> ${s.ubicacion_vehiculo || 'N/A'}</p></div>
                        </div>
                        <div class="mb-2">
                            <strong>Descripción:</strong>
                            <p class="mt-1 mb-0 text-muted">${s.descripcion || 'N/A'}</p>
                        </div>
                        <div class="mb-3">
                            <strong>Partes dañadas:</strong>
                            <p class="mt-1 mb-0 text-muted">${s.partes_dañadas || 'N/A'}</p>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <strong class="d-block mb-2">Fotos</strong>
                                ${fotosHtml}
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong class="d-block mb-2">Ubicación GPS</strong>
                                ${mapHtml}
                            </div>
                        </div>
                        <hr class="mt-1">
                        <small class="text-muted">
                            <i class="fas fa-user me-1"></i>${s.nombre_usuario || 'N/A'}
                            &nbsp;&middot;&nbsp;
                            <i class="fas fa-clock me-1"></i>Registrado: ${s.fecha_registro || 'N/A'}
                        </small>
                    `;

                    $('#modalDetalleBody').html(body);

                    if (tieneCoords) {
                        setTimeout(function () { iniciarMapaModal(s.coordenadas); }, 150);
                    }
                },
                error: function () {
                    $('#modalDetalleBody').html('<p class="text-danger">Error de comunicación con el servidor.</p>');
                }
            });
        }

        function iniciarMapaModal(coordenadas) {
            if (leafletMapModal) { leafletMapModal.remove(); leafletMapModal = null; }
            var partes = coordenadas.split(',').map(function (c) { return parseFloat(c.trim()); });
            if (partes.length < 2 || isNaN(partes[0]) || isNaN(partes[1])) return;
            leafletMapModal = L.map('mapDetalle').setView(partes, 14);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(leafletMapModal);
            L.marker(partes).addTo(leafletMapModal).bindPopup('Ubicación del siniestro').openPopup();
        }
    </script>
</body>
</html>
