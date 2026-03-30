/**
 * RK Marketing Drive - JavaScript Principal
 *
 * @package RKMarketingDrive
 * @version 1.0.0
 */

// =============================================================================
// CONFIGURACION GLOBAL
// =============================================================================

const APP_CONFIG = {
    baseUrl: window.location.origin + window.location.pathname.replace(/\/$/, ''),
    maxFileSize: 500 * 1024 * 1024, // 500MB
    maxFilesPerUpload: 10,
    allowedExtensions: ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp', 'mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv', 'mp3', 'wav', 'ogg', 'flac', 'aac', 'm4a', 'zip', 'rar', '7z', 'tar', 'gz']
};

// =============================================================================
// UTILIDADES
// =============================================================================

/**
 * Formatea un tamano de archivo
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * Formatea una fecha
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Obtiene el icono de un tipo de archivo
 */
function getFileIcon(extension) {
    const icons = {
        image: ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico'],
        pdf: ['pdf'],
        doc: ['doc', 'docx', 'odt', 'txt'],
        xls: ['xls', 'xlsx', 'ods'],
        ppt: ['ppt', 'pptx', 'odp'],
        video: ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv'],
        audio: ['mp3', 'wav', 'ogg', 'flac', 'aac', 'm4a'],
        archive: ['zip', 'rar', '7z', 'tar', 'gz']
    };

    for (const [type, extensions] of Object.entries(icons)) {
        if (extensions.includes(extension.toLowerCase())) {
            return type;
        }
    }
    return 'default';
}

/**
 * Muestra una notificacion toast
 */
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();

    const toast = document.createElement('div');
    toast.className = `toast show align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;

    toastContainer.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 5000);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    document.body.appendChild(container);
    return container;
}

/**
 * Confirma una accion
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// =============================================================================
// MANEJO DE FORMULARIOS
// =============================================================================

/**
 * Serializa un formulario a objeto
 */
function serializeForm(form) {
    const formData = new FormData(form);
    const object = {};

    formData.forEach((value, key) => {
        if (object[key]) {
            if (!Array.isArray(object[key])) {
                object[key] = [object[key]];
            }
            object[key].push(value);
        } else {
            object[key] = value;
        }
    });

    return object;
}

/**
 * Valida un formulario
 */
function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;

    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });

    return isValid;
}

// =============================================================================
// SUBIDA DE ARCHIVOS
// =============================================================================

/**
 * Maneja la subida de archivos con drag and drop
 */
class FileUploader {
    constructor(dropzone, options = {}) {
        this.dropzone = dropzone;
        this.options = {
            maxFileSize: APP_CONFIG.maxFileSize,
            maxFiles: APP_CONFIG.maxFilesPerUpload,
            allowedExtensions: APP_CONFIG.allowedExtensions,
            onDrop: null,
            onProgress: null,
            onComplete: null,
            onError: null,
            ...options
        };

        this.init();
    }

    init() {
        // Prevenir comportamiento por defecto
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            this.dropzone.addEventListener(eventName, preventDefaults, false);
        });

        // Resaltar al arrastrar
        ['dragenter', 'dragover'].forEach(eventName => {
            this.dropzone.addEventListener(eventName, () => {
                this.dropzone.classList.add('dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            this.dropzone.addEventListener(eventName, () => {
                this.dropzone.classList.remove('dragover');
            }, false);
        });

        // Manejar drop
        this.dropzone.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            this.handleFiles(files);
        }, false);

        // Click para seleccionar
        this.dropzone.addEventListener('click', () => {
            const input = document.createElement('input');
            input.type = 'file';
            input.multiple = this.options.maxFiles > 1;
            input.accept = this.options.allowedExtensions.map(ext => `.${ext}`).join(',');

            input.onchange = (e) => {
                this.handleFiles(e.target.files);
            };

            input.click();
        });
    }

    handleFiles(files) {
        const validFiles = [];

        Array.from(files).forEach(file => {
            if (this.validateFile(file)) {
                validFiles.push(file);
            }
        });

        if (validFiles.length > 0 && this.options.onDrop) {
            this.options.onDrop(validFiles);
        }
    }

    validateFile(file) {
        // Validar tamano
        if (file.size > this.options.maxFileSize) {
            showToast(`El archivo ${file.name} excede el tamano maximo de ${formatFileSize(this.options.maxFileSize)}`, 'danger');
            return false;
        }

        // Validar extension
        const ext = file.name.split('.').pop().toLowerCase();
        if (!this.options.allowedExtensions.includes(ext)) {
            showToast(`El tipo de archivo .${ext} no esta permitido`, 'danger');
            return false;
        }

        return true;
    }

    uploadFiles(files, url, folderId) {
        const formData = new FormData();

        files.forEach((file, index) => {
            formData.append(`files[${index}]`, file);
        });

        formData.append('folder_id', folderId);
        formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');

        return fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (this.options.onComplete) {
                    this.options.onComplete(data);
                }
                showToast(data.message || 'Archivos subidos correctamente', 'success');
            } else {
                throw new Error(data.message || 'Error al subir archivos');
            }
        })
        .catch(error => {
            if (this.options.onError) {
                this.options.onError(error);
            }
            showToast(error.message, 'danger');
        });
    }
}

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

// =============================================================================
// INICIALIZACION
// =============================================================================

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips de Bootstrap
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(tooltipTriggerEl => {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Inicializar popovers de Bootstrap
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    popoverTriggerList.forEach(popoverTriggerEl => {
        new bootstrap.Popover(popoverTriggerEl);
    });

    // Manejar formularios con validacion
    document.querySelectorAll('form[data-validate]').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                showToast('Por favor, completa todos los campos requeridos', 'warning');
            }
        });
    });

    // Cerrar alertas automaticamente
    setTimeout(() => {
        document.querySelectorAll('.alert-dismissible').forEach(alert => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        });
    }, 5000);

    // Dark / Light mode toggle
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon   = document.getElementById('themeIcon');

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('rk-theme', theme);
        if (themeIcon) {
            themeIcon.className = theme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
        }
    }

    // Inicializar icono segun tema actual
    const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
    if (themeIcon) {
        themeIcon.className = currentTheme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
    }

    if (themeToggle) {
        themeToggle.addEventListener('click', function () {
            const next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            applyTheme(next);
        });
    }
});

// Exponer funciones globales
window.APP_CONFIG = APP_CONFIG;
window.formatFileSize = formatFileSize;
window.formatDate = formatDate;
window.showToast = showToast;
window.confirmAction = confirmAction;
window.FileUploader = FileUploader;