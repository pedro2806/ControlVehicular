<?php
session_start();
include 'conn.php';
if (empty($_COOKIE['noEmpleado'])) {
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
    <title>Control Vehicular - Historial de Gasolina</title>
    <!-- MESS Design System -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="css/mess-ds.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <!-- Responsive de DataTables (2.5.0): en móvil esconde las columnas que no caben
         y las muestra en una fila desplegable. Sin esto la tabla se salía de la
         pantalla. Se sirve desde css/lib y js/lib, no por CDN, para que no dependa
         de internet. -->
    <link rel="stylesheet" href="css/lib/responsive.dataTables.min.css">
    <!-- RowGroup: dibuja el separador entre ciclos de tarjeta. Sin él, la última carga de
         un crédito agotado y la primera del siguiente se veían seguidas, como si fueran la
         misma secuencia de consumo. -->
    <link rel="stylesheet" href="https://cdn.datatables.net/rowgroup/1.4.1/css/rowGroup.dataTables.min.css">
    <style>
        /* Corte entre créditos: banda sólida del color de marca, para que se vea de un
           vistazo dónde termina un crédito y empieza el siguiente.
           Se apunta a "> *" y no a "td": RowGroup 1.4 dibuja la celda como <th>. El
           estilo anterior apuntaba a td y nunca se aplicó; por eso los cortes se veían
           apagados, con el gris por defecto de la librería. */
        #tablaGas tr.dtrg-group > * {
            /* Bootstrap 5 pinta el fondo de las celdas con un box-shadow interior que
               queda ENCIMA del background (rayado de .table-striped): sin esto la banda
               se ve gris y el texto claro no se lee. */
            box-shadow: none;
            background: var(--accent);
            color: var(--card-bg);
            border-top: 3px solid var(--accent-dark);
            font-weight: 800;
            font-size: .95rem;
            padding-top: .6rem;
            padding-bottom: .6rem;
        }
        /* Espacio antes de cada corte que no sea el primero: separa los créditos */
        #tablaGas tbody tr:not(:first-child).dtrg-group > * { border-top-width: 1.25rem; border-top-color: var(--card-bg); }
        .gas-corte__dato { font-weight: 600; opacity: .9; }
        /* Recarga hecha fuera del sistema: banda amarilla, para que no pase de largo */
        #tablaGas tr.dtrg-group:has(.gas-corte--detectado) > * { background: var(--warning); color: var(--text); }
        .gas-corte__aviso { background: var(--card-bg); color: var(--text); }
        /* Cargas de antes del control de créditos: gris, son historia */
        #tablaGas tr.dtrg-group:has(.gas-corte--sin-ciclo) > * { background: var(--text-muted); color: var(--card-bg); }

        /* ── Dos columnas (idea de oktoberMESS: mesa-kits / correo de agradecimiento) ──
           Izquierda las cargas; derecha el estado de la tarjeta y de la solicitud, que es
           lo que la gente viene a revisar y antes quedaba al fondo de la página. En
           celular una sola columna, con el estado ANTES que la tabla. */
        .gas-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 1.25rem;
            align-items: start;
        }
        .gas-lado { display: grid; gap: 1rem; order: -1; }
        .gas-lado .card, .gas-principal .card { margin-bottom: 0; }
        @media (min-width: 1200px) {
            .gas-layout { grid-template-columns: minmax(0, 7fr) minmax(22rem, 4fr); }
            /* El panel se queda a la vista mientras se recorre la tabla */
            .gas-lado { order: 0; position: sticky; top: 1rem; }
        }
        /* #content-wrapper trae overflow-x: hidden (mess-ds.css), que convierte al
           contenedor en el de scroll y anula el sticky. clip recorta igual sin eso;
           un navegador que no lo soporte se queda con hidden y el panel solo no se fija. */
        body.pagina-gas #content-wrapper { overflow-x: clip; }

        .gas-etiqueta { font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--text-muted); }
        .gas-saldo { font-size: 2.25rem; font-weight: 800; line-height: 1.1; margin: .25rem 0 .5rem; color: var(--text); }
        .gas-saldo.es-ok      { color: var(--success); }
        .gas-saldo.es-bajo    { color: var(--warning); }
        .gas-saldo.es-agotado { color: var(--danger); }
        .gas-barra { height: .5rem; background: var(--card-soft); }
        .gas-barra .progress-bar { background: var(--success); }
        .gas-barra.es-bajo .progress-bar    { background: var(--warning); }
        .gas-barra.es-agotado .progress-bar { background: var(--danger); }

        /* Bloque de estado de la solicitud: el color lo da el estado */
        .gas-estado {
            display: flex; align-items: flex-start; gap: .75rem;
            padding: .75rem 1rem; border-radius: .5rem;
            border: 1px solid var(--border); border-left-width: 4px;
            background: var(--card-soft);
        }
        /* Es un botón (abre el detalle colapsado): se le quita el aspecto de botón */
        button.gas-estado { width: 100%; text-align: left; font: inherit; color: inherit; cursor: pointer; }
        button.gas-estado:hover { background: var(--accent-soft); }
        .gas-estado__flecha { align-self: center; color: var(--text-muted); transition: transform .2s; }
        .gas-estado[aria-expanded="true"] .gas-estado__flecha { transform: rotate(180deg); }
        /* Doble clase: .gas-accion se declara más abajo y, con la misma especificidad, ganaba */
        .gas-accion.gas-accion--sola { margin-top: 0; padding-top: 0; border-top: 0; }
        .gas-estado__icono { font-size: 1.5rem; line-height: 1; color: var(--text-muted); }
        .gas-estado__titulo { font-weight: 800; color: var(--text); }
        .gas-estado--pendiente { border-left-color: var(--warning); } .gas-estado--pendiente .gas-estado__icono { color: var(--warning); }
        .gas-estado--parcial   { border-left-color: var(--info); }    .gas-estado--parcial .gas-estado__icono   { color: var(--info); }
        .gas-estado--aprobada  { border-left-color: var(--success); } .gas-estado--aprobada .gas-estado__icono  { color: var(--success); }
        .gas-estado--rechazada { border-left-color: var(--danger); }  .gas-estado--rechazada .gas-estado__icono { color: var(--danger); }

        /* Pasos: Enviada → En revisión → Resuelta */
        .gas-pasos { list-style: none; display: flex; margin: 1rem 0 .75rem; padding: 0; }
        .gas-paso { flex: 1; position: relative; text-align: center; font-size: .72rem; color: var(--text-muted); }
        .gas-paso::before {
            content: ''; position: absolute; top: .55rem; left: -50%; width: 100%; height: 2px;
            background: var(--border);
        }
        .gas-paso:first-child::before { display: none; }
        .gas-paso__punto {
            position: relative; z-index: 1; display: grid; place-items: center;
            width: 1.2rem; height: 1.2rem; margin: 0 auto .3rem; border-radius: 50%;
            border: 2px solid var(--border); background: var(--card-bg);
            font-size: .55rem; color: var(--card-bg);
        }
        .gas-paso.es-hecho::before, .gas-paso.es-rechazo::before { background: var(--success); }
        .gas-paso.es-hecho .gas-paso__punto  { background: var(--success); border-color: var(--success); }
        .gas-paso.es-actual { color: var(--text); font-weight: 700; }
        .gas-paso.es-actual .gas-paso__punto { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-soft); }
        .gas-paso.es-rechazo { color: var(--danger); font-weight: 700; }
        .gas-paso.es-rechazo .gas-paso__punto { background: var(--danger); border-color: var(--danger); }

        .gas-datos { display: grid; grid-template-columns: auto 1fr; gap: .2rem .75rem; margin: 0; font-size: .85rem; }
        .gas-datos dt { font-weight: 600; color: var(--text-muted); }
        .gas-datos dd { margin: 0; color: var(--text); text-align: right; }
        .gas-accion { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border); }
        .gas-nota { font-size: .82rem; color: var(--text-muted); }

        /* Mis solicitudes: lista compacta; cada una abre su vehículo */
        .gas-missol .list-group-item { background: transparent; color: var(--text); border-color: var(--border); cursor: pointer; }
        .gas-missol .list-group-item:hover { background: var(--accent-soft); }
        .gas-missol .list-group-item.es-actual { border-left: 3px solid var(--accent); }
    </style>
</head>
<body id="page-top" class="pagina-gas">
<div id="wrapper">
    <?php include 'menu.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include 'encabezado.php'; ?>
            <div class="container-fluid">
                <div class="mb-3">
                    <h1 class="h3 mb-0 text-gray-800">Historial de Cargas de Gasolina</h1>
                </div>

                <!-- El vehículo va arriba y a todo lo ancho: manda sobre las dos columnas. -->
                <div class="card shadow mb-3">
                    <div class="card-body py-2">
                        <label class="form-label small mb-1" for="filtroVehiculo">Vehículo (tarjeta de gas)</label>
                        <select id="filtroVehiculo" class="form-select form-select-sm">
                            <option value="">Selecciona un vehículo</option>
                        </select>
                    </div>
                </div>

                <div class="gas-layout mb-4">
                    <div class="gas-principal">
                        <div class="card shadow">
                            <!-- El Excel va junto a la tabla que exporta, no en el título de la página. -->
                            <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between gap-2">
                                <h6 class="m-0 fw-bold text-dark"><i class="fas fa-gas-pump me-1"></i> Cargas</h6>
                                <button id="btnDescargar" class="btn btn-outline-success btn-sm" onclick="descargarReporteGas()" disabled>
                                    <i class="fas fa-file-excel me-1"></i> Descargar Excel
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="tablaGas" class="table table-striped table-bordered w-100">
                                        <thead>
                                            <tr>
                                                <th>Vehículo</th>
                                                <th>Usuario</th>
                                                <th>Monto</th>
                                                <th>Pagos</th>
                                                <th>Saldo</th>
                                                <th>Km Actual</th>
                                                <th>Km Consumidos</th>
                                                <!-- Cliente y OT/OV juntos: son opcionales y muchas cargas
                                                     no traen ninguno, así que dos columnas casi vacías no
                                                     se justifican. -->
                                                <th>Destino</th>
                                                <th>Fecha Carga</th>
                                                <!-- Columna 9, oculta: solo existe para que RowGroup
                                                     agrupe por ciclo de tarjeta. Acciones se fue al panel
                                                     de la derecha y Fecha Registro se quitó: la que importa
                                                     es cuándo se cargó, que ahora trae la hora. -->
                                                <th>Ciclo</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <aside class="gas-lado">
                        <!-- Saldo VIGENTE de la tarjeta, que no es el de la última carga: una
                             reposición aprobada abre un ciclo nuevo sin registrar carga, y hasta
                             que no se cargue gasolina la tabla sigue mostrando el saldo del ciclo
                             anterior. -->
                        <div class="card shadow" id="panelTarjeta" style="display:none;">
                            <div class="card-body">
                                <div class="gas-etiqueta">Saldo de la tarjeta</div>
                                <div class="gas-saldo" id="saldoTarjeta">—</div>
                                <div class="progress gas-barra" id="barraSaldo" role="progressbar"
                                     aria-label="Crédito disponible" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar"></div>
                                </div>
                                <div class="small text-muted mt-2" id="detalleCredito"></div>
                            </div>
                        </div>

                        <!-- Estado de la solicitud de reposición DEL VEHÍCULO (la haya pedido
                             quien sea) y el botón para pedirla. Antes el estado solo se veía en
                             la pantalla de quien autoriza y el botón vivía escondido en la fila
                             de la última carga. -->
                        <div class="card shadow" id="panelSolicitud" style="display:none;">
                            <div class="card-header bg-white py-2">
                                <h6 class="m-0 fw-bold text-dark"><i class="fas fa-rotate me-1"></i> Solicitud de reposición</h6>
                            </div>
                            <div class="card-body">
                                <div id="estadoSolicitud"></div>
                                <div class="gas-accion" id="accionReposicion"></div>
                            </div>
                        </div>

                        <!-- Seguimiento de lo que YO pedí, de cualquier vehículo. La card se
                             oculta cuando el usuario nunca ha solicitado nada. -->
                        <div class="card shadow" id="cardMisSolicitudes" style="display:none;">
                            <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between">
                                <h6 class="m-0 fw-bold text-dark">
                                    <i class="fas fa-clipboard-list me-1"></i> Mis solicitudes
                                </h6>
                                <span class="badge bg-secondary" id="badgeMisSolicitudes"></span>
                            </div>
                            <ul class="list-group list-group-flush gas-missol" id="listaMisSolicitudes"></ul>
                        </div>
                    </aside>
                </div>

            </div><!-- /.container-fluid -->
        </div><!-- /#content -->

        <footer class="sticky-footer bg-white">
            <div class="container my-auto">
                <div class="copyright text-center my-auto">
                    <span>Copyright &copy; MESS <?php echo date("Y"); ?></span>
                </div>
            </div>
        </footer>
    </div><!-- /#content-wrapper -->
</div><!-- /#wrapper -->

<a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="js/lib/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/rowgroup/1.4.1/js/dataTables.rowGroup.min.js"></script>
<!-- xlsx-js-style: SheetJS con soporte de estilos (rellenos, bordes, formatos). La
     versión normal de SheetJS no escribe colores, y el reporte necesita la cinta cian
     de las cargas y las franjas de cada ciclo. Expone el mismo global XLSX. -->
<script src="https://cdn.jsdelivr.net/npm/xlsx-js-style@1.2.0/dist/xlsx.bundle.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
var tabla;
var idVehSel = 0;
var placaSel = '';
// Datos de cada ciclo de tarjeta, por id, para rotular el separador de la tabla.
// RowGroup solo entrega el valor de la columna que agrupa, no la fila completa.
var infoCiclos = {};

$(document).ready(function () {
    tabla = $('#tablaGas').DataTable({
        destroy: true,
        paging: true,
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],
        ordering: true,
        searching: true,
        info: true,
        autoWidth: false,
        responsive: true,
        order: [[8, 'desc']],
        // El orden de prioridad decide qué columnas sobreviven en pantalla chica.
        // Lo que importa desde el celular es cuándo se cargó, cuánto queda y poder
        // actuar; el vehículo ya viene del selector de arriba y el usuario casi
        // siempre es uno mismo, así que esos dos son los primeros en esconderse.
        //
        // OJO: si se agrega o mueve una columna hay que recorrer estos índices, o el
        // orden por defecto y el agrupador apuntan a la equivocada. Se quitaron Acciones
        // (el botón de reposición pasó al panel) y Fecha Registro.
        // 0 Vehículo · 1 Usuario · 2 Monto · 3 Pagos · 4 Saldo · 5 Km Actual
        // 6 Km Consumidos · 7 Destino · 8 Fecha Carga · 9 Ciclo
        // Agrupa por ciclo de tarjeta. La columna 9 va oculta y solo existe para esto.
        rowGroup: {
            dataSrc: 9,
            startRender: function (rows, group) {
                return etiquetaCiclo(group, rows.count());
            }
        },
        columnDefs: [
            { visible: false, searchable: false, targets: [9] },
            // Fecha: se ordena y se busca por el valor crudo de MySQL, que es
            // cronológico como texto, y se muestra en formato legible.
            {
                targets: [8],
                render: function (d, type) {
                    if (type === 'sort' || type === 'type') return d || '';
                    return fmtFechaGas(d);
                }
            },
            { responsivePriority: 1,  targets: 8 },        // Fecha Carga
            { responsivePriority: 2,  targets: 4 },        // Saldo
            { responsivePriority: 3,  targets: 2 },        // Monto
            { responsivePriority: 4,  targets: 7 },        // Destino
            { responsivePriority: 5,  targets: 3 },        // Pagos
            { responsivePriority: 6,  targets: 5 },        // Km Actual
            { responsivePriority: 7,  targets: 6 },        // Km Consumidos
            { responsivePriority: 9,  targets: 1 },        // Usuario
            { responsivePriority: 10, targets: 0 }         // Vehículo
        ],
        language: {
            lengthMenu: "Mostrar _MENU_ registros",
            zeroRecords: "No se encontraron resultados",
            emptyTable: "Selecciona un vehículo para ver su tarjeta de gas",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            infoEmpty: "Sin registros disponibles",
            infoFiltered: "(filtrado de _MAX_ totales)",
            search: "Buscar:",
            paginate: { first: "Primero", last: "Último", next: "Siguiente", previous: "Anterior" }
        }
    });

    // El select se llena con los vehículos asignados (o todos si super usuario),
    // igual que mantenimiento; al cargar se precarga uno automáticamente.
    cargarSelectVehiculos(preseleccionarVehiculo);

    $('#filtroVehiculo').on('change', cargarHistorialVehiculo);

    // Tocar una de "Mis solicitudes" abre la tarjeta de ese vehículo. El buscador del
    // select reconstruye las opciones al filtrar, así que primero se limpia para que la
    // opción exista.
    $(document).on('click', '#listaMisSolicitudes .list-group-item', function () {
        var id = String($(this).data('vehiculo'));
        var $buscador = $('#filtroVehiculoBuscador');
        if ($buscador.length && $buscador.val()) $buscador.val('').trigger('input');
        if (!$('#filtroVehiculo option[value="' + id + '"]').length) {
            Swal.fire({ icon: 'info', title: 'Vehículo no disponible',
                text: 'Ese vehículo ya no está en tu lista (puede estar dado de baja o ya no ser tuyo).' });
            return;
        }
        $('#filtroVehiculo').val(id).trigger('change');
    });

    cargarMisSolicitudes();
});

/** Badge del estado de una solicitud. Mismos colores que validar_recargas.php. */
function badgeSolicitud(e) {
    if (e === 'PENDIENTE') return '<span class="badge bg-warning text-dark">Pendiente</span>';
    // Parcialidad en azul, no en amarillo: ya recibió dinero, pero sigue abierta.
    if (e === 'PARCIAL')   return '<span class="badge bg-info text-dark">Parcialidad</span>';
    if (e === 'APROBADA')  return '<span class="badge bg-success">Aprobada</span>';
    return '<span class="badge bg-danger">Rechazada</span>';
}

/**
 * Solicitudes que hizo el usuario, para que pueda seguirlas sin depender de la pantalla
 * de autorización (que requiere un acceso especial que casi nadie tiene).
 */
function cargarMisSolicitudes() {
    $.ajax({
        url: 'acciones_gas.php',
        method: 'POST',
        dataType: 'json',
        data: { accion: 'misSolicitudesGas' },
        success: function (resp) {
            if (!resp || resp.status !== 'success' || !Array.isArray(resp.solicitudes)) return;

            var lista = resp.solicitudes;
            // Sin solicitudes la card no aparece: no tiene caso una tabla vacía en una
            // pantalla que ya trae otra.
            if (!lista.length) { $('#cardMisSolicitudes').hide(); return; }

            var credito = Number(resp.credito || 4000);
            var abiertas = lista.filter(function (s) {
                return s.estatus === 'PENDIENTE' || s.estatus === 'PARCIAL';
            }).length;

            $('#badgeMisSolicitudes').text(
                lista.length + (lista.length === 1 ? ' solicitud' : ' solicitudes')
                + (abiertas ? ' · ' + abiertas + ' en trámite' : '')
            );

            // Lista compacta para la columna derecha. Cada una abre su vehículo arriba.
            var $ul = $('#listaMisSolicitudes').empty();
            lista.forEach(function (s) {
                var abonado = Number(s.abonado_acumulado || 0);
                var falta = Math.max(0, credito - Number(s.saldo_solicitud || 0) - abonado);

                // Lo abonado solo cuando hubo abono, y el "faltan" solo mientras siga
                // abierta: en una cerrada ya no hay nada que esperar.
                var detalle = 'saldo al pedir ' + fmtMoneda(s.saldo_solicitud);
                if (abonado > 0) detalle += ' · abonado ' + fmtMoneda(abonado);
                if (s.estatus === 'PARCIAL' && falta > 0) detalle += ' · faltan ' + fmtMoneda(falta);

                $ul.append($('<li class="list-group-item py-2" title="Ver la tarjeta de este vehículo">')
                    .attr('data-vehiculo', s.id_vehiculo)
                    .html(
                        '<div class="d-flex justify-content-between align-items-center gap-2">'
                        +   '<span class="fw-bold">' + escHtml(s.placa || 'S/P') + '</span>' + badgeSolicitud(s.estatus)
                        + '</div>'
                        + '<div class="small text-muted">' + escHtml(fmtFechaGas(s.fecha)) + ' · ' + detalle + '</div>'
                        + (s.notas_resolucion ? '<div class="small fst-italic">' + escHtml(s.notas_resolucion) + '</div>' : '')
                    ));
            });

            marcarSolicitudActual();
            $('#cardMisSolicitudes').show();
        }
    });
}

/** Resalta en "Mis solicitudes" las del vehículo que está abierto arriba. */
function marcarSolicitudActual() {
    $('#listaMisSolicitudes .list-group-item').each(function () {
        $(this).toggleClass('es-actual', String($(this).data('vehiculo')) === String(idVehSel));
    });
}

/** '$1,234.50' */
function fmtMoneda(n) {
    return '$' + (Number(n) || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Llena el select con consultarInventario (asignados / todos), como mantenimiento
function cargarSelectVehiculos(callback) {
    $.ajax({
        url: 'acciones_siniestro',
        type: 'POST',
        data: { accion: 'consultarInventario' },
        dataType: 'json',
        success: function (data) {
            var sel = $('#filtroVehiculo');
            sel.empty().append('<option value="">Selecciona un vehículo</option>');
            if (Array.isArray(data)) {
                data.forEach(function (v) {
                    // etiquetaVehiculo (js/global/vehiculos.js) agrega el nombre del
                    // usuario, que es como la gente identifica los autos.
                    sel.append($('<option>').val(v.id_vehiculo)
                        .attr('data-placa', v.placa)
                        .text(etiquetaVehiculo(v)));
                });
                // Al final, cuando ya están todas: el buscador guarda la lista completa.
                hacerSelectBuscable('filtroVehiculo', 'Buscar placa, modelo o usuario...');
            }
            if (typeof callback === 'function') callback();
        },
        error: function () {
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar la lista de vehículos.' });
        }
    });
}

// Precarga: 1 vehículo -> ese; varios -> el de más km recorridos (vehiculoPrincipalGas)
function preseleccionarVehiculo() {
    var opciones = $('#filtroVehiculo option').filter(function () { return this.value !== ''; });
    if (opciones.length === 0) { cargarHistorialVehiculo(); return; }
    if (opciones.length === 1) {
        $('#filtroVehiculo').val(opciones.first().val());
        cargarHistorialVehiculo();
        return;
    }
    $.ajax({
        url: 'acciones_gas.php',
        type: 'POST',
        data: { accion: 'vehiculoPrincipalGas' },
        dataType: 'json',
        success: function (resp) {
            var id = (resp && resp.id_vehiculo) ? String(resp.id_vehiculo) : '';
            if (id && $('#filtroVehiculo option[value="' + id + '"]').length) {
                $('#filtroVehiculo').val(id);
            } else {
                $('#filtroVehiculo').val(opciones.first().val());
            }
            cargarHistorialVehiculo();
        },
        error: function () {
            $('#filtroVehiculo').val(opciones.first().val());
            cargarHistorialVehiculo();
        }
    });
}

/**
 * 'YYYY-MM-DD HH:MM:SS' -> 'DD/MM/YYYY HH:MM'. Mismo criterio que fmtFecha() de
 * validar_recargas.php: se arma por partes para no depender de cómo interprete
 * new Date() una fecha de MySQL sin zona horaria.
 *
 * Las dos columnas se imprimían crudas. Daba igual mientras fecha_carga era DATE, pero
 * ahora es DATETIME y saldría "2026-09-04 13:20:00" en la tabla.
 */
function fmtFechaGas(f) {
    if (!f) return '—';
    var p = String(f).split(/[- :]/);
    if (p.length < 3) return String(f);
    // fecha_carga era DATE: las cargas de antes de pasarla a DATETIME quedaron a las
    // 00:00:00 porque su hora nunca se guardó. Se muestra solo la fecha en vez de una
    // medianoche inventada (mismo criterio que fechaTL de validar_recargas.php).
    var conHora = p[3] !== undefined && !(p[3] === '00' && (p[4] || '00') === '00' && (p[5] || '00') === '00');
    return p[2] + '/' + p[1] + '/' + p[0] + (conHora ? ' ' + p[3] + ':' + (p[4] || '00') : '');
}

/**
 * Texto del separador que divide la tabla por ciclo de tarjeta.
 *
 * Un ciclo es la vida de un crédito: desde que se abona dinero hasta el siguiente abono.
 * Sin este separador, la última carga de un crédito agotado y la primera del siguiente se
 * veían pegadas, como si fueran la misma secuencia de consumo — que fue justo lo que se
 * notó al ver un saldo de $200 seguido de uno de $3,500.
 */
function etiquetaCiclo(clave, cuantas) {
    var plural = cuantas === 1 ? '1 carga' : cuantas + ' cargas';

    // La clase del span marca el tipo de corte; el CSS pinta la banda completa con
    // :has(), porque RowGroup arma el <tr> y solo deja poner su contenido.
    if (clave === 'sin-ciclo') {
        return '<span class="gas-corte gas-corte--sin-ciclo"><i class="fas fa-clock-rotate-left me-2"></i>'
             + 'Cargas anteriores al control de créditos <span class="gas-corte__dato">· ' + plural + '</span></span>';
    }

    var info = infoCiclos[clave] || {};
    var monto = parseFloat(info.saldoInicial);
    var partes = [];
    if (!isNaN(monto)) partes.push(fmtMoneda(monto));
    if (info.fechaInicio) partes.push('desde ' + fmtFechaGas(info.fechaInicio));
    partes.push(plural);

    // DETECTADO = el abono no pasó por una solicitud, se dedujo de una carga cuyo monto
    // subió. Vale la pena distinguirlo: significa que alguien recargó fuera del sistema.
    var detectado = info.origen === 'DETECTADO';
    return '<span class="gas-corte' + (detectado ? ' gas-corte--detectado' : '') + '">'
         + '<i class="fas fa-credit-card me-2"></i>Crédito <span class="gas-corte__dato">· ' + partes.join(' · ') + '</span>'
         + (detectado ? ' <span class="badge gas-corte__aviso ms-2">Recarga no registrada</span>' : '')
         + '</span>';
}

function cargarHistorialVehiculo() {
    idVehSel = parseInt($('#filtroVehiculo').val()) || 0;
    placaSel = $('#filtroVehiculo option:selected').data('placa') || '';
    marcarSolicitudActual();

    if (!idVehSel) {
        tabla.clear().draw();
        toggleBotones(false);
        $('#panelTarjeta, #panelSolicitud').hide();
        return;
    }

    $.ajax({
        url: 'acciones_gas.php',
        type: 'POST',
        data: { accion: 'obtenerHistorialGas', id_vehiculo: idVehSel },
        dataType: 'json',
        success: function (resp) {
            // El endpoint devuelve {registros, saldo_actual, solicitud_abierta,
            // ultima_solicitud, ciclo_abierto}. Se valida el array aparte porque ante un
            // error responde un objeto sin 'registros'.
            var data = (resp && Array.isArray(resp.registros)) ? resp.registros : [];
            var solicitudAbierta = !!(resp && resp.solicitud_abierta);
            // El crédito estándar lo manda el servidor (constante CREDITO_TARJETA de
            // acciones_gas.php) para no hardcodear otro 4000 aquí: ya está repetido en
            // verPlaca() (js/global/vehiculos.js) y en validar_recargas.php.
            var CREDITO_TARJETA = parseFloat(resp && resp.credito_tarjeta) || 4000;
            tabla.clear();
            // Se reinicia con cada vehículo: los ciclos del anterior no aplican.
            infoCiclos = {};

            // El buscador del select reconstruye las opciones sin su data-placa; en ese
            // caso la placa se toma de las cargas.
            if (!placaSel && data.length) placaSel = data[0].placa || '';

            // La carga más reciente (mayor id): su saldo es el respaldo cuando el vehículo
            // no tiene ciclo de tarjeta.
            var latest = null;
            data.forEach(function (r) {
                if (!latest || parseInt(r.id) > parseInt(latest.id)) latest = r;
            });

            // saldo_actual viene del ciclo de tarjeta abierto, no de la última carga: tras
            // una reposición aprobada el ciclo nuevo ya tiene el crédito aunque todavía no
            // exista una carga que lo refleje. Solo se cae al saldo de la última carga
            // cuando el vehículo no tiene ningún ciclo (null).
            var haySaldoCiclo = resp && resp.saldo_actual !== null && resp.saldo_actual !== undefined;
            var saldoTarjeta = haySaldoCiclo
                ? parseFloat(resp.saldo_actual)
                : (latest ? parseFloat(latest.saldo) : NaN);
            if (isNaN(saldoTarjeta)) saldoTarjeta = 0;

            data.forEach(function (r) {
                var saldo   = parseFloat(r.saldo) || 0;
                var kmCons  = parseInt(r.km_consumidos) || 0;
                var usuario = r.nombre_usuario || r.usuario || '—';

                var badgeSaldo = saldo <= 0
                    ? '<span class="badge bg-danger">$' + saldo.toFixed(2) + '</span>'
                    : saldo < 500
                        ? '<span class="badge bg-warning text-dark">$' + saldo.toFixed(2) + '</span>'
                        : '<span class="badge bg-success">$' + saldo.toFixed(2) + '</span>';

                var kmBadge = kmCons > 0
                    ? '<span class="text-primary fw-bold">' + kmCons.toLocaleString() + ' km</span>'
                    : '<span class="text-muted">—</span>';

                // Cliente y OT/OV en una sola celda. Cuando no hay ninguno se pone un guion
                // en vez de dejarla vacía, para que se lea como "sin dato" y no como error.
                var destino = '';
                if (r.cliente) destino += escHtml(r.cliente);
                if (r.ot) destino += (destino ? '<div class="text-muted small">' : '<span class="text-muted small">')
                                   + 'OT/OV: ' + escHtml(r.ot) + (destino ? '</div>' : '</span>');
                if (!destino) destino = '<span class="text-muted">—</span>';

                var claveCiclo = (r.id_ciclo === null || r.id_ciclo === undefined) ? 'sin-ciclo' : String(r.id_ciclo);

                tabla.row.add([
                    escHtml(r.Vehiculo),
                    escHtml(usuario),
                    '$' + parseFloat(r.monto).toFixed(2),
                    '$' + parseFloat(r.pagos || 0).toFixed(2),
                    badgeSaldo,
                    (parseInt(r.km_actual) || 0).toLocaleString() + ' km',
                    kmBadge,
                    destino,
                    // Las fechas viajan CRUDAS ('YYYY-MM-DD HH:MM:SS') y se formatean en el
                    // render de la columna. Si se guardan ya formateadas como 'DD/MM/YYYY',
                    // DataTables las ordena como texto: 27/07 quedaba por encima de 04/09, y
                    // al perderse el orden cronológico las cargas de un mismo ciclo dejaban
                    // de ser contiguas y el separador se repetía varias veces.
                    r.fecha_carga || '',
                    // Columna oculta que agrupa. Las cargas anteriores a los ciclos de
                    // tarjeta no tienen id_ciclo; se juntan todas bajo la misma etiqueta.
                    claveCiclo
                ]);

                // Datos para rotular el separador. Se guardan aparte porque RowGroup solo
                // recibe el valor de la columna que agrupa, no la fila completa.
                if (!infoCiclos[claveCiclo]) {
                    infoCiclos[claveCiclo] = {
                        saldoInicial: r.ciclo_saldo_inicial,
                        fechaInicio:  r.ciclo_fecha_inicio,
                        origen:       r.ciclo_origen
                    };
                }
            });

            tabla.draw(false);
            toggleBotones(data.length > 0);

            pintarPanelTarjeta((data.length || haySaldoCiclo) ? saldoTarjeta : null,
                CREDITO_TARJETA, resp ? resp.ciclo_abierto : null);
            pintarPanelSolicitud(resp ? resp.ultima_solicitud : null, {
                credito:    CREDITO_TARJETA,
                // Si se puede pedir y por qué no: lo decide el servidor con la misma regla
                // que aplica al recibir la solicitud (evaluarReposicion en acciones_gas.php).
                reposicion: resp ? resp.reposicion : null
            });
        },
        error: function () {
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar el historial.' });
        }
    });
}

function toggleBotones(activo) {
    $('#btnDescargar').prop('disabled', !activo);
}

/**
 * Panel de la tarjeta: saldo vigente en grande y cuánto queda del crédito.
 * saldo null = el vehículo no tiene tarjeta registrada (ni cargas ni ciclo).
 */
function pintarPanelTarjeta(saldo, credito, ciclo) {
    if (saldo === null) { $('#panelTarjeta').hide(); return; }

    // Mismos cortes que los badges de la tabla: agotado, menos de $500, el resto.
    var nivel = saldo <= 0 ? 'es-agotado' : (saldo < 500 ? 'es-bajo' : 'es-ok');
    var pct = credito > 0 ? Math.max(0, Math.min(100, saldo / credito * 100)) : 0;

    $('#saldoTarjeta').text(fmtMoneda(saldo)).attr('class', 'gas-saldo ' + nivel);
    $('#barraSaldo').attr('class', 'progress gas-barra ' + nivel).attr('aria-valuenow', Math.round(pct))
        .find('.progress-bar').css('width', pct + '%');

    var detalle = fmtMoneda(saldo) + ' de ' + fmtMoneda(credito);
    if (ciclo && ciclo.fecha_inicio) detalle += ' · crédito vigente desde ' + fmtFechaGas(ciclo.fecha_inicio);
    $('#detalleCredito').text(detalle);
    $('#panelTarjeta').show();
}

// Cómo se presenta cada estado. Mismos colores que badgeSolicitud y validar_recargas.php.
var ESTADOS_SOLICITUD = {
    PENDIENTE: { clase: 'pendiente', icono: 'fa-hourglass-half',     titulo: 'Pendiente de autorizar' },
    PARCIAL:   { clase: 'parcial',   icono: 'fa-circle-half-stroke', titulo: 'Abono parcial' },
    APROBADA:  { clase: 'aprobada',  icono: 'fa-circle-check',       titulo: 'Aprobada' },
    RECHAZADA: { clase: 'rechazada', icono: 'fa-circle-xmark',       titulo: 'Rechazada' }
};

/**
 * Panel de la solicitud de reposición: en qué va la última del vehículo (la haya pedido
 * quien sea) y, abajo, el botón para pedir una nueva o el motivo por el que no se puede.
 * Antes el estado solo se veía en la pantalla de quien autoriza y el botón vivía en la
 * fila de la última carga, donde casi nadie lo encontraba.
 */
function pintarPanelSolicitud(s, ctx) {
    // El estado solo se muestra cuando hay algo que seguir: Pendiente, Parcialidad o
    // Rechazada. Una aprobada ya cumplió y "Sin solicitudes" no dice nada, así que
    // ocupaban espacio sin aportar. Y va colapsado: una línea con el estado, y el
    // detalle (pasos y datos) solo si se despliega.
    var html = '';
    if (s && s.estatus !== 'APROBADA') {
        var e = ESTADOS_SOLICITUD[s.estatus] || ESTADOS_SOLICITUD.PENDIENTE;
        var abonado = Number(s.abonado_acumulado || 0);
        var falta = Math.max(0, ctx.credito - Number(s.saldo_solicitud || 0) - abonado);

        var sub;
        if (s.estatus === 'PENDIENTE')      sub = 'Enviada el ' + fmtFechaGas(s.fecha) + '. Falta que la autoricen.';
        else if (s.estatus === 'PARCIAL')   sub = 'Se abonaron ' + fmtMoneda(abonado) + (falta > 0 ? '; faltan ' + fmtMoneda(falta) + '.' : '.');
        else                                sub = 'Se rechazó el ' + fmtFechaGas(s.fecha_resuelto) + '.';

        html = '<button type="button" class="gas-estado gas-estado--' + e.clase + ' collapsed" '
             +   'data-bs-toggle="collapse" data-bs-target="#detalleSolicitud" '
             +   'aria-expanded="false" aria-controls="detalleSolicitud" title="Ver detalle de la solicitud">'
             +   '<i class="fas ' + e.icono + ' gas-estado__icono"></i>'
             +   '<span class="flex-grow-1"><span class="gas-estado__titulo d-block">' + e.titulo + '</span>'
             +   '<span class="small text-muted d-block">' + escHtml(sub) + '</span></span>'
             +   '<i class="fas fa-chevron-down gas-estado__flecha"></i>'
             + '</button>'
             + '<div class="collapse" id="detalleSolicitud">'
             +   pasosSolicitud(s.estatus)
             +   '<dl class="gas-datos">'
             +     '<dt>Solicitó</dt><dd>' + escHtml(s.solicitante || 'S/R') + '</dd>'
             +     '<dt>Fecha</dt><dd>' + escHtml(fmtFechaGas(s.fecha)) + '</dd>'
             +     '<dt>Saldo al pedir</dt><dd>' + fmtMoneda(s.saldo_solicitud) + '</dd>'
             +     (abonado > 0 ? '<dt>Abonado</dt><dd>' + fmtMoneda(abonado) + '</dd>' : '')
             +     (s.fecha_resuelto ? '<dt>Resolvió</dt><dd>' + escHtml(s.resolvio || 'S/R') + '</dd>' : '')
             +     (s.notas_resolucion ? '<dt>Notas</dt><dd class="fst-italic">' + escHtml(s.notas_resolucion) + '</dd>' : '')
             +   '</dl>'
             + '</div>';
    }

    // El botón (o el motivo por el que no se puede pedir) se queda aunque no haya estado
    // que mostrar: tras una aprobada, el responsable tiene que poder pedir la siguiente.
    var accion = accionReposicion(ctx.reposicion);
    $('#estadoSolicitud').html(html).toggle(html !== '');
    $('#accionReposicion').html(accion).toggle(accion !== '')
        // Sin estado arriba, la acción no lleva la línea divisoria.
        .toggleClass('gas-accion--sola', html === '');
    $('#panelSolicitud').toggle(html !== '' || accion !== '');
}

/** Los tres pasos: Enviada → En revisión (o Abono parcial) → Aprobada / Rechazada. */
function pasosSolicitud(estatus) {
    var resuelta = estatus === 'APROBADA' || estatus === 'RECHAZADA';
    var pasos = [
        { texto: 'Enviada', clase: 'es-hecho' },
        { texto: estatus === 'PARCIAL' ? 'Abono parcial' : 'En revisión', clase: resuelta ? 'es-hecho' : 'es-actual' },
        { texto: estatus === 'RECHAZADA' ? 'Rechazada' : (estatus === 'APROBADA' ? 'Aprobada' : 'Resuelta'),
          clase: estatus === 'RECHAZADA' ? 'es-rechazo' : (estatus === 'APROBADA' ? 'es-hecho' : '') }
    ];
    return '<ol class="gas-pasos" aria-label="Avance de la solicitud">' + pasos.map(function (p) {
        var icono = p.clase === 'es-hecho' ? '<i class="fas fa-check"></i>'
                  : (p.clase === 'es-rechazo' ? '<i class="fas fa-xmark"></i>' : '');
        return '<li class="gas-paso ' + p.clase + '"><span class="gas-paso__punto">' + icono + '</span>' + p.texto + '</li>';
    }).join('') + '</ol>';
}

/**
 * Botón o aviso de la reposición. No decide nada: pinta la evaluación que manda el
 * servidor (evaluarReposicion en acciones_gas.php), que es la misma que valida al
 * recibir la solicitud. Antes esta función tenía su propia regla y no coincidía con
 * la del servidor.
 */
function accionReposicion(r) {
    // Sin cargas o vehículo no disponible: no hay nada que ofrecer.
    if (!r || r.motivo === 'sin_cargas' || r.motivo === 'vehiculo') return '';

    if (r.puede) {
        return '<button id="btnReposicion" class="btn btn-primary w-100" '
             +   'onclick="solicitarReposicion(' + idVehSel + ',' + Number(r.saldo).toFixed(2) + ',\'' + escHtml(placaSel) + '\')">'
             +   '<i class="fas fa-paper-plane me-1"></i>Solicitar reposición de crédito</button>';
    }
    if (r.motivo === 'en_tramite') {
        return '<button class="btn btn-outline-secondary w-100" disabled>'
             +   '<i class="fas fa-hourglass-half me-1"></i>Solicitud en trámite</button>'
             + '<div class="gas-nota mt-2">Cuando se resuelva podrás pedir otra.</div>';
    }
    var iconos = { al_corriente: 'fa-check', saldo_suficiente: 'fa-gauge-high', no_autorizado: 'fa-lock' };
    return '<div class="gas-nota"><i class="fas ' + (iconos[r.motivo] || 'fa-circle-info') + ' me-1"></i>'
         + escHtml(r.mensaje) + '</div>';
}

function solicitarReposicion(id_vehiculo, saldo, placa) {
    Swal.fire({
        title: '¿Solicitar reposición?',
        html: 'Se enviará un correo al encargado solicitando crédito de gasolina para <strong>' + escHtml(placa) + '</strong>.<br>Saldo actual: <strong>$' + parseFloat(saldo).toFixed(2) + '</strong>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-paper-plane me-1"></i> Enviar solicitud',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#050D9E'
    }).then(function (result) {
        if (!result.isConfirmed) return;
        $.ajax({
            url: 'acciones_gas.php',
            method: 'POST',
            dataType: 'json',
            data: { accion: 'solicitarReposicionGas', id_vehiculo: id_vehiculo, saldo: saldo },
            success: function (resp) {
                Swal.fire({
                    icon: resp.status === 'success' ? 'success' : 'error',
                    title: resp.status === 'success' ? '¡Enviado!' : 'Error',
                    text: resp.message,
                    timer: 2500,
                    showConfirmButton: false
                });
                // Se refresca el seguimiento para que la solicitud recién hecha aparezca
                // de inmediato; si no, habría que recargar la página para verla.
                // Y también el historial: ahí vive el botón de reposición, que tiene que
                // pasar a "Solicitud enviada". Sin esto el botón se quedaba intacto y se
                // podían mandar solicitudes repetidas sin darse cuenta.
                if (resp.status === 'success') {
                    cargarMisSolicitudes();
                    cargarHistorialVehiculo();
                }
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo enviar la solicitud.' });
            }
        });
    });
}


/**
 * Reporte de gasolina del vehículo seleccionado, en Excel.
 *
 * Antes el botón volcaba la tabla tal cual (solo cargas, y solo la página visible del
 * DataTable). Ahora es el mismo contenido que el detalle de una solicitud en
 * validar_recargas.php, pero de todos los ciclos de la tarjeta: cada crédito con sus
 * recorridos y sus cargas en orden, la cinta cian en las cargas y el corte en cada
 * solicitud de renovación. Los datos los arma reporteGasVehiculo en acciones_gas.php.
 */
function descargarReporteGas() {
    if (!idVehSel) return;
    var $btn = $('#btnDescargar');
    var htmlBtn = $btn.html();
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Generando...');

    $.ajax({
        url: 'acciones_gas.php',
        method: 'POST',
        dataType: 'json',
        data: { accion: 'reporteGasVehiculo', id_vehiculo: idVehSel }
    }).done(function (resp) {
        if (!resp || resp.status !== 'success') {
            Swal.fire({ icon: 'error', title: 'No se pudo generar', text: (resp && resp.message) ? resp.message : 'Error del servidor.' });
            return;
        }
        construirExcelGas(resp);
    }).fail(function () {
        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo generar el reporte.' });
    }).always(function () {
        $btn.prop('disabled', false).html(htmlBtn);
    });
}

function construirExcelGas(resp) {
    var veh = resp.vehiculo || {};

    // Colores del Excel. Van en hex porque Excel no entiende variables CSS; son los
    // mismos del modal: --accent-soft / --accent para las franjas y el cian de Bootstrap
    // (--bs-info) para las cargas.
    var C = { franja: 'EAF3FF', acento: '1C83F1', cian: 'CFF4FC', cianFuerte: '0DCAF0', gris: '6C757D', borde: 'D9DEE5' };
    var FMT = { dinero: '"$"#,##0.00', km: '#,##0', fecha: 'dd/mm/yyyy' };

    // Columnas. OJO: los índices de COL se usan en todas las filas; si se agrega una
    // columna hay que moverla aquí y en ANCHOS.
    var ENCABEZADOS = ['Fecha', 'Hora', 'Movimiento', 'Detalle', 'OT / OV', 'Conductor',
                       'Odómetro inicial', 'Odómetro final', 'Recorrido (km)', 'Gasto', 'Saldo'];
    var COL = { fecha: 0, hora: 1, mov: 2, detalle: 3, ot: 4, conductor: 5,
                kmIni: 6, kmFin: 7, km: 8, gasto: 9, saldo: 10 };
    var ANCHOS = [12, 15, 22, 42, 14, 28, 15, 15, 14, 13, 13];
    var N = ENCABEZADOS.length;

    var celdas = [];   // filas de celdas {v, t, z, s}
    var merges = [];

    var partes = function (f) { return String(f || '').split(/[- :]/); };
    // Fecha de Excel como número de serie: se calcula en UTC a partir del texto de MySQL
    // para que la zona horaria del navegador no la mueva un día.
    var serialFecha = function (f) {
        var p = partes(f);
        if (!p[0] || !p[2]) return null;
        return (Date.UTC(+p[0], +p[1] - 1, +p[2]) - Date.UTC(1899, 11, 30)) / 86400000;
    };
    var hora = function (f) { var p = partes(f); return p[3] ? p[3] + ':' + (p[4] || '00') : ''; };
    var ddmm = function (f) { var p = partes(f); return p[2] + '/' + p[1]; };
    var fmtLargo = function (f) {
        var p = partes(f);
        return p[2] + '/' + p[1] + '/' + p[0] + (p[3] && hora(f) !== '00:00' ? ' ' + hora(f) : '');
    };
    var dinero = function (v) {
        return '$' + Number(v || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };
    var num = function (v) { return (v === null || v === undefined || v === '') ? null : Number(v); };

    // Crea una fila con estilo base y la agrega. valores: {indiceColumna: valor}; los
    // numéricos van como {v, z} para que Excel los pueda sumar y filtrar.
    // sinBorde: para títulos y renglones separadores.
    var agregarFila = function (valores, estilo, sinBorde) {
        var fila = [];
        for (var i = 0; i < N; i++) {
            var v = valores[i];
            var celda = { v: '', t: 's', s: $.extend(true, {
                font: { name: 'Calibri', sz: 10 },
                alignment: { vertical: 'top', wrapText: i === COL.detalle },
                border: sinBorde ? {} : { bottom: { style: 'thin', color: { rgb: C.borde } } }
            }, estilo || {}) };
            if (v !== null && v !== undefined && v !== '') {
                if (typeof v === 'object') {
                    if (v.v !== null && v.v !== undefined) { celda.v = v.v; celda.t = 'n'; celda.z = v.z; }
                } else {
                    celda.v = v;
                }
            }
            fila.push(celda);
        }
        celdas.push(fila);
        return fila;
    };

    // ---- Encabezado del documento ----
    var titulo = function (texto, sz, bold, color) {
        var f = agregarFila({ 0: texto }, { font: { sz: sz, bold: bold, color: { rgb: color || '000000' } } }, true);
        merges.push({ s: { r: celdas.length - 1, c: 0 }, e: { r: celdas.length - 1, c: N - 1 } });
        return f;
    };
    titulo('Reporte de gasolina', 14, true);
    titulo([veh.placa || 'S/P', [veh.marca, veh.modelo].filter(Boolean).join(' '),
            veh.efecticard ? 'Tarjeta ' + veh.efecticard : 'Sin tarjeta registrada',
            veh.usuario_vehiculo ? 'Usuario: ' + veh.usuario_vehiculo : ''].filter(Boolean).join('  ·  '), 11, true);
    titulo('Saldo actual de la tarjeta: ' + (resp.saldo_actual !== null && resp.saldo_actual !== undefined ? dinero(resp.saldo_actual) : 'sin dato')
           + '  ·  Generado el ' + fmtLargo(resp.generado), 10, false, C.gris);
    titulo('Cada crédito va en su sección, del más reciente al más antiguo. Dentro de cada una, en orden cronológico: '
           + 'recorridos en blanco, cargas de gas en cian y el corte de cada solicitud de renovación.', 9, false, C.gris)
        [0].s.alignment.wrapText = true;
    agregarFila({}, null, true);

    agregarFila(ENCABEZADOS.reduce(function (o, h, i) { o[i] = h; return o; }, {}), {
        font: { bold: true, color: { rgb: 'FFFFFF' } },
        fill: { fgColor: { rgb: C.acento } },
        alignment: { vertical: 'center', horizontal: 'center', wrapText: true }
    });
    var filaEncabezado = celdas.length - 1;

    var estiloFranja = { font: { bold: true }, fill: { fgColor: { rgb: C.franja } },
                         border: { top: { style: 'medium', color: { rgb: C.acento } }, bottom: { style: 'thin', color: { rgb: C.borde } } } };
    var estiloCarga = { fill: { fgColor: { rgb: C.cian } } };

    var filaCarga = function (cg) {
        var detalle = [cg.cliente, [cg.ciudad, cg.estado].filter(Boolean).join(', ')].filter(Boolean).join(' — ');
        var v = {};
        v[COL.fecha] = { v: serialFecha(cg.fecha_carga), z: FMT.fecha };
        v[COL.hora] = hora(cg.fecha_carga);
        v[COL.mov] = 'Carga de gas';
        v[COL.detalle] = detalle;
        v[COL.ot] = cg.ot || '';
        v[COL.conductor] = cg.usuario || '';
        if (num(cg.km_actual)) v[COL.kmFin] = { v: num(cg.km_actual), z: FMT.km };
        v[COL.gasto] = { v: num(cg.pagos) || 0, z: FMT.dinero };
        v[COL.saldo] = { v: num(cg.saldo) || 0, z: FMT.dinero };
        var fila = agregarFila(v, estiloCarga);
        fila[COL.mov].s.font.bold = true;
        // La "cinta": borde grueso cian a la izquierda de la fila.
        fila[0].s.border.left = { style: 'thick', color: { rgb: C.cianFuerte } };
    };

    var filaRecorrido = function (r) {
        var inicio = r.fecha_inicio || r.fecha_fin;
        var horaTxt;
        if (r.tipo) {
            horaTxt = hora(inicio);
        } else {
            var fin = '';
            if (r.fecha_fin) {
                fin = String(r.fecha_fin).split(' ')[0] === String(inicio).split(' ')[0]
                    ? hora(r.fecha_fin) : ddmm(r.fecha_fin) + ' ' + hora(r.fecha_fin);
            }
            horaTxt = (r.fecha_inicio ? hora(r.fecha_inicio) : '…') + ' – ' + (fin || 'sin regreso');
        }
        var detalle = r.tipo ? r.tipo : (r.notas_inicio || r.notas_fin || '');
        var avisos = [];
        if (!r.fecha_inicio) avisos.push('inició antes del crédito');
        if (!r.tipo && !r.fecha_fin) avisos.push('sin regreso registrado');
        if (avisos.length) detalle += (detalle ? ' ' : '') + '(' + avisos.join(', ') + ')';

        var v = {};
        v[COL.fecha] = { v: serialFecha(inicio), z: FMT.fecha };
        v[COL.hora] = horaTxt;
        v[COL.mov] = 'Recorrido';
        v[COL.detalle] = detalle;
        v[COL.ot] = r.ot || '';
        v[COL.conductor] = r.usuario || '';
        if (r.km_inicio !== null) v[COL.kmIni] = { v: r.km_inicio, z: FMT.km };
        if (r.km_fin !== null) v[COL.kmFin] = { v: r.km_fin, z: FMT.km };
        if (r.km_recorridos !== null) v[COL.km] = { v: r.km_recorridos, z: FMT.km };
        var fila = agregarFila(v);
        // Los registros incompletos se resaltan en ámbar: son los que hay que revisar.
        if (avisos.length) fila[COL.detalle].s.font.color = { rgb: 'B8860B' };
    };

    var filaTotales = function (cargas, recorridos, totalGastado, saldoFinal) {
        var kmViajes = 0;
        recorridos.forEach(function (r) { if (r.km_recorridos !== null) kmViajes += Number(r.km_recorridos); });
        var v = {};
        v[COL.mov] = 'Totales';
        v[COL.detalle] = cargas.length + (cargas.length === 1 ? ' carga' : ' cargas')
                       + ' · ' + recorridos.length + (recorridos.length === 1 ? ' recorrido' : ' recorridos');
        v[COL.km] = { v: kmViajes, z: FMT.km };
        v[COL.gasto] = { v: totalGastado, z: FMT.dinero };
        if (saldoFinal !== null) v[COL.saldo] = { v: saldoFinal, z: FMT.dinero };
        agregarFila(v, { font: { bold: true }, border: { top: { style: 'thin', color: { rgb: '000000' } } } });
        agregarFila({}, null, true);
    };

    var ESTATUS = { PENDIENTE: 'pendiente', PARCIAL: 'parcialidad', APROBADA: 'aprobada', RECHAZADA: 'rechazada' };

    var secciones = Array.isArray(resp.secciones) ? resp.secciones : [];
    secciones.forEach(function (sec) {
        var c = sec.ciclo || {};
        var cargas = Array.isArray(sec.cargas) ? sec.cargas : [];
        var recorridos = Array.isArray(sec.recorridos) ? sec.recorridos : [];
        var solicitudes = Array.isArray(sec.solicitudes) ? sec.solicitudes : [];

        // Franja de apertura del crédito.
        var origen = c.origen === 'INICIAL' ? 'Primer crédito registrado'
                   : c.origen === 'DETECTADO' ? 'Recarga no registrada (deducida de las cargas)'
                   : 'Recarga aprobada' + (num(c.monto_abonado) ? ', se abonaron ' + dinero(c.monto_abonado) : '');
        var cierre = c.fecha_fin ? 'Cerrado el ' + fmtLargo(c.fecha_fin) : 'Crédito vigente';
        var v = {};
        v[COL.fecha] = { v: serialFecha(c.fecha_inicio), z: FMT.fecha };
        v[COL.hora] = hora(c.fecha_inicio) !== '00:00' ? hora(c.fecha_inicio) : '';
        v[COL.mov] = 'Crédito en la tarjeta';
        v[COL.detalle] = origen + ' · ' + cierre;
        v[COL.saldo] = { v: num(c.saldo_inicial) || 0, z: FMT.dinero };
        agregarFila(v, estiloFranja);

        // Eventos del ciclo en orden. Las fechas de MySQL ordenan bien como texto.
        var eventos = [];
        recorridos.forEach(function (r) { eventos.push({ f: r.fecha_inicio || r.fecha_fin, tipo: 'viaje', d: r }); });
        cargas.forEach(function (cg) { eventos.push({ f: cg.fecha_carga, tipo: 'carga', d: cg }); });
        solicitudes.forEach(function (so) { eventos.push({ f: so.fecha, tipo: 'corte', d: so }); });
        eventos.sort(function (a, b) { return a.f < b.f ? -1 : (a.f > b.f ? 1 : 0); });

        if (!eventos.length) {
            var vacio = {};
            vacio[COL.detalle] = 'Sin cargas ni recorridos registrados con este crédito.';
            var fv = agregarFila(vacio);
            fv[COL.detalle].s.font.italic = true;
            fv[COL.detalle].s.font.color = { rgb: C.gris };
        }

        eventos.forEach(function (ev) {
            if (ev.tipo === 'carga') return filaCarga(ev.d);
            if (ev.tipo === 'viaje') return filaRecorrido(ev.d);
            var so = ev.d;
            var vc = {};
            vc[COL.fecha] = { v: serialFecha(so.fecha), z: FMT.fecha };
            vc[COL.hora] = hora(so.fecha);
            vc[COL.mov] = 'Corte: solicitud';
            vc[COL.detalle] = 'Solicitud de renovación (' + (ESTATUS[so.estatus] || String(so.estatus).toLowerCase()) + ') · saldo al solicitar';
            vc[COL.saldo] = { v: num(so.saldo_solicitud) || 0, z: FMT.dinero };
            agregarFila(vc, estiloFranja);
        });

        // Saldo con el que terminó: el de la carga más reciente, o el inicial si no hubo.
        var ultima = cargas.slice().sort(function (a, b) {
            return a.fecha_carga < b.fecha_carga ? 1 : (a.fecha_carga > b.fecha_carga ? -1 : b.id - a.id);
        })[0];
        filaTotales(cargas, recorridos, Number(sec.total_gastado) || 0,
                    ultima ? Number(ultima.saldo) : (num(c.saldo_inicial) || 0));
    });

    // Cargas de antes de los ciclos de tarjeta: sin recorridos, no hay crédito que acote.
    var sinCiclo = Array.isArray(resp.sin_ciclo) ? resp.sin_ciclo : [];
    if (sinCiclo.length) {
        var vs = {};
        vs[COL.mov] = 'Cargas anteriores';
        vs[COL.detalle] = 'Antes del control de créditos (sin recorridos)';
        agregarFila(vs, estiloFranja);
        sinCiclo.slice().sort(function (a, b) { return a.fecha_carga < b.fecha_carga ? -1 : (a.fecha_carga > b.fecha_carga ? 1 : a.id - b.id); })
            .forEach(filaCarga);
        filaTotales(sinCiclo, [], sinCiclo.reduce(function (t, cg) { return t + (Number(cg.pagos) || 0); }, 0), null);
    }

    if (!secciones.length && !sinCiclo.length) {
        Swal.fire({ icon: 'info', title: 'Sin información', text: 'Este vehículo no tiene cargas ni créditos registrados.' });
        return;
    }

    // ---- Hoja ----
    var ws = {};
    celdas.forEach(function (fila, r) {
        fila.forEach(function (celda, c) {
            ws[XLSX.utils.encode_cell({ r: r, c: c })] = celda;
        });
    });
    ws['!ref'] = XLSX.utils.encode_range({ s: { r: 0, c: 0 }, e: { r: celdas.length - 1, c: N - 1 } });
    ws['!cols'] = ANCHOS.map(function (w) { return { wch: w }; });
    ws['!merges'] = merges;
    ws['!rows'] = [];
    ws['!rows'][0] = { hpt: 22 };
    ws['!rows'][3] = { hpt: 26 };
    ws['!rows'][filaEncabezado] = { hpt: 28 };
    // Filtro sobre el encabezado: permite quedarse solo con cargas o solo con recorridos.
    ws['!autofilter'] = { ref: XLSX.utils.encode_range({ s: { r: filaEncabezado, c: 0 }, e: { r: celdas.length - 1, c: N - 1 } }) };

    var wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Reporte de gasolina');

    var placa = veh.placa ? String(veh.placa).replace(/[^A-Za-z0-9_-]/g, '') : ('veh' + idVehSel);
    var hoy = String(resp.generado || '').split(' ')[0].replace(/-/g, '');
    XLSX.writeFile(wb, 'Reporte_gasolina_' + placa + (hoy ? '_' + hoy : '') + '.xlsx');
}

function escHtml(str) {
    if (str == null) return '';
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

function getCookie(name) {
    var v = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
    return v ? decodeURIComponent(v[2]) : '';
}
</script>
</body>
</html>
