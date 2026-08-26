/**
 * Deja el modal listo cada vez que se abre.
 *
 * Hace falta porque el guardado terminaba en form.reset(), que devuelve los campos a
 * sus valores del HTML: #fechaCarga vuelve a value="" y pierde la fecha que se le pone
 * por JS al cargar la página, que solo corre una vez. Resultado: a la SEGUNDA carga de
 * gas la fecha y el km salían vacíos.
 *
 * Además refresca monto/km/saldo desde el servidor si ya hay vehículo elegido: tras
 * registrar una carga el saldo cambió, así que los valores que quedaron en pantalla ya
 * no sirven.
 */
function prepararModalGas() {
    var ahora = new Date();
    // toISOString() es UTC y correría la hora; hay que compensar el offset local para
    // que datetime-local muestre la hora real del usuario.
    var local = new Date(ahora.getTime() - ahora.getTimezoneOffset() * 60000);
    $('#fechaCarga').val(local.toISOString().slice(0, 16));

    var id_vehiculo = $('#vehiculoAsignadoGas').val();
    if (id_vehiculo) {
        verPlaca('vehiculoAsignadoGas', 'kmActualGas', 'saldo');
    } else {
        $('#monto, #kmActualGas, #saldo').val('');
    }
    $('#pagos').val('');
    // Cliente y OT se limpian siempre: son de la carga que se está capturando, no del
    // vehículo, así que arrastrarlos de una captura a la siguiente sería un error.
    $('#clienteGas, #idClienteGas, #otGas').val('');
    $('#listaClientesGas').empty();
}

/**
 * Autocompletado de clientes del modal de gasolina.
 *
 * Se busca en el servidor porque son 8,376 clientes: mandarlos todos al navegador para
 * filtrarlos ahí pesaría de más, sobre todo en celular. El <datalist> se repuebla en cada
 * búsqueda y el id real vive en un hidden, porque un datalist solo devuelve el texto.
 */
function buscarClientesGas() {
    var q = $('#clienteGas').val().trim();

    // El id guardado deja de ser válido en cuanto el texto cambia: si no se limpia, editar
    // el nombre después de elegir dejaría guardado el cliente anterior.
    $('#idClienteGas').val('');

    if (q.length < 2) { $('#listaClientesGas').empty(); return; }

    $.ajax({
        url: 'acciones_gas.php',
        method: 'POST',
        dataType: 'json',
        data: { accion: 'buscarClientes', q: q },
        success: function (lista) {
            var $dl = $('#listaClientesGas').empty();
            if (!Array.isArray(lista)) return;
            lista.forEach(function (c) {
                // El value del option es lo que el navegador escribe en el input, así que
                // debe ser el texto que luego se busca para recuperar el id.
                var etiqueta = c.CLIENTE + (c.CIUDAD ? ' — ' + c.CIUDAD : '');
                $dl.append($('<option>').attr('value', etiqueta).attr('data-id', c.IDCLTE));
            });
            // Si lo tecleado ya coincide exactamente con una opción (el usuario la eligió
            // de la lista), se resuelve el id de una vez.
            resolverIdClienteGas();
        }
    });
}

/** Empareja el texto del input con una opción del datalist para sacar su IDCLTE. */
function resolverIdClienteGas() {
    var txt = $('#clienteGas').val().trim();
    var op = $('#listaClientesGas option').filter(function () { return this.value === txt; }).first();
    $('#idClienteGas').val(op.length ? op.attr('data-id') : '');
}

$(function () {
    var temporizador = null;
    // Debounce: sin esto se dispara una consulta por tecla contra una tabla de 8,376 filas.
    $(document).on('input', '#clienteGas', function () {
        clearTimeout(temporizador);
        temporizador = setTimeout(buscarClientesGas, 250);
    });
    // 'change' en un input con datalist se dispara al elegir de la lista.
    $(document).on('change', '#clienteGas', resolverIdClienteGas);
});

/**
 * Impide capturar decimales en el kilometraje.
 *
 * La columna km_actual es INT: un decimal no daba error, MySQL lo redondeaba en
 * silencio y el usuario nunca se enteraba de que se guardó otro número.
 *
 * Se bloquea la TECLA en vez de limpiar el valor después, porque en un input
 * type="number" el valor intermedio "45210." es inválido y el navegador devuelve
 * cadena vacía: al limpiarlo se borraría todo lo que llevaba escrito. El caso de
 * pegado sí se corrige truncando, que ahí el valor sí llega completo.
 */
function soloKmEntero(input) {
    if (!input) return;
    input.addEventListener('keydown', function (e) {
        if (e.key === '.' || e.key === ',' || e.key === 'e' || e.key === 'E') e.preventDefault();
    });
    // El pegado se intercepta ANTES de que el valor llegue al campo: con coma decimal
    // ("45210,9") el input type="number" considera el valor inválido y devuelve cadena
    // vacía, así que después ya no queda nada que truncar.
    input.addEventListener('paste', function (e) {
        var texto = ((e.clipboardData || window.clipboardData) || {}).getData
            ? (e.clipboardData || window.clipboardData).getData('text') : '';
        if (texto && /[.,]/.test(texto)) {
            e.preventDefault();
            input.value = texto.split(/[.,]/)[0].replace(/[^\d]/g, '');
        }
    });
    input.addEventListener('input', function () {
        var v = String(input.value);
        if (v !== '' && /[.,]/.test(v)) input.value = v.split(/[.,]/)[0];
    });
}

$(function () {
    var modalGas = document.getElementById('capturaGasModal');
    if (modalGas) modalGas.addEventListener('show.bs.modal', prepararModalGas);
    soloKmEntero(document.getElementById('kmActualGas'));
});

// Cerrojo de envío en curso. Deshabilitar el botón cubre el doble clic, pero no una
// segunda llamada por teclado o desde otra pantalla (qr_vehiculo.php abre este mismo
// modal), y cada llamada de más es una carga de gasolina duplicada en la BD.
var _guardandoGas = false;

function registrarGas() {
    if (_guardandoGas) return;

    var id_vehiculo = $('#vehiculoAsignadoGas').val();
    if (!id_vehiculo) {
        Swal.fire({ icon: 'warning', title: 'Vehículo requerido', text: 'Selecciona un vehículo antes de registrar.', confirmButtonText: 'Aceptar' });
        return;
    }

    // El botón es type="button" y no dispara submit, así que los `required` del HTML
    // nunca se evalúan: sin esto los campos vacíos se enviaban en silencio. Se nombra
    // cuál falta en vez de un "faltan campos" genérico.
    var faltantes = [];
    if ($('#pagos').val() === '' || isNaN(parseFloat($('#pagos').val()))) faltantes.push('Pagos');
    if (!$('#fechaCarga').val())                                          faltantes.push('Fecha de carga');
    if ($('#kmActualGas').val() === '')                                   faltantes.push('Km actual');

    if (faltantes.length) {
        Swal.fire({
            icon: 'warning',
            title: faltantes.length === 1 ? 'Falta un campo' : 'Faltan campos',
            html: 'Completa antes de registrar:<br><b>' + faltantes.join('</b><br><b>') + '</b>',
            confirmButtonText: 'Aceptar'
        });
        return;
    }

    if (parseFloat($('#kmActualGas').val()) < 0) {
        Swal.fire({ icon: 'warning', title: 'Km inválido', text: 'El kilometraje no puede ser negativo.', confirmButtonText: 'Aceptar' });
        return;
    }

    botonGuardando(true);

    // Bloquear si no tiene checklist completo para este vehículo
    $.ajax({
        url: 'acciones_gas.php',
        method: 'POST',
        dataType: 'json',
        data: { accion: 'verificarChecklistGas', id_vehiculo: id_vehiculo },
        success: function (resp) {
            if (!resp.tiene) {
                botonGuardando(false);
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
    // Cliente y OT/OV son opcionales: si el usuario escribió en el campo de cliente pero
    // no eligió uno de la lista, el hidden queda vacío y se manda sin cliente en vez de
    // guardar un id inventado.
    var id_cliente  = $('#idClienteGas').val() || '';
    var ot          = $('#otGas').val() || '';

    $.ajax({
        url: 'acciones_gas.php',
        method: 'POST',
        dataType: 'json',
        data: { accion: 'registraGas', id_vehiculo: id_vehiculo, monto: monto, pagos: pagos, saldo: saldo,
                fecha_carga: fecha_carga, km_actual: km_actual, id_cliente: id_cliente, ot: ot },
        success: function (resp) {
            // jQuery entra aquí con cualquier HTTP 200, incluso cuando el servidor
            // respondió {"status":"error"}. Sin esta comprobación el modal se cerraba
            // diciendo "¡Guardado!" aunque el INSERT hubiera fallado.
            if (!resp || resp.status !== 'success') {
                botonGuardando(false);
                Swal.fire({
                    icon: 'error',
                    title: 'No se registró la carga',
                    text: (resp && resp.message) ? resp.message : 'El servidor rechazó el registro.',
                    confirmButtonText: 'Aceptar'
                });
                return;
            }

            var saldoNum = parseFloat(saldo) || 0;

            var modalEl = document.getElementById('capturaGasModal');
            var finalizar = function () {
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

                // Solo se limpia el importe pagado. Antes iba un form.reset() completo,
                // que borraba también el vehículo y la fecha y dejaba el modal inservible
                // para una segunda carga. Lo demás lo repone prepararModalGas() al abrir.
                $('#pagos').val('');
                botonGuardando(false);
            };

            // El cierre del modal no puede ser la única vía para liberar el botón: si el
            // usuario cerró el modal a mano mientras la petición iba en vuelo, el evento
            // 'hidden' ya pasó y nunca vuelve a dispararse, dejando el guardado trabado
            // hasta recargar. Por eso se comprueba si sigue visible.
            if (modalEl.classList.contains('show')) {
                modalEl.addEventListener('hidden.bs.modal', finalizar, { once: true });
                // getInstance() devuelve null si el modal nunca se inicializó por JS, y
                // el .hide() reventaba dejando el modal abierto y el botón bloqueado.
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            } else {
                finalizar();
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.error('Error al registrar la carga de gasolina', errorThrown);
            botonGuardando(false);
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo registrar la carga de gasolina.' });
        }
    });
}

/**
 * Estado del botón Guardar durante el envío.
 *
 * Sin esto, el botón no daba señal de estar haciendo algo mientras iban las dos
 * peticiones (verificar checklist + registrar), y volver a pulsarlo registraba la
 * carga dos veces.
 */
function botonGuardando(activo) {
    _guardandoGas = activo;
    var btn = document.querySelector('#capturaGasModal .modal-footer .btn-primary');
    if (!btn) return;
    if (activo) {
        btn.dataset.textoOriginal = btn.dataset.textoOriginal || btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';
    } else {
        btn.disabled = false;
        if (btn.dataset.textoOriginal) btn.innerHTML = btn.dataset.textoOriginal;
    }
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
