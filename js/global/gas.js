function registrarGas() {
    var id_vehiculo = $('#vehiculoAsignadoGas').val();
    if (!id_vehiculo) {
        Swal.fire({ icon: 'warning', title: 'Vehículo requerido', text: 'Selecciona un vehículo antes de registrar.', confirmButtonText: 'Aceptar' });
        return;
    }

    // Bloquear si no tiene checklist completo para este vehículo
    $.ajax({
        url: 'acciones_gas.php',
        method: 'POST',
        dataType: 'json',
        data: { accion: 'verificarChecklistGas', id_vehiculo: id_vehiculo },
        success: function (resp) {
            if (!resp.tiene) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Checklist pendiente',
                    text: 'Debes completar el checklist del vehículo antes de registrar una carga de gasolina.',
                    confirmButtonText: 'Entendido'
                });
                return;
            }
            _enviarRegistroGas(id_vehiculo);
        },
        error: function () {
            // Si la verificación falla, permitir continuar para no bloquear al usuario
            _enviarRegistroGas(id_vehiculo);
        }
    });
}

function _enviarRegistroGas(id_vehiculo) {
    var monto       = $('#monto').val();
    var pagos       = $('#pagos').val();
    var saldo       = $('#saldo').val();
    var fecha_carga = $('#fechaCarga').val();
    var km_actual   = $('#kmActualGas').val();

    $.ajax({
        url: 'acciones_gas.php',
        method: 'POST',
        dataType: 'json',
        data: { accion: 'registraGas', id_vehiculo: id_vehiculo, monto: monto, pagos: pagos, saldo: saldo, fecha_carga: fecha_carga, km_actual: km_actual },
        success: function (resp) {
            var saldoNum = parseFloat(saldo) || 0;

            var modalEl = document.getElementById('capturaGasModal');
            modalEl.addEventListener('hidden.bs.modal', function () {
                document.querySelectorAll('.modal-backdrop').forEach(function(el) { el.remove(); });
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';

                if (saldoNum <= 0) {
                    // Saldo agotado — ofrecer solicitar reposición
                    Swal.fire({
                        icon: 'success',
                        title: '¡Guardado!',
                        html: 'Carga registrada correctamente.<br><span class="text-danger fw-bold">Saldo agotado ($' + saldoNum.toFixed(2) + ')</span>. ¿Deseas solicitar reposición de crédito?',
                        showCancelButton: true,
                        confirmButtonText: '<i class="fas fa-paper-plane me-1"></i> Solicitar reposición',
                        cancelButtonText: 'No por ahora',
                        confirmButtonColor: '#050D9E'
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            solicitarReposicion(id_vehiculo, saldoNum, '');
                        }
                    });
                } else if (saldoNum < 500) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Guardado!',
                        html: 'Carga registrada correctamente.<br><span class="text-warning fw-bold">Saldo bajo: $' + saldoNum.toFixed(2) + '</span>',
                        timer: 2500,
                        timerProgressBar: true
                    });
                } else {
                    Swal.fire({
                        title: '¡Guardado!',
                        text: 'Carga de gasolina registrada correctamente.',
                        icon: 'success',
                        timer: 1500,
                        timerProgressBar: true
                    });
                }

                $('#formCapturaGas')[0].reset();
            }, { once: true });

            bootstrap.Modal.getInstance(modalEl).hide();
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.error('Error al registrar la carga de gasolina', errorThrown);
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo registrar la carga de gasolina.' });
        }
    });
}

function calcularSaldo() {
    var monto = parseFloat($('#monto').val().replace(/[^0-9.-]+/g, ''));
    var pagos = parseFloat($('#pagos').val().replace(/[^0-9.-]+/g, ''));
    if (isNaN(monto)) monto = 0;
    if (isNaN(pagos)) pagos = 0;
    var saldo = monto - pagos;
    $('#saldo').val(saldo.toFixed(2));
}

function solicitarReposicion(id_vehiculo, saldo, placa) {
    var htmlMsg = placa
        ? 'Se notificará al encargado para el vehículo <strong>' + placa + '</strong>.'
        : 'Se notificará al encargado que necesitas reposición de crédito de gasolina.';
    if (saldo !== undefined && saldo !== '') {
        htmlMsg += '<br>Saldo actual: <strong>$' + parseFloat(saldo).toFixed(2) + '</strong>';
    }

    Swal.fire({
        title: '¿Solicitar reposición?',
        html: htmlMsg,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-paper-plane me-1"></i> Enviar solicitud',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#050D9E'
    }).then(function (result) {
        if (!result.isConfirmed) return;
        $.ajax({
            url: 'acciones_gas.php',
            method: 'POST',
            dataType: 'json',
            data: { accion: 'solicitarReposicionGas', id_vehiculo: id_vehiculo || 0, saldo: saldo || 0 },
            success: function (resp) {
                Swal.fire({
                    icon: resp.status === 'success' ? 'success' : 'error',
                    title: resp.status === 'success' ? '¡Enviado!' : 'Error',
                    text: resp.message,
                    timer: 2500,
                    showConfirmButton: false
                });
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo enviar la solicitud.' });
            }
        });
    });
}
