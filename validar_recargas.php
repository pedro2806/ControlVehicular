<?php
// Vista pura: sin conn.php y sin consultas. La sesión la valida menu.php y el permiso
// (verSolicitudesGas) lo resuelve acciones_gas.php en cada llamada; si no lo tiene, el
// primer AJAX responde con el error y esta pantalla redirige.
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Control Vehicular - Recargas</title>
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
                        <h1 class="h3 mb-0 text-black-800">Solicitudes de Recarga</h1>
                        <span class="badge bg-secondary fs-6" id="badgeTotal"></span>
                    </div>

                    <ul class="nav nav-pills mb-3" id="tabsEstatus">
                        <li class="nav-item"><button class="nav-link active" data-estatus="PENDIENTE">Pendientes <span class="badge bg-light text-dark ms-1" id="cntPENDIENTE">0</span></button></li>
                        <li class="nav-item"><button class="nav-link" data-estatus="APROBADA">Aprobadas <span class="badge bg-light text-dark ms-1" id="cntAPROBADA">0</span></button></li>
                        <li class="nav-item"><button class="nav-link" data-estatus="RECHAZADA">Rechazadas <span class="badge bg-light text-dark ms-1" id="cntRECHAZADA">0</span></button></li>
                        <li class="nav-item"><button class="nav-link" data-estatus="">Todas</button></li>
                    </ul>

                    <div id="feedSolicitudes" class="row"></div>

                    <div id="noResultados" class="text-center text-muted py-5" style="display:none;">
                        <i class="fas fa-gas-pump fa-3x mb-3 d-block"></i>
                        <p class="mb-0">No hay solicitudes en este estado.</p>
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

    <!-- Destino de una carga: cliente completo, parque industrial, ciudad y OT/OV.
         Va en un modal aparte porque no cabe en la celda de la tabla. Se apila sobre el
         de detalles (Bootstrap lo maneja solo). -->
    <div class="modal fade" id="modalDestino" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title mb-0">Destino de la carga</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="destinoCuerpo"></div>
            </div>
        </div>
    </div>

    <!-- Detalle de una solicitud: las cargas de gasolina que consumieron el crédito,
         desde la renovación aprobada anterior hasta esta solicitud. Se llena por AJAX
         al abrirlo, no al pintar las tarjetas. -->
    <div class="modal fade" id="modalDetalles" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title mb-0">Cargas de este vehículo</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="detallesCuerpo">
                    <div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i></div>
                </div>
            </div>
        </div>
    </div>

<script>
    var solicitudes = [], puedeResolver = false, estatusActivo = 'PENDIENTE';

    function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }

    function fmtFecha(f) {
        if (!f) return '';
        var p = String(f).split(/[- :]/);
        return p[2] + '/' + p[1] + '/' + p[0] + (p[3] ? ' ' + p[3] + ':' + (p[4] || '00') : '');
    }

    function badgeEstatus(e) {
        if (e === 'PENDIENTE') return '<span class="badge bg-warning text-dark">Pendiente</span>';
        // PARCIAL se distingue del amarillo de "Pendiente" a propósito: sigue abierta,
        // pero ya se le abonó algo y lo que falta es completar el resto.
        if (e === 'PARCIAL')   return '<span class="badge bg-info text-dark">Parcialidad</span>';
        if (e === 'APROBADA')  return '<span class="badge bg-success">Aprobada</span>';
        return '<span class="badge bg-danger">Rechazada</span>';
    }

    /** ¿La solicitud sigue abierta? PARCIAL cuenta: falta abonarle el resto. */
    function estaAbierta(e) { return e === 'PENDIENTE' || e === 'PARCIAL'; }

    function pintar() {
        var $c = $('#feedSolicitudes').empty();
        // La pestana "Pendientes" muestra tambien las PARCIAL: siguen abiertas y son justo
        // las que no hay que perder de vista para abonarles el resto.
        var lista = estatusActivo
            ? solicitudes.filter(function (s) {
                  return estatusActivo === "PENDIENTE" ? estaAbierta(s.estatus) : s.estatus === estatusActivo;
              })
            : solicitudes;

        $('#noResultados').toggle(lista.length === 0);
        $('#badgeTotal').text(lista.length + (lista.length === 1 ? ' solicitud' : ' solicitudes'));

        lista.forEach(function (s) {
            var vehiculo = esc([s.marca, s.modelo].filter(Boolean).join(' ')) + ' — ' + esc(s.placa || 'S/P');

            // km_recorrido puede venir null: el cálculo se descarta cuando la lectura del
            // odómetro es incoherente, y es mejor no mostrar nada que una cifra falsa.
            var km = (s.km_recorrido !== null && s.km_recorrido !== undefined)
                ? Number(s.km_recorrido).toLocaleString('es-MX') + ' km'
                : '<span class="text-muted">sin dato</span>';

            var acciones = '';
            if (puedeResolver && estaAbierta(s.estatus)) {
                acciones =
                    '<div class="d-flex gap-2 mt-3">'
                    + '<button class="btn btn-success btn-sm flex-fill btn-resolver" data-id="' + s.id + '" data-res="APROBADA">'
                    + '<i class="fas fa-check me-1"></i>Aprobar</button>'
                    + '<button class="btn btn-outline-danger btn-sm flex-fill btn-resolver" data-id="' + s.id + '" data-res="RECHAZADA">'
                    + '<i class="fas fa-xmark me-1"></i>Rechazar</button>'
                    + '</div>';
            }
            // El detalle se ofrece para TODAS las solicitudes, no solo las pendientes:
            // sobre una ya resuelta también sirve para revisar en qué se fue el crédito.
            acciones +=
                '<div class="d-grid mt-2">'
                + '<button class="btn btn-outline-secondary btn-sm btn-detalles" data-id="' + s.id + '">'
                + '<i class="fas fa-list-ul me-1"></i>Ver detalles</button>'
                + '</div>';

            var resuelta = '';
            if (!estaAbierta(s.estatus)) {
                resuelta = '<div class="small text-muted mt-2 border-top pt-2">'
                    + '<i class="fas fa-user-check me-1"></i>' + esc(s.resolvio || 'S/R')
                    + ' · ' + fmtFecha(s.fecha_resuelto)
                    + (s.notas_resolucion ? '<div class="mt-1"><i class="fas fa-comment me-1"></i>' + esc(s.notas_resolucion) + '</div>' : '')
                    + '</div>';
            }

            $c.append(
                '<div class="col-lg-6 col-12 mb-3">'
                + ' <div class="card shadow-sm h-100 border-0"><div class="card-body">'
                + '  <div class="d-flex justify-content-between align-items-start mb-2">'
                + '   <div><h5 class="mb-0 text-primary fw-bold"><i class="fas fa-car me-1"></i>' + esc(s.placa || 'S/P') + '</h5>'
                + '    <small class="text-muted">' + vehiculo + '</small></div>'
                + '   ' + badgeEstatus(s.estatus)
                + '  </div>'
                + '  <table class="table table-sm mb-0 small">'
                + '   <tr><td class="text-muted">Tarjeta</td><td class="text-end fw-semibold">'
                +      (s.efecticard ? esc(s.efecticard) : '<span class="text-muted fw-normal">Sin tarjeta registrada</span>') + '</td></tr>'
                + '   <tr><td class="text-muted">Solicitó</td><td class="text-end fw-semibold">' + esc(s.solicitante || 'S/R') + '</td></tr>'
                + '   <tr><td class="text-muted">Fecha</td><td class="text-end">' + fmtFecha(s.fecha) + '</td></tr>'
                + '   <tr><td class="text-muted">Saldo al solicitar</td><td class="text-end fw-semibold">$' + Number(s.saldo_solicitud || 0).toLocaleString('es-MX', {minimumFractionDigits: 2}) + '</td></tr>'
                // Solo aparece cuando ya hubo un abono parcial: dice cuánto se lleva
                // entregado y cuánto falta para cerrar la solicitud.
                + (Number(s.abonado_acumulado || 0) > 0
                    ? '   <tr class="table-info"><td class="text-muted">Abonado / falta</td>'
                      + '<td class="text-end fw-semibold">' + money(s.abonado_acumulado)
                      + ' <span class="text-muted fw-normal">/ '
                      + money(Math.max(0, 4000 - Number(s.saldo_solicitud || 0) - Number(s.abonado_acumulado || 0)))
                      + '</span></td></tr>'
                    : '')
                + '   <tr><td class="text-muted">Km del crédito</td><td class="text-end">' + km + '</td></tr>'
                + '   <tr><td class="text-muted">Kilometraje</td><td class="text-end">' + (s.km_actual ? Number(s.km_actual).toLocaleString('es-MX') + ' km' : '<span class="text-muted">S/R</span>') + '</td></tr>'
                + '  </table>'
                + resuelta + acciones
                + ' </div></div>'
                + '</div>'
            );
        });
    }

    function money(v) {
        return '$' + Number(v || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    /** 'YYYY-MM-DD HH:MM:SS' -> 'YYYY-MM-DD', que es lo que espera un <input type="date">. */
    function soloFecha(f) {
        return f ? String(f).split(' ')[0] : '';
    }

    /**
     * Fecha sin la hora cuando es 00:00.
     *
     * Los ciclos reconstruidos por el backfill salen de fecha_carga, que es un DATE, así
     * que todos quedan a las 00:00. Mostrar esa hora sugiere una precisión que no existe.
     */
    function fmtFechaCorta(f) {
        var t = fmtFecha(f);
        return t.replace(/ 00:00$/, '');
    }

    /**
     * Liga a los check-ins del vehículo acotados al ciclo de tarjeta.
     *
     * La ventana va del inicio del ciclo a su cierre; si el ciclo sigue abierto se usa la
     * fecha de la solicitud, que es hasta donde interesa revisar. Sin acotar, la pantalla
     * abriría con su rango por defecto y habría que filtrar a mano.
     */
    function urlCheckins(s, d) {
        var c = d.ciclo || {};
        var desde = soloFecha(c.fecha_inicio);
        var hasta = soloFecha(c.fecha_fin || s.fecha);
        var p = ['v=' + encodeURIComponent(s.id_vehiculo)];
        if (desde) p.push('desde=' + desde);
        if (hasta) p.push('hasta=' + hasta);
        return 'ver_checkins.php?' + p.join('&');
    }

    // Cargas del ciclo, indexadas por id, para que el modal de destino no tenga que
    // volver a pedirlas al servidor.
    var cargasDelDetalle = {};

    function pintarDetalles(d) {
        var s = d.solicitud || {};
        var vehiculo = [s.marca, s.modelo].filter(Boolean).join(' ');

        // Placa, modelo y tarjeta van en el CUERPO y no en el encabezado azul del modal:
        // ahí el texto secundario quedaba gris sobre azul y casi no se leía. Aquí van en
        // el color de texto normal, que además se adapta al tema oscuro.
        var identificacion =
            '<div class="mb-3">'
            + '<div class="h5 fw-bold mb-0">' + esc(s.placa || 'S/P')
            // Usuario del VEHÍCULO, que no siempre es quien pidió la recarga (en un
            // préstamo son personas distintas).
            +   (s.usuario_vehiculo
                    ? ' <span class="fs-6 fw-normal text-muted">· ' + esc(s.usuario_vehiculo) + '</span>'
                    : '')
            + '</div>'
            + '<div>' + esc(vehiculo)
            +   ' · ' + (s.efecticard
                    ? 'Tarjeta ' + esc(s.efecticard)
                    : '<span class="text-muted">Sin tarjeta registrada</span>')
            + '</div>'
            + '</div>';

        // Qué crédito se está viendo y desde cuándo. Antes decía "Crédito abonado el X por
        // $Y", que no dejaba claro si esa cifra era la recarga, el saldo o el gasto.
        // Ahora se nombra el dato: última recarga, monto abonado, y qué contiene la tabla.
        var c = d.ciclo;
        var rango;
        if (!c) {
            rango = 'No se encontró un ciclo de tarjeta para esta solicitud.';
        } else if (c.origen === 'INICIAL') {
            rango = '<b>Primer crédito registrado</b> del vehículo, desde el ' + fmtFechaCorta(c.fecha_inicio) + '. '
                  + 'Abajo van todas sus cargas.';
        } else {
            rango = '<b>Última recarga:</b> ' + fmtFechaCorta(c.fecha_inicio)
                  + (c.monto_abonado ? ', se abonaron <b>' + money(c.monto_abonado) + '</b>' : '')
                  + '. Abajo van las cargas hechas con ese crédito.'
                  + (c.origen === 'DETECTADO'
                        ? ' <span class="text-muted">(recarga deducida de las cargas, no capturada al aprobar)</span>'
                        : '');
        }
        if (c && c.estatus === 'CERRADO' && c.fecha_fin) {
            rango += ' <span class="text-muted">Este crédito se cerró el ' + fmtFechaCorta(c.fecha_fin) + '.</span>';
        }

        var cargas = Array.isArray(d.cargas) ? d.cargas : [];

        if (!cargas.length) {
            $('#detallesCuerpo').html(
                identificacion
                + '<p class="small text-muted">' + rango + '</p>'
                + '<div class="text-center text-muted py-4">'
                + '<i class="fas fa-gas-pump fa-2x mb-2 d-block"></i>'
                + 'No se registraron cargas en este periodo.</div>');
            return;
        }

        var filas = '';
        cargas.forEach(function (c) {
            // Se muestra 'pagos' (lo gastado) y no 'monto': monto es el saldo previo a la
            // carga, o sea el saldo de la fila anterior repetido. Así la columna suma
            // exactamente el "Total gastado" de arriba y el usuario puede comprobarlo.
            //
            // OJO: el orden de estos <td> tiene que ir a la par del <thead> de abajo,
            // incluidas las clases d-none/d-sm-table-cell de cada columna. Como aquí no
            // hay nada que ate el dato a su encabezado, mover una columna en un solo
            // lado deja los valores debajo del título equivocado.
            // Orden: Registro | Km | Recorridos | Gastado | Saldo | Carga | Destino
            //
            // En la celda va solo el nombre corto del cliente y el OT/OV. Cliente completo,
            // parque industrial y ciudad NO caben en un renglón de tabla (los nombres de
            // cliente llegan a 45+ caracteres), así que van en un modal chico que se abre
            // con el botón de información.
            cargasDelDetalle[c.id] = c;

            var destino = '';
            if (c.cliente) {
                destino += '<div class="d-flex align-items-start gap-1">'
                        +  '<span>' + esc(c.cliente_corto || c.cliente) + '</span>'
                        +  '<button type="button" class="btn btn-link btn-sm p-0 lh-1 btn-destino"'
                        +    ' data-id="' + c.id + '" title="Ver destino completo">'
                        +    '<i class="fas fa-circle-info"></i></button>'
                        +  '</div>';
            }
            if (c.ot) destino += '<div class="text-muted">OT/OV: ' + esc(c.ot) + '</div>';
            if (!destino) destino = '<span class="text-muted">—</span>';

            filas +=
                '<tr>'
                + '<td class="small text-nowrap">' + fmtFecha(c.fecha_registro) + '</td>'
                + '<td class="text-end small text-nowrap d-none d-sm-table-cell">'
                +   (c.km_actual ? Number(c.km_actual).toLocaleString('es-MX') + ' km' : '') + '</td>'
                // null en la primera carga del vehículo: no hay lectura anterior contra
                // la cual medir, y un 0 se leería como "no se movió".
                + '<td class="text-end small text-nowrap d-none d-sm-table-cell">'
                +   (c.km_recorridos !== null && c.km_recorridos !== undefined
                        ? Number(c.km_recorridos).toLocaleString('es-MX') + ' km'
                        : '<span class="text-muted">—</span>') + '</td>'
                + '<td class="text-end small text-nowrap">' + money(c.pagos) + '</td>'
                + '<td class="text-end small text-nowrap">' + money(c.saldo) + '</td>'
                + '<td class="small text-nowrap d-none d-sm-table-cell">' + fmtFecha(c.fecha_carga) + '</td>'
                + '<td class="small d-none d-md-table-cell">' + destino + '</td>'
                + '</tr>';
        });

        $('#detallesCuerpo').html(
            identificacion
            + '<p class="small text-muted">' + rango + '</p>'
            + '<div class="d-flex flex-wrap gap-3 mb-3">'
            +   '<div class="border rounded px-3 py-2"><div class="small text-muted">Cargas</div>'
            +     '<div class="fw-bold">' + cargas.length + '</div></div>'
            +   '<div class="border rounded px-3 py-2"><div class="small text-muted">Total gastado</div>'
            +     '<div class="fw-bold">' + money(d.total_gastado) + '</div></div>'
            +   '<div class="border rounded px-3 py-2"><div class="small text-muted">Km recorridos</div>'
            +     '<div class="fw-bold">'
            +       (d.km_ciclo != null
                        ? Number(d.km_ciclo).toLocaleString('es-MX') + ' km'
                        : '<span class="text-muted fw-normal">Sin dato</span>')
            +     '</div></div>'
            +   '<div class="border rounded px-3 py-2"><div class="small text-muted">Saldo al solicitar</div>'
            +     '<div class="fw-bold">' + money(s.saldo_solicitud) + '</div></div>'
            // Los check-ins del MISMO vehículo y del MISMO periodo del ciclo: sirve para
            // contrastar el gasto contra los viajes que de verdad se hicieron. Se abre en
            // otra pestaña para no perder la revisión que se está haciendo aquí.
            +   '<a class="border rounded px-3 py-2 text-decoration-none d-flex flex-column justify-content-center"'
            +     ' href="' + esc(urlCheckins(s, d)) + '" target="_blank" rel="noopener">'
            +     '<div class="small text-muted">Viajes</div>'
            +     '<div class="fw-bold text-primary"><i class="fas fa-clipboard-list me-1"></i>Ver check-ins</div>'
            +   '</a>'
            + '</div>'
            + '<div class="table-responsive"><table class="table table-sm table-hover align-middle mb-0">'
            // Encabezados centrados; las celdas conservan su alineación (cifras a la
            // derecha, fechas a la izquierda), que es lo que hace comparables las columnas.
            +   '<thead class="table-light"><tr>'
            +     '<th class="text-center">Registro</th>'
            +     '<th class="text-center d-none d-sm-table-cell">Km</th>'
            +     '<th class="text-center d-none d-sm-table-cell">Recorridos</th>'
            +     '<th class="text-center">Gastado</th>'
            +     '<th class="text-center">Saldo</th>'
            +     '<th class="text-center d-none d-sm-table-cell">Carga</th>'
            +     '<th class="text-center d-none d-md-table-cell">Destino</th>'
            +   '</tr></thead><tbody>' + filas + '</tbody></table></div>'
        );
    }

    function contar() {
        ['PENDIENTE', 'APROBADA', 'RECHAZADA'].forEach(function (e) {
            $('#cnt' + e).text(solicitudes.filter(function (s) {
                return e === "PENDIENTE" ? estaAbierta(s.estatus) : s.estatus === e;
            }).length);
        });
    }

    function cargar() {
        $('#feedSolicitudes').html('<div class="col-12 text-center py-5"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i></div>');
        $.ajax({
            url: 'acciones_gas.php', method: 'POST', dataType: 'json',
            data: { accion: 'consultarSolicitudesGas' }
        }).done(function (resp) {
            if (!resp || resp.status !== 'success') {
                $('#feedSolicitudes').empty();
                Swal.fire({ icon: 'error', title: 'Error', text: (resp && resp.message) ? resp.message : 'No se pudieron cargar las solicitudes.' });
                return;
            }
            // Array.isArray antes de iterar: el endpoint devuelve un objeto de error
            // cuando algo falla y recorrerlo rompe en silencio.
            solicitudes  = Array.isArray(resp.solicitudes) ? resp.solicitudes : [];
            puedeResolver = !!resp.puedeResolver;
            contar();
            pintar();
        }).fail(function () {
            $('#feedSolicitudes').html('<div class="col-12 text-center text-danger py-5"><i class="fas fa-exclamation-circle fa-2x"></i><p class="mt-2">Error al cargar.</p></div>');
        });
    }

    $(document).ready(function () {
        cargar();

        $('#tabsEstatus button').on('click', function () {
            $('#tabsEstatus button').removeClass('active');
            $(this).addClass('active');
            estatusActivo = $(this).data('estatus');
            pintar();
        });

        // Destino completo de una carga. Los datos ya vinieron con el detalle, así que no
        // se vuelve a consultar al servidor.
        $(document).on('click', '.btn-destino', function () {
            var c = cargasDelDetalle[$(this).data('id')];
            if (!c) return;

            var fila = function (etiqueta, valor) {
                if (!valor) return '';
                return '<div class="mb-2"><div class="small text-muted">' + etiqueta + '</div>'
                     + '<div>' + esc(valor) + '</div></div>';
            };
            var ciudad = [c.ciudad, c.estado].filter(Boolean).join(', ');

            $('#destinoCuerpo').html(
                fila('Cliente', c.cliente)
                + fila('Parque industrial', c.parque_industrial)
                + fila('Ciudad', ciudad)
                + fila('OT / OV / Otro', c.ot)
                + fila('Fecha de carga', fmtFecha(c.fecha_carga))
            );
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDestino')).show();
        });

        // Modal encima de modal. Bootstrap le pone el MISMO z-index (1055) a todos los
        // modales y 1050 al backdrop, así que el segundo salía detrás del de detalles y
        // tapado por su propio backdrop. Se le sube el z-index al abrirlo, y al backdrop
        // recién creado se le deja justo debajo.
        var elDestino = document.getElementById('modalDestino');

        elDestino.addEventListener('show.bs.modal', function () {
            var apilados = document.querySelectorAll('.modal.show').length;
            if (!apilados) return;   // se abrió solo, Bootstrap ya lo resuelve

            var z = 1055 + apilados * 20;
            elDestino.style.zIndex = z;

            // El backdrop no existe todavía dentro de show.bs.modal: Bootstrap lo inserta
            // después. Por eso se ajusta en el siguiente tick.
            setTimeout(function () {
                var backdrops = document.querySelectorAll('.modal-backdrop');
                var ultimo = backdrops[backdrops.length - 1];
                if (ultimo) ultimo.style.zIndex = z - 5;
            }, 0);
        });

        elDestino.addEventListener('hidden.bs.modal', function () {
            elDestino.style.zIndex = '';
            // Bootstrap quita .modal-open del body al cerrar CUALQUIER modal. Si abajo
            // sigue abierto el de detalles hay que devolvérsela, o el fondo empieza a
            // hacer scroll detrás del modal que sigue visible.
            if (document.querySelectorAll('.modal.show').length) {
                document.body.classList.add('modal-open');
            }
        });

        // Delegado: las tarjetas se vuelven a pintar al cambiar de pestaña y al resolver.
        $(document).on('click', '.btn-detalles', function () {
            var id = $(this).data('id');
            // El título del modal es fijo; la identificación del vehículo la pinta
            // pintarDetalles() dentro del cuerpo, así que basta con limpiar el cuerpo.
            $('#detallesCuerpo').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i></div>');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetalles')).show();

            $.ajax({
                url: 'acciones_gas.php', method: 'POST', dataType: 'json',
                data: { accion: 'historialCargasSolicitud', id_solicitud: id }
            }).done(function (resp) {
                if (!resp || resp.status !== 'success') {
                    $('#detallesCuerpo').html('<div class="alert alert-danger mb-0">'
                        + esc((resp && resp.message) ? resp.message : 'No se pudo cargar el detalle.') + '</div>');
                    return;
                }
                pintarDetalles(resp);
            }).fail(function () {
                $('#detallesCuerpo').html('<div class="alert alert-danger mb-0">No se pudo cargar el detalle.</div>');
            });
        });

        $(document).on('click', '.btn-resolver', function () {
            var id  = $(this).data('id');
            var res = $(this).data('res');
            var aprobar = (res === 'APROBADA');
            var sol = solicitudes.filter(function (x) { return x.id == id; })[0] || {};
            var yaAbonado = Number(sol.abonado_acumulado || 0);
            // Saldo REAL de la tarjeta ahora: el que tenía al solicitar más lo que ya se
            // le abonó en resoluciones anteriores. En una parcialidad, usar solo
            // saldo_solicitud haría proponer de nuevo el abono completo.
            var saldoActual = Number(sol.saldo_solicitud || 0) + yaAbonado;

            // Crédito estándar de una tarjeta. Mismo valor que usa verPlaca() en
            // js/global/vehiculos.js y la constante CREDITO_TARJETA de acciones_gas.php:
            // si cambia, cambiar en los tres.
            var CREDITO_TARJETA = 4000;

            // Por defecto se propone completar la tarjeta a $4,000, que es el caso normal.
            // Ojo: lo que se captura es el ABONO, no el saldo final, así que el default es
            // la diferencia. Si la tarjeta ya trae 4,000 o más no se propone nada.
            var abonoSugerido = Math.max(0, CREDITO_TARJETA - saldoActual);

            // Al aprobar se captura CUÁNTO se abonó: puede no ser lo que se pidió (recarga
            // parcial), y ese monto es el que abre el ciclo de tarjeta y viaja en el correo.
            var htmlAprobar =
                '<div class="text-start">'
                + '<label class="form-label small fw-semibold" for="swalMonto">Monto abonado a la tarjeta</label>'
                + '<input id="swalMonto" type="number" step="0.01" min="0.01" class="form-control"'
                +   ' placeholder="0.00" value="' + (abonoSugerido > 0 ? abonoSugerido.toFixed(2) : '') + '">'
                + '<div class="form-text">'
                +   (yaAbonado > 0
                        ? 'Ya se abonaron <b>' + money(yaAbonado) + '</b> a esta solicitud. Saldo actual '
                        : 'Saldo actual ')
                +   money(saldoActual)
                +   '. Por defecto se completa a ' + money(CREDITO_TARJETA)
                +   '; si abonas menos, la solicitud queda como <b>parcialidad</b> y sigue en pendientes. '
                +   'La tarjeta queda en '
                +   '<span id="swalResultado" class="fw-semibold">' + money(saldoActual + abonoSugerido) + '</span>.</div>'
                + '<label class="form-label small fw-semibold mt-3" for="swalNotas">Notas</label>'
                + '<textarea id="swalNotas" class="form-control" rows="3" placeholder="Referencia del abono, comentarios..."></textarea>'
                + '</div>';

            var htmlRechazar =
                '<div class="text-start">'
                + '<label class="form-label small fw-semibold" for="swalNotas">Motivo del rechazo</label>'
                + '<textarea id="swalNotas" class="form-control" rows="3" placeholder="Explica por qué se rechaza..."></textarea>'
                + '</div>';

            Swal.fire({
                icon: aprobar ? 'question' : 'warning',
                title: aprobar ? '¿Aprobar la recarga?' : '¿Rechazar la solicitud?',
                html: aprobar ? htmlAprobar : htmlRechazar,
                showCancelButton: true,
                confirmButtonText: aprobar ? 'Aprobar' : 'Rechazar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: aprobar ? '#1cc88a' : '#e74a3b',
                didOpen: function () {
                    if (!aprobar) return;
                    // Se muestra en vivo con cuánto queda la tarjeta: el saldo del ciclo es
                    // lo que quedaba MÁS lo abonado, no solo el abono.
                    $('#swalMonto').on('input', function () {
                        var m = parseFloat(this.value);
                        $('#swalResultado').text(money(saldoActual + (isNaN(m) ? 0 : m)));
                    });
                },
                preConfirm: function () {
                    var notas = ($('#swalNotas').val() || '').trim();
                    // Notas obligatorias en las dos resoluciones: aprobar sin dejar rastro
                    // del criterio hace imposible auditar después, y un rechazo sin motivo
                    // deja al solicitante sin saber qué corregir.
                    if (!notas) {
                        Swal.showValidationMessage('Las notas son obligatorias.');
                        return false;
                    }
                    if (!aprobar) return { notas: notas, monto: 0 };

                    var monto = parseFloat($('#swalMonto').val());
                    if (isNaN(monto) || monto <= 0) {
                        Swal.showValidationMessage('Captura el monto que se abonó a la tarjeta.');
                        return false;
                    }
                    return { notas: notas, monto: monto };
                }
            }).then(function (r) {
                if (!r.isConfirmed) return;
                $.ajax({
                    url: 'acciones_gas.php', method: 'POST', dataType: 'json',
                    data: {
                        accion: 'resolverSolicitudGas', id_solicitud: id, resolucion: res,
                        notas: r.value.notas, monto_recarga: r.value.monto
                    }
                }).done(function (resp) {
                    if (!resp || resp.status !== 'success') {
                        Swal.fire({ icon: 'error', title: 'No se pudo resolver', text: (resp && resp.message) ? resp.message : 'Error del servidor.' });
                        return;
                    }
                    Swal.fire({
                        icon: 'success',
                        title: aprobar ? 'Aprobada' : 'Rechazada',
                        text: resp.notificado ? 'Se avisó al solicitante por correo.' : 'Resuelta. No se pudo enviar el correo al solicitante.',
                        timer: 2500, timerProgressBar: true
                    });
                    cargar();
                }).fail(function () {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo resolver la solicitud.' });
                });
            });
        });
    });
</script>
</body>
</html>
