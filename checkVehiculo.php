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
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Control Vehicular</title>

    <!-- Custom fonts for this template-->
    <link href = "vendor/fontawesome-free/css/all.min.css" rel = "stylesheet" type = "text/css">

    <!-- Custom styles for this template-->
    <link href = "css/sb-admin-2.min.css" rel = "stylesheet">    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

    <style>
        .bg-orange {
            background-color: #da880f !important;
        }
    </style>
</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">
        <?php
        include 'menu.php';
        ?>

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <?php
                include 'encabezado.php';
                ?>

                <!-- Begin Page Content -->
                <div class="container-fluid">                    
                    <div class="row" name="DivVehiculosAsignados" id="DivVehiculosAsignados">
                        <div class="col-xl-12 col-lg-12 col-md-1 col-sm-12 col-12">
                            <table id="TVehiculosAsignados" name="TVehiculosAsignados" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Placa</th>
                                        <th>Modelo</th>
                                        <th>Usuario</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>                                    
                                </tbody>    
                            </table>
                        </div>
                    </div>
                    <div name="DivBtnVehiculosAsignados" id="DivBtnVehiculosAsignados" style="display: none;">
                        <input type="hidden" id="id_coche" name="id_coche">
                        <input type="hidden" name="ruta_foto_Asientos">
                        <input type="hidden" name="ruta_foto_Espejos">
                        <input type="hidden" name="ruta_foto_AireAcondicionado">
                        <input type="hidden" name="ruta_foto_Faros">
                        <input type="hidden" name="ruta_foto_Exterior">
                        <input type="hidden" name="ruta_foto_Graficas">
                        <input type="hidden" name="ruta_foto_Limpiaparabrisas">
                        <input type="hidden" name="ruta_foto_Llantas">
                        <input type="hidden" name="ruta_foto_Placas">
                        <input type="hidden" name="ruta_foto_PuertasLlave">
                        <input type="hidden" name="ruta_foto_tarjetaC">
                        <input type="hidden" name="ruta_foto_Verificacion">
                        <input type="hidden" name="ruta_foto_Licencia">
                        <input type="hidden" name="ruta_foto_TarjetaEfe">
                        <input type="hidden" name="ruta_foto_TarjetaIAVE">

                        <div class="card shadow-sm mb-3">
                            <div class="card-body py-3 px-3">
                                <div class="d-flex align-items-center">
                                    <div id="fotoVehiculoPlaceholder" style="width:80px; height:80px; border-radius:8px; background:#e9ecef; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                        <i class="fas fa-car fa-2x text-muted"></i>
                                    </div>
                                    <img id="fotoVehiculo" style="width:80px; height:80px; object-fit:cover; border-radius:8px; display:none; flex-shrink:0;" onerror="this.style.display='none'; document.getElementById('fotoVehiculoPlaceholder').style.display='';">
                                    <div class="ml-3 flex-grow-1">
                                        <h5 class="mb-0 font-weight-bold text-primary" id="placa" name="placa"></h5>
                                        <span class="text-dark" id="modeloMarca"></span>
                                        <br><small class="text-muted"><span id="color" name="color"></span> · <span id="anioVeh"></span></small>
                                    </div>
                                    <div class="text-right">
                                        <div class="mb-1">
                                            <small class="text-muted">Área</small><br>
                                            <span class="font-weight-bold" id="area" name="area"></span>
                                        </div>
                                        <div>
                                            <small class="text-muted">Usuario</small><br>
                                            <span class="font-weight-bold" id="usuario" name="usuario"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mb-3">
                            <button type="button" class="btn btn-primary btn-sm mr-1" onclick="MostrarDivVehiculosAsignados()"><i class="fas fa-exchange-alt mr-1"></i>Cambiar de vehículo</button>
                            <button type="button" id="btnguardarCheck2" name="btnguardarCheck2" class="btn btn-success btn-sm mr-1" onclick="guardarCheckIn()" style="display:none;">Guardar</button>
                            <button type="button" id="btnGuardarAvance2" name="btnGuardarAvance2" class="btn btn-warning btn-sm" onclick="guardarAvance()">Registrar avance</button>
                        </div>
                        <input type="hidden" id="marca" name="marca">
                        <input type="hidden" id="modelo" name="modelo">
                        <input type="hidden" id="kilometraje" name="kilometraje">

                <!-- CHECKLIST CARRUSEL -->
                <?php
                $pasos = [
                    ['titulo' => 'Asientos/Tapetes',            'id' => 'Asientos',           'grupo' => 'Aspectos Físicos', 'tipo' => 'fisico',          'icono' => 'fa-chair'],
                    ['titulo' => 'Exterior del auto',           'id' => 'Exterior',           'grupo' => 'Aspectos Físicos', 'tipo' => 'fisico',          'icono' => 'fa-car'],
                    ['titulo' => 'Gráficas del auto',           'id' => 'Graficas',           'grupo' => 'Aspectos Físicos', 'tipo' => 'fisico',          'icono' => 'fa-exclamation-triangle'],
                    ['titulo' => 'Faros',                       'id' => 'Faros',              'grupo' => 'Aspectos Físicos', 'tipo' => 'fisico',          'icono' => 'fa-lightbulb'],
                    ['titulo' => 'Placas',                      'id' => 'Placas',             'grupo' => 'Aspectos Físicos', 'tipo' => 'fisico',          'icono' => 'fa-hashtag'],
                    ['titulo' => 'Limpiaparabrisas',            'id' => 'Limpiaparabrisas',   'grupo' => 'Aspectos Físicos', 'tipo' => 'fisico',          'icono' => 'fa-tint'],
                    ['titulo' => 'Espejos',                     'id' => 'Espejos',            'grupo' => 'Aspectos Físicos', 'tipo' => 'fisico',          'icono' => 'fa-eye'],
                    ['titulo' => 'Aire acondicionado/Estéreo',  'id' => 'AireAcondicionado',  'grupo' => 'Aspectos Físicos', 'tipo' => 'fisico_estereo',  'icono' => 'fa-snowflake'],
                    ['titulo' => 'Llantas',                     'id' => 'Llantas',            'grupo' => 'Aspectos Físicos', 'tipo' => 'fisico_llantas',  'icono' => 'fa-circle-notch'],
                    ['titulo' => 'Puertas/Llave',               'id' => 'PuertasLlave',       'grupo' => 'Aspectos Físicos', 'tipo' => 'fisico_puertas',  'icono' => 'fa-key'],
                    ['titulo' => 'Tarjeta de circulación',      'id' => 'tarjetaC',           'grupo' => 'Documentación',    'tipo' => 'doc_simple',      'icono' => 'fa-id-card-alt'],
                    ['titulo' => 'Verificación vigente',        'id' => 'Verificacion',       'grupo' => 'Documentación',    'tipo' => 'doc_vencimiento', 'icono' => 'fa-clipboard-check'],
                    ['titulo' => 'Licencia de manejo',          'id' => 'Licencia',           'grupo' => 'Documentación',    'tipo' => 'doc_vencimiento', 'icono' => 'fa-id-card'],
                    ['titulo' => 'Tarjeta Efecticard',          'id' => 'TarjetaEfe',         'grupo' => 'Documentación',    'tipo' => 'doc_tarjeta',     'icono' => 'fa-credit-card'],
                    ['titulo' => 'Tarjeta IAVE',                'id' => 'TarjetaIAVE',        'grupo' => 'Documentación',    'tipo' => 'doc_tarjeta',     'icono' => 'fa-credit-card'],
                ];
                $totalPasos = count($pasos);
                ?>
                <style>
                    .chk-viewport { overflow: hidden; width: 100%; position: relative; }
                    .chk-track { display: flex; transition: transform 0.3s ease; width: 100%; }
                    .chk-slide { width: 100%; min-width: 100%; flex-shrink: 0; padding: 0; box-sizing: border-box; }
                    .chk-progress { display: flex; gap: 6px; justify-content: center; flex-wrap: wrap; }
                    .chk-dot {
                        width: 30px; height: 30px; border-radius: 50%;
                        background: #dee2e6; color: #6c757d;
                        cursor: pointer; transition: background 0.2s, color 0.2s, transform 0.15s;
                        display: flex; align-items: center; justify-content: center;
                        font-size: 11px; border: 2px solid transparent;
                        flex-shrink: 0;
                    }
                    .chk-dot:hover { transform: scale(1.15); }
                    .chk-dot.active { background: #4e73df; color: #fff; border-color: #2e59d9; }
                    .chk-dot.filled { background: #1cc88a; color: #fff; border-color: #17a673; }
                    .chk-foto-area { display: flex; flex-direction: column; align-items: center; }
                    .chk-foto-preview { max-width: 180px; max-height: 140px; border-radius: 8px; margin-top: 6px; display: none; }
                </style>

                        <div class="text-center mb-2">
                            <div class="chk-progress mb-1" id="chkDots">
                                <?php foreach ($pasos as $i => $p): ?>
                                    <span class="chk-dot<?= $i === 0 ? ' active' : '' ?>"
                                          onclick="irAPaso(<?= $i ?>)"
                                          title="<?= htmlspecialchars($p['titulo']) ?>">
                                        <i class="fas <?= $p['icono'] ?>"></i>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                            <small class="text-muted" id="pasoInfo">Paso 1 de <?= $totalPasos ?></small>
                        </div>

                        <div class="chk-viewport">
                            <div class="chk-track" id="chkTrack">
                            <?php foreach ($pasos as $idx => $p): ?>
                                <div class="chk-slide">
                                    <div class="card shadow-sm">
                                        <div class="card-header py-2 text-center" style="background:#4e73df;">
                                            <small class="text-white-50"><?= $p['grupo'] ?></small>
                                            <h6 class="text-white mb-0 font-weight-bold"><?= $p['titulo'] ?></h6>
                                        </div>
                                        <div class="card-body">
                                            <?php if (strpos($p['tipo'], 'fisico') === 0): ?>
                                                <div class="d-flex justify-content-center mb-3">
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" id="buenEstado_<?= $p['id'] ?>" name="buenEstado_<?= $p['id'] ?>" value="1" style="transform: scale(1.8);">
                                                        <label class="form-check-label ml-2" for="buenEstado_<?= $p['id'] ?>">Buen estado</label>
                                                    </div>
                                                </div>
                                                <?php if ($p['tipo'] === 'fisico_estereo'): ?>
                                                    <div class="row mb-3">
                                                        <div class="col">
                                                            <label for="CE_<?= $p['id'] ?>" class="small">Código estéreo:</label>
                                                            <input type="text" id="CE_<?= $p['id'] ?>" name="CE<?= $p['id'] ?>" class="form-control">
                                                        </div>
                                                    </div>
                                                <?php elseif ($p['tipo'] === 'fisico_llantas'): ?>
                                                    <div class="row mb-3">
                                                        <div class="col-6">
                                                            <label for="medidas_<?= $p['id'] ?>" class="small">Medidas:</label>
                                                            <input type="text" id="medidas_<?= $p['id'] ?>" name="medidas_<?= $p['id'] ?>" class="form-control">
                                                        </div>
                                                        <div class="col-6">
                                                            <label for="CE_<?= $p['id'] ?>" class="small">No. Rin:</label>
                                                            <input type="text" id="CE_<?= $p['id'] ?>" name="CE<?= $p['id'] ?>" class="form-control">
                                                        </div>
                                                    </div>
                                                <?php elseif ($p['tipo'] === 'fisico_puertas'): ?>
                                                    <div class="row mb-3">
                                                        <div class="col">
                                                            <label for="duplicado_<?= $p['id'] ?>" class="small">Duplicado:</label>
                                                            <input type="text" id="duplicado_<?= $p['id'] ?>" name="duplicado_<?= $p['id'] ?>" class="form-control">
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            <?php elseif ($p['tipo'] === 'doc_vencimiento'): ?>
                                                <div class="row mb-3">
                                                    <div class="col">
                                                        <label for="vencimiento_<?= $p['id'] ?>" class="small">Vencimiento:</label>
                                                        <input type="date" id="vencimiento_<?= $p['id'] ?>" name="vencimiento_<?= $p['id'] ?>" class="form-control">
                                                    </div>
                                                </div>
                                            <?php elseif ($p['tipo'] === 'doc_tarjeta'): ?>
                                                <div class="row mb-3">
                                                    <div class="col-6">
                                                        <label for="vencimiento_<?= $p['id'] ?>" class="small">Vencimiento:</label>
                                                        <input type="date" id="vencimiento_<?= $p['id'] ?>" name="vencimiento_<?= $p['id'] ?>" class="form-control">
                                                    </div>
                                                    <div class="col-6">
                                                        <label for="no_tarjeta_<?= $p['id'] ?>" class="small">No. Tarjeta:</label>
                                                        <input type="text" id="no_tarjeta_<?= $p['id'] ?>" name="no_tarjeta_<?= $p['id'] ?>" class="form-control">
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <div class="chk-foto-area mb-3" style="position:relative;">
                                                <div id="captura_foto_<?= $p['id'] ?>" class="foto-captura" onclick="document.getElementById('foto_<?= $p['id'] ?>').click()" style="height:140px;">
                                                    <div class="foto-viewfinder">
                                                        <div id="placeholder_foto_<?= $p['id'] ?>" class="text-center text-muted">
                                                            <i class="fas fa-camera fa-3x mb-2 d-block"></i>
                                                            <span style="font-size:0.82rem;">Tomar foto</span>
                                                        </div>
                                                    </div>
                                                    <span class="corner tl"></span>
                                                    <span class="corner tr"></span>
                                                    <span class="corner bl"></span>
                                                    <span class="corner br"></span>
                                                </div>
                                                <div id="wrap_foto_<?= $p['id'] ?>" style="display:none; position:relative; text-align:center;">
                                                    <img id="preview_foto_<?= $p['id'] ?>" src="" style="max-height:160px; max-width:100%; border-radius:8px; object-fit:cover;">
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="quitarFoto('<?= $p['id'] ?>')" style="position:absolute; top:-8px; right:-8px; border-radius:50%; width:28px; height:28px; padding:0; line-height:28px; font-size:14px; z-index:2;">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                                <input type="file" id="foto_<?= $p['id'] ?>" name="foto_<?= $p['id'] ?>" class="d-none" accept="image/*" capture="camera">
                                            </div>

                                            <div>
                                                <label for="observaciones_<?= $p['id'] ?>" class="small">Observaciones:</label>
                                                <textarea id="observaciones_<?= $p['id'] ?>" name="observaciones_<?= $p['id'] ?>" class="form-control" placeholder="Observaciones" rows="2"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-3 mb-3">
                            <button type="button" id="btnRegresar" class="btn btn-secondary btn-sm" onclick="irAPaso(pasoActual - 1)" style="display:none;">
                                <i class="fas fa-arrow-left mr-1"></i> Regresar
                            </button>
                            <div class="ml-auto">
                                <button type="button" id="btnGuardarAvance" name="btnGuardarAvance" class="btn btn-warning btn-sm mr-1" onclick="guardarAvance()">Guardar avance</button>
                                <button type="button" id="btnguardarCheck" name="btnguardarCheck" class="btn btn-success btn-sm mr-1" onclick="guardarCheckIn()" style="display:none;">Guardar</button>
                                <button type="button" id="btnSiguiente" class="btn btn-primary btn-sm" onclick="irAPaso(pasoActual + 1)">
                                    Siguiente <i class="fas fa-arrow-right ml-1"></i>
                                </button>
                            </div>
                        </div>
                        </div>
                    </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; MESS <?php echo date("Y"); ?></span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
</body>

    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <!-- Removed duplicate Bootstrap script to avoid conflicts -->    
    <!-- Core plugin JavaScript-->
    <script src = "vendor/jquery-easing/jquery.easing.min.js"></script>
    <!-- Custom scripts for all pages-->
    <script src = "js/sb-admin-2.min.js"></script>    
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    
<script type="text/javascript">
                        

                        
    var autoSelectVehiculo = <?php echo intval($_GET['v'] ?? 0); ?>;

    $(document).ready(function () {
        llenaTVehiculosAsignados(); //LLENAR TABLA DE VEHICULOS ASIGNADOS

        $('#TVehiculosAsignados').DataTable({
            destroy: true,
            paging: true,
            pageLength: 10,
            ordering: true,
            searching: true,
            info: true,
            language: {
                decimal: ",",
                thousands: ".",
                processing: "Procesando...",
                loadingRecords: "Cargando...",
                zeroRecords: "No se encontraron resultados",
                emptyTable: "No hay datos disponibles en la tabla",
                search: "Buscar:",
                lengthMenu: "Mostrar _MENU_ registros",
                info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                infoEmpty: "Mostrando 0 registros",
                paginate: {
                    first: "Primero",
                    last: "Último",
                    next: "Siguiente",
                    previous: "Anterior"
                }
            },
            createdRow: function(row, data, dataIndex) {
                $(row).css('font-size', '15px');
            }
        });
        
    });
    function llenaTVehiculosAsignados() {
                    var opcion = "llenaTVehiculosAsignados";                                        
                    var cookieNoEmpleado = getCookie('noEmpleado');                     
                    $.ajax({
                        url: 'AccionesCheckVehiculo.php', 
                        method: 'POST',
                        dataType: 'json', //TIPO DE DATO JSON
                        data:{opcion, cookieNoEmpleado}, 
                        success: function(registros) {
                            
                            var table = $('#TVehiculosAsignados').DataTable();
                            
                            table.clear().draw();                            
                            Array.isArray(registros) && registros.forEach(function(Registro) {
                                var asignado = Registro.asignado || Registro.usuario || '-';
                                table.row.add([
                                    '<i class="fas fa-car fa-1x"></i><b> ' + Registro.placa + ' </b>',
                                    '<b> ' + Registro.modelo + ' </b>',
                                    asignado,
                                    '<div class="text-center"><button type="button" class="btn btn-sm btn-success" onclick=\'SeleccionaVehiculo(' + JSON.stringify(Registro) + ')\'><i class="fas fa-check fa-1x"></i></button></div>'
                                ]).draw(false);
                            });

                            // Si viene desde un QR, auto-seleccionar el vehículo correspondiente
                            if (autoSelectVehiculo) {
                                var match = registros.find(function(r) { return r.idCoche == autoSelectVehiculo; });
                                if (match) SeleccionaVehiculo(match);
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            
                        }
                    });
    }
    function SeleccionaVehiculo(Registro) {
        $('#id_coche').val(Registro.idCoche);
        $('#placa').text(Registro.placa);
        $('#marca').val(Registro.marca);
        $('#modelo').val(Registro.modelo);
        $('#modeloMarca').text([Registro.marca, Registro.modelo].filter(Boolean).join(' '));
        $('#color').text(Registro.color);
        $('#anioVeh').text(Registro.anio || '');
        $('#area').text(Registro.area);
        $('#usuario').text(Registro.usuario || Registro.asignado || '');
        $('#kilometraje').val(Registro.kilometraje);

        var foto = Registro.fotoGeneral || '';
        if (foto) {
            $('#fotoVehiculo').attr('src', foto).show();
            $('#fotoVehiculoPlaceholder').hide();
        } else {
            $('#fotoVehiculo').hide();
            $('#fotoVehiculoPlaceholder').show();
        }

        limpiarRutasFoto();
        OcultaDivVehiculosAsignados();
        verificarBorrador(Registro.idCoche);
        verificarCompletitud();
    }

        function guardarCheckIn() {
            if (!$('#id_coche').val()) {
                Swal.fire('Error', 'Selecciona un vehículo primero.', 'error');
                return;
            }
            let formData = new FormData();

            // Recolectar valores de inputs de texto, date, hidden, y otros
            $('input[type="text"], input[type="date"], input[type="input"], input[type="hidden"]').each(function () {
                formData.append($(this).attr('name'), $(this).val());
            });

            // Recolectar valores de checkboxes
            $('input[type="checkbox"]').each(function () {
                formData.append($(this).attr('name'), $(this).is(':checked') ? 1 : 0);
            });

            // Recolectar valores de selects (si los hubiera)
            $('select').each(function () {
                formData.append($(this).attr('name'), $(this).val());
            });

            // Recolectar valores de labels
            $('label').each(function () {
                if ($(this).attr('name')) {
                    formData.append($(this).attr('name'), $(this).text());
                }
            });

            // Recolectar valores de textareas
            $('textarea').each(function () {
                formData.append($(this).attr('name'), $(this).val());
            });

            // Recolectar archivos tipo file
            $('input[type="file"]').each(function () {
                if ($(this)[0].files.length > 0) {
                    formData.append($(this).attr('name'), $(this)[0].files[0]);
                }
            });

            // Agregar opción al FormData
            formData.append('opcion', 'guardarCheckIn');
            formData.append('estatus', 'completo');

            // Deshabilitar botones para evitar múltiples envíos
            $('#btnguardarCheck').prop('disabled', true);
            $('#btnguardarCheck2').prop('disabled', true);
            $('#btnGuardarAvance').prop('disabled', true);
            $('#btnGuardarAvance2').prop('disabled', true);
            
            // Mostrar mensaje de procesamiento
            Swal.fire({
                title: "Procesando...",
                text: "Se está procesando tu solicitud.",
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Enviar datos mediante AJAX
            $.ajax({
                url: 'AccionesCheckVehiculo.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    Swal.close();
                    if (response.success) {
                        Swal.fire("Éxito", "El check-in se guardó correctamente.", "success");
                        window.location.assign("verifica_checkinVehiculo");
                    } else {
                        Swal.fire("Error", "Hubo un problema al guardar el check-in. Inténtalo nuevamente.", "error");
                        $('#btnguardarCheck, #btnguardarCheck2, #btnGuardarAvance, #btnGuardarAvance2').prop('disabled', false);
                        verificarCompletitud();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Swal.close();
                    Swal.fire("Error", "No se pudo completar la solicitud. Por favor, inténtalo más tarde.", "error");
                    $('#btnguardarCheck, #btnguardarCheck2, #btnGuardarAvance, #btnGuardarAvance2').prop('disabled', false);
                    verificarCompletitud();
                }
            });
            
        }

        function guardarAvance() {
            let formData = new FormData();

            $('input[type="text"], input[type="date"], input[type="input"], input[type="hidden"]').each(function () {
                formData.append($(this).attr('name'), $(this).val());
            });
            $('input[type="checkbox"]').each(function () {
                formData.append($(this).attr('name'), $(this).is(':checked') ? 1 : 0);
            });
            $('select').each(function () {
                formData.append($(this).attr('name'), $(this).val());
            });
            $('label').each(function () {
                if ($(this).attr('name')) {
                    formData.append($(this).attr('name'), $(this).text());
                }
            });
            $('textarea').each(function () {
                formData.append($(this).attr('name'), $(this).val());
            });
            $('input[type="file"]').each(function () {
                if ($(this)[0].files.length > 0) {
                    formData.append($(this).attr('name'), $(this)[0].files[0]);
                }
            });

            formData.append('opcion', 'guardarCheckIn');
            formData.append('estatus', 'borrador');

            $('#btnguardarCheck').prop('disabled', true);
            $('#btnguardarCheck2').prop('disabled', true);
            $('#btnGuardarAvance').prop('disabled', true);
            $('#btnGuardarAvance2').prop('disabled', true);

            Swal.fire({
                title: "Guardando avance...",
                text: "Se está guardando tu progreso.",
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => { Swal.showLoading(); }
            });

            $.ajax({
                url: 'AccionesCheckVehiculo.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    Swal.close();
                    if (response.success) {
                        Swal.fire("Avance guardado", "Tu progreso fue guardado. Puedes continuar el registro más tarde.", "success").then(function() {
                            $('#btnGuardarAvance, #btnGuardarAvance2').prop('disabled', false);
                            verificarCompletitud();
                        });
                    } else {
                        Swal.fire("Error", "Hubo un problema al guardar el avance.", "error");
                        $('#btnGuardarAvance, #btnGuardarAvance2').prop('disabled', false);
                        verificarCompletitud();
                    }
                },
                error: function() {
                    Swal.close();
                    Swal.fire("Error", "No se pudo completar la solicitud.", "error");
                    $('#btnGuardarAvance, #btnGuardarAvance2').prop('disabled', false);
                    verificarCompletitud();
                }
            });
        }

        function limpiarRutasFoto() {
            $('input[name^="ruta_foto_"]').val('');
        }

        function verificarBorrador(id_coche) {
            $.ajax({
                url: 'AccionesCheckVehiculo.php',
                method: 'POST',
                dataType: 'json',
                data: { opcion: 'cargarBorrador', id_coche: id_coche },
                success: function(response) {
                    if (response.found) {
                        Swal.fire({
                            title: 'Avance guardado encontrado',
                            text: 'Existe un registro incompleto para este vehículo. ¿Deseas cargar el avance anterior?',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Sí, cargar',
                            cancelButtonText: 'No, empezar de nuevo'
                        }).then(function(result) {
                            if (result.isConfirmed) {
                                cargarDatosBorrador(response);
                            }
                        });
                    }
                }
            });
        }

        function cargarDatosBorrador(data) {
            function setVal(name, val) {
                var v = (val && val !== 'S/R' && val !== '0000-00-00') ? val : '';
                $('[name="' + name + '"]').val(v);
            }
            function setCheck(name, val) {
                $('input[name="' + name + '"]').prop('checked', val == 1 || val === '1');
            }
            function setRuta(name, val) {
                if (!val) return;
                $('[name="' + name + '"]').val(val);
                var fileInputName = name.replace('ruta_', '');
                var $input = $('input[name="' + fileInputName + '"]');
                if ($input.length) {
                    $input.parent().find('.foto-borrador-preview, [id^="preview_"]').remove();
                    $('<img>')
                        .attr('src', val)
                        .addClass('foto-borrador-preview')
                        .css({ maxHeight: '60px', maxWidth: '80px', marginTop: '4px', borderRadius: '4px', display: 'block' })
                        .on('error', function () { $(this).hide(); })
                        .insertAfter($input);
                }
            }

            if (data.motivo && data.motivo !== 'S/R') $('textarea[name="motivo"]').val(data.motivo);

            if (data.asientos) {
                setCheck('si_no_Asientos', data.asientos.si_no);
                setCheck('buenEstado_Asientos', data.asientos.buen_estado);
                setVal('observaciones_Asientos', data.asientos.observaciones);
                setRuta('ruta_foto_Asientos', data.asientos.foto);
                if (data.asientos.foto === '') {
                    $('#accordion-item-Asientos').removeClass('bg-secondary').addClass('bg-orange');
                }
            }
            if (data.espejos) {
                setCheck('si_no_Espejos', data.espejos.si_no);
                setCheck('buenEstado_Espejos', data.espejos.buen_estado);
                setVal('observaciones_Espejos', data.espejos.observaciones);
                setRuta('ruta_foto_Espejos', data.espejos.foto);
                if (data.espejos.foto === '') {
                    $('#accordion-item-Espejos').removeClass('bg-primary').addClass('bg-warning');
                }
            }
            if (data.estereos) {
                setCheck('si_no_AireAcondicionado', data.estereos.si_no);
                setCheck('buenEstado_AireAcondicionado', data.estereos.buen_estado);
                setVal('observaciones_AireAcondicionado', data.estereos.observaciones);
                setVal('CEAireAcondicionado', data.estereos.cd_estereo);
                setRuta('ruta_foto_AireAcondicionado', data.estereos.foto);
                if (data.estereos.foto === '') {
                    $('#accordion-item-AireAcondicionado').removeClass('bg-secondary').addClass('bg-orange');
                }
            }
            if (data.faros) {
                setCheck('si_no_Faros', data.faros.si_no);
                setCheck('buenEstado_Faros', data.faros.buen_estado);
                setVal('observaciones_Faros', data.faros.observaciones);
                setRuta('ruta_foto_Faros', data.faros.foto);
                if (data.faros.foto === '') {
                    $('#accordion-item-Faros').removeClass('bg-secondary').addClass('bg-orange');
                }
            }
            if (data.golpes) {
                setCheck('si_no_Exterior', data.golpes.si_no);
                setCheck('buenEstado_Exterior', data.golpes.buen_estado);
                setVal('observaciones_Exterior', data.golpes.observaciones);
                setRuta('ruta_foto_Exterior', data.golpes.foto);
                if (data.golpes.foto === '') {
                    $('#accordion-item-Exterior').removeClass('bg-secondary').addClass('bg-orange');
                }
            }
            if (data.graficas) {
                setCheck('si_no_Graficas', data.graficas.si_no);
                setCheck('buenEstado_Graficas', data.graficas.buen_estado);
                setVal('observaciones_Graficas', data.graficas.observaciones);
                setRuta('ruta_foto_Graficas', data.graficas.foto);
                if (data.graficas.foto === '') {
                    $('#accordion-item-Graficas').removeClass('bg-primary').addClass('bg-warning');
                }
            }
            if (data.limpiaparabrisas) {
                setCheck('si_no_Limpiaparabrisas', data.limpiaparabrisas.si_no);
                setCheck('buenEstado_Limpiaparabrisas', data.limpiaparabrisas.buen_estado);
                setVal('observaciones_Limpiaparabrisas', data.limpiaparabrisas.observaciones);
                setRuta('ruta_foto_Limpiaparabrisas', data.limpiaparabrisas.foto);
                if (data.limpiaparabrisas.foto === '') {
                    $('#accordion-item-Limpiaparabrisas').removeClass('bg-secondary').addClass('bg-orange');
                }
            }
            if (data.limpieza) {
                setCheck('si_no_Limpieza', data.limpieza.si_no);
                setCheck('buenEstado_Limpieza', data.limpieza.buen_estado);
                setVal('observaciones_Limpieza', data.limpieza.observaciones);
                setRuta('ruta_foto_Limpieza', data.limpieza.foto);
                if (data.limpieza.foto === '') {
                    $('#accordion-item-Limpieza').removeClass('bg-primary').addClass('bg-warning');
                }
            }
            if (data.llantas) {
                setCheck('buenEstado_Llantas', data.llantas.buen_estado);
                setVal('CELlantas', data.llantas.no_rin);
                setVal('medidas_Llantas', data.llantas.medidas);
                setVal('observaciones_Llantas', data.llantas.observaciones);
                setRuta('ruta_foto_Llantas', data.llantas.foto);
                if (data.llantas.foto === '') {
                    $('#accordion-item-Llantas').removeClass('bg-primary').addClass('bg-warning');
                }
            }
            if (data.placas) {
                setCheck('si_no_Placas', data.placas.si_no);
                setCheck('buenEstado_Placas', data.placas.buen_estado);
                setVal('observaciones_Placas', data.placas.observaciones);
                setRuta('ruta_foto_Placas', data.placas.foto);
                if (data.placas.foto === '') {
                    $('#accordion-item-Placas').removeClass('bg-primary').addClass('bg-warning');
                }
            }
            if (data.puertas) {
                setCheck('buenEstado_PuertasLlave', data.puertas.buen_estado);
                setVal('duplicado_PuertasLlave', data.puertas.duplicado_llaves);
                setVal('observaciones_PuertasLlave', data.puertas.observaciones);
                setRuta('ruta_foto_PuertasLlave', data.puertas.foto);
                if (data.puertas.foto === '') {
                    $('#accordion-item-PuertasLlave').removeClass('bg-secondary').addClass('bg-orange');
                }
            }

            var docs = data.documentacion || {};
            if (docs['Tarjeta de Circulacion']) {
                setCheck('si_no_tarjetaC', docs['Tarjeta de Circulacion'].si_no);
                setVal('observaciones_tarjetaC', docs['Tarjeta de Circulacion'].observaciones);
                setRuta('ruta_foto_tarjetaC', docs['Tarjeta de Circulacion'].foto);
                if (docs['Tarjeta de Circulacion'].foto === '') {
                    $('#accordion-item-tarjetaC').removeClass('bg-secondary').addClass('bg-orange');
                }
            }
            if (docs['Refrendo']) {
                setCheck('si_no_Refrendo', docs['Refrendo'].si_no);
                setVal('observaciones_Refrendo', docs['Refrendo'].observaciones);
                setRuta('ruta_foto_Refrendo', docs['Refrendo'].foto);
                if (docs['Refrendo'].foto === '') {
                    $('#accordion-item-Refrendo').removeClass('bg-black').addClass('bg-warning');
                }
            }
            if (docs['Seguro de Auto']) {
                setCheck('si_no_Seguro', docs['Seguro de Auto'].si_no);
                setVal('vencimiento_Seguro', docs['Seguro de Auto'].vencimiento);
                setVal('no_tarjeta_Seguro', docs['Seguro de Auto'].no_tarjeta);
                setVal('observaciones_Seguro', docs['Seguro de Auto'].observaciones);
                setRuta('ruta_foto_Seguro', docs['Seguro de Auto'].foto);
                if (docs['Seguro de Auto'].foto === '') {
                    $('#accordion-item-Seguro').removeClass('bg-secondary').addClass('bg-orange');
                }
            }
            if (docs['Verificacion']) {
                setCheck('si_no_Verificacion', docs['Verificacion'].si_no);
                setVal('vencimiento_Verificacion', docs['Verificacion'].vencimiento);
                setVal('observaciones_Verificacion', docs['Verificacion'].observaciones);
                setRuta('ruta_foto_Verificacion', docs['Verificacion'].foto);
                if (docs['Verificacion'].foto === '') {
                    $('#accordion-item-Verificacion').removeClass('bg-black').addClass('bg-warning');
                }
            }
            if (docs['Licencia de Manejo']) {
                setCheck('si_no_Licencia', docs['Licencia de Manejo'].si_no);
                setVal('vencimiento_Licencia', docs['Licencia de Manejo'].vencimiento);
                setVal('observaciones_Licencia', docs['Licencia de Manejo'].observaciones);
                setRuta('ruta_foto_Licencia', docs['Licencia de Manejo'].foto);
                if (docs['Licencia de Manejo'].foto === '') {
                    $('#accordion-item-Licencia').removeClass('bg-secondary').addClass('bg-orange');
                }
            }
            if (docs['Tarjeta Efecticard']) {
                setCheck('si_no_TarjetaEfe', docs['Tarjeta Efecticard'].si_no);
                setVal('vencimiento_TarjetaEfe', docs['Tarjeta Efecticard'].vencimiento);
                setVal('no_tarjeta_TarjetaEfe', docs['Tarjeta Efecticard'].no_tarjeta);
                setVal('observaciones_TarjetaEfe', docs['Tarjeta Efecticard'].observaciones);
                setRuta('ruta_foto_TarjetaEfe', docs['Tarjeta Efecticard'].foto);
                if (docs['Tarjeta Efecticard'].foto === '') {
                    $('#accordion-item-TarjetaEfe').removeClass('bg-black').addClass('bg-warning');
                }
            }
            if (docs['Tarjeta IAVE']) {
                setCheck('si_no_TarjetaIAVE', docs['Tarjeta IAVE'].si_no);
                setVal('vencimiento_TarjetaIAVE', docs['Tarjeta IAVE'].vencimiento);
                setVal('no_tarjeta_TarjetaIAVE', docs['Tarjeta IAVE'].no_tarjeta);
                setVal('observaciones_TarjetaIAVE', docs['Tarjeta IAVE'].observaciones);
                setRuta('ruta_foto_TarjetaIAVE', docs['Tarjeta IAVE'].foto);
                if (docs['Tarjeta IAVE'].foto === '') {
                    $('#accordion-item-TarjetaIAVE').removeClass('bg-secondary').addClass('bg-orange');
                }
            }
            verificarCompletitud();
        }

        // ======== CARRUSEL ========
        var pasoActual = 0;
        var totalPasos = <?= $totalPasos ?>;

        function irAPaso(n) {
            if (n < 0 || n >= totalPasos) return;
            pasoActual = n;
            document.getElementById('chkTrack').style.transform = 'translateX(-' + (pasoActual * 100) + '%)';
            document.getElementById('pasoInfo').textContent = 'Paso ' + (pasoActual + 1) + ' de ' + totalPasos;

            var dots = document.querySelectorAll('.chk-dot');
            dots.forEach(function(d, i) {
                d.classList.toggle('active', i === pasoActual);
            });

            document.getElementById('btnRegresar').style.display = pasoActual === 0 ? 'none' : '';

            var btnSig = document.getElementById('btnSiguiente');
            if (pasoActual === totalPasos - 1) {
                btnSig.innerHTML = '<i class="fas fa-check mr-1"></i> Finalizar';
                btnSig.className = 'btn btn-success btn-sm';
                btnSig.onclick = function() { intentarFinalizar(); };
            } else {
                btnSig.innerHTML = 'Siguiente <i class="fas fa-arrow-right ml-1"></i>';
                btnSig.className = 'btn btn-primary btn-sm';
                btnSig.onclick = function() { irAPaso(pasoActual + 1); };
            }
        }

        function marcarDotLleno(idx) {
            var dots = document.querySelectorAll('.chk-dot');
            if (dots[idx]) dots[idx].classList.add('filled');
        }

        function pasoTieneFoto(idx) {
            var inputs = document.querySelectorAll('input[type="file"][name^="foto_"]');
            var input = inputs[idx];
            if (!input) return false;
            if (input.files && input.files.length > 0) return true;
            var rutaInput = document.querySelector('input[name="ruta_' + input.name + '"]');
            return rutaInput && rutaInput.value.trim() !== '';
        }

        function obtenerFaltantes() {
            var faltantes = [];
            var slides = document.querySelectorAll('.chk-slide');
            var inputs = document.querySelectorAll('input[type="file"][name^="foto_"]');
            inputs.forEach(function(input, idx) {
                if (!pasoTieneFoto(idx)) {
                    var header = slides[idx] ? slides[idx].querySelector('.card-header h6') : null;
                    faltantes.push({ idx: idx, nombre: header ? header.textContent : ('Paso ' + (idx + 1)) });
                }
            });
            return faltantes;
        }

        function verificarCompletitud() {
            var inputs = document.querySelectorAll('input[type="file"][name^="foto_"]');
            inputs.forEach(function(input, idx) {
                if (pasoTieneFoto(idx)) marcarDotLleno(idx);
            });
        }

        function intentarFinalizar() {
            var faltantes = obtenerFaltantes();
            if (faltantes.length === 0) {
                guardarCheckIn();
                return;
            }
            var lista = faltantes.map(function(f) { return '• ' + f.nombre; }).join('\n');
            Swal.fire({
                icon: 'warning',
                title: 'Faltan fotos en ' + faltantes.length + ' sección(es)',
                html: '<div class="text-left"><small>' + faltantes.map(function(f) {
                    return '<span class="d-block">• ' + f.nombre + '</span>';
                }).join('') + '</small></div>',
                showCancelButton: true,
                confirmButtonText: 'Ir al primero faltante',
                cancelButtonText: 'Guardar avance'
            }).then(function(result) {
                if (result.isConfirmed) {
                    irAPaso(faltantes[0].idx);
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    guardarAvance();
                }
            });
        }

        $(document).on('input', 'textarea, input[type="text"], input[type="date"]', verificarCompletitud);
        $(document).on('change', 'input[type="checkbox"]', verificarCompletitud);

        function OcultaDivVehiculosAsignados() {
            $('#DivVehiculosAsignados').hide();
            $('#DivBtnVehiculosAsignados').show();
            irAPaso(0);
        }
        function MostrarDivVehiculosAsignados() {
            $('#DivBtnVehiculosAsignados').hide();
            $('#DivVehiculosAsignados').show();
        }

        $(document).on('change', 'input[type="file"][name^="foto_"]', function() {
            var input = this;
            var file = input.files[0];
            if (!file) return;
            var id = input.name.replace('foto_', '');
            var preview = document.getElementById('preview_foto_' + id);
            var captura = document.getElementById('captura_foto_' + id);
            var wrap = document.getElementById('wrap_foto_' + id);
            if (preview && captura && wrap) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    captura.style.display = 'none';
                    wrap.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
            verificarCompletitud();
        });

        function quitarFoto(id) {
            var input = document.getElementById('foto_' + id);
            var captura = document.getElementById('captura_foto_' + id);
            var wrap = document.getElementById('wrap_foto_' + id);
            var preview = document.getElementById('preview_foto_' + id);
            var ruta = document.querySelector('input[name="ruta_foto_' + id + '"]');
            if (input) input.value = '';
            if (ruta) ruta.value = '';
            if (preview) preview.src = '';
            if (wrap) wrap.style.display = 'none';
            if (captura) captura.style.display = '';
            verificarCompletitud();
        }
</script>
</html>
