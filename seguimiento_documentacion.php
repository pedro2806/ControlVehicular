<?php
session_start();
include 'conn.php';
if ($_COOKIE['noEmpleado'] == '' || $_COOKIE['noEmpleado'] == null) {
    echo '<script>window.location.assign("index")</script>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Control Vehicular - Documentación</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <!-- Custom styles for this template-->
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

    <style>
        /* Estilo para la imagen miniatura */
        .img-zoomable {
            max-height: 80px;
            max-width: 120px;
            transition: all 0.3s ease;
            cursor: zoom-in;
        }

        /* Clase que se aplica al hacer clic */
        .img-zoomable.zoomed {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(1);
            max-width: 90vw;
            max-height: 90vh;
            z-index: 10000;
            cursor: zoom-out;
            box-shadow: 0 0 50px rgba(0,0,0,0.8);
            background: white;
        }
    </style>
</head>
<body id="page-top">
    <!-- Page Wrapper -->
    <div id="wrapper">
        <?php include 'menu.php'; ?>
        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">
            <!-- Main Content -->
            <div id="content">
                <?php include 'encabezado.php'; ?>
                <!-- Begin Page Content -->
                <div class="container-fluid">
                    <!-- Content Row -->
                    <div class="row">
                        <div class="col-xl-12 col-lg-12">
                            <h1 class="h3 mb-0 text-black-800">Documentación</h1>
                            <br>
                            <!-- CONTENEDOR INFO AUTO --> 
                            <div id="placaSeleccionada" class="alert alert-info" style="display: none;"></div>                             
                            <div class="card shadow mb-4">                                
                                <!-- Tabla de Registros -->
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="TablaRegistrosDocumentacion" width="100%" cellspacing="0">
                                            <thead class="thead-dark">
                                                <tr>
                                                    <th>Vehículo</th>
                                                    <th>Fecha de Registro</th>
                                                    <th>Usuario</th>
                                                    <th>Contacto</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                                <div id="detalleDocumentacionContainer"></div>
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
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
    <!-- Bootstrap core JavaScript-->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>
    <!-- DataTables JavaScript -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            // Inicializar DataTables            
            var TablaRegistrosDocumentacion = $('#TablaRegistrosDocumentacion').DataTable({
                data: [],
                paging: false,
                pageLength: 5,
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
                    info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    infoEmpty: "Mostrando 0 a 0 de 0 registros",
                    infoFiltered: "(filtrado de _MAX_ registros totales)",
                    search: "Buscar:",
                    paginate: {
                        first: "Primero",
                        last: "Último",
                        next: "Siguiente",
                        previous: "Anterior"
                    },
                    lengthMenu: "Mostrar _MENU_ registros",
                    aria: {
                        sortAscending: ": activar para ordenar la columna de manera ascendente",
                        sortDescending: ": activar para ordenar la columna de manera descendente"
                    }
                }
            });            
            documentacionXvehiculo();
        });

        // Seleccionar Vehículo
        function seleccionarVehiculo(id_vehiculo, placa, modelo, marca, color) {
            $("#placaSeleccionada")
                .html(`
                    <div style="display: flex; justify-content: space-between; font-weight: bold;">
                        <span>Placa:<span id="placaVehiculo" style="font-weight: normal;">${placa}</span>
                        <span>Modelo:<span style="font-weight: normal;">${modelo}</span>
                        <span>Marca:<span style="font-weight: normal;">${marca}</span>
                        <span>Color:<span style="font-weight: normal;">${color}</span>
                    </div>
                `).show();
            $("#id_vehiculo").val(id_vehiculo);            
            $("#TablaRegistrosDocumentacion").closest(".table-responsive").show();
            documentacionXvehiculo(id_vehiculo);
        }
        // Cambiar de Vehículo
        function cambiarVehiculo() {
            $("#placaSeleccionada").hide();            
            $("#TablaRegistrosDocumentacion").closest(".table-responsive").hide();
        }

        // Cargar registros de documentación
        function documentacionXvehiculo(id_vehiculo) {
            $.ajax({
                type: "POST",
                url: 'acciones_ver_registros',
                data: { accion: 'verDocumentacionXVehiculo', id_vehiculo: id_vehiculo },
                dataType: "json",
                success: function(respuesta) {
                    var TablaRegistrosDocumentacion = $('#TablaRegistrosDocumentacion').DataTable();
                    TablaRegistrosDocumentacion.clear();

                    respuesta.forEach(function(documento) {
                        var fila = [
                            `<b>${documento.placa + ' - ' + documento.modelo || 'N/A'}</b>`,
                            `${documento.fecha_registro || ''}`,
                            `${documento.usuario || ''}`,
                            `${documento.contacto || ''}`,
                            `<center>
                                <button class="btn btn-outline-warning btn-sm" onclick='mostrarDetalleDocumentacion(${JSON.stringify(documento)})'>
                                    <i class="fas fa-eye"></i> 
                                </button>
                            </center>`
                        ];
                        TablaRegistrosDocumentacion.row.add(fila);
                    });
                    TablaRegistrosDocumentacion.draw();
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "No se pudo cargar la información de la documentación.",
                        confirmButtonText: "Aceptar"
                    });
                }
            });
        }

        // Mostrar detalle de la documentación
        function mostrarDetalleDocumentacion(documento) {
            // Puedes personalizar los nombres y el orden según tus columnas reales
            const docs = [
                { nombre: "Licencia", archivo: documento.licencia },
                { nombre: "Tarjeta de Circulación", archivo: documento.tarjeta_circulacion },
                { nombre: "Refrendo Actual", archivo: documento.refrendo_actual },
                { nombre: "Seguro Vehículo", archivo: documento.seguro_vehiculo },
                { nombre: "Verificación Vigente", archivo: documento.verificacion_vigente }
            ];

            let docsHtml = docs.map(doc => {
                const tieneArchivo = doc.archivo && doc.archivo !== 'S/R';
                
                // Extraer extensión de forma limpia
                const ext = tieneArchivo ? doc.archivo.split('.').pop().toLowerCase() : '';
                
                let contenidoCelda = 'S/R';

                if (tieneArchivo) {
                    if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(ext)) {
                        // Usamos una clase CSS para el zoom en lugar de estilos inline masivos
                        contenidoCelda = `
                            <div class="img-container">
                                <img src="${doc.archivo}" 
                                    alt="${doc.nombre}" 
                                    class="img-thumbnail img-zoomable" 
                                    onclick="toggleZoom(this)">
                            </div>`;
                    } else {
                        // Botones estilizados con iconos de FontAwesome
                        const esPdf = ext === 'pdf';
                        const btnClass = esPdf ? 'btn-outline-danger' : 'btn-outline-primary';
                        const iconClass = esPdf ? 'fa-file-pdf' : 'fa-file-download';
                        
                        contenidoCelda = `
                            <a href="${doc.archivo}" target="_blank" class="btn ${btnClass} btn-sm shadow-sm">
                                <i class="fas ${iconClass} me-1"></i> Ver ${ext.toUpperCase()}
                            </a>`;
                    }
                }

                return `
                    <tr>
                        <td class="align-middle text-secondary" style="width: 40%;">
                            <small class="fw-bold text-uppercase">${doc.nombre}:</small>
                        </td>
                        <td class="align-middle">
                            ${contenidoCelda}
                        </td>
                    </tr>`;
            }).join('');

            if (!docsHtml) {
                docsHtml = `<tr><td colspan="2" class="text-center">No hay documentos disponibles.</td></tr>`;
            }

            var tarjeta = `
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center">
                        <i class="fas fa-file-alt text-primary me-2"></i>
                        <h6 class="m-0 font-weight-bold text-dark">Detalle de Documentación</h6>
                    </div>
                    
                    <div class="card-body">
                        <div class="row g-4">
                            <div class="col-md-4 border-end">
                                <div class="d-flex flex-column gap-3">
                                    <div class="info-item mb-4">
                                        <small class="text-muted d-block uppercase font-weight-bold" style="font-size: 0.9rem; letter-spacing: 1px;">VEHÍCULO</small>
                                        <span class="text-dark" style="font-size: 1.2rem; font-weight: 500;">
                                            <i class="fas fa-car fa-lg me-2 text-secondary"></i>${documento.placa + ' - ' + documento.modelo || 'N/A'}
                                        </span>
                                    </div>

                                    <div class="info-item mb-4">
                                        <small class="text-muted d-block uppercase font-weight-bold" style="font-size: 0.9rem; letter-spacing: 1px;">FECHA DE REGISTRO</small>
                                        <span class="text-dark" style="font-size: 1.2rem; font-weight: 500;">
                                            <i class="far fa-calendar-alt fa-lg me-2 text-secondary"></i>${documento.fecha_registro || 'N/A'}
                                        </span>
                                    </div>

                                    <div class="info-item mb-4">
                                        <small class="text-muted d-block uppercase font-weight-bold" style="font-size: 0.9rem; letter-spacing: 1px;">USUARIO RESPONSABLE</small>
                                        <span class="text-dark" style="font-size: 1.2rem; font-weight: 500;">
                                            <i class="fas fa-user-circle fa-lg me-2 text-secondary"></i>${documento.usuario || 'N/A'}
                                        </span>
                                    </div>

                                    <div class="info-item mb-4">
                                        <small class="text-muted d-block uppercase font-weight-bold" style="font-size: 0.9rem; letter-spacing: 1px;">CONTACTO</small>
                                        <span class="text-dark" style="font-size: 1.2rem; font-weight: 500;">
                                            <i class="fas fa-address-book fa-lg me-2 text-secondary"></i>${documento.contacto || 'N/A'}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-8">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="py-2 small text-muted" style="font-size: 0.75rem;">DOCUMENTO</th>
                                                <th class="py-2 small text-muted text-end" style="font-size: 0.75rem;"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${docsHtml || '<tr><td colspan="2" class="text-center text-muted">No hay documentos</td></tr>'}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                `;

            $('#detalleDocumentacionContainer').html(tarjeta);
        }

        function toggleZoom(elemento) {
            elemento.classList.toggle('zoomed');
            
            // Opcional: Bloquear el scroll del body cuando hay zoom
            if (elemento.classList.contains('zoomed')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = 'auto';
            }
        }
    </script>
</body>
</html>