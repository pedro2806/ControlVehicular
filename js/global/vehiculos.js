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

function renderCardVehiculo(idVeh, placa, modelo, abierto) {
    var titulo = (placa || '') + (modelo ? ' - ' + modelo : '');
    var shown   = abierto ? ' show'    : '';
    var btnCls  = abierto ? ''          : ' collapsed';
    var spinner = '<div class="text-center text-muted p-3"><i class="fas fa-spinner fa-spin"></i></div>';

    var placaT = (placa || '').trim();
    var tenenciaBtn = placaT
        ? '<a href="TENENCIAS_2026/' + encodeURIComponent(placaT) + '.pdf" target="_blank" rel="noopener noreferrer" '
        +     'class="btn btn-info btn-sm text-white mr-2 flex-shrink-0" title="Tenencia 2026 de ' + placaT + '" '
        +     'onclick="event.stopPropagation();">'
        +   '<i class="fas fa-file-pdf mr-1"></i>Tenencia 2026'
        + '</a>'
        : '';
    var polizaBtn = placaT
        ? '<a href="poliza.php?placa=' + encodeURIComponent(placaT) + '" target="_blank" rel="noopener noreferrer" '
        +     'class="btn btn-success btn-sm text-white mr-2 flex-shrink-0" title="Póliza 2026 de ' + placaT + '" '
        +     'onclick="event.stopPropagation();">'
        +   '<i class="fas fa-file-contract mr-1"></i>Póliza 2026'
        + '</a>'
        : '';

    return ''
        + '<div class="card shadow-sm mb-3" data-vehiculo="' + idVeh + '">'
        +   '<div class="card-header p-0 d-flex align-items-center" id="headingV-' + idVeh + '" style="background: var(--card-soft); border-color: var(--border);">'
        +     '<button class="btn btn-link flex-grow-1 text-left py-2 px-3 d-flex align-items-center justify-content-between font-weight-bold' + btnCls + '" '
        +             'style="color: var(--text); text-decoration:none;" '
        +             'type="button" data-bs-toggle="collapse" data-bs-target="#collapseV-' + idVeh + '" aria-expanded="' + (abierto ? 'true' : 'false') + '" aria-controls="collapseV-' + idVeh + '">'
        +       '<span><i class="fas fa-car mr-2"></i>' + titulo + '</span>'
        +       '<i class="fas fa-chevron-down"></i>'
        +     '</button>'
        +     tenenciaBtn
        +     polizaBtn
        +   '</div>'
        +   '<div id="collapseV-' + idVeh + '" class="collapse' + shown + '" aria-labelledby="headingV-' + idVeh + '" data-parent="#accordionVehiculos">'
        +     '<div class="card-body p-2">'
        +       '<div class="row no-gutters">'
        +         '<div class="col-md-6 px-1 mb-2">'
        +           '<div class="card h-100">'
        +             '<div class="card-header py-2 d-flex align-items-center justify-content-between" style="background: var(--card-soft); border-color: var(--border);">'
        +               '<h6 class="m-0 font-weight-bold small">Documentación</h6>'
        +               '<a href="documentacion?v=' + idVeh + '" target="_blank" class="btn btn-warning btn-sm" title="Actualizar Docs">'
        +                 '<i class="fas fa-folder-open"></i>'
        +               '</a>'
        +             '</div>'
        +             '<div class="card-body p-0" id="docsVeh-' + idVeh + '">' + spinner + '</div>'
        +           '</div>'
        +         '</div>'
        +         '<div class="col-md-6 px-1 mb-2">'
        +           '<div class="card h-100">'
        +             '<div class="card-header py-2 d-flex align-items-center justify-content-between" style="background: var(--card-soft); border-color: var(--border);">'
        +               '<h6 class="m-0 font-weight-bold small">Checklist</h6>'
        +               '<a href="checkVehiculo?v=' + idVeh + '" target="_blank" class="btn btn-warning btn-sm" title="Realizar Checklist">'
        +                 '<i class="fas fa-clipboard-check"></i>'
        +               '</a>'
        +             '</div>'
        +             '<div class="card-body p-0" id="chkVeh-' + idVeh + '">' + spinner + '</div>'
        +           '</div>'
        +         '</div>'
        +       '</div>'
        +     '</div>'
        +   '</div>'
        + '</div>';
}

// Renderiza solo la lista (sin envoltorio card) — la card y header se crean en renderCardVehiculo.
function renderListaChecklist(data) {
    var subareasOrden = [
        { campo: 'asientos',          label: 'Asientos' },
        { campo: 'espejos_ventanas',  label: 'Espejos y ventanas' },
        { campo: 'estereos_aire',     label: 'Estéreos y aire' },
        { campo: 'faros',             label: 'Faros' },
        { campo: 'golpes_exterior',   label: 'Golpes exterior' },
        { campo: 'limpiaparabrisas',  label: 'Limpiaparabrisas' },
        { campo: 'limpieza',          label: 'Limpieza' },
        { campo: 'llantas',           label: 'Llantas' },
        { campo: 'placas',            label: 'Placas' },
        { campo: 'puertas_llave',     label: 'Puertas y llave' }
    ];
    var subareas = (data.checklist && data.checklist.subareas) ? data.checklist.subareas : {};
    var html = '<ul class="list-group list-group-flush">';
    subareasOrden.forEach(function(s){
        var estado = subareas[s.campo] || 'no_revisado';
        var icono;
        if (estado === 'ok')        icono = '<i class="fas fa-check-circle text-success fa-fw mr-2"></i>';
        else if (estado === 'mal')  icono = '<i class="fas fa-times-circle text-danger fa-fw mr-2"></i>';
        else                        icono = '<i class="fas fa-minus-circle text-muted fa-fw mr-2"></i>';
        html += '<li class="list-group-item d-flex align-items-center py-2">' + icono + '<span class="small">' + s.label + '</span></li>';
    });
    html += '</ul>';
    return html;
}

function renderListaDocs(v) {
    var docs = [{
            campo: 'licencia',
            label: 'Licencia'
        },
        {
            campo: 'tarjeta_circulacion',
            label: 'T. Circulación'
        },
        {
            campo: 'refrendo_actual',
            label: 'Refrendo'
        },
        {
            campo: 'seguro_vehiculo',
            label: 'Seguro'
        },
        {
            campo: 'verificacion_vigente',
            label: 'Verificación'
        }
    ];
    if (!v.fecha_reg_doc) {
        return '<p class="text-muted small mb-0 p-3">Sin documentación registrada</p>';
    }
    var html = '<ul class="list-group list-group-flush">';
    docs.forEach(function(d) {
        var tiene = v[d.campo] && v[d.campo] !== 'S/R';
        var icono = tiene ?
            '<i class="fas fa-check-circle text-success fa-fw mr-2"></i>' :
            '<i class="fas fa-times-circle text-danger fa-fw mr-2"></i>';
        html += '<li class="list-group-item d-flex align-items-center py-2">' + icono + '<span>' + d.label + '</span></li>';
    });
    html += '</ul>';
    return html;
}

function evaluarValidaciones(data) {
    var total = 0, ok = 0;

    // Checklist (10 subáreas)
    var subareas = (data.checklist && data.checklist.subareas) ? data.checklist.subareas : {};
    ['asientos','espejos_ventanas','estereos_aire','faros','golpes_exterior',
        'limpiaparabrisas','limpieza','llantas','placas','puertas_llave'].forEach(function(k){
        total++;
        if (subareas[k] === 'ok') ok++;
    });

    // Mantenimiento (3 items: autorizado, sin pendientes, próximo al día).
    // Misma semántica que renderListaMantenimiento: REALIZADO cuenta como autorizado
    // y "sin fecha_proxi tras un REALIZADO" cuenta como al día.
    var mt = data.mantenimiento || null;
    total += 3;
    if (mt) {
        var vobo = (mt.VoBo_jefe || '').toUpperCase();
        var realizado = (vobo === 'REALIZADO');
        if (vobo === 'AUTORIZADO' || realizado) ok++;
        if (vobo && vobo !== 'PENDIENTE') ok++;
        if (mt.fecha_proxi) {
            var prox = new Date(mt.fecha_proxi);
            if (!isNaN(prox.getTime()) && prox >= new Date()) ok++;
        } else if (realizado) {
            ok++;
        }
    }

    return { ok: ok, total: total };
}

var vehiculosEstado = {};
var vehiculosDocsCargados = false;

function syncCookieNoEmpleado() {
    var v = getCookie('noEmpleadoL') || getCookie('noEmpleado');
    if (v) document.cookie = 'noEmpleado=' + encodeURIComponent(v) + '; path=/; SameSite=Lax';
}

function evaluarDocs(v) {
    var docs = ['licencia', 'tarjeta_circulacion', 'refrendo_actual', 'seguro_vehiculo', 'verificacion_vigente'];
    var total = docs.length;
    var ok = 0;
    if (v.fecha_reg_doc) {
        docs.forEach(function(c){
            if (v[c] && v[c] !== 'S/R') ok++;
        });
    }
    return { ok: ok, total: total };
}