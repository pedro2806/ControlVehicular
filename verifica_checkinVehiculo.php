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
                    
                    <div class="row alert alert-primary">                                                
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

                    <div class="row" name="DivChecksVehiculo" id="DivChecksVehiculo">
                        <div class="col-xl-12 col-lg-12 col-md-1 col-sm-12 col-12">
                            <table id="TChecksVehiculo" name="TChecksVehiculo" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Motivo</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>                                    
                                </tbody>    
                            </table>
                        </div>
                    </div>

                    
                        <div class="row" id="contenedorTarjetas">
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

        $('#TChecksVehiculo').DataTable({
            destroy: true, // Permitir reinicializar la tabla
            paging: true, // Activar paginado
            pageLength: 5, // Paginado de 5 en 5            
            language: {
            decimal: ",",
            thousands: ".",
            processing: "Procesando...",
            loadingRecords: "Cargando...",
            zeroRecords: "No se encontraron resultados",
            emptyTable: "No hay datos disponibles en la tabla",
            paginate: {
                first: "Primero",
                last: "Último",
                next: "Siguiente",
                previous: "Anterior"
            }
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
        $('#marca').text(Registro.marca); // Asignar valor a la etiqueta de marca
        $('#modelo').text(Registro.modelo); // Asignar valor a la etiqueta de modelo
        $('#color').text(Registro.color); // Asignar valor a la etiqueta de color
        $('#placa').text(Registro.placa); // Asignar valor a la etiqueta de placa
        verChecks(Registro.idCoche); // Llamar a la función verChecks con el idCoche del registro seleccionado
        $('#DivVehiculosAsignados').hide(); // Ocultar la tabla de vehículos asignados
    }

    function verChecks(idCoche) { // Función para mostrar los checks del vehículo seleccionado
        var opcion = "verChecks";                                                
        $.ajax({
            url: 'AccionesCheckVehiculo.php', 
            method: 'POST',
            dataType: 'json', //TIPO DE DATO JSON
            data:{opcion, idCoche}, 
            success: function(registros) {                
                var table = $('#TChecksVehiculo').DataTable();
                table.clear().draw();
                registros.forEach(function(Registro) { 
                    table.row.add([                                    
                        '<b> ' + Registro.fecha + ' </b>',
                        '<b> ' + Registro.motivo + ' </b>',
                        '<center><button type="button" class="btn btn-sm btn-outline-primary" onclick=\'VerCheck(' + JSON.stringify(Registro.id) + ')\'><i class="fas fa-eye fa-1x"></i></button></center>'
                    ]).draw(false);
                });
            },
            error: function(jqXHR, textStatus, errorThrown) {
                
            }
        });
    }
    
    function VerCheck(Registro) { // Función para ver el check seleccionado

        const contenedorTarjetas = document.getElementById('contenedorTarjetas');
        contenedorTarjetas.innerHTML = ''; // Limpiar el contenedor antes de agregar nuevas tarjetas
        var idCheck = Registro; // Obtener el id del check        
        checklist_asientos(idCheck);
        checklist_espejos_ventanas(idCheck);
        checklist_estereos_aire(idCheck);
        checklist_faros(idCheck);
        checklist_golpes_exterior(idCheck);
        checklist_graficas(idCheck);
        checklist_limpiaparabrisas(idCheck);
        checklist_limpieza(idCheck);
        checklist_llantas(idCheck);
        checklist_placas(idCheck);
        checklist_puertas_llaves(idCheck);
        checklist_documentacion(idCheck);
    }

    function checklist_documentacion(idCheck) {
        // Implementación de la función para checklist_documentacion        
        opcion = "checklist_documentacion";
        $.ajax({
            url: 'AccionesCheckVehiculo.php', 
            method: 'POST',
            dataType: 'json', //TIPO DE DATO JSON
            data:{opcion, idCheck}, 
            success: function(registros) {
                generarTarjetas(registros); // Llamar a la función para generar tarjetas con los registros
            },
            error: function(jqXHR, textStatus, errorThrown) {
                
            }
        });
    }

    function checklist_asientos(idCheck) {
        // Implementación de la función para checklist_asientos        
        opcion = "checklist_asientos";
        $.ajax({
            url: 'AccionesCheckVehiculo.php', 
            method: 'POST',
            dataType: 'json', //TIPO DE DATO JSON
            data:{opcion, idCheck}, 
            success: function(registros) {
                generarTarjetas(registros); // Llamar a la función para generar tarjetas con los registros
            },
            error: function(jqXHR, textStatus, errorThrown) {
                
            }
        });
    }

    function checklist_espejos_ventanas(idCheck) {
        // Implementación de la función para checklist_espejos_ventanas        
        opcion = "checklist_espejos_ventanas";
        $.ajax({
            url: 'AccionesCheckVehiculo.php', 
            method: 'POST',
            dataType: 'json', //TIPO DE DATO JSON
            data:{opcion, idCheck}, 
            success: function(registros) {
                generarTarjetas(registros); // Llamar a la función para generar tarjetas con los registros
            },
            error: function(jqXHR, textStatus, errorThrown) {
                
            }
        });
    }

    function checklist_estereos_aire(idCheck) {
        // Implementación de la función para checklist_estereos_aire        
        opcion = "checklist_estereos_aire";
        $.ajax({
            url: 'AccionesCheckVehiculo.php', 
            method: 'POST',
            dataType: 'json', //TIPO DE DATO JSON
            data:{opcion, idCheck}, 
            success: function(registros) {
                generarTarjetas(registros); // Llamar a la función para generar tarjetas con los registros
            },
            error: function(jqXHR, textStatus, errorThrown) {
                
            }
        });
    }

    function checklist_faros(idCheck) {
        // Implementación de la función para checklist_faros        
        opcion = "checklist_faros";
        $.ajax({
            url: 'AccionesCheckVehiculo.php', 
            method: 'POST',
            dataType: 'json', //TIPO DE DATO JSON
            data:{opcion, idCheck}, 
            success: function(registros) {
                generarTarjetas(registros); // Llamar a la función para generar tarjetas con los registros
            },
            error: function(jqXHR, textStatus, errorThrown) {
                
            }
        });
    }

    function checklist_golpes_exterior(idCheck) {
        // Implementación de la función para checklist_golpes_exterior        
        opcion = "checklist_golpes_exterior";
        $.ajax({
            url: 'AccionesCheckVehiculo.php', 
            method: 'POST',
            dataType: 'json', //TIPO DE DATO JSON
            data:{opcion, idCheck}, 
            success: function(registros) {
                generarTarjetas(registros); // Llamar a la función para generar tarjetas con los registros
            },
            error: function(jqXHR, textStatus, errorThrown) {
                
            }
        });
    }

    function checklist_graficas(idCheck) {
        // Implementación de la función para checklist_graficas        
        opcion = "checklist_graficas";
        $.ajax({
            url: 'AccionesCheckVehiculo.php', 
            method: 'POST',
            dataType: 'json', //TIPO DE DATO JSON
            data:{opcion, idCheck}, 
            success: function(registros) {
                generarTarjetas(registros); // Llamar a la función para generar tarjetas con los registros
            },
            error: function(jqXHR, textStatus, errorThrown) {
                
            }
        });
    }

    function checklist_limpiaparabrisas(idCheck) {
        // Implementación de la función para checklist_limpiaparabrisas        
        opcion = "checklist_limpiaparabrisas";
        $.ajax({
            url: 'AccionesCheckVehiculo.php', 
            method: 'POST',
            dataType: 'json', //TIPO DE DATO JSON
            data:{opcion, idCheck}, 
            success: function(registros) {
                generarTarjetas(registros); // Llamar a la función para generar tarjetas con los registros
            },
            error: function(jqXHR, textStatus, errorThrown) {
                
            }
        });
    }

    function checklist_limpieza(idCheck) {
        // Implementación de la función para checklist_limpieza        
        opcion = "checklist_limpieza";
        $.ajax({
            url: 'AccionesCheckVehiculo.php', 
            method: 'POST',
            dataType: 'json', //TIPO DE DATO JSON
            data:{opcion, idCheck}, 
            success: function(registros) {
                generarTarjetas(registros); // Llamar a la función para generar tarjetas con los registros
            },
            error: function(jqXHR, textStatus, errorThrown) {
                
            }
        });
    }

    function checklist_llantas(idCheck) {
        // Implementación de la función para checklist_llantas        
        opcion = "checklist_llantas";
        $.ajax({
            url: 'AccionesCheckVehiculo.php', 
            method: 'POST',
            dataType: 'json', //TIPO DE DATO JSON
            data:{opcion, idCheck}, 
            success: function(registros) {
                generarTarjetas(registros); // Llamar a la función para generar tarjetas con los registros
            },
            error: function(jqXHR, textStatus, errorThrown) {
                
            }
        });
    }

    function checklist_placas(idCheck) {
        // Implementación de la función para checklist_placas        
        opcion = "checklist_placas";
        $.ajax({
            url: 'AccionesCheckVehiculo.php', 
            method: 'POST',
            dataType: 'json', //TIPO DE DATO JSON
            data:{opcion, idCheck}, 
            success: function(registros) {
                generarTarjetas(registros); // Llamar a la función para generar tarjetas con los registros
            },
            error: function(jqXHR, textStatus, errorThrown) {
                
            }
        });
    }

    function checklist_puertas_llaves(idCheck) {
        // Implementación de la función para checklist_puertas_llaves        
        opcion = "checklist_puertas_llaves";
        $.ajax({
            url: 'AccionesCheckVehiculo.php', 
            method: 'POST',
            dataType: 'json', //TIPO DE DATO JSON
            data:{opcion, idCheck}, 
            success: function(registros) {
                generarTarjetas(registros); // Llamar a la función para generar tarjetas con los registros
            },
            error: function(jqXHR, textStatus, errorThrown) {
                
            }
        });
    }


/// FUNCION PARA GENERAR TARJETAS DINAMICAMENTE    

    function generarTarjetas(registros) {
        const contenedorTarjetas = document.getElementById('contenedorTarjetas');
        //contenedorTarjetas.innerHTML = ''; // Limpiar el contenedor antes de agregar nuevas tarjetas

        registros.forEach(function(registro) {
            // Crear el div principal de la tarjeta
            const tarjetaDiv = document.createElement('div');
            tarjetaDiv.classList.add('col-lg-3', 'col-md-3', 'col-sm-6', 'col-6');

            // Crear el div de la card
            const cardDiv = document.createElement('div');
            cardDiv.classList.add('card', 'border-left-info', 'shadow', 'h-60', 'py-0');

            // Crear el header de la card
            const cardHeaderDiv = document.createElement('div');
            cardHeaderDiv.classList.add('card-header');
            const tituloH6 = document.createElement('h6');
            tituloH6.classList.add('m-0', 'font-weight-bold', 'text-info');

            // Determinar el título de la tarjeta (puedes ajustarlo según la estructura de tu JSON)
            tituloH6.textContent = registro.nombre_seccion || 'Elemento'; // Ejemplo: si tu JSON tiene un campo 'nombre_seccion'
            cardHeaderDiv.appendChild(tituloH6);
            cardDiv.appendChild(cardHeaderDiv);

            // Crear el body de la card
            const cardBodyDiv = document.createElement('div');
            cardBodyDiv.classList.add('card-body');

            // Iterar sobre las propiedades del registro para crear las labels dinámicamente
            for (const key in registro) {
                // Excluir el ID o cualquier otra propiedad que no quieras mostrar como label
                if (key !== 'idCheck' && key !== 'nombre_seccion' && !key.includes('imagen')) {
                    const labelTitulo = document.createElement('label');
                    labelTitulo.classList.add('font-weight-bold');
                    labelTitulo.textContent = `${formatearNombreLabel(key)}:`;

                    const labelInfo = document.createElement('label');
                    labelInfo.id = formatearIdNombre(key);
                    labelInfo.name = formatearIdNombre(key);
                    labelInfo.textContent = registro[key] || 'N/A'; // Mostrar 'N/A' si el valor es null o undefined

                    cardBodyDiv.appendChild(labelTitulo);
                    cardBodyDiv.appendChild(document.createTextNode(' '));
                    cardBodyDiv.appendChild(labelInfo);
                    cardBodyDiv.appendChild(document.createElement('br'));
                }
            }

            // Agregar la imagen (asumiendo que tienes una propiedad que indica la ruta de la imagen)
            for (const key in registro) {
                if (key.includes('imagen')) {
                    if (registro[key].slice(-3) === 'S/R') { // Validar si los últimos 3 caracteres son 'S/R'
                        const sinImagenLabel = document.createElement('label');
                        sinImagenLabel.textContent = 'Sin imagen';
                        sinImagenLabel.classList.add('text-muted', 'd-block', 'mt-1');
                        cardBodyDiv.appendChild(sinImagenLabel);
                    } else {
                        const imagen = document.createElement('img');
                        imagen.src = registro[key]; // Ajusta esto al nombre real de la propiedad de la imagen
                        imagen.alt = registro.nombre_seccion || 'Imagen'; // Texto alternativo para la imagen
                        imagen.classList.add('img-fluid', 'mt-1');
                        cardBodyDiv.appendChild(imagen);
                    }
                    break; // Suponiendo que solo hay una imagen por tarjeta
                }
            }

            cardDiv.appendChild(cardBodyDiv);
            tarjetaDiv.appendChild(cardDiv);
            contenedorTarjetas.appendChild(tarjetaDiv);
        });
    }

    function formatearNombreLabel(nombrePropiedad) {
        const palabras = nombrePropiedad.replace(/_/g, ' ').replace(/([A-Z])/g, ' $1').trim().split(' ');
        return palabras.map(palabra => palabra.charAt(0).toUpperCase() + palabra.slice(1)).join(' ');
    }

    function formatearIdNombre(nombrePropiedad) {
        const palabras = nombrePropiedad.split('_');
        return palabras.map(palabra => palabra.charAt(0).toUpperCase() + palabra.slice(1)).join('');
    }
</script>
</html>
