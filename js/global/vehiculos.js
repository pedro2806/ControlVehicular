function cargarVehiculos(selectVehiculo) {
    $.ajax({
        url: 'acciones_kilometraje.php',
        method: 'POST',
        dataType: 'json',
        data: { accion: 'CargarVehiculos' },
        success: function (data) {
            var select = $('#' + selectVehiculo);
            select.empty();
            select.append('<option value="">Seleccione un vehículo</option>');
            if (!Array.isArray(data)) return;
            data.forEach(function (vehiculo) {
                if (vehiculo.id_prestamo !== null && vehiculo.id_prestamo !== '') {
                    $('#PidPrestamo').val(vehiculo.id_vehiculo + ',' + vehiculo.id_prestamo);
                    select.append('<option value="' + vehiculo.id_vehiculo + '" style="background-color: #ffeeba;" selected>PRESTAMO - ' + vehiculo.placa + '-' + vehiculo.modelo + '-  ' + vehiculo.estatus + '</option>');
                } else {
                    select.append('<option value="' + vehiculo.id_vehiculo + '">' + vehiculo.placa + '-' + vehiculo.modelo + '</option>');
                }
            });
            verPlaca('vehiculoAsignado', 'kmActual');
        },
        error: function () {
            console.error('Error al cargar los vehículos');
        }
    });
}

function verPlaca(selectVehiculo, inputKm, saldo) {
    var IDvehiculoAsignado = document.getElementById(selectVehiculo).value;
    if (IDvehiculoAsignado) {
        var vehiculoAsignado = document.getElementById(selectVehiculo).options[document.getElementById(selectVehiculo).selectedIndex].text;
        var primerValor = (vehiculoAsignado.split('-')[1] || '').trim();
        primerValor = primerValor.replace("Seleccione un vehículo", "");
        document.getElementById("placaElegida").value = primerValor;

        $.ajax({
            url: 'acciones_kilometraje.php',
            method: 'POST',
            dataType: 'json',
            data: { accion: 'tomaKm', IDvehiculoAsignado: IDvehiculoAsignado },
            success: function (Registros) {
                if (Registros && Registros[0] && Registros[0].kmMax !== undefined) {
                    $('#' + inputKm).val(Registros[0].kmMax);
                    $('#gasActual').val(Registros[0].gasolina_actual);
                    $('#monto').val(Registros[0].saldo);
                }
            }
        });
    }
}

function verPlacaCheckOut() {
    var vehiculoAsignado = document.getElementById("vehiculoAsignado").textContent;
    if (vehiculoAsignado) {
        var primerValor = vehiculoAsignado.split('-')[0].trim();
        primerValor = primerValor.replace("Seleccione un vehículo", "");
        document.getElementById("placaElegida").value = primerValor;
    }
}
