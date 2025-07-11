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

    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

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
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>                                    
                                </tbody>    
                            </table>
                        </div>
                    </div>
                    <div class="row" name="DivBtnVehiculosAsignados" id="DivBtnVehiculosAsignados" style="display: none;">
                        <div class="col-lg-6 col-md-6 col-sm-6 col-6">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="MostrarDivVehiculosAsignados()">Cambiar de vehículo</button>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-6 col-6">
                            <button type="button" id = "btnguardarCheck2" name = "btnguardarCheck2" class="btn btn-outline-success btn-sm" onclick="guardarCheckIn()">Guardar</button>
                            <input type="hidden" id="id_coche" name="id_coche">

                        </div>
                    
                    
                        <div class="row">                                                
                            <div class="col-lg-3 col-md-3 col-sm-3 col-3">
                                <label for="marca"><b>Marca:</b></label>
                                <label id="marca" name="marca"></label>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-3 col-3">
                                <label for="modelo"><b>Modelo:</b></label>
                                <label type="text" id="modelo" name="modelo"></label>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-3 col-3">
                                <label for="color"><b>Color:</b></label>
                                <label id="color" name="color"></label>
                            </div>
                            <div class="col-lg-3 col-md-3 col-sm-3 col-3">
                                <label for="placa"><b>Placa:</b></label>
                                <label id="placa" name="placa"></label>
                            </div>
                        </div>
                        <div class="row">                                                
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label for="area"><b>Área:</b></label>
                                <label id="area" name="area"></label>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label for="usuario"><b>Nombre del Usuario:</b></label>
                                <label id="usuario" name="usuario"></label>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label for="fecha_ultimo_servicio"><b>Fecha último servicio:</b></label>
                                <label id="fecha_ultimo_servicio" name="fecha_ultimo_servicio"></label>
                            </div>
                            <div class="col-lg-3 col-md-6 col-sm-6 col-6">
                                <label for="kilometraje"><b>Kilometraje:</b></label>
                                <label id="kilometraje" name="kilometraje"></label>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                                <label for="motivo"><b>Motivo:</b></label>
                                <textarea id="motivo" name="motivo" class="form-control" placeholder="Motivo" rows="2"></textarea>                            
                            </div>
                        </div><br>

                <!-- ASPECTOS FISICOS -->
                        <div class="row">
                            <div class="col-lg-6 col-md-6 col-sm-6 col-6">
                                <h1 class = "h5 mb-0 text-gray-800">Aspectos Físicos</h1>                            
                            </div>
                        </div>
                        <div class="row">                    
                            <div class="accordion" id="accordionExample">
                                <?php
                                $accordionItems = [
                                    ["title" => "Asientos/Tapetes plasticos", "id" => "Asientos", "color" => "secondary"],
                                    ["title" => "Limpieza General", "id" => "Limpieza", "color" => "primary"],
                                    ["title" => "Exterior del auto", "id" => "Exterior", "color" => "secondary"],
                                    ["title" => "Gráficas del auto", "id" => "Graficas", "color" => "primary"],
                                    ["title" => "Faros", "id" => "Faros", "color" => "secondary"],
                                    ["title" => "Placas", "id" => "Placas", "color" => "primary"],
                                    ["title" => "Limpiaparabrisas", "id" => "Limpiaparabrisas", "color" => "secondary"],
                                    ["title" => "Espejos", "id" => "Espejos", "color" => "primary"],
                                    ["title" => "Aire acondicionado/Estéreo", "id" => "AireAcondicionado", "color" => "secondary"],
                                    ["title" => "Llantas", "id" => "Llantas", "color" => "primary"],
                                    ["title" => "Puertas/Llave", "id" => "PuertasLlave", "color" => "secondary"]
                                ];

                                foreach ($accordionItems as $item) {
                                    echo '<div class="accordion-item">';
                                        echo '<h2 class="accordion-header">';
                                            echo '<button class="accordion-button collapsed bg-' . $item["color"] . ' text-white" type="button" data-bs-toggle="collapse" data-bs-target="#' . $item["id"] . '" aria-expanded="false" aria-controls="' . $item["id"] . '" style="font-size: 14px; padding: 4px;">';
                                            echo $item["title"];
                                            echo '</button>';
                                        echo '</h2>';
                                        echo '<div id="' . $item["id"] . '" class="accordion-collapse collapse" data-bs-parent="#accordionExample">';
                                            echo '<div class="accordion-body">';
                                                echo '<div class="row">';
                                                if($item["id"] == "Llantas") {
                                                        echo '<div class="col-lg-5 col-md-5 col-sm-5 col-5">';
                                                            echo '<label for="medidas_' . $item["id"] . '">Medidas:</label><br>';
                                                            echo '<input type="text" id="medidas_' . $item["id"] . '" name="medidas_' . $item["id"] . '" class="form-control">';
                                                        echo '</div>';
                                                        echo '<div class="col-lg-7 col-md-7 col-sm-7 col-7">';
                                                            echo '<div class="form-check form-switch me-3">';
                                                                echo '<input class="form-check-input" type="checkbox" id="buenEstado_' . $item["id"] . '" name="buenEstado_' . $item["id"] . '" value="1" style="transform: scale(1.5);" required>';
                                                                echo '<label for="buenEstado_' . $item["id"] . '">Buen estado No/Si</label>';
                                                            echo '</div>';
                                                        echo '</div>';
                                                } else {
                                                    if($item["id"] == "PuertasLlave") {
                                                        echo '<div class="col-lg-7 col-md-7 col-sm-7 col-7">';
                                                            echo '<div class="form-check form-switch me-3">';                                                                
                                                                echo '<input class="form-check-input" type="checkbox" id="buenEstado_' . $item["id"] . '" name="buenEstado_' . $item["id"] . '" value="1" style="transform: scale(1.5);" required>';
                                                                echo '<label for="buenEstado_' . $item["id"] . '">Buen estado No/Si</label>';
                                                            echo '</div>';
                                                        echo '</div>';
                                                        echo '<div class="col-lg-5 col-md-5 col-sm-5 col-5">';
                                                            echo '<input type="text" id="duplicado_' . $item["id"] . '" name="duplicado_' . $item["id"] . '" class="form-control">';
                                                            echo '<label for="duplicado_' . $item["id"] . '">Duplicado:</label><br>';
                                                        echo '</div>';
                                                    } else {
                                                        echo '<div class="col-lg-4 col-md-4 col-sm-4 col-4">';
                                                            echo '<div class="form-check form-switch me-3">';                                                                
                                                                echo '<input class="form-check-input" type="checkbox" id="si_no_' . $item["id"] . '" name="si_no_' . $item["id"] . '" value="1" style="transform: scale(1.5);" required>';
                                                                echo '<label for="si_no_' . $item["id"] . '">No/Si</label>';
                                                            echo '</div>';
                                                        echo '</div>';
                                                        echo '<div class="col-lg-8 col-md-8 col-sm-8 col-8">';
                                                            echo '<div class="form-check form-switch me-3">';
                                                                echo '<input class="form-check-input" type="checkbox" id="buenEstado_' . $item["id"] . '" name="buenEstado_' . $item["id"] . '" value="1" style="transform: scale(1.5);" required>';
                                                                echo '<label for="buenEstado_' . $item["id"] . '">Buen estado No/Si</label>';
                                                            echo '</div>';
                                                        echo '</div>';
                                                    }
                                                }
                                                echo '</div>';                                
                                            echo '<div class="row">';
                                                if($item["id"] == "AireAcondicionado") {
                                                    echo '<div class="col-lg-6 col-md-6 col-sm-6 col-6">';
                                                        echo '<label for="CE_' . $item["id"] . '">Código estéreo:</label>';
                                                        echo '<input type="input" id="CE_' . $item["id"] . '" name="CE' . $item["id"] . '" class="form-control">';
                                                    echo '</div>';
                                                    echo '<div class="col-lg-6 col-md-6 col-sm-6 col-6">';
                                                        echo '<label for="observaciones_' . $item["id"] . '">Observaciones:</label>';
                                                        echo '<textarea id="observaciones_' . $item["id"] . '" name="observaciones_' . $item["id"] . '" class="form-control" placeholder="Observaciones" rows="2"></textarea>';
                                                    echo '</div>';
                                                } else {    
                                                    if($item["id"] == "Llantas") {
                                                        echo '<div class="col-lg-6 col-md-6 col-sm-6 col-6">';
                                                            echo '<label for="CE_' . $item["id"] . '">No. Rin:</label>';
                                                            echo '<input type="input" id="CE_' . $item["id"] . '" name="CE' . $item["id"] . '" class="form-control">';
                                                        echo '</div>';
                                                        echo '<div class="col-lg-6 col-md-6 col-sm-6 col-6">';
                                                            echo '<label for="observaciones_' . $item["id"] . '">Observaciones:</label>';
                                                            echo '<textarea id="observaciones_' . $item["id"] . '" name="observaciones_' . $item["id"] . '" class="form-control" placeholder="Observaciones" rows="2"></textarea>';
                                                        echo '</div>';
                                                    } else {
                                                        echo '<div class="col-lg-12 col-md-12 col-sm-6 col-12">';
                                                            echo '<label for="observaciones_' . $item["id"] . '">Observaciones:</label>';
                                                            echo '<textarea id="observaciones_' . $item["id"] . '" name="observaciones_' . $item["id"] . '" class="form-control" placeholder="Observaciones" rows="2"></textarea>';
                                                        echo '</div>';
                                                    }
                                                }
                                                
                                            echo '</div>';
                                            echo '<div class="row">';                                                
                                                        echo '<div class="col-lg-12 col-md-12 col-sm-12 col-12">';
                                                            echo '<label for="foto_inspeccion">Foto:</label>';
                                                            echo '<input type="file" id="foto_' . $item["id"] . '" name="foto_' . $item["id"] . '" class="form-control form-control-sm" accept="image/*" capture="camera">';                                                        
                                                        echo '</div>';                                                                                                    
                                            echo '</div>';                                
                                        echo '</div>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                                ?>
                            </div>
                        </div>  
                        <br> <br> 
                <!-- DOCUMENTACIÓN -->
                
                        <div class="row">
                            <div class="col-lg-6 col-md-6 col-sm-6 col-6">
                                <h1 class = "h5 mb-0 text-gray-800">Documentación</h1>
                            </div>
                        </div>
                        <div class="row">                    
                            <div class="accordion" id="accordionExample2">
                                <?php
                                $accordionItems2 = [
                                    ["title" => "Tarjeta de circulación", "id" => "tarjetaC", "color" => "secondary"],
                                    ["title" => "Refrendo actual", "id" => "Refrendo", "color" => "black"],
                                    ["title" => "Seguro de auto (Póliza vigente)", "id" => "Seguro", "color" => "secondary"],
                                    ["title" => "Verificación vigente", "id" => "Verificacion", "color" => "black"],
                                    ["title" => "Licencia de manejo", "id" => "Licencia", "color" => "secondary"],
                                    ["title" => "Tarjeta Efecticard", "id" => "TarjetaEfe", "color" => "black"],
                                    ["title" => "Tarjeta IAVE", "id" => "TarjetaIAVE", "color" => "secondary"]
                                ];

                                foreach ($accordionItems2 as $item) {
                                    echo '<div class="accordion-item">';
                                        echo '<h2 class="accordion-header">';
                                            echo '<button class="accordion-button collapsed bg-' . $item["color"] . ' text-white" type="button" data-bs-toggle="collapse" data-bs-target="#' . $item["id"] . '" aria-expanded="false" aria-controls="' . $item["id"] . '" style="font-size: 14px; padding: 4px;">';
                                            echo $item["title"];
                                            echo '</button>';
                                        echo '</h2>';
                                        echo '<div id="' . $item["id"] . '" class="accordion-collapse collapse" data-bs-parent="#accordionExample2">';
                                            echo '<div class="accordion-body">';
                                                echo '<div class="row">';
                                                    if ($item["id"] == "tarjetaC" || $item["id"] == "Refrendo") {
                                                        echo '<div class="col-lg-12 col-md-12 col-sm-12 col-12">';
                                                            echo '<div class="form-check form-switch me-3">';                                                                
                                                                echo '<input class="form-check-input" type="checkbox" id="si_no_' . $item["id"] . '" name="si_no_' . $item["id"] . '" value="1" style="transform: scale(1.5);" required>';
                                                                echo '<label for="si_no_' . $item["id"] . '">No/Si</label>';
                                                            echo '</div>';
                                                        echo '</div>';
                                                    } else {	
                                                        echo '<div class="col-lg-6 col-md-6 col-sm-6 col-6">';
                                                            echo '<label for="vencimiento_' . $item["id"] . '">Vencimiento:</label><br>';
                                                            echo '<input type="date" id="vencimiento_' . $item["id"] . '" name="vencimiento_' . $item["id"] . '" class="form-control">';
                                                        echo '</div>';
                                                        echo '<div class="col-lg-6 col-md-6 col-sm-6 col-6">';
                                                            echo '<div class="form-check form-switch me-3">';                                                                
                                                                echo '<input class="form-check-input" type="checkbox" id="si_no_' . $item["id"] . '" name="si_no_' . $item["id"] . '" value="1" style="transform: scale(1.5);" required>';
                                                                echo '<label for="si_no_' . $item["id"] . '">No/Si</label>';
                                                            echo '</div>';
                                                        echo '</div>';
                                                    }
                                                echo '</div>';                                
                                                echo '<div class="row">';
                                                    if ($item["id"] == "tarjetaC" || $item["id"] == "Refrendo" || $item["id"] == "Licencia") {
                                                        echo '<div class="col-lg-12 col-md-12 col-sm-12 col-12">';
                                                            echo '<label for="observaciones_' . $item["id"] . '">Observaciones:</label>';
                                                            echo '<textarea id="observaciones_' . $item["id"] . '" name="observaciones_' . $item["id"] . '" class="form-control" placeholder="Observaciones" rows="2"></textarea>';
                                                        echo '</div>';                                                                                            
                                                    } else {	
                                                        echo '<div class="col-lg-6 col-md-6 col-sm-6 col-6">';
                                                            echo '<label for="no_tarjeta_' . $item["id"] . '">No. Tarjeta:</label>';
                                                            echo '<input type="input" id="no_tarjeta_' . $item["id"] . '" name="no_tarjeta_' . $item["id"] . '" class="form-control">';
                                                        echo '</div>';
                                                        echo '<div class="col-lg-6 col-md-6 col-sm-6 col-6">';
                                                            echo '<label for="observaciones_' . $item["id"] . '">Observaciones:</label>';
                                                            echo '<textarea id="observaciones_' . $item["id"] . '" name="observaciones_' . $item["id"] . '" class="form-control" placeholder="Observaciones" rows="2"></textarea>';
                                                        echo '</div>';                                                                                            
                                                    }
                                                echo '</div>';
                                                echo '<div class="row">';                                                
                                                        echo '<div class="col-lg-12 col-md-12 col-sm-12 col-12">';
                                                            echo '<label for="foto_inspeccion">Foto:</label>';
                                                            echo '<input type="file" id="foto_' . $item["id"] . '" name="foto_' . $item["id"] . '" class="form-control form-control-sm" accept="image/*" capture="camera">';
                                                        echo '</div>';                                                                                                    
                                                echo '</div>';                                
                                        echo '</div>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                                ?>
                            </div>
                        </div>  
                        <div class="row">
                            <div class="col-md-12 mt-3">
                            <button type="button" id="btnguardarCheck" name="btnguardarCheck" class="btn btn-outline-success btn-sm" onclick="guardarCheckIn()">Guardar</button>                            
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
                        <span>Copyright &copy; MESS 2025</span>
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
    <script src = "vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- Custom scripts for all pages-->
    <script src = "js/sb-admin-2.min.js"></script>    
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/1.10.8/js/jquery.dataTables.min.js" defer="defer"></script>    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    
<script type="text/javascript">
                        

                        
    $(document).ready(function () {
        llenaTVehiculosAsignados(); //LLENAR TABLA DE VEHICULOS ASIGNADOS

        $('#TVehiculosAsignados').DataTable({
            destroy: true, // Permitir reinicializar la tabla
            paging: false, // Quitar paginado
            ordering: false, // Quitar orden
            searching: false, // Quitar buscador
            info: false, // Quitar leyendas a pie de tabla
            language: {
            decimal: ",",
            thousands: ".",
            processing: "Procesando...",
            loadingRecords: "Cargando...",
            zeroRecords: "No se encontraron resultados",
            emptyTable: "No hay datos disponibles en la tabla"
            },
            createdRow: function(row, data, dataIndex) {
                $(row).css('font-size', '12px'); // Reducir tamaño del texto
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
                            registros.forEach(function(Registro) { 
                                table.row.add([                                    
                                    '<i class="fas fa-car fa-1x"></i><b> ' + Registro.placa + ' </b>',
                                    '<b> ' + Registro.modelo + ' </b>',
                                    '<center><button type="button" class="btn btn-sm btn-outline-success" onclick=\'SeleccionaVehiculo(' + JSON.stringify(Registro) + ')\'><i class="fas fa-check fa-1x"></i></button></center>'
                                ]).draw(false);
                            });
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            
                        }
                    });
    }
    function SeleccionaVehiculo(Registro) {
        //console.log(Registro);
        $('#id_coche').val(Registro.idCoche);
        $('#expediente').text(Registro.expediente);
        $('#empresa').text(Registro.empresa);
        $('#marca').text(Registro.marca);
        $('#modelo').text(Registro.modelo);            
        $('#color').text(Registro.color);
        $('#placa').text(Registro.placa);
        $('#area').text(Registro.area);            
        $('#usuario').text(Registro.usuario);            
        $('#fecha_ultimo_servicio').text(Registro.fecha_ultimo_servicio);                        
        
        $('#kilometraje').val(Registro.kilometraje);            
        $('#fecha_inspeccion').val('');            
        $('#carta_resguardo').prop('checked', false);            
        $('#foto_inspeccion').val('');
        OcultaDivVehiculosAsignados(); // Ocultar el div de vehículos asignados
    }

    function validarFormulario() {
        let esValido = true;
        let mensajeError = "";

        // Validar todos los campos de tipo date, sin importar si tienen required
        // Validar los campos de fecha requeridos
        const camposFecha = [
            'vencimiento_Seguro',
            'vencimiento_Verificacion',
            'vencimiento_Licencia',
            'vencimiento_TarjetaEfe'
        ];
        camposFecha.forEach(function(nombreCampo) {
            const campo = $(`input[name="${nombreCampo}"]`);
            if (campo.length && campo.val().trim() === "") {
            esValido = false;
            mensajeError += `El campo ${nombreCampo.replace(/_/g, ' ')} es obligatorio.\n`;
            }
        });

        if (!esValido) {
            Swal.fire("Error", mensajeError, "error");
        }

        return esValido;
    }

        function guardarCheckIn() {
            // Validar el formulario antes de enviar
            if (!validarFormulario()) {
                return; // Detener la ejecución si hay errores de validación
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


            // Deshabilitar botones para evitar múltiples envíos
            $('#btnguardarCheck').prop('disabled', true);
            $('#btnguardarCheck2').prop('disabled', true);
            
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
                    Swal.close(); // Cerrar el mensaje de procesamiento
                    if (response.success) {
                        Swal.fire("Éxito", "El check-in se guardó correctamente.", "success");
                        window.location.assign("verifica_checkinVehiculo");
                    } else {
                        Swal.fire("Error", "Hubo un problema al guardar el check-in. Inténtalo nuevamente.", "error");
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Swal.close(); // Cerrar el mensaje de procesamiento
                    Swal.fire("Error", "No se pudo completar la solicitud. Por favor, inténtalo más tarde.", "error");
                }
            });
            
        }
                
        // Función para obtener el valor de una cookie por su nombre
        function getCookie(name) {
            var value = "; " + document.cookie;
            var parts = value.split("; " + name + "=");
            if (parts.length === 2) return parts.pop().split(";").shift();
        }

        // funcion para ocultar el DivVehiculosAsignados
        function OcultaDivVehiculosAsignados() {
            $('#DivVehiculosAsignados').hide();
            $('#DivBtnVehiculosAsignados').show();
        }
        function MostrarDivVehiculosAsignados() {
            $('#DivBtnVehiculosAsignados').hide();
            $('#DivVehiculosAsignados').show();

        }

       /* $('input[type="file"]').on('click', function (e) {
            //alert("Por favor, selecciona una foto de inspección." + navigator.userAgent);
            // Solo aplica en dispositivos móviles
            if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
                // Forzar el atributo capture para abrir la cámara
                alert("Por favor, selecciona una foto de inspección.");
                $(this).attr('capture', 'environment');
                // Opcional: evitar que se pueda seleccionar archivos existentes
                $(this).attr('accept', 'image/*');
            } else {
                // En desktop, permitir selección normal
                $(this).removeAttr('capture');
            }
        });*/
        document.addEventListener('DOMContentLoaded', function() {
            // Selecciona el input por su ID (tendrás que adaptar esto si tienes varios inputs dinámicos)
            // Usaremos un selector más genérico si tienes varios inputs con nombres similares
            var inputsFoto = document.querySelectorAll('input[type="file"][name^="foto_"]');

            inputsFoto.forEach(function(input) {
                input.addEventListener('change', function(event) {
                    const file = event.target.files[0]; // Obtiene el primer archivo seleccionado

                    if (file) {
                        console.log('¡Foto detectada!', file.name, file.type, file.size);
                        // Aquí es donde puedes mostrar una vista previa de la imagen,
                        // subirla por AJAX, etc.
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            // Por ejemplo, mostrar una vista previa:
                            const previewId = 'preview_' + input.id.split('_')[1]; // Asumiendo que quieres una ID de vista previa para cada input
                            let previewElement = document.getElementById(previewId);
                            if (!previewElement) {
                                previewElement = document.createElement('img');
                                previewElement.id = previewId;
                                previewElement.style.maxWidth = '100px';
                                previewElement.style.maxHeight = '100px';
                                input.parentNode.appendChild(previewElement); // Añade la vista previa debajo del input
                            }
                            previewElement.src = e.target.result;
                        };
                        reader.readAsDataURL(file); // Lee el archivo como una URL de datos para la vista previa
                    } else {
                        console.log('No se seleccionó ninguna foto.');
                    }
                });
            });
        });
</script>
</html>
