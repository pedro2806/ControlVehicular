<?php
// Vista pura: no consulta la BD. El id llega por ?v= y todo lo carga acciones_vehiculos.php,
// que además valida que este usuario pueda ver ese vehículo (anti-IDOR).
$idVehiculo = intval($_GET['v'] ?? 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Control Vehicular - Historial del vehículo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="css/mess-ds.css" rel="stylesheet">
</head>
<body id="page-top">
<div id="wrapper">
    <!-- menu.php va DENTRO de #wrapper y antes de #content-wrapper: el layout es un flex
         de dos columnas (sidebar + contenido). Incluirlo antes del <!DOCTYPE> deja el
         sidebar fuera del wrapper y el contenido se pierde de la pantalla. -->
    <?php include 'menu.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include 'encabezado.php'; ?>
            <div class="container-fluid">

                <div class="d-sm-flex align-items-center justify-content-between mb-3">
                    <h1 class="h3 mb-0 text-black-800">Historial del vehículo</h1>
                    <a href="vehiculos" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> Vehículos
                    </a>
                </div>

                <div id="avisoHistorial" class="alert alert-warning" style="display:none;"></div>

                <!-- Ficha del vehículo -->
                <div class="card shadow mb-4" id="fichaVehiculo" style="display:none;">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                            <div>
                                <div class="display-6 fw-bold mb-1" id="fichaPlaca"></div>
                                <div class="fs-5 text-muted" id="fichaModelo"></div>
                                <div class="d-flex flex-wrap gap-2 mt-3" id="fichaBadges"></div>
                            </div>
                            <a id="btnVerQR" class="btn btn-outline-primary" href="#">
                                <i class="fas fa-qrcode me-1"></i> Ver ficha QR
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Un tab por tipo de registro, con su conteo. Se ocultan los vacíos para
                     no ofrecer pestañas que no llevan a ningún lado. -->
                <ul class="nav nav-pills mb-3 flex-wrap" id="tabsHistorial"></ul>

                <div class="card shadow mb-4">
                    <div class="card-body p-0">
                        <div id="contenidoHistorial">
                            <div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i></div>
                        </div>
                    </div>
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

<script>
    var idVehiculo = <?= $idVehiculo ?>;
    var datos = null;
    var seccionActiva = '';

    function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }
    function money(v) { return '$' + Number(v || 0).toLocaleString('es-MX', { minimumFractionDigits: 2 }); }
    function num(v) { return Number(v || 0).toLocaleString('es-MX'); }

    /** 'YYYY-MM-DD HH:MM:SS' -> 'DD/MM/YYYY HH:MM'. Se arma por partes para no depender
        de cómo interprete new Date() una fecha de MySQL sin zona horaria. */
    function fecha(f) {
        if (!f) return '—';
        var p = String(f).split(/[- :]/);
        if (p.length < 3) return String(f);
        return p[2] + '/' + p[1] + '/' + p[0] + (p[3] ? ' ' + p[3] + ':' + (p[4] || '00') : '');
    }

    function badge(txt, color) { return '<span class="badge bg-' + color + '">' + esc(txt) + '</span>'; }

    // Cada sección declara su título, su ícono y cómo se pinta una fila. Tenerlas en una
    // sola estructura evita repetir el armado de tabla ocho veces.
    var SECCIONES = {
        checkins: {
            titulo: 'Check-ins', icono: 'fa-clipboard-check',
            cols: ['Fecha', 'Tipo', 'Km', 'Gasolina', 'OT/OV', 'Usuario'],
            fila: function (r) {
                return [fecha(r.fecha_actividad),
                        badge(r.tipo_actividad === 'INICIO' ? 'Inicio' : 'Fin',
                              r.tipo_actividad === 'INICIO' ? 'primary' : 'secondary'),
                        num(r.km_actual) + ' km', esc(r.gasolina_actual || '—'),
                        esc(r.ot || '—'), esc(r.usuario)];
            }
        },
        cargas: {
            titulo: 'Cargas de gas', icono: 'fa-gas-pump',
            cols: ['Carga', 'Gastado', 'Saldo', 'Km', 'Destino', 'Usuario'],
            fila: function (r) {
                var destino = esc(r.cliente || '');
                if (r.ot) destino += (destino ? '<div class="small text-muted">' : '<span class="small text-muted">')
                                   + 'OT/OV: ' + esc(r.ot) + (destino ? '</div>' : '</span>');
                return [fecha(r.fecha_carga), money(r.pagos), money(r.saldo),
                        num(r.km_actual) + ' km', destino || '—', esc(r.usuario)];
            }
        },
        solicitudes: {
            titulo: 'Solicitudes de recarga', icono: 'fa-credit-card',
            cols: ['Fecha', 'Estado', 'Saldo al pedir', 'Km', 'Resolución'],
            fila: function (r) {
                var color = r.estatus === 'PENDIENTE' ? 'warning text-dark'
                          : r.estatus === 'PARCIAL' ? 'info text-dark'
                          : r.estatus === 'APROBADA' ? 'success' : 'danger';
                return [fecha(r.fecha), badge(r.estatus, color), money(r.saldo_solicitud),
                        num(r.km_actual) + ' km',
                        r.fecha_resuelto ? (fecha(r.fecha_resuelto) + '<div class="small text-muted">' + esc(r.notas_resolucion || '') + '</div>') : '—'];
            }
        },
        checklists: {
            titulo: 'Checklists', icono: 'fa-list-check',
            cols: ['Fecha', 'Estado', 'Motivo', 'Usuario'],
            fila: function (r) {
                return [fecha(r.fecha),
                        badge(r.estatus, r.estatus === 'completo' ? 'success' : 'warning text-dark'),
                        esc(r.motivo || '—'), esc(r.usuario)];
            }
        },
        mantenimientos: {
            titulo: 'Mantenimientos', icono: 'fa-screwdriver-wrench',
            cols: ['Fecha', 'Estado', 'Tipo', 'Km', 'Descripción'],
            fila: function (r) {
                var color = r.estatus === 'AUTORIZADO' ? 'success'
                          : r.estatus === 'PENDIENTE' ? 'warning text-dark' : 'secondary';
                return [fecha(r.fecha_registro), badge(r.estatus || 'S/R', color),
                        esc(r.tipo_mantenimiento || '—'), num(r.kilometraje) + ' km',
                        esc(r.descripcion || '—')];
            }
        },
        siniestros: {
            titulo: 'Siniestros', icono: 'fa-car-burst',
            cols: ['Fecha', 'Hora', 'Lugar', 'Km', 'Descripción'],
            fila: function (r) {
                return [fecha(r.fecha), esc(r.hora || '—'), esc(r.lugar || '—'),
                        num(r.kilometraje) + ' km', esc(r.descripcion || '—')];
            }
        },
        anomalias: {
            titulo: 'Anomalías', icono: 'fa-triangle-exclamation',
            cols: ['Fecha', 'Descripción', 'Foto', 'Usuario'],
            fila: function (r) {
                var foto = r.foto_ruta
                    ? '<a href="' + esc(r.foto_ruta) + '" target="_blank" rel="noopener"><i class="fas fa-image"></i></a>'
                    : '—';
                return [fecha(r.fecha), esc(r.descripcion || '—'), foto, esc(r.usuario)];
            }
        },
        prestamos: {
            titulo: 'Préstamos', icono: 'fa-handshake',
            cols: ['Registro', 'Estado', 'Del', 'Al', 'Uso', 'Usuario'],
            fila: function (r) {
                var color = r.estatus === 'FINALIZADO' ? 'secondary'
                          : r.estatus === 'EN CURSO' ? 'primary'
                          : r.estatus === 'AUTORIZADO' ? 'success' : 'warning text-dark';
                return [fecha(r.fecha_registro), badge(r.estatus || 'S/R', color),
                        fecha(r.fecha_inc_prestamo), fecha(r.fecha_fin_prestamo),
                        esc(r.tipo_uso || '—'), esc(r.usuario)];
            }
        }
    };

    function pintarFicha(v) {
        $('#fichaPlaca').text(v.placa || 'S/P');
        $('#fichaModelo').text([v.marca, v.modelo, v.anio].filter(Boolean).join(' · '));

        // fs-6 en los badges: el tamaño por omisión de Bootstrap los deja diminutos y
        // aquí son datos que se leen, no adornos.
        var cls = ' class="badge fs-6 fw-normal bg-light text-dark border"';
        var b = '';
        b += '<span class="badge fs-6 bg-' + (String(v.estatus).trim() === 'Activo' ? 'success' : 'secondary') + '">'
           + esc(String(v.estatus).trim()) + '</span>';
        if (v.nombre_usuario || v.usuario)
            b += '<span' + cls + '><i class="fas fa-user me-1"></i>' + esc(v.nombre_usuario || v.usuario) + '</span>';
        if (v.area) b += '<span' + cls + '><i class="fas fa-building me-1"></i>' + esc(v.area) + '</span>';
        b += '<span' + cls + '><i class="fas fa-credit-card me-1"></i>'
           + (v.efecticard ? 'Tarjeta ' + esc(v.efecticard) : 'Sin tarjeta') + '</span>';
        if (v.color) b += '<span' + cls + '>' + esc(v.color) + '</span>';
        $('#fichaBadges').html(b);

        $('#btnVerQR').attr('href', 'qr_vehiculo?v=' + v.id_vehiculo);
        $('#fichaVehiculo').show();
    }

    function pintarTabs() {
        var html = '';
        var primera = '';

        Object.keys(SECCIONES).forEach(function (k) {
            var n = (datos[k] || []).length;
            // Las secciones sin registros no se ofrecen: una pestaña que abre en vacío
            // solo hace perder un clic.
            if (!n) return;
            if (!primera) primera = k;
            html += '<li class="nav-item"><button class="nav-link fs-6 px-3 py-2" data-seccion="' + k + '">'
                  + '<i class="fas ' + SECCIONES[k].icono + ' me-2"></i>' + SECCIONES[k].titulo
                  + ' <span class="badge bg-light text-dark ms-2 fs-6">' + n + '</span></button></li>';
        });
        $('#tabsHistorial').html(html);
        if (primera) mostrarSeccion(primera);
        else $('#contenidoHistorial').html('<p class="text-muted text-center py-5 mb-0 fs-5">Este vehículo todavía no tiene registros.</p>');
    }

    function mostrarSeccion(clave) {
        seccionActiva = clave;
        $('#tabsHistorial .nav-link').removeClass('active');
        $('#tabsHistorial .nav-link[data-seccion="' + clave + '"]').addClass('active');

        var def = SECCIONES[clave];
        var filas = datos[clave] || [];

        // Sin table-sm y sin .small en las celdas: esta pantalla se consulta para revisar
        // el historial de un vehículo, no para meter cien filas en la pantalla, así que
        // se prioriza que se lea cómodo. py-3 le da aire a cada renglón.
        var html = '<div class="table-responsive"><table class="table table-hover align-middle mb-0 fs-6">'
                 + '<thead class="table-light"><tr>';
        def.cols.forEach(function (c, i) {
            // De la tercera columna en adelante se esconden en móvil: no caben y las dos
            // primeras (fecha y estado) son las que identifican el registro.
            html += '<th class="text-center py-3' + (i >= 2 ? ' d-none d-md-table-cell' : '') + '">' + c + '</th>';
        });
        html += '</tr></thead><tbody>';

        filas.forEach(function (r) {
            html += '<tr>';
            def.fila(r).forEach(function (celda, i) {
                html += '<td class="py-3' + (i >= 2 ? ' d-none d-md-table-cell' : '') + '">' + celda + '</td>';
            });
            html += '</tr>';
        });

        html += '</tbody></table></div>';
        $('#contenidoHistorial').html(html);
    }

    $(document).ready(function () {
        if (!idVehiculo) {
            $('#avisoHistorial').text('No se indicó ningún vehículo.').show();
            $('#contenidoHistorial').empty();
            return;
        }

        $.ajax({
            url: 'acciones_vehiculos.php', method: 'POST', dataType: 'json',
            data: { accion: 'historialVehiculo', id_vehiculo: idVehiculo }
        }).done(function (resp) {
            if (!resp || resp.status !== 'success') {
                $('#avisoHistorial').text((resp && resp.message) ? resp.message : 'No se pudo cargar el historial.').show();
                $('#contenidoHistorial').empty();
                return;
            }
            datos = resp;
            pintarFicha(resp.vehiculo);
            pintarTabs();
        }).fail(function () {
            $('#avisoHistorial').text('No se pudo conectar con el servidor.').show();
            $('#contenidoHistorial').empty();
        });

        // Delegado: las pestañas se generan después de que responde el AJAX.
        $(document).on('click', '#tabsHistorial .nav-link', function () {
            mostrarSeccion($(this).data('seccion'));
        });
    });
</script>
</body>
</html>
