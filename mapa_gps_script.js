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

let rutaLayer = null;  // Capa para la ruta dibujada

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
                        popupHtml = `<strong>${p.nombre}</strong><br><em>Check-In (${letraGrupo})</em><br>Fecha/Hora: ${p.fecha}<br>Vehículo: ${p.placa || ''} (${p.marca || ''} ${p.modelo || ''})`;
                        break;
                    case 'checkout':
                        icono = createLabeledIcon('#dc3545', letraGrupo);
                        popupHtml = `<strong>${p.nombre}</strong><br><em>Check-Out (${letraGrupo})</em><br>Fecha/Hora: ${p.fecha}<br>Vehículo: ${p.placa || ''} (${p.marca || ''} ${p.modelo || ''})`;
                        break;
                    case 'normal':
                    default:
                        if (grupo.icon === 'vehiculo') {
                            icono = iconVehiculo;
                            popupHtml = `<strong>${p.nombre}</strong><br>Fecha/Hora: ${p.fecha}<br>Vehículo: ${p.placa || ''} (${p.marca || ''} ${p.modelo || ''})`;
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

                    // Limpiar resaltados previos
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

                    // Dibujar ruta si hay inicio y fin
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

// Dibujar ruta entre dos puntos usando OSRM API
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

            // Si ya hay una ruta dibujada, la eliminamos
            if (rutaLayer) {
                map.removeLayer(rutaLayer);
            }

            rutaLayer = L.polyline(rutaCoords, {color: 'blue', weight: 5, opacity: 0.7}).addTo(map);

            // Ajustar zoom para que la ruta se vea completa
            const bounds = rutaLayer.getBounds();
            map.fitBounds(bounds, {padding: [50, 50]});
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
    // Limpiar resaltados
    if (marcadorResaltadoInicio) {
        marcadorResaltadoInicio.setIcon(createLabeledIcon('#28a745', marcadorResaltadoInicio.options.icon.options.label));
        marcadorResaltadoInicio = null;
    }
    if (marcadorResaltadoFin) {
        marcadorResaltadoFin.setIcon(createLabeledIcon('#dc3545', marcadorResaltadoFin.options.icon.options.label));
        marcadorResaltadoFin = null;
    }

    // Borrar ruta dibujada
    borrarRuta();
});