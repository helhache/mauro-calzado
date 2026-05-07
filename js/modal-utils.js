/**
 * MC - Modal personalizado para Mauro Calzado
 * Reemplaza alert() y confirm() nativos con modales Bootstrap estilizados
 * Disponible globalmente como window.MC
 */
var MC = (function () {
    'use strict';

    var _cb      = null;
    var _bsModal = null;

    var TIPOS = {
        success: { icon: 'bi-check-circle-fill',         bg: '#d1e7dd', color: '#0f5132', btn: 'btn-success' },
        danger:  { icon: 'bi-exclamation-triangle-fill', bg: '#f8d7da', color: '#842029', btn: 'btn-danger'  },
        warning: { icon: 'bi-exclamation-circle-fill',   bg: '#fff3cd', color: '#664d03', btn: 'btn-warning' },
        info:    { icon: 'bi-info-circle-fill',           bg: '#cff4fc', color: '#055160', btn: 'btn-info'    }
    };

    function getModal() {
        if (!_bsModal) {
            _bsModal = new bootstrap.Modal(document.getElementById('mc-modal'), { backdrop: 'static', keyboard: false });
        }
        return _bsModal;
    }

    function render(cfg) {
        var t = TIPOS[cfg.tipo] || TIPOS.info;
        var iconWrap = document.getElementById('mc-modal-icon-wrap');
        iconWrap.style.background = t.bg;
        var icon = document.getElementById('mc-modal-icon');
        icon.className = 'bi ' + t.icon + ' fs-5';
        icon.style.color = t.color;
        document.getElementById('mc-modal-titulo').textContent  = cfg.titulo  || 'Aviso';
        document.getElementById('mc-modal-mensaje').textContent = cfg.mensaje || '';
        var btnOk = document.getElementById('mc-modal-ok');
        btnOk.textContent = cfg.btnOk || 'Aceptar';
        btnOk.className   = 'btn px-4 fw-semibold ' + t.btn;
        var btnCancel = document.getElementById('mc-modal-cancelar');
        if (cfg.btnCancel !== false) {
            btnCancel.style.display = '';
            btnCancel.textContent   = cfg.btnCancel || 'Cancelar';
        } else {
            btnCancel.style.display = 'none';
        }
    }

    function bindOnce() {
        document.getElementById('mc-modal-ok').addEventListener('click', function () {
            getModal().hide();
            var cb = _cb; _cb = null;
            if (cb) cb(true);
        });
        document.getElementById('mc-modal-cancelar').addEventListener('click', function () {
            var cb = _cb; _cb = null;
            if (cb) cb(false);
        });
        document.getElementById('mc-modal').addEventListener('hidden.bs.modal', function () {
            var cb = _cb; _cb = null;
            if (cb) cb(false);
        });

        // Manejador global para elementos con data-confirm
        document.addEventListener('click', function (e) {
            var el = e.target.closest('[data-confirm]');
            if (!el) return;
            e.preventDefault();
            e.stopImmediatePropagation();
            MC.confirm(
                el.dataset.confirm,
                function (ok) {
                    if (!ok) return;
                    if (el.tagName === 'A' && el.getAttribute('href')) {
                        window.location.href = el.getAttribute('href');
                    } else {
                        var form = el.closest('form');
                        if (form) { el.removeAttribute('data-confirm'); el.click(); }
                    }
                },
                {
                    tipo:   el.dataset.confirmTipo  || 'warning',
                    titulo: el.dataset.confirmTitulo || 'Confirmar',
                    btnOk:  el.dataset.confirmOk    || 'Sí, continuar'
                }
            );
        }, true);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindOnce);
    } else {
        bindOnce();
    }

    return {
        alert: function (mensaje, tipo, titulo) {
            _cb = null;
            render({ mensaje: mensaje, tipo: tipo || 'info', titulo: titulo || 'Aviso', btnCancel: false, btnOk: 'Aceptar' });
            getModal().show();
        },
        confirm: function (mensaje, callback, opciones) {
            _cb = callback || null;
            var opts = Object.assign({ tipo: 'warning', titulo: 'Confirmar', btnOk: 'Sí, continuar', btnCancel: 'Cancelar' }, opciones || {});
            opts.mensaje = mensaje;
            render(opts);
            getModal().show();
        }
    };
})();
