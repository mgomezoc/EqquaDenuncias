/**
 * MAIN
 */

AOS.init({
    duration: 800, // Duración de la animación
    easing: 'ease-in-out', // Efecto de transición
    once: true, // La animación ocurre solo una vez mientras se desplaza
    disable: 'mobile' // Deshabilitar en dispositivos móviles para mejorar el rendimiento
});

/**
 * Serializa un formulario en un objeto.
 *
 * @returns {Object} - El objeto serializado del formulario.
 */
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
