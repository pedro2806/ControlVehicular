function registrarGas() {
    var accion = "registraGas";
    var id_vehiculo = $('#vehiculoAsignadoGas').val();
    var monto = $('#monto').val();
    var pagos = $('#pagos').val();
    var saldo = $('#saldo').val();
    var fecha_carga = $('#fechaCarga').val();
    var km_actual = $('#kmActualGas').val();

    $.ajax({
        url: 'acciones_gas.php',
        method: 'POST',
        dataType: 'json',
        data: { accion: accion, id_vehiculo: id_vehiculo, monto: monto, pagos: pagos, saldo: saldo, fecha_carga: fecha_carga, km_actual: km_actual },
        success: function (Registros) {
            Swal.fire({
                title: "¡Guardado!",
                text: "Carga de gasolina registrada correctamente.",
                icon: "success",
                timer: 1000,
                timerProgressBar: true
            }).then(function () {
                $('#formCapturaGas')[0].reset();
                bootstrap.Modal.getInstance(document.getElementById('capturaGasModal')).hide();
            });
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.error('Error al registrar la carga de gasolina', errorThrown);
        }
    });
}

function calcularSaldo() {
    var monto = parseFloat($('#monto').val().replace(/[^0-9.-]+/g, ""));
    var pagos = parseFloat($('#pagos').val().replace(/[^0-9.-]+/g, ""));
    if (isNaN(monto)) monto = 0;
    if (isNaN(pagos)) pagos = 0;
    var saldo = monto - pagos;
    $('#saldo').val(saldo.toFixed(2));
}
