<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Control Vehicular - Mantenimiento</title>
    <meta name="viewport" content="width=device-width, initial-scale=1"> <!-- Hace responsivo el diseño -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

</head>
<body id="page-top">
    <div id="wrapper">
        <?php include 'menu.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'encabezado.php'; ?>
                <div class="container-fluid">                    
                    <div class="row">                        
                        <div class="card shadow mb-4">
                            <div class="card-body">
                                <div class="row">
                                    <h1 class="h3 mb-2 text-black-800">Seguimiento Mantenimientos</h1>
                                    <div class="col-12">
                                        <div class="btn-group" role="group" aria-label="Opciones de Mantenimiento">
                                            <button onclick="mantenimientoXvehiculo('PENDIENTE', 'warning')" type="button" class="btn btn-outline-warning">Pendientes</button>
                                            <button onclick="mantenimientoXvehiculo('AUTORIZADO', 'primary')" type="button" class="btn btn-outline-primary">Autorizados</button>
                                            <button onclick="mantenimientoXvehiculo('REALIZADO', 'success')" type="button" class="btn btn-outline-success">Realizados</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6 col-md-6 col-sm-12 col-12">
                                        <div class="card shadow mb-2">
                                            <div class="card-hreader" id="headerHistorialMantenimiento">
                                                <h6 class="m-3 font-weight-bold text-black" id="DescTabla"></h6>
                                            </div>
                                            <div class="card-body" id="historialMantenimientoContainer">
                                                <div class="table-responsive">
                                                    <br>
                                                    <table class="table table-bordered table-striped" id="TablaRegistrosMantenimiento" width="100%" cellspacing="0">
                                                        <thead>
                                                            <tr>
                                                                <th>Fecha Registro</th>
                                                                <th>Vehiculo</th>
                                                                <th>Descripción</th>                                                        
                                                                <th></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody></tbody>
                                                    </table>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-sm-12 col-12">                                        
                                        <div class="card shadow mb-4">
                                            <div class="card-header py-3" id="headerDetalleMantenimiento">
                                                <h6 class="m-0 font-weight-bold text-black">Detalle de Mantenimiento</h6>
                                            </div>
                                            <div class="card-body" id="detalleMantenimientoContainer">
                                            </div>
                                            <div class="card-footer" id="footerDetalleMantenimiento">
                                                
                                            </div>
                                        </div>                                        
                                    </div>
                                </div>
                            </div>                                                                                        
                        </div>
                    </div>
                </div>                
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; MESS 2025</span>
                    </div>
                </div>
            </footer>
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
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript">
        
        $(document).ready(function() {
            mantenimientoXvehiculo('PENDIENTE', 'warning'); // Cargar el historial de mantenimiento al cargar la página
            // Inicializar la tabla de registros de mantenimiento           
            $('#TablaRegistrosMantenimiento').DataTable({
                destroy: true, // Permitir reinicializar la tabla
                paging: true, // Quitar paginado
                ordering: false, // Quitar orden
                searching: false, // Quitar buscador
                info: false, // Quitar leyendas a pie de tabla
                pageLength: 5,
                lengthMenu: [5, 10, 25, 50, 100],
                language: {
                decimal: ",",
                thousands: ".",
                processing: "Procesando...",
                loadingRecords: "Cargando...",
                zeroRecords: "No se encontraron resultados",
                emptyTable: "No hay datos disponibles en la tabla"
                },
                createdRow: function(row, data, dataIndex) {
                    $(row).css('font-size', '14px'); // Reducir tamaño del texto
                }
            });
        });       

        // Función para cargar el historial de mantenimiento del vehículo seleccionado
        function mantenimientoXvehiculo(estatus, estiloTabla) {
            $.ajax({
                type: "POST",
                url: 'acciones_ver_registros',
                data: { accion: 'verMantenimientoXVehiculo', estatus: estatus },
                dataType: "json",
                success: function(respuesta) {

                    var table = $('#TablaRegistrosMantenimiento').DataTable();
                            
                    table.clear().draw();                            
                    respuesta.forEach(function(mantenimiento) { 
                        table.row.add([                                    
                            mantenimiento.fecha_registro,
                            mantenimiento.placa + ' / ' + mantenimiento.modelo,
                            mantenimiento.descripcion,
                            `<center>
                                <button class="btn btn-outline-warning btn-sm" onclick='mostrarDetalleMantenimiento(${JSON.stringify(mantenimiento)})'>
                                    <i class="fas fa-eye"></i>
                                </button>
                            </center>`
                        ]).draw(false);
                    });
                    
                }
            });   
            // Actualizar el encabezado de la tabla
            $('#DescTabla').text('Mantenimientos - ' + estatus);
            //Limpiar el contenedor del detalle de mantenimiento
            $('#detalleMantenimientoContainer').empty();
            // Agregar estilo al thead de la tabla            
            $('#TablaRegistrosMantenimiento thead').removeClass('table-warning table-primary table-success');
            $('#TablaRegistrosMantenimiento thead').addClass('table-' + estiloTabla);
        }

        // Función para mostrar el detalle del mantenimiento
        function mostrarDetalleMantenimiento(mantenimiento) {
            var imgHtml = '';
            if (mantenimiento.foto && mantenimiento.foto !== '') {
                imgHtml = `
                    <a href="${mantenimiento.foto}" target="_blank">
                        <img src="${mantenimiento.foto}" alt="Foto Mantenimiento" style="max-height:280px;max-width:100%;" class="img-thumbnail"/>
                    </a>
                `;
            } else {
                imgHtml = 'No hay foto disponible.';
            }
            
            encabezado = '';
            estiloEstatus = mantenimiento.VoBo_jefe;
            if (estiloEstatus == 'PENDIENTE') {
                estiloEstatus = 'warning';                
            } else if (estiloEstatus == 'AUTORIZADO') {
                estiloEstatus = 'primary';
                encabezado = `<button class="btn btn-outline-success btn-sm" onclick="mantenimientoRealizado('${mantenimiento.id_mantenimiento}')"><i class="fas fa-check"></i> Marcar como realizado</button>`;
            } else if (estiloEstatus == 'REALIZADO') {
                estiloEstatus = 'success';
            } else {
                estiloEstatus = 'secondary';
            }

            $('#footerDetalleMantenimiento').html(encabezado);

            var tarjeta = `                
                        <div class="row">
                            <div id="placaSeleccionada" class="alert alert-info">
                                <strong>Placa:</strong> ${mantenimiento.placa || 'N/A'}
                                <strong>Modelo:</strong> ${mantenimiento.modelo || 'N/A'}
                                <strong>Marca:</strong> ${mantenimiento.marca || 'N/A'}
                                <strong>Año:</strong> ${mantenimiento.anio || 'N/A'}
                            </div>
                        </div>
                        <div class="row">
                            <!-- Primera columna con información -->
                            <div class="col-sm-7">
                                <p style="margin:0;"><strong>Fecha de Registro:</strong> ${mantenimiento.fecha_registro || 'N/A'}</p>
                                <p style="margin:1;"><strong>Tipo:</strong> ${mantenimiento.tipo_mantenimiento || 'N/A'}</p>
                                <p style="margin:0;"><strong>Kilometraje:</strong> ${mantenimiento.kilometraje || 'N/A'}</p>
                                <p style="margin:1;"><strong>Fecha Próximo Mantenimiento:</strong> ${mantenimiento.fecha_proxi || 'N/A'}</p>
                                <p style="margin:1;"><strong>Descripción:</strong> ${mantenimiento.descripcion || 'N/A'}</p>
                            </div>
                            <!-- Segunda columna con la imagen -->
                            <div class="col-sm-5">
                                <h5><span class="badge text-bg-${estiloEstatus}"><strong>Estatus:</strong> ${mantenimiento.VoBo_jefe || 'N/A'}</span></h5>
                                <p style="margin:0;"><strong>Folio OC:</strong> ${mantenimiento.folio || 'N/A'}</p> <br>
                                ${imgHtml}
                            </div>
                            <!-- Imagen centrada -->
                            <div class="row mt-2">
                                <div class="col-12 d-flex align-items-center justify-content-center">                                    
                                </div>
                            </div>
                        </div>
                    
            `;
            $('#detalleMantenimientoContainer').html(tarjeta);
        }

        function mantenimientoRealizado(id_mantenimiento) {
            
            Swal.fire({
                title: "¿Estás seguro que se realizó el mantenimiento?",
                text: "",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Confirmar!",
                cancelButtonText: "Cancelar"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: "POST",
                        url: 'acciones_ver_registros',
                        data: { accion: 'mantenimientoRealizado', id_mantenimiento: id_mantenimiento },
                        dataType: "json",
                        success: function(respuesta) {
                            if (respuesta.success) {
                                Swal.fire({
                                    title: "Mantenimiento marcado como realizado",
                                    text: "El mantenimiento ha sido actualizado correctamente.",
                                    icon: "success",
                                    confirmButtonText: "Aceptar"
                                });                                
                                mantenimientoXvehiculo('REALIZADO', 'success'); // Actualizar la tabla
                            } else {
                                Swal.fire({
                                    title: "Error",
                                    text: respuesta.error || "No se pudo actualizar el mantenimiento.",
                                    icon: "error",
                                    confirmButtonText: "Aceptar"
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            alert("Error en la solicitud: " + error);
                        }
                    });
                }
            });

                        
        }
    </script>
</body>
</html>