<!-- Vista: app/Views/denuncias/partials/sugerencia_ia.php -->

<div id="sugerencia-ia-section" class="mt-4" style="display: none;">
    <div class="card border-primary">
        <div class="card-header bg-gradient-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-robot me-2"></i>
                    Sugerencia de Solución (Inteligencia Artificial)
                </h5>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-light btn-sm" onclick="regenerarSugerencia()" title="Regenerar sugerencia">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button type="button" class="btn btn-outline-light btn-sm" onclick="toggleSugerencia()" title="Minimizar/Expandir">
                        <i class="fas fa-chevron-up" id="toggle-icon"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body" id="sugerencia-content">
            <!-- Loading state -->
            <div id="sugerencia-loading" class="text-center py-3" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Generando sugerencia...</span>
                </div>
                <p class="mt-2 text-muted">Analizando la denuncia y generando sugerencia...</p>
            </div>

            <!-- Content -->
            <div id="sugerencia-text" style="display: none;">
                <div class="sugerencia-content-text"></div>
            </div>

            <!-- Error state -->
            <div id="sugerencia-error" class="alert alert-warning" style="display: none;">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <span>No se pudo generar la sugerencia automáticamente.</span>
                <button type="button" class="btn btn-sm btn-outline-warning ms-2" onclick="generarSugerenciaManual()">
                    Intentar nuevamente
                </button>
            </div>
        </div>

        <!-- Actions -->
        <div class="card-footer" id="sugerencia-actions" style="display: none;">
            <div class="row">
                <div class="col-md-8">
                    <h6 class="mb-2">¿Qué tan útil fue esta sugerencia?</h6>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-success btn-sm" onclick="evaluarSugerencia(5)">
                            <i class="fas fa-star"></i> Excelente
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="evaluarSugerencia(4)">
                            <i class="fas fa-thumbs-up"></i> Buena
                        </button>
                        <button type="button" class="btn btn-outline-warning btn-sm" onclick="evaluarSugerencia(3)">
                            <i class="fas fa-meh"></i> Regular
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="evaluarSugerencia(2)">
                            <i class="fas fa-thumbs-down"></i> Mala
                        </button>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <small class="text-muted">
                        <div id="sugerencia-metadata"></div>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .sugerencia-content-text {
        line-height: 1.7;
        font-size: 14px;
    }

    .sugerencia-content-text h6 {
        color: #495057;
        font-weight: 600;
        margin-top: 20px;
        margin-bottom: 10px;
        border-left: 3px solid #007bff;
        padding-left: 10px;
    }

    .sugerencia-content-text ul {
        margin-left: 20px;
        margin-bottom: 15px;
    }

    .sugerencia-content-text li {
        margin-bottom: 5px;
    }

    .btn-group .btn-sm {
        font-size: 12px;
        padding: 4px 8px;
    }

    @media (max-width: 768px) {
        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .btn-group .btn {
            width: 100%;
        }
    }

    /* Animaciones */
    .fade-in {
        animation: fadeIn 0.5s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<script>
    // Variables globales para manejar el estado
    let currentDenunciaId = <?= $denuncia['id'] ?? 'null' ?>;
    let currentSugerenciaId = null;
    let sugerenciaCollapsed = false;

    // Inicializar cuando el documento esté listo
    document.addEventListener('DOMContentLoaded', function() {
        if (currentDenunciaId) {
            obtenerSugerenciaExistente();
        }
    });

    /**
     * Obtiene sugerencia existente o genera una nueva
     */
    async function obtenerSugerenciaExistente() {
        try {
            showSugerenciaSection();
            showLoading();

            const response = await fetch(`/api/denuncias/${currentDenunciaId}/sugerencia-ia`);
            const data = await response.json();

            hideLoading();

            if (data.success) {
                mostrarSugerencia(data.sugerencia);
            } else {
                // No existe sugerencia, generar una nueva automáticamente
                await generarNuevaSugerencia();
            }

        } catch (error) {
            hideLoading();
            showError();
            console.error('Error al obtener sugerencia:', error);
        }
    }

    /**
     * Genera una nueva sugerencia
     */
    async function generarNuevaSugerencia() {
        try {
            showLoading();

            const response = await fetch(`/api/denuncias/${currentDenunciaId}/sugerencia-ia`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            hideLoading();

            if (data.success) {
                mostrarSugerencia(data);
                showToast('Sugerencia generada exitosamente', 'success');
            } else {
                showError(data.message);
            }

        } catch (error) {
            hideLoading();
            showError();
            console.error('Error al generar sugerencia:', error);
        }
    }

    /**
     * Regenera la sugerencia actual
     */
    async function regenerarSugerencia() {
        if (!confirm('¿Está seguro de que desea regenerar la sugerencia? La actual se perderá.')) {
            return;
        }

        try {
            showLoading();

            const response = await fetch(`/api/denuncias/${currentDenunciaId}/sugerencia-ia/regenerar`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            hideLoading();

            if (data.success) {
                mostrarSugerencia(data);
                showToast('Sugerencia regenerada exitosamente', 'success');
            } else {
                showError(data.message);
            }

        } catch (error) {
            hideLoading();
            showError();
            console.error('Error al regenerar sugerencia:', error);
        }
    }

    /**
     * Evalúa la sugerencia
     */
    async function evaluarSugerencia(evaluacion) {
        if (!currentSugerenciaId) {
            showToast('Error: No se puede evaluar la sugerencia', 'danger');
            return;
        }

        try {
            const response = await fetch('/api/denuncias/sugerencia-ia/evaluar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    id_sugerencia: currentSugerenciaId,
                    evaluacion: evaluacion
                })
            });

            const data = await response.json();

            if (data.success) {
                showToast('¡Gracias por evaluar la sugerencia!', 'success');
                hideActions();
            } else {
                showToast(data.message || 'Error al evaluar sugerencia', 'danger');
            }

        } catch (error) {
            showToast('Error de conexión al evaluar sugerencia', 'danger');
            console.error('Error:', error);
        }
    }

    /**
     * Genera sugerencia manualmente (botón de reintentar)
     */
    function generarSugerenciaManual() {
        hideError();
        generarNuevaSugerencia();
    }

    /**
     * Toggle para minimizar/expandir la sección
     */
    function toggleSugerencia() {
        const content = document.getElementById('sugerencia-content');
        const icon = document.getElementById('toggle-icon');

        sugerenciaCollapsed = !sugerenciaCollapsed;

        if (sugerenciaCollapsed) {
            content.style.display = 'none';
            icon.className = 'fas fa-chevron-down';
        } else {
            content.style.display = 'block';
            icon.className = 'fas fa-chevron-up';
        }
    }

    /**
     * Funciones auxiliares para manejar UI
     */
    function showSugerenciaSection() {
        document.getElementById('sugerencia-ia-section').style.display = 'block';
    }

    function showLoading() {
        document.getElementById('sugerencia-loading').style.display = 'block';
        document.getElementById('sugerencia-text').style.display = 'none';
        document.getElementById('sugerencia-error').style.display = 'none';
    }

    function hideLoading() {
        document.getElementById('sugerencia-loading').style.display = 'none';
    }

    function showError(message = 'No se pudo generar la sugerencia') {
        document.getElementById('sugerencia-error').style.display = 'block';
        document.getElementById('sugerencia-text').style.display = 'none';
        if (message !== 'No se pudo generar la sugerencia') {
            document.querySelector('#sugerencia-error span').textContent = message;
        }
    }

    function hideError() {
        document.getElementById('sugerencia-error').style.display = 'none';
    }

    function mostrarSugerencia(data) {
        const textContainer = document.querySelector('.sugerencia-content-text');
        const metadataContainer = document.getElementById('sugerencia-metadata');

        // Almacenar ID para evaluaciones
        currentSugerenciaId = data.id;

        // Formatear y mostrar el texto
        const sugerenciaFormateada = formatearSugerencia(data.sugerencia_generada || data.sugerencia);
        textContainer.innerHTML = sugerenciaFormateada;

        // Mostrar metadata
        const metadata = [];
        if (data.tokens_utilizados) metadata.push(`${data.tokens_utilizados} tokens`);
        if (data.tiempo_generacion) metadata.push(`${data.tiempo_generacion}s`);
        if (data.costo_estimado) metadata.push(`$${parseFloat(data.costo_estimado).toFixed(6)}`);

        metadataContainer.innerHTML = metadata.join(' | ');

        // Mostrar contenido
        document.getElementById('sugerencia-text').style.display = 'block';
        document.getElementById('sugerencia-text').classList.add('fade-in');

        // Mostrar acciones si no ha sido evaluada
        if (data.estado_sugerencia !== 'evaluada') {
            document.getElementById('sugerencia-actions').style.display = 'block';
        }
    }

    function formatearSugerencia(texto) {
        return texto
            .replace(/\*\*(.*?)\*\*/g, '<h6>$1</h6>')
            .replace(/\n(\d+\.\s)/g, '\n<li>')
            .replace(/\n-\s/g, '\n<li>')
            .replace(/\n{2,}/g, '</ul><p>')
            .replace(/\n/g, '<br>')
            .replace(/<li>/g, '</p><ul><li>')
            .replace(/(<li>.*?)(<br>|<p>)/g, '$1</li>')
            .replace(/<\/ul><p>/g, '</ul><p>')
            .replace(/^/, '<p>')
            .replace(/$/, '</p>');
    }

    function hideActions() {
        document.getElementById('sugerencia-actions').style.display = 'none';
    }

    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

        document.body.appendChild(toast);

        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 5000);
    }
</script>