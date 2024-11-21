/**
 * APP
 */

// Definir el helper personalizado en Handlebars
Handlebars.registerHelper('select', function (value, options) {
    return options.fn(this).replace(new RegExp(' value="' + value + '"'), '$& selected="selected"');
});

Handlebars.registerHelper('selectOptions', function (options, selected) {
    var html = `<option value="" selected disabled>Selecciona una opcion</option>`;

    // Verificar si options es undefined, null, o no es un array
    if (!Array.isArray(options)) {
        options = []; // Si no es un array, inicializarlo como un array vacío
    }

    options.forEach(function (option) {
        var isSelected = option.id === selected ? 'selected' : '';
        html += '<option value="' + option.id + '" ' + isSelected + '>' + option.name + '</option>';
    });

    return new Handlebars.SafeString(html); // Usar SafeString para prevenir la escapada de HTML
});

Handlebars.registerHelper('ifCond', function (v1, operator, v2, options) {
    switch (operator) {
        case '==':
            return v1 == v2 ? options.fn(this) : options.inverse(this);
        case '===':
            return v1 === v2 ? options.fn(this) : options.inverse(this);
        case '!=':
            return v1 != v2 ? options.fn(this) : options.inverse(this);
        case '!==':
            return v1 !== v2 ? options.fn(this) : options.inverse(this);
        case '<':
            return v1 < v2 ? options.fn(this) : options.inverse(this);
        case '<=':
            return v1 <= v2 ? options.fn(this) : options.inverse(this);
        case '>':
            return v1 > v2 ? options.fn(this) : options.inverse(this);
        case '>=':
            return v1 >= v2 ? options.fn(this) : options.inverse(this);
        case '&&':
            return v1 && v2 ? options.fn(this) : options.inverse(this);
        case '||':
            return v1 || v2 ? options.fn(this) : options.inverse(this);
        default:
            return options.inverse(this);
    }
});

$(document).ready(function () {
    // Obtener la URL actual
    var currentUrl = window.location.href;

    // Buscar todos los enlaces del menú
    $('.main-menu .side-menu__item').each(function () {
        var linkUrl = $(this).attr('href'); // URL del enlace del menú

        // Comprobar si la URL actual contiene la URL del enlace
        if (currentUrl.includes(linkUrl)) {
            // Remover la clase 'active' de otros elementos (por seguridad)
            $('.main-menu .side-menu__item').removeClass('active');
            // Agregar la clase 'active' al enlace actual
            $(this).addClass('active');
        }
    });
});

function initSelect2() {
    $('.select2').select2({
        theme: 'bootstrap-5',
        placeholder: 'Seleccione una opción'
    });
}

/**
 * Establece la página activa en la navegación.
 *
 * @param {string} page - El nombre de la página a activar.
 */
function SetActivePage(page) {
    $(`.nav-item[data-page=${page}]`).addClass('active');
}

/**
 * Muestra un diálogo de confirmación usando SweetAlert.
 *
 * @param {string} title - El título del diálogo.
 * @param {string} text - El texto del diálogo.
 * @returns {Promise} - Una promesa que se resuelve según la respuesta del usuario.
 */
function confirm(title, text) {
    return Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Continuar',
        cancelButtonText: 'Cancelar'
    });
}

/**
 * Muestra un mensaje tipo Toast usando SweetAlert.
 *
 * @param {string} title - El título del mensaje.
 * @param {string} icon - El icono del mensaje (success, error, warning, info, question).
 * @param {number} [duration=3000] - Duración en milisegundos del Toast. Por defecto es 3000 ms.
 * @param {boolean} [showCloseButton=false] - Si debe mostrar el botón de cerrar.
 */
function showToast(title, icon, duration = 3000, showCloseButton = false) {
    Swal.fire({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: duration,
        timerProgressBar: true,
        title: title,
        icon: icon,
        showCloseButton: showCloseButton,
        didOpen: toast => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });
}

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

/**
 * Realiza una llamada AJAX con manejo de token CSRF y redireccionamiento.
 *
 * @param {Object} options - Las opciones para la llamada AJAX.
 * @returns {Promise} - Una promesa que se resuelve con la respuesta del servidor.
 */
async function ajaxCall(options) {
    // Añadir el token CSRF a las opciones de datos antes de hacer la llamada AJAX
    const token = $('input[name="__RequestVerificationToken"]').val();
    if (typeof token !== 'undefined') {
        options.data = options.data || {};
        if (typeof options.data === 'string') {
            if (options.data.indexOf('__RequestVerificationToken=') === -1) {
                options.data += (options.data.length > 0 ? '&' : '') + '__RequestVerificationToken=' + encodeURIComponent(token);
            }
        } else if (typeof options.data === 'object') {
            options.data.__RequestVerificationToken = token;
        }
    }

    try {
        const response = await $.ajax(options);

        // Manejo del redireccionamiento 302
        if (response.status === 302) {
            window.location.reload();
        }

        if (options.success) {
            options.success(response);
        }
        return response; // Retorna la respuesta para uso adicional si es necesario.
    } catch (xhr) {
        if (xhr.status === 401 || xhr.getResponseHeader('Location')) {
            // Manejar la expiración de la sesión
            await swal.fire('Sesión Expirada', 'Tu sesión ha expirado. Por favor, vuelve a iniciar sesión.', 'warning');
            window.location.href = `${Server}Account/Login`;
        } else if (options.error) {
            options.error(xhr);
        } else {
            // Log general para otros errores no manejados específicamente
            console.error('Error en la llamada Ajax:', xhr.statusText);
            throw xhr; // Re-lanzar el error si necesitas que sea manejado más arriba en la cadena de promesas
        }
        throw xhr; // Esto permite que los errores sean manejados también por quien llama a ajaxCall si es necesario
    }
}

/**
 * Establece la opción seleccionada en un elemento select.
 *
 * @param {string} selectId - El ID del elemento select.
 * @param {string} value - El valor de la opción a seleccionar.
 */
function setSelectOption(selectId, value) {
    const selectElement = document.getElementById(selectId);
    if (selectElement) {
        // Buscar la opción correspondiente y establecerla como seleccionada
        for (let i = 0; i < selectElement.options.length; i++) {
            if (selectElement.options[i].value === value) {
                selectElement.selectedIndex = i;
                break;
            }
        }
    } else {
        console.error(`Elemento con id ${selectId} no encontrado.`);
    }
}

/**
 * Inicializa los tooltips en la página.
 */
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Inicializa los popovers en la página.
 */
function initializePopovers() {
    const popoverTriggerList = document.querySelectorAll('[title]');
    popoverTriggerList.forEach(function (popoverTriggerEl) {
        new bootstrap.Popover(popoverTriggerEl);
    });
}

/**
 * Genera opciones HTML para un elemento select a partir de un array de objetos.
 *
 * @param {Array} options - Array de objetos con propiedades: text, value, y selected.
 * @returns {string} - Cadena de elementos HTML <option>.
 */
function crearOpcionesSelect(options) {
    // Validar que la entrada sea un array
    if (!Array.isArray(options)) {
        throw new TypeError('Se esperaba un array de opciones');
    }

    // Validar cada objeto de opción
    options.forEach(option => {
        if (typeof option !== 'object' || option === null) {
            throw new TypeError('Cada opción debe ser un objeto no nulo');
        }
        if (!('text' in option)) {
            throw new Error('Cada opción debe tener una propiedad "text"');
        }
        if (!('value' in option)) {
            throw new Error('Cada opción debe tener una propiedad "value"');
        }
        if (!('selected' in option)) {
            option.selected = false; // Por defecto, false si no está proporcionado
        }
    });

    // Crear elementos HTML de opción
    const elementosOpcion = options.map(option => {
        const { text, value, selected } = option;
        const atributoSeleccionado = selected ? ' selected' : '';
        return `<option value="${escaparHTML(value)}"${atributoSeleccionado}>${escaparHTML(text)}</option>`;
    });

    // Unir y retornar los elementos de opción como una sola cadena
    return elementosOpcion.join('');
}

/**
 * Escapa los caracteres especiales en una cadena para prevenir ataques XSS.
 *
 * @param {string} str - La cadena a escapar.
 * @returns {string} - La cadena escapada.
 */
function escaparHTML(str) {
    const mapaEscape = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return str.replace(/[&<>"']/g, char => mapaEscape[char]);
}

/**
 * Bloquea o desbloquea todos los inputs, selects y botones de un formulario.
 *
 * @param {Object} $form - El formulario a bloquear o desbloquear.
 * @param {boolean} showLoading - Indica si se debe mostrar la animación de carga.
 */
function loadingFormXHR($form, showLoading) {
    const disabled = showLoading;

    // Deshabilitar o habilitar todos los elementos select, input y button
    $form.find('select, input, button').attr('disabled', disabled);

    // Manejar los textareas de TinyMCE
    if (typeof tinymce !== 'undefined') {
        const containerTinyMCE = $form.find('.tox-tinymce');
        if (disabled) {
            containerTinyMCE.addClass('loading');
        } else {
            containerTinyMCE.removeClass('loading');
        }
    }

    // Manejar el botón de submit por separado
    const $submitButton = $form.find('button[type="submit"]');

    if (disabled) {
        $submitButton.addClass('disabled');
        // Guardar el HTML original del botón
        $submitButton.data('original-html', $submitButton.html());
        // Cambiar el HTML del botón para mostrar el mensaje de carga y el spinner
        $submitButton.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ...espere...');
    } else {
        $submitButton.removeClass('disabled');
        // Restaurar el HTML original del botón
        $submitButton.html($submitButton.data('original-html'));
    }
}

/**SPARIC */
const scrollToTop = document.querySelector('.scrollToTop');
const $rootElement = document.documentElement;
const $body = document.body;
window.onscroll = () => {
    const scrollTop = window.scrollY || window.pageYOffset;
    const clientHt = $rootElement.scrollHeight - $rootElement.clientHeight;
    if (window.scrollY > 100) {
        scrollToTop.style.display = 'flex';
    } else {
        scrollToTop.style.display = 'none';
    }
};
scrollToTop.onclick = () => {
    window.scrollTo(0, 0);
};
