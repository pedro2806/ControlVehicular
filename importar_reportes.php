<?php
if (empty($_COOKIE['noEmpleado'])) {
    echo '<script>window.location.assign("index")</script>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Control Vehicular - Importar Reportes SCOT</title>

    <!-- MESS Design System -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="css/mess-ds.css" rel="stylesheet">

    <style>
        .file-drop {
            border: 2px dashed #adb5bd;
            border-radius: 10px;
            padding: 18px;
            text-align: center;
            cursor: pointer;
            background: var(--card-soft);
            transition: background .15s ease, border-color .15s ease;
            min-height: 130px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .file-drop:hover { background: var(--accent-soft); border-color: var(--text-muted); }
        .file-drop.is-loaded { border-color: #198754; background: #f3fbf6; }
        .file-drop input[type="file"] { display: none; }
        .file-name { font-size: 0.85rem; color: var(--text-muted); word-break: break-all; }
        .resumen-tabla td { padding: .35rem .5rem; }
    </style>
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include 'menu.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'encabezado.php'; ?>

                <div class="container-fluid pb-4" style="max-width: 820px;">

                    <!-- Bloqueo por acceso -->
                    <div id="loadingAcceso" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="mt-2 text-muted">Verificando acceso...</div>
                    </div>

                    <div id="sinAcceso" class="alert alert-danger" style="display:none;">
                        <i class="fas fa-lock me-2"></i>
                        No tienes permisos para cargar reportes SCOT. Solicita el acceso especial <strong>cargarReportes</strong>.
                    </div>

                    <!-- Contenido principal -->
                    <div id="vistaImportar" style="display:none;">

                        <div class="card shadow mb-3">
                            <div class="card-header bg-white py-2 d-flex align-items-center">
                                <i class="fas fa-file-csv text-primary me-2"></i>
                                <h6 class="m-0 fw-bold text-dark">Importar reportes SCOT</h6>
                            </div>
                            <div class="card-body">
                                <form id="formImportar" enctype="multipart/form-data">
                                    <p class="text-muted mb-4"> El sistema importa OV/OT, geocodifica clientes nuevos y, si subes el archivo de Actividades, las carga para calcular los KM al hacer check-in por QR.</p>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold small mb-1">Ventas</label>
                                            <label class="file-drop" id="dropVentas" for="csvVentas">
                                                <i class="fas fa-file-csv fa-2x text-muted mb-2 d-block"></i>
                                                <span class="file-name" id="nameVentas">VENTAS_*.csv</span>
                                                <input type="file" id="csvVentas" name="ventas" accept=".csv">
                                            </label>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold small mb-1">Detalle - Tiempo Sitio</label>
                                            <label class="file-drop" id="dropTiempoSitio" for="csvTiempoSitio">
                                                <i class="fas fa-file-csv fa-2x text-muted mb-2 d-block"></i>
                                                <span class="file-name" id="nameTiempoSitio">DETALLE-TIEMPO-SITIO_*.csv</span>
                                                <input type="file" id="csvTiempoSitio" name="tiempo_sitio" accept=".csv">
                                            </label>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold small mb-1">Detalle - Sin Tiempo</label>
                                            <label class="file-drop" id="dropSinTiempo" for="csvSinTiempo">
                                                <i class="fas fa-file-csv fa-2x text-muted mb-2 d-block"></i>
                                                <span class="file-name" id="nameSinTiempo">DETALLE-SIN-TIEMPO_*.csv</span>
                                                <input type="file" id="csvSinTiempo" name="sin_tiempo" accept=".csv">
                                            </label>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label fw-semibold small mb-1">Actividades</label>
                                            <label class="file-drop" id="dropOtros" for="csvOtros">
                                                <i class="fas fa-file-csv fa-2x text-muted mb-2 d-block"></i>
                                                <span class="file-name" id="nameOtros">FactActividades_*.csv</span>
                                                <input type="file" id="csvOtros" name="otros" accept=".csv">
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-check mt-3">
                                        <input class="form-check-input" type="checkbox" id="chkGeocodificar" checked>
                                        <label class="form-check-label small" for="chkGeocodificar">
                                            Geocodificar clientes nuevos despues de importar (puede tardar varios minutos)
                                        </label>
                                    </div>

                                    <div class="d-flex justify-content-end mt-4">
                                        <button type="submit" class="btn btn-primary fw-semibold px-4" id="btnImportar">
                                            <i class="fas fa-cloud-upload-alt me-1"></i> Importar
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Resultado -->
                        <div class="card shadow mb-3" id="cardResultado" style="display:none;">
                            <div class="card-header bg-white py-2">
                                <h6 class="m-0 fw-bold text-dark">Resultado</h6>
                            </div>
                            <div class="card-body" id="resultadoBody"></div>
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function () {
            verificarAcceso();
            inicializarDropZones();

            $('#formImportar').on('submit', function (e) {
                e.preventDefault();
                ejecutarImportacion();
            });
        });

        function verificarAcceso() {
            $.ajax({
                url: 'acciones_importar.php',
                method: 'POST',
                dataType: 'json',
                data: { accion: 'verificarAcceso' },
                success: function (resp) {
                    $('#loadingAcceso').hide();
                    if (resp.tieneAcceso) {
                        $('#vistaImportar').show();
                    } else {
                        $('#sinAcceso').show();
                    }
                },
                error: function () {
                    $('#loadingAcceso').hide();
                    $('#sinAcceso').show();
                }
            });
        }

        function inicializarDropZones() {
            var mapa = {
                csvVentas: 'nameVentas',
                csvTiempoSitio: 'nameTiempoSitio',
                csvSinTiempo: 'nameSinTiempo',
                csvOtros: 'nameOtros'
            };
            Object.keys(mapa).forEach(function (inputId) {
                document.getElementById(inputId).addEventListener('change', function () {
                    var f = this.files[0];
                    var dropId = this.parentElement.id;
                    if (f) {
                        $('#' + mapa[inputId]).text(f.name);
                        $('#' + dropId).addClass('is-loaded');
                    } else {
                        $('#' + dropId).removeClass('is-loaded');
                    }
                });
            });
        }

        function ejecutarImportacion() {
            var ventas       = $('#csvVentas')[0].files[0];
            var tiempoSitio  = $('#csvTiempoSitio')[0].files[0];
            var sinTiempo    = $('#csvSinTiempo')[0].files[0];
            var otros        = $('#csvOtros')[0].files[0];

            if (!ventas && !tiempoSitio && !sinTiempo && !otros) {
                Swal.fire({ icon: 'warning', title: 'Falta archivo', text: 'Sube al menos un archivo CSV.', confirmButtonText: 'Aceptar' });
                return;
            }

            var fd = new FormData();
            fd.append('accion', 'ejecutar');
            if (ventas) fd.append('ventas', ventas);
            if (tiempoSitio) fd.append('tiempo_sitio', tiempoSitio);
            if (sinTiempo) fd.append('sin_tiempo', sinTiempo);
            if (otros) fd.append('otros', otros);
            fd.append('geocodificar', $('#chkGeocodificar').is(':checked') ? '1' : '0');

            $('#btnImportar').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Procesando...');
            $('#cardResultado').hide();

            Swal.fire({
                title: 'Procesando archivos',
                html: 'Importando OV/OT y geocodificando clientes nuevos. Esto puede tardar varios minutos, no cierres esta ventana.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: function () { Swal.showLoading(); }
            });

            $.ajax({
                url: 'acciones_importar.php',
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (resp) {
                    Swal.close();
                    if (resp.success) {
                        renderResumen(resp);
                        Swal.fire({ icon: 'success', title: 'Importacion completada', timer: 1800, showConfirmButton: false });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: resp.error || 'Fallo la importacion.', confirmButtonText: 'Aceptar' });
                    }
                },
                error: function (xhr) {
                    Swal.close();
                    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo conectar con el servidor.' + (xhr.responseText ? ' Detalle: ' + xhr.responseText.substring(0, 200) : ''), confirmButtonText: 'Aceptar' });
                },
                complete: function () {
                    $('#btnImportar').prop('disabled', false).html('<i class="fas fa-cloud-upload-alt me-1"></i> Importar');
                }
            });
        }

        function renderResumen(r) {
            var html = '<table class="table table-sm resumen-tabla mb-0">';
            html += '<tbody>';
            html += '<tr><td class="text-muted">OV insertadas</td><td class="fw-semibold">' + (r.ov_ok || 0) + ' <span class="text-muted small">(' + (r.ov_err || 0) + ' errores)</span></td></tr>';
            html += '<tr><td class="text-muted">OT (tiempo sitio)</td><td class="fw-semibold">' + (r.ot_ts_ok || 0) + ' <span class="text-muted small">(' + (r.ot_ts_err || 0) + ' errores)</span></td></tr>';
            html += '<tr><td class="text-muted">OT (sin tiempo)</td><td class="fw-semibold">' + (r.ot_st_ok || 0) + ' <span class="text-muted small">(' + (r.ot_st_err || 0) + ' errores)</span></td></tr>';
            if (r.geo) {
                html += '<tr><td class="text-muted">Clientes geocodificados (exactos)</td><td class="fw-semibold">' + (r.geo.ok || 0) + '</td></tr>';
                html += '<tr><td class="text-muted">Clientes geocodificados (centroide CP)</td><td class="fw-semibold">' + (r.geo.cp || 0) + '</td></tr>';
                html += '<tr><td class="text-muted">Clientes fallidos</td><td class="fw-semibold">' + (r.geo.err || 0) + '</td></tr>';
            }
            if (r.otros) {
                html += '<tr><td class="text-muted">Actividades importadas</td><td class="fw-semibold">' + (r.otros.ok || 0) + '</td></tr>';
                html += '<tr><td class="text-muted">Actividades ignoradas (correo/llamada)</td><td class="fw-semibold">' + (r.otros.ignoradas || 0) + '</td></tr>';
                html += '<tr><td class="text-muted">Actividades duplicadas (omitidas)</td><td class="fw-semibold">' + (r.otros.dup || 0) + '</td></tr>';
            }
            html += '<tr><td class="text-muted">Tiempo total</td><td class="fw-semibold">' + (r.duracion || '?') + ' seg</td></tr>';
            html += '</tbody></table>';
            $('#resultadoBody').html(html);
            $('#cardResultado').show();
        }
    </script>
</body>
</html>
