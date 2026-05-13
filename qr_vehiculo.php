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
                                    <i class="fas fa-calendar-check text-muted fa-fw"></i>
                                    <span class="text-muted small fw-semibold" style="min-width:110px;">Verificación:</span>
                                    <span class="badge px-3 py-2" id="badgeVerif"></span>
                                </div>
                            </div>
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
                                        onclick="validarActividadesPendientes()">
                                        <i class="far fa-check-square"></i>
                                        Check-In / Out
                                    </button>
                                </div>

                                <div class="col-6">
                                    <a href="checkVehiculo.php?v=<?php echo $id_vehiculo; ?>" class="btn btn-outline-info w-100 action-btn">
                                        <i class="fas fa-book"></i>
                                        Check List
                                    </a>
                                </div>

                                <div class="col-6">
                                    <button class="btn btn-outline-warning w-100 action-btn"
                                        id="btnGas">
                                        <i class="fas fa-gas-pump"></i>
                                        Gasolina
                                    </button>
                                </div>

                                <div class="col-6">
                                    <button class="btn btn-outline-danger w-100 action-btn"
                                        data-bs-toggle="modal" data-bs-target="#modalAnomalia">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        Anomalía
                                    </button>
                                </div>

                            </div>
                        </div>
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

    <!-- Modal: Reportar Anomalía -->
    <div class="modal fade" id="modalAnomalia" tabindex="-1" aria-labelledby="modalAnomaliaLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAnomaliaLabel">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>Reportar Anomalía
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Descripción <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="anomaliaDescripcion" rows="3"
                            placeholder="Describe la anomalía encontrada..."></textarea>
                    </div>
                    <div class="mb-1">
                        <label class="form-label fw-semibold">Foto <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="anomaliaFoto"
                            accept="image/*" capture="environment">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="btnGuardarAnomalia" onclick="guardarAnomalia()">
                        <i class="fas fa-paper-plane me-1"></i> Reportar
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
        var vehiculoQR = { id: 0, placa: '', modelo: '' };

        $(document).ready(function () {
            cargarVehiculo();
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
                    $('#cardVehiculo, #cardEstatus, #cardAcciones').show();
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

            // Badges de color / área / responsable
            var badges = '';
            if (v.color)   badges += '<span class="badge bg-light text-dark border"><i class="fas fa-palette me-1"></i>' + escapeHtml(v.color) + '</span>';
            if (v.area)    badges += '<span class="badge bg-light text-dark border"><i class="fas fa-building me-1"></i>' + escapeHtml(v.area) + '</span>';
            if (v.usuario) badges += '<span class="badge bg-light text-dark border"><i class="fas fa-user me-1"></i>' + escapeHtml(v.usuario) + '</span>';
            $('#infoBadges').html(badges);

            // Badge checklist
            var ck = { bg: 'secondary', texto: 'Sin checklist registrado' };
            if (v.estatus_checklist === 'completo') {
                ck = { bg: 'success', texto: 'Checklist OK · ' + formatFecha(v.fecha_checklist) };
            } else if (v.estatus_checklist === 'borrador') {
                ck = { bg: 'warning text-dark', texto: 'Checklist borrador · ' + formatFecha(v.fecha_checklist) };
            }
            $('#badgeChecklist').removeClass().addClass('badge px-3 py-2 bg-' + ck.bg).text(ck.texto);

            // Badge verificación
            var vf = { bg: 'secondary', texto: 'Sin registro de verificación' };
            if (v.fecha_prox) {
                var dias = (new Date(v.fecha_prox) - new Date()) / 86400000;
                var fechaFmt = formatFecha(v.fecha_prox);
                if (dias < 0)        vf = { bg: 'danger',            texto: 'Verificación vencida · ' + fechaFmt };
                else if (dias <= 30) vf = { bg: 'warning text-dark', texto: 'Vence pronto · ' + fechaFmt };
                else                 vf = { bg: 'success',           texto: 'Al corriente · ' + fechaFmt };
            }
            $('#badgeVerif').removeClass().addClass('badge px-3 py-2 bg-' + vf.bg).text(vf.texto);

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
            var p = fechaStr.split('-');
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

        function escapeHtml(str) {
            if (str == null) return '';
            return String(str)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }
    </script>
</body>
</html>
