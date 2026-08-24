/* =========================================================
   MESS Design System — comportamiento del shell
   Sidebar toggle + theme toggle (portado de Tickets BI).
   Reemplaza a js/sb-admin-2.min.js.
   Requiere jQuery cargado antes.
   ========================================================= */
$(function () {
    /* ===== Sidebar toggle (estilo SB Admin 2) ===== */
    var ANCHO_MOVIL = 768;

    function esMovil() { return $(window).width() < ANCHO_MOVIL; }

    // Backdrop del menú en móvil. Se inyecta aquí para que valga en todas las
    // vistas sin tocar el markup de cada una.
    var $backdrop = $('<div class="sidebar-backdrop"></div>').appendTo('body');

    function pintarBackdrop() {
        $('body').toggleClass('sidebar-abierto', !$('.sidebar').hasClass('toggled'));
    }

    function guardarPreferencia() {
        // La preferencia es solo de escritorio. En móvil el menú siempre arranca
        // cerrado; persistir ese estado dejaría el sidebar colapsado al volver
        // a una pantalla grande.
        if (esMovil()) return;
        try { localStorage.setItem('sidebarToggled', $('.sidebar').hasClass('toggled')); } catch (e) {}
    }

    function alternarSidebar() {
        $('.sidebar').toggleClass('toggled');
        pintarBackdrop();
        guardarPreferencia();
    }

    function cerrarSidebar() {
        $('.sidebar').addClass('toggled');
        pintarBackdrop();
        guardarPreferencia();
    }

    // Toggle del pie del sidebar y de la topbar
    $(document).on('click', '#sidebarToggle, #sidebarToggleTop', function (e) {
        e.preventDefault();
        alternarSidebar();
    });

    // Salidas del menú abierto en móvil: botón de cerrar, tocar el backdrop y
    // Escape. El toggle de la topbar no sirve ahí porque el sidebar lo tapa.
    $(document).on('click', '#sidebarCerrarMovil', function (e) {
        e.preventDefault();
        cerrarSidebar();
    });
    $backdrop.on('click', cerrarSidebar);
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && !$('.sidebar').hasClass('toggled') && esMovil()) cerrarSidebar();
    });

    // Auto-cerrar en mobile después de navegar
    if (esMovil()) {
        $('.sidebar').addClass('toggled');
        $(document).on('click', '.sidebar .nav-link:not([data-bs-toggle])', function () {
            if (esMovil()) cerrarSidebar();
        });
    } else if (localStorage.getItem('sidebarToggled') === 'true') {
        $('.sidebar').addClass('toggled');
    }
    pintarBackdrop();

    // Al girar el teléfono o pasar a escritorio, el backdrop no debe quedarse.
    $(window).on('resize', pintarBackdrop);

    /* ===== Theme toggle (MESS) =====
       Clave unificada 'mess-theme': el tema se hereda entre sistemas MESS. */
    function applyTheme(theme) {
        var $icon = $('#themeToggle i');
        if (theme === 'dark') {
            document.body.classList.add('theme-dark');
            $icon.removeClass('fa-moon').addClass('fa-sun');
        } else {
            document.body.classList.remove('theme-dark');
            $icon.removeClass('fa-sun').addClass('fa-moon');
        }
        try { localStorage.setItem('mess-theme', theme); } catch (e) {}
        document.dispatchEvent(new CustomEvent('mess:themechange', { detail: { theme: theme } }));
    }

    applyTheme(document.body.classList.contains('theme-dark') ? 'dark' : 'light');

    $('#themeToggle').on('click', function () {
        var next = document.body.classList.contains('theme-dark') ? 'light' : 'dark';
        applyTheme(next);
    });

    /* ===== Scroll to top (reemplaza sb-admin-2.js sin jquery-easing) ===== */
    $(window).on('scroll', function () {
        if ($(this).scrollTop() > 100) {
            $('.scroll-to-top').fadeIn();
        } else {
            $('.scroll-to-top').fadeOut();
        }
    });
    $(document).on('click', 'a.scroll-to-top', function (e) {
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});

/* ===== Helpers globales: tokens MESS desde CSS ===== */
function messColor(token) {
    var v = getComputedStyle(document.body).getPropertyValue('--' + token);
    return (v || '').trim();
}

function messRgba(token, alpha) {
    var hex = messColor(token);
    if (!hex || hex.charAt(0) !== '#') return hex;
    var h = hex.replace('#', '');
    if (h.length === 3) { h = h[0]+h[0]+h[1]+h[1]+h[2]+h[2]; }
    var r = parseInt(h.substring(0, 2), 16);
    var g = parseInt(h.substring(2, 4), 16);
    var b = parseInt(h.substring(4, 6), 16);
    return 'rgba(' + r + ',' + g + ',' + b + ',' + alpha + ')';
}
