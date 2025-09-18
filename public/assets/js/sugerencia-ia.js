// Archivo: public/assets/js/sugerencia-ia.js

class SugerenciaIA {
    constructor() {
        this.apiBase = '/api/denuncias';
        this.currentDenunciaId = null;
        this.currentSugerenciaId = null;
        this.init();
    }

    init() {
        // Escuchar cuando se cree una denuncia nueva
        document.addEventListener('denunciaGuardada', event => {
            if (event.detail && event.detail.id_denuncia) {
                this.currentDenunciaId = event.detail.id_denuncia;
                this.mostrarSeccionSugerencia();
                this.obtenerOGenerarSugerencia();
            }
        });
    }

    async obtenerOGenerarSugerencia() {
        try {
            this.mostrarCargando();

            // Primero intentar obtener sugerencia existente
            const response = await fetch(`${this.apiBase}/${this.currentDenunciaId}/sugerencia-ia`);
            const data = await response.json();

            if (data.success) {
                this.mostrarSugerencia(data.sugerencia);
            } else {
                // No existe, generar nueva
                await this.generarNuevaSugerencia();
            }
        } catch (error) {
            this.mostrarError();
            console.error('Error:', error);
        }
    }

    async generarNuevaSugerencia() {
        try {
            const response = await fetch(`${this.apiBase}/${this.currentDenunciaId}/sugerencia-ia`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.mostrarSugerencia(data);
                this.mostrarNotificacion('Sugerencia generada exitosamente', 'success');
            } else {
                this.mostrarError(data.message);
            }
        } catch (error) {
            this.mostrarError();
            console.error('Error:', error);
        }
    }

    mostrarSeccionSugerencia() {
        const section = document.getElementById('sugerencia-ia-section');
        if (section) {
            section.style.display = 'block';
        }
    }

    mostrarCargando() {
        const loading = document.getElementById('sugerencia-loading');
        const content = document.getElementById('sugerencia-content');
        const error = document.getElementById('sugerencia-error');

        if (loading) loading.style.display = 'block';
        if (content) content.style.display = 'none';
        if (error) error.style.display = 'none';
    }

    mostrarSugerencia(data) {
        const loading = document.getElementById('sugerencia-loading');
        const content = document.getElementById('sugerencia-content');
        const textContainer = document.querySelector('.sugerencia-text');

        this.currentSugerenciaId = data.id;

        if (loading) loading.style.display = 'none';
        if (content) content.style.display = 'block';

        if (textContainer) {
            textContainer.innerHTML = this.formatearTexto(data.sugerencia_generada || data.sugerencia);
        }

        // Mostrar botones de evaluación si no ha sido evaluada
        if (data.estado_sugerencia !== 'evaluada') {
            this.mostrarBotonesEvaluacion();
        }
    }

    mostrarError(mensaje = 'No se pudo generar la sugerencia') {
        const loading = document.getElementById('sugerencia-loading');
        const error = document.getElementById('sugerencia-error');

        if (loading) loading.style.display = 'none';
        if (error) {
            error.style.display = 'block';
            error.querySelector('span').textContent = mensaje;
        }
    }

    formatearTexto(texto) {
        return texto
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n\n/g, '</p><p>')
            .replace(/\n/g, '<br>')
            .replace(/^/, '<p>')
            .replace(/$/, '</p>');
    }

    mostrarBotonesEvaluacion() {
        const botones = document.getElementById('botones-evaluacion');
        if (botones) {
            botones.style.display = 'block';
        }
    }

    async evaluarSugerencia(puntuacion) {
        try {
            const response = await fetch('/api/denuncias/sugerencia-ia/evaluar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    id_sugerencia: this.currentSugerenciaId,
                    evaluacion: puntuacion
                })
            });

            const data = await response.json();

            if (data.success) {
                this.mostrarNotificacion('¡Gracias por evaluar!', 'success');
                this.ocultarBotonesEvaluacion();
            }
        } catch (error) {
            console.error('Error al evaluar:', error);
        }
    }

    ocultarBotonesEvaluacion() {
        const botones = document.getElementById('botones-evaluacion');
        if (botones) {
            botones.style.display = 'none';
        }
    }

    mostrarNotificacion(mensaje, tipo) {
        // Crear toast de notificación
        const toast = document.createElement('div');
        toast.className = `alert alert-${tipo} position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999;';
        toast.textContent = mensaje;

        document.body.appendChild(toast);

        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 3000);
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function () {
    window.sugerenciaIA = new SugerenciaIA();
});

// Funciones globales para los botones
function evaluarSugerencia(puntuacion) {
    if (window.sugerenciaIA) {
        window.sugerenciaIA.evaluarSugerencia(puntuacion);
    }
}

function regenerarSugerencia() {
    if (window.sugerenciaIA && confirm('¿Regenerar sugerencia?')) {
        window.sugerenciaIA.generarNuevaSugerencia();
    }
}
