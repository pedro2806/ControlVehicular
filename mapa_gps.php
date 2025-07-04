<?php
session_start();
include 'conn.php';
if ($_COOKIE['noEmpleado'] == '' || $_COOKIE['noEmpleado'] == null) {
    echo '<script>window.location.assign("index")</script>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Control Vehicular - Mapa GPS</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />

    <style>
        #map { height: 600px; width: 100%; }
        .leaflet-tile {
            filter: brightness(0.9) saturate(1.1) hue-rotate(200deg);
        }
    </style>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css" />
    <link href="css/sb-admin-2.min.css" rel="stylesheet" />
</head>
<body id="page-top">
<div id="wrapper">
    <?php include 'menu.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include 'encabezado.php'; ?>
            <div class="container-fluid">
                <h1 class="h3 mb-4 text-gray-800">Mapa de ubicaciones</h1>

                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Usuario</label>
                        <select name="usuario" class="form-select" id="usuario-select">
                            <option value="">Todos</option>
                            <?php
                            $res_usuarios = $conn->query("SELECT id_usuario, nombre FROM usuarios");
                            while ($u = $res_usuarios->fetch_assoc()) {
                                $selected = ($_GET['usuario'] ?? '') == $u['id_usuario'] ? 'selected' : '';
                                echo "<option value='{$u['id_usuario']}' $selected>{$u['nombre']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Vehículo</label>
                        <select name="vehiculo" id="vehiculo" class="form-select">
                            <option value="">Todos</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Desde</label>
                        <input type="date" name="fecha_inicio" class="form-control" value="<?= $_GET['fecha_inicio'] ?? '' ?>" />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Hasta</label>
                        <input type="date" name="fecha_fin" class="form-control" value="<?= $_GET['fecha_fin'] ?? '' ?>" />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Mostrar ubicaciones</label>
                        <select name="con_vehiculo" class="form-select">
                            <option value="" <?= ($_GET['con_vehiculo'] ?? '') === '' ? 'selected' : '' ?>>Todos</option>
                            <option value="1" <?= ($_GET['con_vehiculo'] ?? '') === '1' ? 'selected' : '' ?>>Con vehículo</option>
                            <option value="0" <?= ($_GET['con_vehiculo'] ?? '') === '0' ? 'selected' : '' ?>>Sin vehículo</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary" type="submit">Filtrar</button>
                    </div>
                </form>

                <div id="map"></div>
            </div>
        </div>
        <footer class="sticky-footer bg-white">
            <div class="container my-auto">
                <div class="copyright text-center my-auto">
                    <span>© MESS 2025</span>
                </div>
            </div>
        </footer>
    </div>
</div>

<!-- JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="js/sb-admin-2.min.js"></script>

<!-- Vehículos dinámicos -->
<script>
    const vehiculoSelect = document.getElementById('vehiculo');
    const usuarioSelect = document.getElementById('usuario-select');
    const urlParams = new URLSearchParams(window.location.search);

    function cargarVehiculos(usuarioId) {
        let url = 'obtener_vehiculos_usuario.php';
        if (usuarioId) {
            url += '?usuario=' + usuarioId;
        }
        vehiculoSelect.innerHTML = '<option value="">Cargando...</option>';
        fetch(url)
            .then(res => res.json())
            .then(data => {
                vehiculoSelect.innerHTML = '<option value="">Todos</option>';
                data.forEach(v => {
                    const option = document.createElement('option');
                    option.value = v.id;
                    option.textContent = `${v.marca} ${v.modelo} (${v.placa})`;
                    if (urlParams.get('vehiculo') === v.id) {
                        option.selected = true;
                    }
                    vehiculoSelect.appendChild(option);
                });
            })
            .catch(err => {
                console.error('Error cargando vehículos:', err);
                vehiculoSelect.innerHTML = '<option value="">Todos</option>';
            });
    }

    usuarioSelect.addEventListener('change', () => {
        cargarVehiculos(usuarioSelect.value);
    });

    window.addEventListener('DOMContentLoaded', () => {
        if (usuarioSelect.value) {
            cargarVehiculos(usuarioSelect.value);
        } else {
            cargarVehiculos(null);
        }
    });
</script>

<!-- Mapa y lógica -->
<script>
    function createLabeledIcon(labelColor, label) {
        const svg = `
        <svg width="30" height="36" xmlns="http://www.w3.org/2000/svg">
            <path d="M15,0 C24,0 30,8 30,18 C30,28 15,36 15,36 C15,36 0,28 0,18 C0,8 6,0 15,0 Z"
                  fill="${labelColor}" stroke="#000" stroke-width="1.5"/>
            <text x="15" y="23" font-family="Arial" font-size="10" font-weight="bold"
                  text-anchor="middle" fill="white">${label}</text>
        </svg>`;
        const svgUrl = "data:image/svg+xml;base64," + btoa(svg);
        return L.icon({
            iconUrl: svgUrl,
            iconSize: [30, 36],
            iconAnchor: [15, 36],
            popupAnchor: [0, -30],
            label
        });
    }

    function createLabeledIconResaltado(labelColor, label) {
        const svg = `
        <svg width="36" height="42" xmlns="http://www.w3.org/2000/svg">
            <path d="M18,0 C29,0 36,10 36,21 C36,32 18,42 18,42 C18,42 0,32 0,21 C0,10 7,0 18,0 Z"
                  fill="${labelColor}" stroke="#ffff00" stroke-width="3"/>
            <text x="18" y="28" font-family="Arial" font-size="14" font-weight="bold"
                  text-anchor="middle" fill="white">${label}</text>
        </svg>`;
        const svgUrl = "data:image/svg+xml;base64," + btoa(svg);
        return L.icon({
            iconUrl: svgUrl,
            iconSize: [36, 42],
            iconAnchor: [18, 42],
            popupAnchor: [0, -35],
            label
        });
    }

    const iconVehiculo = L.icon({
        iconUrl: 'https://messbook.com.mx/ControlVehicular/img/coche.png',
        iconSize: [32, 37],
        iconAnchor: [16, 37],
        popupAnchor: [0, -30]
    });

    const iconPersona = L.icon({
        iconUrl: 'https://messbook.com.mx/ControlVehicular/img/persona.png',
        iconSize: [32, 37],
        iconAnchor: [16, 37],
        popupAnchor: [0, -30]
    });

    const map = L.map('map').setView([19.4326, -99.1332], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

    const markersCluster = L.markerClusterGroup();

    let marcadorResaltadoInicio = null;
    let marcadorResaltadoFin = null;
    let rutaLayer = null;

    // Obtener datos con todos los parámetros de filtro
    const urlDatos = 'obtener_ubicaciones.php' + window.location.search;

    fetch(urlDatos)
        .then(res => res.json())
        .then(data => {
            if (!data || Object.keys(data).length === 0) {
                alert('No se encontraron puntos para mostrar.');
                return;
            }

            Object.entries(data).forEach(([key, grupo], i) => {
                const letraGrupo = String.fromCharCode(65 + i);

                let markerInicio = null;
                let markerFin = null;

                grupo.puntos.forEach(p => {
                    let icono, popupHtml;
                    switch (p.tipo) {
                        case 'checkin':
                            icono = createLabeledIcon('#28a745', letraGrupo);
                            popupHtml = `<strong>${p.nombre}</strong><br><em>Check-In (${letraGrupo})</em><br>Fecha/Hora: ${p.fecha}<br>Vehículo: ${p.placa ?? ''} (${p.marca ?? ''} ${p.modelo ?? ''})`;
                            break;
                        case 'checkout':
                            icono = createLabeledIcon('#dc3545', letraGrupo);
                            popupHtml = `<strong>${p.nombre}</strong><br><em>Check-Out (${letraGrupo})</em><br>Fecha/Hora: ${p.fecha}<br>Vehículo: ${p.placa ?? ''} (${p.marca ?? ''} ${p.modelo ?? ''})`;
                            break;
                        case 'normal':
                        default:
                            if (grupo.icon === 'vehiculo') {
                                icono = iconVehiculo;
                                popupHtml = `<strong>${p.nombre}</strong><br>Fecha/Hora: ${p.fecha}<br>Vehículo: ${p.placa ?? ''} (${p.marca ?? ''} ${p.modelo ?? ''})`;
                            } else {
                                icono = iconPersona;
                                popupHtml = `<strong>${p.nombre}</strong><br>Fecha/Hora: ${p.fecha}`;
                            }
                    }

                    const marker = L.marker([p.lat, p.lon], { icon: icono });
                    marker.bindPopup(popupHtml);

                    if (p.tipo === 'checkin') markerInicio = marker;
                    if (p.tipo === 'checkout') markerFin = marker;

                    marker.on('click', () => {
                        marker.openPopup();

                        if (marcadorResaltadoInicio) marcadorResaltadoInicio.setIcon(createLabeledIcon('#28a745', marcadorResaltadoInicio.options.icon.options.label));
                        if (marcadorResaltadoFin) marcadorResaltadoFin.setIcon(createLabeledIcon('#dc3545', marcadorResaltadoFin.options.icon.options.label));

                        if (p.tipo === 'checkin') {
                            marcadorResaltadoInicio = marker;
                            marcadorResaltadoFin = markerFin;
                        } else if (p.tipo === 'checkout') {
                            marcadorResaltadoInicio = markerInicio;
                            marcadorResaltadoFin = marker;
                        } else {
                            marcadorResaltadoInicio = null;
                            marcadorResaltadoFin = null;
                        }

                        if (marcadorResaltadoInicio) marcadorResaltadoInicio.setIcon(createLabeledIconResaltado('#28a745', marcadorResaltadoInicio.options.icon.options.label));
                        if (marcadorResaltadoFin) marcadorResaltadoFin.setIcon(createLabeledIconResaltado('#dc3545', marcadorResaltadoFin.options.icon.options.label));

                        if (marcadorResaltadoInicio && marcadorResaltadoFin) {
                            dibujarRuta(
                                marcadorResaltadoInicio.getLatLng(),
                                marcadorResaltadoFin.getLatLng()
                            );
                        } else {
                            borrarRuta();
                        }
                    });

                    markersCluster.addLayer(marker);
                });
            });

            map.addLayer(markersCluster);
        })
        .catch(err => {
            console.error('Error cargando ubicaciones:', err);
            alert('Hubo un problema al cargar los datos del mapa.');
        });

    function dibujarRuta(inicio, fin) {
        const url = `https://router.project-osrm.org/route/v1/driving/${inicio.lng},${inicio.lat};${fin.lng},${fin.lat}?overview=full&geometries=geojson`;
        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.code !== 'Ok' || !data.routes || data.routes.length === 0) {
                    alert('No se pudo obtener la ruta.');
                    return;
                }
                const rutaCoords = data.routes[0].geometry.coordinates.map(c => [c[1], c[0]]);
                if (rutaLayer) map.removeLayer(rutaLayer);
                rutaLayer = L.polyline(rutaCoords, { color: 'blue', weight: 5, opacity: 0.7 }).addTo(map);
                map.fitBounds(rutaLayer.getBounds(), { padding: [50, 50] });
            })
            .catch(err => {
                console.error('Error al obtener la ruta:', err);
                alert('Error al obtener la ruta.');
            });
    }

    function borrarRuta() {
        if (rutaLayer) {
            map.removeLayer(rutaLayer);
            rutaLayer = null;
        }
    }

    map.on('click', () => {
        if (marcadorResaltadoInicio) {
            marcadorResaltadoInicio.setIcon(createLabeledIcon('#28a745', marcadorResaltadoInicio.options.icon.options.label));
            marcadorResaltadoInicio = null;
        }
        if (marcadorResaltadoFin) {
            marcadorResaltadoFin.setIcon(createLabeledIcon('#dc3545', marcadorResaltadoFin.options.icon.options.label));
            marcadorResaltadoFin = null;
        }
        borrarRuta();
    });
</script>
</body>
</html>