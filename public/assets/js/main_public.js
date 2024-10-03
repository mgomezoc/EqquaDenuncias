$(document).ready(function () {
    // Inicialización de AOS (Animación)
    AOS.init({
        duration: 800, // Duración de la animación
        easing: 'ease-in-out', // Efecto de transición
        once: true, // La animación ocurre solo una vez mientras se desplaza
        disable: 'mobile' // Deshabilitar en dispositivos móviles para mejorar el rendimiento
    });

    // Función para obtener la URL actual y marcar el enlace activo
    function setActiveMenu() {
        var path = window.location.pathname; // Obtener la ruta actual (path relativo)

        // Iterar sobre cada enlace del menú
        $('.navbar-nav .nav-link').each(function () {
            // Crear un elemento 'a' temporal para extraer el path del 'href'
            var href = new URL($(this).attr('href'), window.location.origin).pathname;

            // Normalizamos ambos valores eliminando barras finales
            if (path.endsWith('/')) {
                path = path.slice(0, -1);
            }
            if (href.endsWith('/')) {
                href = href.slice(0, -1);
            }

            // Comparar si el path actual coincide exactamente con el 'href'
            if (path === href) {
                $(this).addClass('active');
            } else {
                $(this).removeClass('active');
            }
        });
    }

    // Llamar a la función para establecer el menú activo
    setActiveMenu();

    // Función para serializar el formulario
    $.fn.serializeObject = function () {
        var o = {};
        var a = this.serializeArray();
        $.each(a, function () {
            if (o[this.name] !== undefined) {
                if (!o[this.name].push) {
                    o[this.name] = [o[this.name]];
                }
                o[this.name].push(this.value || '');
            } else {
                o[this.name] = this.value || '';
            }
        });
        return o;
    };
});
