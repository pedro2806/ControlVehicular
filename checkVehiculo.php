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

    <!-- Custom styles for this template-->
    <!-- MESS Design System -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="css/mess-ds.css" rel="stylesheet">
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
                    <?php $vDesdeQR = intval($_GET['v'] ?? 0); ?>
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                        <h1 class="h3 mb-0 text-black-800">Checklist de Vehículo</h1>
                        <?php if ($vDesdeQR): ?>
                            <!-- Solo cuando se llegó escaneando el QR (?v=). En ese flujo el
                                 usuario viene de qr_vehiculo.php y no tiene menú a la mano. -->
                            <a href="qr_vehiculo.php?v=<?= $vDesdeQR ?>"
                               class="btn btn-outline-secondary btn-sm flex-shrink-0">
                                <i class="fas fa-qrcode me-1"></i> Volver al vehículo
                            </a>
                        <?php endif; ?>
                    </div>
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
                        <!-- La placa se muestra en el <h5 id="placa"> de abajo, pero los
                             recolectores de FormData solo recorren inputs/select/textarea/label:
                             desde que ese dato pasó de <label> a <h5> (commit a6e584b) dejó de
                             viajar al servidor y TODAS las fotos se guardaban en
                             img_control_vehicular//checklist/... sin placa ni en la carpeta ni
                             en el nombre del archivo. Este hidden lo repone; lo llena
                             SeleccionaVehiculo(). -->
                        <input type="hidden" id="placaCheck" name="placa">
                        <input type="hidden" name="ruta_foto_Asientos">
                        <input type="hidden" name="ruta_foto_Espejos">
                        <input type="hidden" name="ruta_foto_AireAcondicionado">
                        <input type="hidden" name="ruta_foto_Faros">
                        <input type="hidden" name="ruta_foto_Exterior">
                        <input type="hidden" name="ruta_foto_Graficas">
                        <input type="hidden" name="ruta_foto_Limpiaparabrisas">
                        <!-- Estos tres existían antes de la reestructura y se perdieron, pero
                             cargarDatosBorrador() sigue llamando a setRuta() para ellos: sin el
                             input, el selector no encontraba nada y la ruta guardada del
                             borrador no se restauraba (fallo mudo). -->
                        <input type="hidden" name="ruta_foto_Limpieza">
                        <input type="hidden" name="ruta_foto_Refrendo">
                        <input type="hidden" name="ruta_foto_Seguro">
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
                                    <div id="fotoVehiculoPlaceholder" style="width:80px; height:80px; border-radius:8px; background:var(--card-soft); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
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
                            <!-- Aquí había un segundo par de botones (btnguardarCheck2 /
                                 btnGuardarAvance2) que repetía los de la navegación del
                                 asistente, con etiquetas distintas para la misma acción
                                 ("Registrar avance" aquí, "Guardar avance" allá). Se dejó un
                                 solo grupo, el de la navegación entre pasos, que ahora vive
                                 arriba del carrusel (justo debajo de los puntitos de avance). -->
                            <button type="button" class="btn btn-primary btn-sm mr-1" onclick="MostrarDivVehiculosAsignados()"><i class="fas fa-exchange-alt mr-1"></i>Cambiar de vehículo</button>
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
                    .chk-dot.filled { background: #1cc88a; color: #fff; border-color: #17a673; }
                    /* Amarillo: el apartado tiene datos capturados pero le falta la foto.
                       Antes solo había verde (con foto) y gris (sin nada), así que un
                       apartado a medio llenar se veía igual que uno sin tocar. */
                    .chk-dot.parcial { background: #f6c23e; color: #6b4c00; border-color: #dda20a; }
                    /* .active va al FINAL y con doble clase a propósito: marca en qué paso
                       estás y debe ganarle al color de estado. Declarada antes, el verde y
                       el amarillo la tapaban y se perdía el "aquí estás". */
                    .chk-dot.active,
                    .chk-dot.active.filled,
                    .chk-dot.active.parcial { background: var(--accent); color: #fff; border-color: var(--accent-dark); }
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

                        <!-- Botones ARRIBA, entre los pasos y la tarjeta del paso actual. Estaban
                             al pie, después de todo el carrusel: en celular había que bajar hasta
                             el final de cada paso para avanzar o guardar. Es el mismo y único
                             grupo de botones, solo cambió de lugar (no se duplicó: el comentario
                             de más arriba explica por qué se eliminó el segundo par que existía). -->
                        <div class="d-flex justify-content-between align-items-center mt-2 mb-3">
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

                        <div class="chk-viewport">
                            <div class="chk-track" id="chkTrack">
                            <?php foreach ($pasos as $idx => $p): ?>
                                <div class="chk-slide">
                                    <div class="card shadow-sm">
                                        <div class="card-header py-2 text-center" style="background:var(--accent);">
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

    <!-- Removed duplicate Bootstrap script to avoid conflicts -->    
    <!-- Core plugin JavaScript-->
    <!-- Custom scripts for all pages-->
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
                                    // Antes era solo un ✔ sin texto: no se entendía que ese
                                    // botón abre el checklist del vehículo.
                                    '<div class="text-center"><button type="button" class="btn btn-sm btn-success" onclick=\'SeleccionaVehiculo(' + JSON.stringify(Registro) + ')\'><i class="fas fa-clipboard-check mr-1"></i>Registrar</button></div>'
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
        // El <h5> es solo para verlo; lo que viaja al servidor es este hidden.
        $('#placaCheck').val(Registro.placa || '');
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

        // Limpieza completa: si no, al cambiar de vehículo se quedaban las fotos y los
        // datos capturados del anterior.
        idChecklistActual = 0;
        forzarChecklistNuevo = false;
        limpiarFormularioChecklist();
        OcultaDivVehiculosAsignados();
        verificarBorrador(Registro.idCoche);
        verificarCompletitud();
    }

        function guardarCheckIn() {
            if (!$('#id_coche').val()) {
                Swal.fire('Error', 'Selecciona un vehículo primero.', 'error');
                return;
            }

            // Mismas dos esperas que en guardarAvance. Aquí el guardado NO se descarta si
            // hay otro en vuelo (es el final del checklist): se encola detrás.
            var pendientes = esperarCompresiones();
            if (pendientes) {
                pendientes.then(function () { guardarCheckIn(); });
                return;
            }
            if (guardadoEnVuelo) {
                guardadoEnVuelo.always(function () { guardarCheckIn(); });
                return;
            }

            let formData = new FormData();

            // Recolectar valores de inputs de texto, date, hidden, y otros
            $('input[type="text"], input[type="date"], input[type="input"], input[type="hidden"]').each(function () {
                formData.append($(this).attr('name'), $(this).val());
            });

            // Recolectar valores de checkboxes
            $('input[type="checkbox"]').each(function () {
                var nombre = $(this).attr('name');
                // Los toggles de "Buen estado" solo se mandan si el usuario los tocó.
                // Antes se mandaba 0 para TODOS en cada autoguardado, así que al pasar
                // del paso 1 al 2 se grababan las 15 secciones como "en mal estado"
                // aunque no se hubieran revisado: 0 significaba a la vez "sin responder"
                // y "en mal estado". Sin tocar se manda '' y queda como no respondida.
                if (nombre && nombre.indexOf('buenEstado_') === 0 && this.dataset.tocado !== '1') {
                    formData.append(nombre, '');
                    return;
                }
                formData.append(nombre, $(this).is(':checked') ? 1 : 0);
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

            // Recolectar archivos tipo file, saltando los que ya viajaron.
            //
            // Antes se mandaban TODAS las fotos de la sesión en esta última petición (hasta
            // 15). Con fotos de celular eso son decenas de MB en un solo POST: se excedía
            // post_max_size (8M), PHP descartaba $_POST y $_FILES enteros, el endpoint
            // respondía vacío y jQuery mostraba el modal "No se pudo completar la
            // solicitud" sin más explicación. Es el mismo criterio que ya usaba
            // guardarAvance: el servidor conserva la ruta de las fotos que no vuelven a
            // viajar.
            var enviadasAhora = [];
            $('input[type="file"]').each(function () {
                var nombre = $(this).attr('name');
                if ($(this)[0].files.length > 0 && !fotosEnviadas.has(nombre)) {
                    formData.append(nombre, $(this)[0].files[0]);
                    enviadasAhora.push(nombre);
                }
            });

            // Agregar opción al FormData
            formData.append('opcion', 'guardarCheckIn');
            formData.append('estatus', 'completo');
            // Identifica el checklist destino: evita que el servidor adivine "el último
            // borrador del vehículo" y acabe escribiendo en otro.
            if (idChecklistActual > 0) formData.append('id_checklist', idChecklistActual);
            if (forzarChecklistNuevo)  formData.append('nuevo', '1');

            // Deshabilitar botones para evitar múltiples envíos. btnSiguiente entra en la
            // lista porque en el último paso es el que llama a finalizar: quedaba activo y
            // cada pulsación extra creaba otro checklist completo (en la BD hay tres del
            // mismo vehículo con 23 y 48 segundos de diferencia).
            $('#btnguardarCheck').prop('disabled', true);

            $('#btnGuardarAvance').prop('disabled', true);
            $('#btnSiguiente').prop('disabled', true);
            
            
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
            guardadoEnVuelo = $.ajax({
                url: 'AccionesCheckVehiculo.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                complete: function () { guardadoEnVuelo = null; },
                success: function(response) {
                    Swal.close();

                    // El id se guarda también aquí (antes solo lo hacía guardarAvance): si
                    // el usuario vuelve a pulsar finalizar, se escribe en ESTE checklist en
                    // vez de crear otro.
                    if (response.id_checklist) {
                        idChecklistActual = parseInt(response.id_checklist, 10) || 0;
                        forzarChecklistNuevo = false;
                    }
                    var fallidas = response.fotos_fallidas || {};
                    enviadasAhora.forEach(function (n) {
                        if (!fallidas[n]) fotosEnviadas.add(n);
                    });

                    if (response.success) {
                        // Con fotos sin guardar no se sale de la pantalla: si redirigiera,
                        // el usuario se iría creyendo que quedó completo y ya no tendría
                        // dónde volver a tomarlas.
                        if (Object.keys(fallidas).length) {
                            avisarFotosFallidas(response);
                            $('#btnguardarCheck, #btnGuardarAvance, #btnSiguiente').prop('disabled', false);
                            verificarCompletitud();
                            return;
                        }
                        Swal.fire("Éxito", "El check-in se guardó correctamente.", "success");
                        window.location.assign("verifica_checkinVehiculo");
                    } else {
                        Swal.fire("Error", response.error || "Hubo un problema al guardar el check-in. Inténtalo nuevamente.", "error");
                        $('#btnguardarCheck, #btnGuardarAvance, #btnSiguiente').prop('disabled', false);
                        verificarCompletitud();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Swal.close();
                    Swal.fire("Error", "No se pudo completar la solicitud. Por favor, inténtalo más tarde.", "error");
                    $('#btnguardarCheck, #btnGuardarAvance, #btnSiguiente').prop('disabled', false);
                    verificarCompletitud();
                }
            });
            
        }

        /**
         * Guarda el avance del checklist.
         *
         * @param {boolean} silencioso  true cuando lo dispara el cambio de apartado:
         *        no bloquea con el modal de carga ni muestra el "Avance guardado", que
         *        en cada paso sería insoportable. Solo avisa si algo falla.
         */
        function guardarAvance(silencioso) {
            // Si hay fotos comprimiéndose se espera: mandarlas a medias significaba
            // subir la imagen original de 3-8 MB, que el servidor rechaza en silencio.
            var pendientes = esperarCompresiones();
            if (pendientes) {
                pendientes.then(function () { guardarAvance(silencioso); });
                return;
            }

            // Un guardado a la vez. El avance que llegue mientras hay otro en vuelo se
            // deja en cola (uno solo: el último estado de la pantalla es el que importa)
            // en lugar de salir en paralelo y acabar creando un segundo checklist.
            if (guardadoEnVuelo) { avanceEnCola = true; return; }

            let formData = new FormData();

            $('input[type="text"], input[type="date"], input[type="input"], input[type="hidden"]').each(function () {
                formData.append($(this).attr('name'), $(this).val());
            });
            $('input[type="checkbox"]').each(function () {
                var nombre = $(this).attr('name');
                // Los toggles de "Buen estado" solo se mandan si el usuario los tocó.
                // Antes se mandaba 0 para TODOS en cada autoguardado, así que al pasar
                // del paso 1 al 2 se grababan las 15 secciones como "en mal estado"
                // aunque no se hubieran revisado: 0 significaba a la vez "sin responder"
                // y "en mal estado". Sin tocar se manda '' y queda como no respondida.
                if (nombre && nombre.indexOf('buenEstado_') === 0 && this.dataset.tocado !== '1') {
                    formData.append(nombre, '');
                    return;
                }
                formData.append(nombre, $(this).is(':checked') ? 1 : 0);
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
            // Cada foto se envía UNA sola vez. getFotoInfo() en AccionesCheckVehiculo.php
            // renombra el archivo con la hora de subida, así que reenviarlo en cada
            // guardado dejaría una copia distinta en disco por cada paso. Y no hace falta:
            // cuando no llega archivo, el servidor CONSERVA la ruta que ya tenía
            // (upsertChecklistSeccion), así que la foto guardada no se pierde ni se
            // sustituye. Solo se vuelve a mandar si el usuario elige otra imagen, que es
            // cuando se borra la marca (ver el listener de change de los input file).
            var enviadasAhora = [];
            $('input[type="file"]').each(function () {
                var nombre = $(this).attr('name');
                if ($(this)[0].files.length > 0 && !fotosEnviadas.has(nombre)) {
                    formData.append(nombre, $(this)[0].files[0]);
                    enviadasAhora.push(nombre);
                }
            });

            formData.append('opcion', 'guardarCheckIn');
            formData.append('estatus', 'borrador');
            // Identifica el checklist destino (ver comentario en guardarCheckIn).
            if (idChecklistActual > 0) formData.append('id_checklist', idChecklistActual);
            if (forzarChecklistNuevo)  formData.append('nuevo', '1');

            $('#btnguardarCheck').prop('disabled', true);
            $('#btnGuardarAvance').prop('disabled', true);

            if (!silencioso) {
                Swal.fire({
                    title: "Guardando avance...",
                    text: "Se está guardando tu progreso.",
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => { Swal.showLoading(); }
                });
            }

            guardadoEnVuelo = $.ajax({
                url: 'AccionesCheckVehiculo.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                complete: function () {
                    guardadoEnVuelo = null;
                    // Se atiende el avance que quedó en cola mientras este iba en camino.
                    if (avanceEnCola) { avanceEnCola = false; guardarAvance(true); }
                },
                success: function(response) {
                    if (!silencioso) Swal.close();
                    $('#btnGuardarAvance').prop('disabled', false);

                    // Los datos pudieron guardarse y alguna foto no (rechazada por peso o
                    // por fallo al escribirla). Se avisa siempre, incluso en el guardado
                    // automático: es justo el caso que se reportó como "la pantalla decía
                    // completo pero la ruta de la imagen no estaba en la BD".
                    avisarFotosFallidas(response);

                    if (response.success) {
                        // A partir de aquí ya se sabe en qué checklist se trabaja: los
                        // siguientes guardados lo reenvían y dejan de depender de que el
                        // servidor adivine cuál es.
                        if (response.id_checklist) {
                            idChecklistActual = parseInt(response.id_checklist, 10) || 0;
                            forzarChecklistNuevo = false;
                        }

                        // Ya están en el servidor: no volver a subirlas en los siguientes
                        // guardados. Si el usuario cambia la imagen, el listener de change
                        // quita la marca y vuelve a viajar.
                        // Las que el servidor rechazó NO se marcan: deben reintentarse en
                        // el siguiente guardado en vez de darse por subidas.
                        var fallidas = response.fotos_fallidas || {};
                        enviadasAhora.forEach(function (n) {
                            if (!fallidas[n]) fotosEnviadas.add(n);
                        });
                        // En silencioso no se confirma nada: el usuario ya está en el
                        // siguiente apartado y un modal ahí solo estorbaría.
                        if (!silencioso) {
                            Swal.fire("Avance guardado", "Tu progreso fue guardado. Puedes continuar el registro más tarde.", "success").then(verificarCompletitud);
                        } else {
                            verificarCompletitud();
                        }
                    } else {
                        // El error sí se avisa siempre: si no se guardó, el usuario tiene
                        // que enterarse aunque el guardado fuera automático. Y se muestra
                        // el motivo que manda el servidor (response.error), que antes se
                        // descartaba: sin él no había forma de saber qué apartado falló.
                        Swal.fire("Error", response.error || "Hubo un problema al guardar el avance.", "error");
                        verificarCompletitud();
                    }
                },
                error: function() {
                    if (!silencioso) Swal.close();
                    Swal.fire("Error", "No se pudo completar la solicitud.", "error");
                    $('#btnGuardarAvance').prop('disabled', false);
                    verificarCompletitud();
                }
            });
        }

        /**
         * Avisa de las fotos que el servidor no pudo guardar.
         *
         * El guardado puede salir bien y aun así perderse una imagen (peso, escritura en
         * disco). Cuando eso se callaba, el checklist se veía completo en pantalla y en la
         * BD faltaban rutas: es el síntoma que se reportó desde producción.
         */
        function avisarFotosFallidas(response) {
            var fallidas = (response && response.fotos_fallidas) || {};
            var nombres = Object.keys(fallidas);
            if (!nombres.length) return;

            var detalle = nombres.map(function (k) {
                return '<li><b>' + k.replace('foto_', '') + '</b>: ' + fallidas[k] + '</li>';
            }).join('');
            Swal.fire({
                icon: 'warning',
                title: 'Hay fotos sin guardar',
                html: 'Los datos sí se guardaron, pero estas fotos no:<ul class="text-start mt-2">' + detalle + '</ul>'
                    + 'Vuelve a tomarlas antes de finalizar.'
            });
        }

        /**
         * Deja el formulario del checklist como recién abierto.
         *
         * limpiarRutasFoto() solo vaciaba los campos ruta_foto_*, pero NO quitaba las
         * miniaturas que inserta setRuta() (.foto-borrador-preview) ni los datos ya
         * capturados. Por eso, al empezar un checklist nuevo o al cambiar de vehículo,
         * seguían viéndose las fotos y los valores del anterior.
         *
         * Se limpia solo el contenido de los pasos (.chk-slide): fuera de ahí están los
         * campos ocultos del vehículo (id_coche, placa, marca...), que deben conservarse.
         */
        // Chrome restaura los valores de los inputs al volver a una página (bfcache /
        // restauración de sesión), y lo hace DESPUÉS de que corra la limpieza. Con
        // autocomplete=off deja de hacerlo, así que un checklist nuevo no aparece con los
        // datos del anterior.
        $(function () {
            $('.chk-slide').find('input, textarea, select').attr('autocomplete', 'off');
        });

        // Marca el toggle como respondido en cuanto el usuario lo mueve, en cualquiera
        // de los dos sentidos: apagarlo a propósito es una respuesta ("está mal"), y hay
        // que poder distinguirla de no haberlo tocado nunca.
        $(document).on('change', 'input[type="checkbox"][name^="buenEstado_"]', function () {
            this.dataset.tocado = '1';
        });

        function limpiarFormularioChecklist() {
            limpiarRutasFoto();

            // Miniaturas del borrador y previsualizaciones de fotos recién tomadas
            $('.foto-borrador-preview').remove();
            $('[id^="preview_foto_"]').attr('src', '');
            $('[id^="wrap_foto_"]').hide();
            $('[id^="captura_foto_"]').show();

            $('.chk-slide').find('input[type="file"]').val('');
            $('.chk-slide').find('input[type="text"], input[type="date"], input[type="number"], textarea').val('');
            $('.chk-slide').find('select').prop('selectedIndex', 0);
            // Se limpia también la marca de "tocado": en un checklist nuevo ninguna
            // sección está respondida todavía.
            $('.chk-slide').find('input[type="checkbox"]').prop('checked', false)
                .each(function () { delete this.dataset.tocado; });

            // Ninguna foto de esta sesión sigue vigente para el checklist nuevo.
            fotosEnviadas.clear();
            // Un avance encolado del vehículo anterior escribiría con los datos ya
            // limpiados, así que se descarta al cambiar de checklist.
            avanceEnCola = false;

            verificarCompletitud();
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
                                // Continuar el borrador existente: se trabaja sobre él.
                                idChecklistActual = parseInt(response.id_checklist, 10) || 0;
                                forzarChecklistNuevo = false;
                                cargarDatosBorrador(response);
                            } else {
                                // Empezar de cero. Antes esto no hacía NADA: ni vaciaba el
                                // formulario ni creaba un checklist nuevo, así que se veían
                                // los datos del anterior y el guardado terminaba pisándolo.
                                idChecklistActual = 0;
                                forzarChecklistNuevo = true;
                                limpiarFormularioChecklist();
                            }
                        });
                    } else if (response.completo) {
                        // Sin borrador pero con un checklist ya terminado. Antes se abría
                        // un formulario en blanco sin avisar y parecía que el checklist
                        // recién completado se había perdido.
                        Swal.fire({
                            title: 'Este vehículo ya tiene un checklist completo',
                            html: 'Se registró el <b>' + fechaLegible(response.completo.fecha) + '</b>.<br>¿Quieres empezar uno nuevo?',
                            icon: 'info',
                            showCancelButton: true,
                            confirmButtonText: 'Sí, empezar uno nuevo',
                            cancelButtonText: 'No, ver el vehículo'
                        }).then(function (result) {
                            if (result.isConfirmed) {
                                idChecklistActual = 0;
                                forzarChecklistNuevo = true;
                                limpiarFormularioChecklist();
                            } else if (id_coche) {
                                // Se usa id_coche y no autoSelectVehiculo: este último solo
                                // existe si se llegó con ?v= desde el QR, así que entrando
                                // por el menú valía 0 y el botón no hacía nada.
                                window.location.assign('qr_vehiculo.php?v=' + id_coche);
                            }
                        });
                    }
                }
            });
        }

        // 'YYYY-MM-DD HH:MM:SS' -> 'DD/MM/YYYY'. Se arma por partes para no depender
        // de cómo interprete new Date() una fecha de MySQL sin zona horaria.
        function fechaLegible(fecha) {
            if (!fecha) return 'fecha desconocida';
            var p = String(fecha).split(' ')[0].split('-');
            return p.length === 3 ? (p[2] + '/' + p[1] + '/' + p[0]) : String(fecha);
        }

        function cargarDatosBorrador(data) {
            function setVal(name, val) {
                var v = (val && val !== 'S/R' && val !== '0000-00-00') ? val : '';
                $('[name="' + name + '"]').val(v);
            }
            function setCheck(name, val) {
                var $el = $('input[name="' + name + '"]');
                $el.prop('checked', val == 1 || val === '1');
                // Un valor guardado ('0' o '1') ya es una respuesta del usuario: se marca
                // como tocada para que el siguiente guardado no la borre. '' o 'S/R'
                // siguen contando como sin responder.
                var respondido = (val !== null && val !== undefined && val !== '' && val !== 'S/R');
                $el.each(function () {
                    if (respondido) { this.dataset.tocado = '1'; }
                    else { delete this.dataset.tocado; }
                });
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

        function irAPaso(n, sinGuardar) {
            if (n < 0 || n >= totalPasos) return;

            // El avance se guarda solo al cambiar de apartado, que era el acuerdo: el
            // usuario llenaba una sección, se movía y perdía lo capturado si no se
            // acordaba de pulsar el botón. Se guarda en silencio (sin el modal de
            // "Guardando avance...") para no interrumpir la navegación.
            // sinGuardar = true en las llamadas que no son navegación del usuario:
            // el arranque en el paso 0 y el salto a la sección con fotos faltantes.
            if (!sinGuardar && n !== pasoActual && typeof guardarAvance === 'function') {
                guardarAvance(true);
            }

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

        /**
         * ¿El apartado tiene algo capturado, aunque le falte la foto?
         *
         * Se mira el contenido real del slide: texto, fechas, observaciones, selects y
         * casillas marcadas. Sirve para distinguir un apartado a medio llenar de uno
         * que nadie ha tocado, que antes se veían igual (los dos grises).
         */
        // Fotos que ya viajaron al servidor en esta sesión de captura. Evita reenviar el
        // mismo archivo en cada guardado, que crearía una copia nueva en disco cada vez.
        var fotosEnviadas = new Set();

        // Checklist sobre el que se está trabajando. El servidor lo devuelve al guardar y
        // se reenvía en los guardados siguientes; así se escribe siempre en el mismo, en
        // lugar de que el servidor adivine "el último borrador del vehículo" y acabe
        // sobrescribiendo otro.
        var idChecklistActual = 0;
        // true cuando el usuario eligió "No, empezar de nuevo": el primer guardado debe
        // crear un checklist nuevo en vez de continuar el borrador existente.
        var forzarChecklistNuevo = false;

        // Compresiones de foto en curso. Comprimir es asíncrono y el guardado automático
        // se dispara al cambiar de apartado, así que sin esto se podía mandar el FormData
        // mientras la foto todavía se estaba comprimiendo y viajaba la original (o nada).
        var compresionesPendientes = [];

        /** Promesa de las compresiones en curso, o null si no hay ninguna. */
        function esperarCompresiones() {
            return compresionesPendientes.length ? Promise.all(compresionesPendientes.slice()) : null;
        }

        // Petición de guardado en vuelo (jqXHR) y avance en cola.
        //
        // Antes no había ningún control: irAPaso() dispara guardarAvance() en CADA cambio
        // de apartado, los puntitos de progreso permiten saltar de golpe y el endpoint no
        // usa sesión, así que dos peticiones corrían de verdad en paralelo. Mientras la
        // primera estaba en vuelo, idChecklistActual seguía en 0, las dos entraban por el
        // INSERT y se creaban DOS checklists para el mismo vehículo, con las fotos
        // repartidas entre ambos. En la BD hay varios casos (veh 50 el 19/08 con tres
        // checklists "completo" en 48 segundos).
        var guardadoEnVuelo = null;
        var avanceEnCola = false;

        function pasoTieneDatos(idx) {
            var slide = document.querySelectorAll('.chk-slide')[idx];
            if (!slide) return false;

            var conDato = false;
            slide.querySelectorAll('input[type="text"], input[type="date"], input[type="number"], textarea, select').forEach(function(el) {
                if (String(el.value || '').trim() !== '') conDato = true;
            });
            if (!conDato) {
                slide.querySelectorAll('input[type="checkbox"]').forEach(function(el) {
                    if (el.checked) conDato = true;
                });
            }
            return conDato;
        }

        function verificarCompletitud() {
            var inputs = document.querySelectorAll('input[type="file"][name^="foto_"]');
            var dots = document.querySelectorAll('.chk-dot');
            inputs.forEach(function(input, idx) {
                // Tres estados: verde = con foto (completo), amarillo = capturado pero
                // sin foto, gris = sin tocar.
                // El verde se sincroniza en ambos sentidos: antes solo se agregaba y
                // nunca se quitaba, así que al borrar una foto el punto seguía en verde.
                var tieneFoto = pasoTieneFoto(idx);
                if (!dots[idx]) return;
                dots[idx].classList.toggle('filled', tieneFoto);
                dots[idx].classList.toggle('parcial', !tieneFoto && pasoTieneDatos(idx));
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
                    irAPaso(faltantes[0].idx, true);   // salto guiado, no es navegacion del usuario
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    guardarAvance();
                }
            });
        }

        // Se añaden number y select para que el amarillo (capturado sin foto) también
        // reaccione a esos campos; pasoTieneDatos() los mira.
        $(document).on('input', 'textarea, input[type="text"], input[type="date"], input[type="number"]', verificarCompletitud);
        $(document).on('change', 'input[type="checkbox"], select', verificarCompletitud);

        function OcultaDivVehiculosAsignados() {
            $('#DivVehiculosAsignados').hide();
            $('#DivBtnVehiculosAsignados').show();
            irAPaso(0, true);   // arranque: no hay nada que guardar todavia
        }
        function MostrarDivVehiculosAsignados() {
            $('#DivBtnVehiculosAsignados').hide();
            $('#DivVehiculosAsignados').show();
        }

        $(document).on('change', 'input[type="file"][name^="foto_"]', function() {
            var input = this;
            var file = input.files[0];
            if (!file) return;
            // Imagen nueva para este apartado: se quita la marca para que vuelva a
            // subirse y sustituya a la anterior en el servidor.
            fotosEnviadas.delete(input.name);
            var id = input.name.replace('foto_', '');

            // El campo de ruta se limpia SIEMPRE al elegir foto nueva. Traía dos valores
            // capaces de pisar la imagen recién subida en el guardado siguiente (que ya no
            // manda el archivo, por fotosEnviadas):
            //   '__BORRAR__' de quitarFoto()  -> el servidor la borraba
            //   la ruta vieja de un borrador  -> el servidor volvía a la imagen anterior
            // En los dos casos la pantalla seguía mostrando la foto nueva y la BD no.
            var campoRuta = document.querySelector('input[name="ruta_foto_' + id + '"]');
            if (campoRuta) campoRuta.value = '';

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

            // Se comprime AQUÍ, al elegirla, y se sustituye el archivo del input por el
            // resultado. Así los guardados siguen siendo síncronos (no hay que esperar
            // promesas al armar el FormData) y lo que viaja siempre es la versión ligera.
            //
            // Es el arreglo de fondo del bug reportado: una foto de celular pesa 3-8 MB y
            // upload_max_filesize es 2 MB, así que PHP la descartaba, el servidor lo leía
            // como "esta petición no traía foto", conservaba la columna y respondía OK.
            // La pantalla decía "guardado" y la ruta nunca llegaba a la BD.
            // Mismo patrón que ya usa qr_vehiculo.php.
            if (typeof comprimirImagen === 'function' && typeof DataTransfer === 'function' && /^image\//.test(file.type)) {
                var compresion = comprimirImagen(file, 1280, 0.7).then(function (blob) {
                    // Si la compresión no da resultado o sale más pesada (imagen ya
                    // pequeña), se deja la original: nunca empeorar lo que había.
                    if (!blob || blob.size >= file.size) return;
                    try {
                        var dt = new DataTransfer();
                        dt.items.add(new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' }));
                        input.files = dt.files;
                    } catch (e) {
                        console.warn('No se pudo sustituir la foto por la comprimida:', e);
                    }
                }).catch(function (e) {
                    console.warn('Falló la compresión de la foto:', e);
                }).then(function () {
                    // Sale de la lista pase lo que pase: si se quedara dentro, los
                    // guardados esperarían para siempre una promesa ya resuelta.
                    var i = compresionesPendientes.indexOf(compresion);
                    if (i !== -1) compresionesPendientes.splice(i, 1);
                });
                compresionesPendientes.push(compresion);
            }

            verificarCompletitud();
        });

        function quitarFoto(id) {
            var input = document.getElementById('foto_' + id);
            var captura = document.getElementById('captura_foto_' + id);
            var wrap = document.getElementById('wrap_foto_' + id);
            var preview = document.getElementById('preview_foto_' + id);
            var ruta = document.querySelector('input[name="ruta_foto_' + id + '"]');
            if (input) {
                input.value = '';
                fotosEnviadas.delete(input.name);
            }
            // Centinela, no cadena vacía: el servidor conserva la foto guardada cuando no
            // recibe archivo ni ruta (para que un guardado parcial no la borre), así que
            // hace falta una señal explícita de "esta sí quiero quitarla".
            if (ruta) ruta.value = '__BORRAR__';
            if (preview) preview.src = '';
            if (wrap) wrap.style.display = 'none';
            if (captura) captura.style.display = '';
            verificarCompletitud();
        }
</script>
</html>
