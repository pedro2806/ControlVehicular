<?php
session_start();

$id_vehiculo = intval($_GET['v'] ?? 0);
if (!$id_vehiculo) {
    header('location: inicio');
    exit;
}

// Si no hay sesión, redirigir al login conservando la URL de retorno
if (empty($_COOKIE['noEmpleado'])) {
    header('location: index?redirect=' . urlencode('qr_vehiculo.php?v=' . $id_vehiculo));
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Control Vehicular - QR Vehículo</title>

    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        .vehicle-photo {
            width: 110px; height: 90px;
            object-fit: cover;
            border-radius: 8px;
        }
        .vehicle-photo-placeholder {
            width: 110px; height: 90px;
            background: #e9ecef;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #adb5bd;
        }
        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 18px 10px;
            border-radius: 12px;
            gap: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            line-height: 1.3;
        }
        .action-btn i { font-size: 1.5rem; }

        .foto-captura {
            background: #dee2e6;
            border-radius: 12px;
            position: relative;
            height: 220px;
            cursor: pointer;
            padding: 14px;
            user-select: none;
        }
        .foto-captura .foto-viewfinder {
            border: 2px dashed #9aa3ad;
            border-radius: 6px;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .foto-captura .corner {
            position: absolute;
            width: 20px;
            height: 20px;
            border-color: #495057;
            border-style: solid;
        }
        .foto-captura .corner.tl { top: 5px; left: 5px;   border-width: 3px 0 0 3px; border-radius: 3px 0 0 0; }
        .foto-captura .corner.tr { top: 5px; right: 5px;  border-width: 3px 3px 0 0; border-radius: 0 3px 0 0; }
        .foto-captura .corner.bl { bottom: 5px; left: 5px;  border-width: 0 0 3px 3px; border-radius: 0 0 0 3px; }
        .foto-captura .corner.br { bottom: 5px; right: 5px; border-width: 0 3px 3px 0; border-radius: 0 0 3px 0; }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include 'menu.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'encabezado.php'; ?>

                <div class="container-fluid pb-4" style="max-width: 720px;">

                    <!-- Spinner de carga inicial -->
                    <div id="loadingQR" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="mt-2 text-muted">Cargando información...</div>
                    </div>

                    <!-- Tarjeta del vehículo (se llena por JS) -->
                    <div class="card shadow mb-3" id="cardVehiculo" style="display:none;">
                        <div class="card-body">
                            <div class="d-flex gap-3 align-items-center">
                                <div id="fotoVehiculo" class="flex-shrink-0">
                                    <div class="vehicle-photo-placeholder">
                                        <i class="fas fa-car fa-2x"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <h4 class="fw-bold mb-0 text-truncate" id="infoPlaca"></h4>
                                    <div class="text-muted" style="font-size: 0.95rem;" id="infoMarcaModelo"></div>
                                    <div class="d-flex flex-wrap gap-1 mt-2" id="infoBadges"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Disclaimer checklist incompleto -->
                    <div class="alert alert-warning d-flex gap-3 align-items-start mb-3" id="disclaimerChecklist" style="display:none !important;">
                        <i class="fas fa-exclamation-triangle fa-lg mt-1 flex-shrink-0"></i>
                        <div class="flex-grow-1">
                            <div class="fw-semibold mb-1">Checklist incompleto</div>
                            <div style="font-size:0.875rem;">El checklist de este vehículo está incompleto. Cualquier imperfecto no reportado previamente podrá asumirse como responsabilidad del último usuario que lo utilizó.</div>
                            <a id="btnIrChecklist" href="#" class="btn btn-warning btn-sm fw-semibold mt-2">
                                <i class="fas fa-clipboard-check me-1"></i> Llenar Checklist
                            </a>
                        </div>
                    </div>

                    <!-- Acciones rápidas -->
                    <div class="card shadow mb-3" id="cardAcciones" style="display:none;">
                        <div class="card-header bg-white py-2">
                            <h6 class="m-0 fw-bold text-dark">Acciones rápidas</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">

                                <div class="col-6">
                                    <button class="btn btn-outline-primary w-100 action-btn"
                                        id="btnCheckinKM" data-bs-toggle="modal" data-bs-target="#modalCheckinKM">
                                        <i class="far fa-check-square"></i>
                                        Check-In / Out
                                    </button>
                                </div>

                                <div class="col-6">
                                    <button class="btn btn-outline-warning w-100 action-btn"
                                        id="btnGas">
                                        <i class="fas fa-gas-pump"></i>
                                        Gasolina
                                    </button>
                                </div>

                                <div class="col-12">
                                    <button class="btn btn-outline-danger w-100 action-btn"
                                        data-bs-toggle="modal" data-bs-target="#modalAnomalia">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Anomalía
                                    </button>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Estatus (se llena por JS) -->
                    <div class="card shadow mb-3" id="cardEstatus" style="display:none;">
                        <div class="card-body py-3">
                            <div class="d-flex flex-column gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-clipboard-check text-muted fa-fw"></i>
                                    <span class="text-muted small fw-semibold" style="min-width:110px;">Check List:</span>
                                    <span class="badge px-3 py-2" id="badgeChecklist"></span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-wrench text-muted fa-fw"></i>
                                    <span class="text-muted small fw-semibold" style="min-width:110px;">Mantenimiento:</span>
                                    <span class="badge px-3 py-2" id="badgeMant"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Documentación (se llena por JS) -->
                    <div class="card shadow mb-3" id="cardDocumentacion" style="display:none;">
                        <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between">
                            <h6 class="m-0 fw-bold text-dark">Documentación</h6>
                            <a id="btnIrDocumentacion" href="documentacion" class="btn btn-warning btn-sm fw-semibold" style="display:none;">
                                <i class="fas fa-folder-open me-1"></i> Actualizar Docs
                            </a>
                        </div>
                        <div class="card-body py-2" id="listaDocumentacion"></div>
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

    <!-- Modal: Check-In / Out + KM (combinado) -->
    <div class="modal fade" id="modalCheckinKM" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-1">
                    <h5 class="modal-title fw-bold" id="modalCheckinKMLabel">Check-In / Out</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body pt-2">
                    <p class="text-muted mb-3" id="checkinMensaje">Verificando estado...</p>

                    <label class="form-label fw-semibold mb-1">KM actual: <span class="text-danger">*</span></label>
                    <input type="number" class="form-control mb-3" id="checkinKM" min="0" placeholder="Ej. 45320">

                    <label class="form-label fw-semibold mb-1">OT / OV:</label>
                    <input type="text" class="form-control mb-3" id="checkinOT" placeholder="Número de OT u OV">

                    <div id="checkinFotoSection" style="display:none;">
                        <div class="foto-captura" onclick="document.getElementById('checkinFoto').click()">
                            <div class="foto-viewfinder">
                                <div id="checkinFotoPlaceholder" class="text-center text-muted">
                                    <i class="fas fa-camera fa-3x mb-2 d-block"></i>
                                    <span style="font-size:0.82rem;">Foto del KM</span>
                                </div>
                                <img id="checkinFotoPreview" src="" alt=""
                                    style="display:none; width:100%; height:100%; object-fit:cover; border-radius:4px;">
                            </div>
                            <span class="corner tl"></span>
                            <span class="corner tr"></span>
                            <span class="corner bl"></span>
                            <span class="corner br"></span>
                        </div>
                        <input type="file" id="checkinFoto" accept="image/*" capture="environment" style="display:none;">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-1">
                    <button type="button" class="btn btn-primary fw-semibold px-4"
                        id="btnGuardarCheckinKM" disabled>
                        Verificando...
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Reportar Anomalía -->
    <div class="modal fade" id="modalAnomalia" tabindex="-1" aria-labelledby="modalAnomaliaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-1">
                    <h5 class="modal-title fw-bold" id="modalAnomaliaLabel">Reporte Anomalía</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body pt-2">
                    <!-- Área de captura de foto -->
                    <div class="foto-captura mb-3" id="fotoCaptura"
                        onclick="document.getElementById('anomaliaFoto').click()">
                        <div class="foto-viewfinder">
                            <div id="fotoPlaceholder" class="text-center text-muted">
                                <i class="fas fa-camera fa-3x mb-2 d-block"></i>
                                <span style="font-size:0.82rem;">Toca para capturar</span>
                            </div>
                            <img id="fotoPreviewImg" src="" alt=""
                                style="display:none; width:100%; height:100%; object-fit:cover; border-radius:4px;">
                        </div>
                        <span class="corner tl"></span>
                        <span class="corner tr"></span>
                        <span class="corner bl"></span>
                        <span class="corner br"></span>
                    </div>
                    <input type="file" id="anomaliaFoto" accept="image/*" capture="environment" style="display:none;">

                    <!-- Detalles -->
                    <label class="form-label fw-semibold mb-1">Detalles:</label>
                    <textarea class="form-control" id="anomaliaDescripcion" rows="3"
                        placeholder="Describe la anomalía encontrada..."></textarea>
                </div>
                <div class="modal-footer border-0 pt-1">
                    <button type="button" class="btn btn-primary fw-semibold px-4"
                        id="btnGuardarAnomalia" onclick="guardarAnomalia()">
                        Registrar Anomalía
                    </button>
                </div>
            </div>
        </div>
    </div>

    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        var idVehiculo = <?php echo $id_vehiculo; ?>;
        var idUsuarioActual = <?php echo intval($_COOKIE['id_usuarioL'] ?? 0); ?>;
        var vehiculoQR = { id: 0, placa: '', modelo: '' };
        var checkinEstado = { tieneCheckinActivo: false };
        var ultimoKMVehiculo = 0;

        $(document).ready(function () {
            cargarVehiculo();

            // Preview de foto al seleccionar
            document.getElementById('anomaliaFoto').addEventListener('change', function () {
                var file = this.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        $('#fotoPreviewImg').attr('src', e.target.result).show();
                        $('#fotoPlaceholder').hide();
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Modal checkin/km: cargar estado al abrirse
            document.getElementById('modalCheckinKM').addEventListener('show.bs.modal', function () {
                $('#modalCheckinKMLabel').text('Check-In / Out');
                $('#checkinMensaje').text('Verificando estado...');
                $('#btnGuardarCheckinKM').prop('disabled', true).text('Verificando...');

                $.ajax({
                    url: 'acciones_qr.php',
                    method: 'POST',
                    dataType: 'json',
                    data: { accion: 'verificarEstadoCheckin', id_vehiculo: idVehiculo },
                    success: function (resp) {
                        checkinEstado = resp;
                        var esCheckout = resp.tieneCheckinActivo;
                        $('#modalCheckinKMLabel').text(esCheckout ? 'Check-Out' : 'Check-In');
                        $('#checkinMensaje').text(
                            esCheckout
                                ? 'Ya tienes una entrada activa para este vehículo. ¿Registras tu salida?'
                                : '¿Confirmas tu entrada para este vehículo?'
                        );
                        $('#checkinFotoSection').toggle(resp.primerKMDeLaSemana === true);
                        $('#btnGuardarCheckinKM')
                            .prop('disabled', false)
                            .text(esCheckout ? 'Registrar Salida' : 'Registrar Entrada');
                    },
                    error: function () {
                        $('#checkinMensaje').text('Error al verificar el estado. Intenta de nuevo.');
                    }
                });

                // Cargar ultimo KM registrado: pre-llenar el campo y usarlo como minimo
                $.ajax({
                    url: 'acciones_qr.php',
                    method: 'POST',
                    dataType: 'json',
                    data: { accion: 'obtenerUltimoKM', id_vehiculo: idVehiculo },
                    success: function (resp) {
                        ultimoKMVehiculo = parseInt(resp.ultimoKM) || 0;
                        if (ultimoKMVehiculo > 0) {
                            $('#checkinKM').attr('min', ultimoKMVehiculo).val(ultimoKMVehiculo);
                        } else {
                            $('#checkinKM').attr('min', 0);
                        }
                    }
                });
            });

            // FileReader para foto de check-in
            document.getElementById('checkinFoto').addEventListener('change', function () {
                var file = this.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        $('#checkinFotoPreview').attr('src', e.target.result).show();
                        $('#checkinFotoPlaceholder').hide();
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Reset del modal checkin al cerrarse
            document.getElementById('modalCheckinKM').addEventListener('hidden.bs.modal', function () {
                $('#checkinFotoPreview').hide().attr('src', '');
                $('#checkinFotoPlaceholder').show();
                $('#checkinKM').val('').removeAttr('min');
                $('#checkinOT').val('');
                $('#checkinFoto').val('');
                ultimoKMVehiculo = 0;
            });

            // Botón guardar checkin
            document.getElementById('btnGuardarCheckinKM').addEventListener('click', guardarCheckinKM);

            // Placeholder dinámico OT/OV — efecto typewriter
            (function () {
                var ejemplos = ['MT26-01E-34', 'MESS-OV-3434-2026'];
                var idx = 0, pos = 0, borrando = false;
                var input = document.getElementById('checkinOT');
                function tick() {
                    var texto = ejemplos[idx];
                    if (!borrando) {
                        pos++;
                        input.placeholder = texto.slice(0, pos);
                        if (pos === texto.length) { borrando = true; setTimeout(tick, 1800); return; }
                        setTimeout(tick, 100);
                    } else {
                        pos--;
                        input.placeholder = texto.slice(0, pos);
                        if (pos === 0) { borrando = false; idx = (idx + 1) % ejemplos.length; setTimeout(tick, 400); return; }
                        setTimeout(tick, 55);
                    }
                }
                tick();
            })();

            // Reset del modal al cerrarse (dismiss sin enviar)
            document.getElementById('modalAnomalia').addEventListener('hidden.bs.modal', function () {
                $('#fotoPreviewImg').hide().attr('src', '');
                $('#fotoPlaceholder').show();
                $('#anomaliaDescripcion').val('');
                $('#anomaliaFoto').val('');
            });
        });

        function cargarVehiculo() {
            $.ajax({
                url: 'acciones_qr.php',
                method: 'POST',
                dataType: 'json',
                data: { accion: 'obtenerDatosVehiculo', id_vehiculo: idVehiculo },
                success: function (v) {
                    if (v.error) {
                        $('#loadingQR').html('<div class="alert alert-danger">' + escapeHtml(v.error) + '</div>');
                        return;
                    }
                    renderVehiculo(v);
                    $('#loadingQR').hide();
                    $('#cardVehiculo, #cardAcciones, #cardEstatus, #cardDocumentacion').show();

                    // Registrar préstamo automático si el vehículo no es del usuario
                    $.post('acciones_qr.php', { accion: 'registrarPrestamoQR', id_vehiculo: idVehiculo });
                },
                error: function () {
                    $('#loadingQR').html('<div class="alert alert-danger">No se pudo cargar la información del vehículo.</div>');
                }
            });
        }

        function renderVehiculo(v) {
            // Foto o placeholder
            if (v.foto_general) {
                var img = new Image();
                img.className = 'vehicle-photo';
                img.alt = 'Foto';
                img.onerror = function () {
                    $('#fotoVehiculo').html('<div class="vehicle-photo-placeholder"><i class="fas fa-car fa-2x"></i></div>');
                };
                img.src = escapeHtml(v.foto_general);
                $('#fotoVehiculo').empty().append(img);
            }

            // Info principal
            $('#infoPlaca').text(v.placa || '');
            $('#infoMarcaModelo').text(
                (v.marca || '') + ' ' + (v.modelo || '') + ' · ' + (v.anio || '')
            );

            // Badges: área, usuario y verificación (color se omite para no saturar)
            var badges = '';
            if (v.area)    badges += '<span class="badge bg-light text-dark border"><i class="fas fa-building me-1"></i>' + escapeHtml(v.area) + '</span>';
            if (v.usuario) badges += '<span class="badge bg-light text-dark border"><i class="fas fa-user me-1"></i>' + escapeHtml(v.usuario) + '</span>';
            if (v.fecha_prox) {
                var diasVerif = (new Date(v.fecha_prox) - new Date()) / 86400000;
                if (diasVerif < 0)
                    badges += '<span class="badge bg-danger"><i class="fas fa-calendar-times me-1"></i>Verificación · Vencida</span>';
                else
                    badges += '<span class="badge bg-success"><i class="fas fa-calendar-check me-1"></i>Verificación · Vigente</span>';
            } else {
                badges += '<span class="badge bg-secondary">Verificación · Sin registro</span>';
            }
            $('#infoBadges').html(badges);

            // Badge checklist
            var ck = { bg: 'secondary', texto: 'Sin registro' };
            if (v.estatus_checklist === 'completo') {
                ck = { bg: 'success', texto: 'Completo · ' + formatFecha(v.fecha_checklist) };
            } else if (v.estatus_checklist === 'borrador') {
                ck = { bg: 'warning text-dark', texto: 'Borrador · ' + formatFecha(v.fecha_checklist) };
            }
            $('#badgeChecklist').removeClass().addClass('badge px-3 py-2 bg-' + ck.bg).text(ck.texto);

            // Determinar si el usuario actual es dueño o asignado del vehículo
            var esAsignado = (v.id_usuario == idUsuarioActual || v.id_us_asignado == idUsuarioActual);

            // Disclaimer: visible para todos cuando el checklist no está completo
            // El botón de llenar checklist solo aparece al usuario asignado
            if (v.estatus_checklist !== 'completo') {
                $('#disclaimerChecklist').css('display', 'flex');
                if (esAsignado) {
                    $('#btnIrChecklist').attr('href', 'checkVehiculo?v=' + v.id_vehiculo).show();
                } else {
                    $('#btnIrChecklist').hide();
                }
            } else {
                $('#disclaimerChecklist').hide();
            }

            // Badge mantenimiento
            var mt = { bg: 'secondary', texto: 'Sin registros' };
            if (v.estatus_mant) {
                if (v.estatus_mant === 'PENDIENTE')
                    mt = { bg: 'warning text-dark', texto: 'Pendiente · ' + formatFecha(v.fecha_mant) };
                else if (v.estatus_mant === 'AUTORIZADO')
                    mt = { bg: 'info text-dark', texto: 'Autorizado · ' + formatFecha(v.fecha_mant) };
                else if (v.estatus_mant === 'DENEGADO')
                    mt = { bg: 'secondary', texto: 'Denegado · ' + formatFecha(v.fecha_mant) };
                else
                    mt = { bg: 'secondary', texto: v.estatus_mant + ' · ' + formatFecha(v.fecha_mant) };
            }
            $('#badgeMant').removeClass().addClass('badge px-3 py-2 bg-' + mt.bg).text(mt.texto);

            // Lista documentación
            var nombreDoc = {
                licencia:             'Licencia',
                tarjeta_circulacion:  'T. Circulación',
                refrendo_actual:      'Refrendo',
                seguro_vehiculo:      'Seguro',
                verificacion_vigente: 'Verificación'
            };
            var listaHtml = '';
            var faltanDocs = false;
            if (!v.fecha_reg_doc) {
                listaHtml = '<p class="text-muted small mb-0">Sin documentación registrada</p>';
                faltanDocs = true;
            } else {
                Object.keys(nombreDoc).forEach(function (c) {
                    var tiene = v[c] && v[c] !== 'S/R';
                    if (!tiene) faltanDocs = true;
                    listaHtml += '<div class="d-flex align-items-center gap-2 py-1 border-bottom">'
                        + '<i class="fas fa-' + (tiene ? 'check-circle text-success' : 'times-circle text-danger') + ' fa-fw"></i>'
                        + '<span class="small">' + nombreDoc[c] + '</span>'
                        + '</div>';
                });
            }
            $('#listaDocumentacion').html(listaHtml);
            $('#btnIrDocumentacion').attr('href', 'documentacion?v=' + v.id_vehiculo).toggle(faltanDocs && esAsignado);

            // Guardar datos del vehículo para pre-selección en modales
            vehiculoQR = { id: v.id_vehiculo, placa: v.placa, modelo: v.modelo };

            // Botón gasolina: pre-selecciona este vehículo al abrir el modal
            $('#btnGas').on('click', function () { abrirGasModal(vehiculoQR.id, vehiculoQR.placa, vehiculoQR.modelo); });
        }

        // Abre el modal de gasolina (definido en encabezado.php) con el vehículo pre-seleccionado
        function abrirGasModal(idVeh, placa, modelo) {
            $.ajax({
                url: 'acciones_kilometraje.php',
                method: 'POST',
                dataType: 'json',
                data: { accion: 'CargarVehiculos' },
                success: function (data) {
                    var lista = Array.isArray(data) ? data : [];
                    // Garantizar que el vehículo escaneado siempre aparezca en la lista
                    var enLista = lista.some(function (item) { return item.id_vehiculo == idVeh; });
                    if (!enLista) {
                        lista.unshift({ id_vehiculo: idVeh, placa: placa, modelo: modelo });
                    }
                    var select = $('#vehiculoAsignadoGas');
                    select.empty().append('<option value="">Seleccione un vehículo</option>');
                    lista.forEach(function (item) {
                        select.append($('<option>', { value: item.id_vehiculo, text: item.placa + ' - ' + item.modelo }));
                    });
                    select.val(idVeh);
                    $('#capturaGasModal').modal('show');
                },
                error: function () {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar los vehículos.', confirmButtonText: 'Aceptar' });
                }
            });
        }

        function formatFecha(fechaStr) {
            if (!fechaStr) return '';
            var dateOnly = String(fechaStr).split(' ')[0];
            var p = dateOnly.split('-');
            return p.length === 3 ? p[2] + '/' + p[1] + '/' + p[0] : fechaStr;
        }

        function guardarAnomalia() {
            var descripcion = $('#anomaliaDescripcion').val().trim();
            var foto = $('#anomaliaFoto')[0].files[0];

            if (!descripcion) {
                Swal.fire({ icon: 'warning', title: 'Requerido', text: 'Escribe una descripción de la anomalía.', confirmButtonText: 'Aceptar' });
                return;
            }
            if (!foto) {
                Swal.fire({ icon: 'warning', title: 'Requerido', text: 'Adjunta una foto de la anomalía.', confirmButtonText: 'Aceptar' });
                return;
            }

            var formData = new FormData();
            formData.append('accion', 'registrarAnomalia');
            formData.append('id_vehiculo', idVehiculo);
            formData.append('descripcion', descripcion);
            formData.append('foto', foto);

            $('#btnGuardarAnomalia').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Guardando...');

            $.ajax({
                url: 'acciones_anomalias.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (resp) {
                    if (resp.success) {
                        $('#anomaliaDescripcion').val('');
                        $('#anomaliaFoto').val('');
                        $('#fotoPreviewImg').hide().attr('src', '');
                        $('#fotoPlaceholder').show();
                        // Cerrar modal y limpiar backdrops residuales (conflicto BS4+BS5 en la misma página)
                        var modalEl = document.getElementById('modalAnomalia');
                        modalEl.addEventListener('hidden.bs.modal', function () {
                            document.querySelectorAll('.modal-backdrop').forEach(function (el) { el.remove(); });
                            document.body.classList.remove('modal-open');
                            document.body.style.overflow = '';
                            document.body.style.paddingRight = '';
                            Swal.fire({ icon: 'success', title: 'Reportado', text: 'Anomalía registrada correctamente.', timer: 2000, showConfirmButton: false });
                        }, { once: true });
                        bootstrap.Modal.getInstance(modalEl).hide();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: resp.error || 'No se pudo guardar la anomalía.', confirmButtonText: 'Aceptar' });
                    }
                },
                error: function () {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo conectar con el servidor.', confirmButtonText: 'Aceptar' });
                },
                complete: function () {
                    $('#btnGuardarAnomalia').prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> Reportar');
                }
            });
        }

        // Sobreescribe cargarVehiculos de encabezado.php para garantizar que el vehículo del QR
        // siempre aparezca en la lista y quede pre-seleccionado, incluso si no está asignado al usuario.
        function cargarVehiculos(selectVehiculo) {
            $.ajax({
                url: 'acciones_kilometraje.php',
                method: 'POST',
                dataType: 'json',
                data: { accion: 'CargarVehiculos' },
                success: function (data) {
                    var lista = Array.isArray(data) ? data : [];

                    // Garantizar que el vehículo del QR esté en la lista
                    var enLista = lista.some(function (v) { return v.id_vehiculo == vehiculoQR.id; });
                    if (!enLista && vehiculoQR.id) {
                        lista.unshift({ id_vehiculo: vehiculoQR.id, placa: vehiculoQR.placa, modelo: vehiculoQR.modelo, id_prestamo: null, estatus: null });
                    }

                    var select = $('#' + selectVehiculo);
                    select.empty().append('<option value="">Seleccione un vehículo</option>');
                    lista.forEach(function (vehiculo) {
                        if (vehiculo.id_prestamo) {
                            $('#PidPrestamo').val(vehiculo.id_vehiculo + ',' + vehiculo.id_prestamo);
                            select.append('<option value="' + vehiculo.id_vehiculo + '" style="background-color:#ffeeba;">PRESTAMO - ' + vehiculo.placa + ' - ' + vehiculo.modelo + ' - ' + vehiculo.estatus + '</option>');
                        } else {
                            select.append('<option value="' + vehiculo.id_vehiculo + '">' + vehiculo.placa + ' - ' + vehiculo.modelo + '</option>');
                        }
                    });

                    // Pre-seleccionar el vehículo del QR
                    select.val(vehiculoQR.id);
                    verPlaca(selectVehiculo, 'kmActual');
                },
                error: function () {
                    console.error('Error al cargar los vehículos');
                }
            });
        }

        function guardarCheckinKM() {
            var esCheckout = checkinEstado.tieneCheckinActivo;
            var km   = $('#checkinKM').val().trim();
            var otOv = $('#checkinOT').val().trim();

            if (!km || isNaN(km) || parseInt(km) <= 0) {
                Swal.fire({ icon: 'warning', title: 'Requerido', text: 'Ingresa el KM actual.', confirmButtonText: 'Aceptar' });
                return;
            }

            if (ultimoKMVehiculo > 0 && parseInt(km) < ultimoKMVehiculo) {
                Swal.fire({
                    icon: 'warning',
                    title: 'KM inválido',
                    text: 'El KM ingresado (' + parseInt(km).toLocaleString('es-MX') + ') es menor al último registrado (' + ultimoKMVehiculo.toLocaleString('es-MX') + ').',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            $('#btnGuardarCheckinKM').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Guardando...');

            function enviar(latNum, lngNum) {
                var coordenadas = (latNum != null && lngNum != null) ? (latNum + ',' + lngNum) : '';

                var formData = new FormData();
                formData.append('accion', esCheckout ? 'checkOutQR' : 'checkInQR');
                formData.append('id_vehiculo', idVehiculo);
                formData.append('coordenadas', coordenadas);
                formData.append('km_actual', km);
                formData.append('ot', otOv);
                var foto = $('#checkinFoto')[0].files[0];
                if (foto) formData.append('foto', foto);

                $.ajax({
                    url: 'acciones_qr.php',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function (resp) {
                        if (resp.success) {
                            var modalEl = document.getElementById('modalCheckinKM');
                            var msg = esCheckout ? 'Salida registrada correctamente.' : 'Entrada registrada correctamente.';
                            modalEl.addEventListener('hidden.bs.modal', function () {
                                document.querySelectorAll('.modal-backdrop').forEach(function (el) { el.remove(); });
                                document.body.classList.remove('modal-open');
                                document.body.style.overflow = '';
                                document.body.style.paddingRight = '';
                                Swal.fire({ icon: 'success', title: '¡Listo!', text: msg, timer: 2000, showConfirmButton: false });
                            }, { once: true });
                            bootstrap.Modal.getInstance(modalEl).hide();

                            // Si el usuario capturó OT/OV y tenemos GPS, registramos km en paralelo
                            if (otOv && latNum != null && lngNum != null) {
                                calcularRutaAsync(otOv, latNum, lngNum);
                            }
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: resp.error || 'No se pudo registrar.', confirmButtonText: 'Aceptar' });
                        }
                    },
                    error: function () {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo conectar con el servidor.', confirmButtonText: 'Aceptar' });
                    },
                    complete: function () {
                        $('#btnGuardarCheckinKM').prop('disabled', false).text(esCheckout ? 'Registrar Salida' : 'Registrar Entrada');
                    }
                });
            }

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function (pos) { enviar(pos.coords.latitude, pos.coords.longitude); },
                    function () { enviar(null, null); },
                    { timeout: 5000, maximumAge: 60000 }
                );
            } else {
                enviar(null, null);
            }
        }

        function calcularRutaAsync(otOv, lat, lng) {
            $.ajax({
                url: 'calcular_ruta.php',
                method: 'POST',
                data: { ov_ot: otOv, lat: lat, lng: lng }
            });
        }

        function escapeHtml(str) {
            if (str == null) return '';
            return String(str)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }
    </script>
</body>
</html>
