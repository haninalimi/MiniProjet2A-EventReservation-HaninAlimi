
'use strict';


const Theme = (() => {
    const KEY  = 'ea-admin-theme';
    const html = document.documentElement;
    let dark   = localStorage.getItem(KEY) !== 'light';

    const apply = () => {
        html.setAttribute('data-theme', dark ? 'dark' : 'light');
        const icon = document.getElementById('themeIcon');
        if (icon) icon.className = dark ? 'bi bi-moon-stars-fill' : 'bi bi-sun-fill';
        localStorage.setItem(KEY, dark ? 'dark' : 'light');
    };
    const toggle = () => { dark = !dark; apply(); };
    const init   = () => apply();

    return { init, toggle };
})();


const Clock = (() => {
    const pad  = n => String(n).padStart(2, '0');
    const tick = () => {
        const el = document.getElementById('adminClock');
        if (!el) return;
        const d = new Date();
        el.textContent = `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
    };
    const init = () => { tick(); setInterval(tick, 1000); };
    return { init };
})();


const Flash = (() => {
    const init = () => {
        document.querySelectorAll('.adm-alert').forEach(el => {
            setTimeout(() => {
                el.style.transition = 'opacity .4s ease, transform .4s ease';
                el.style.opacity    = '0';
                el.style.transform  = 'translateX(12px)';
                setTimeout(() => el.remove(), 400);
            }, 4500);
        });
    };
    return { init };
})();


const Modal = (() => {
    const CLS = 'is-open';

    const open = (id) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.add(CLS);
        document.body.style.overflow = 'hidden';
        setTimeout(() => {
            el.querySelector('input:not([type=hidden]),textarea,select')?.focus();
        }, 280);
    };

    const close = (id) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.remove(CLS);
        document.body.style.overflow = '';
    };

    const onBackdrop = (e, id) => {
        if (e.target === document.getElementById(id)) close(id);
    };

    const submit = (formId) => {
        document.getElementById(formId)?.submit();
    };

    const init = () => {
        document.addEventListener('keydown', e => {
            if (e.key !== 'Escape') return;
            document.querySelectorAll(`.adm-modal-backdrop.${CLS}`)
                .forEach(m => { m.classList.remove(CLS); document.body.style.overflow = ''; });
        });
    };

    return { open, close, onBackdrop, submit, init };
})();


const TableSearch = (() => {
    const bind = (inputId, tableId, colIndex = 1) => {
        const input   = document.getElementById(inputId);
        const table   = document.getElementById(tableId);
        const counter = document.getElementById(`${tableId}-count`);
        if (!input || !table) return;

        const rows = table.querySelectorAll('tbody tr');

        input.addEventListener('input', () => {
            const q = input.value.toLowerCase().trim();
            let visible = 0;
            rows.forEach(row => {
                const cell  = row.cells[colIndex];
                const match = !q || (cell && cell.textContent.toLowerCase().includes(q));
                row.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            if (counter) counter.textContent = `${visible} résultat(s)`;
        });
    };
    return { bind };
})();


const Sidebar = (() => {
    const init = () => {
        const btn = document.getElementById('sidebarToggle');
        const sb  = document.querySelector('.adm-sidebar');
        if (!btn || !sb) return;
        btn.addEventListener('click', () => sb.classList.toggle('open'));
        document.addEventListener('click', e => {
            if (!sb.contains(e.target) && e.target !== btn) sb.classList.remove('open');
        });
    };
    return { init };
})();

const FormValidation = (() => {

   
    const showError = (field, message) => {
        field.style.borderColor = 'var(--c-red)';
        field.style.boxShadow   = '0 0 0 3px rgba(224,82,82,.15)';

        const old = field.parentNode.querySelector('.fv-error');
        if (old) old.remove();

        const err = document.createElement('div');
        err.className = 'fv-error';
        err.style.cssText = `
            font-size:.6rem;
            font-family:var(--font-mono, monospace);
            color:var(--c-red);
            letter-spacing:.06em;
            margin-top:.3rem;
            display:flex;
            align-items:center;
            gap:.3rem;
        `;
        err.innerHTML = `<i class="bi bi-exclamation-circle"></i> ${message}`;
        field.parentNode.appendChild(err);
    };

    
    const clearError = (field) => {
        field.style.borderColor = '';
        field.style.boxShadow   = '';
        const err = field.parentNode.querySelector('.fv-error');
        if (err) err.remove();
    };

   
    const validate = (formId) => {
        const form = document.getElementById(formId);
        if (!form) return true;

        let valid = true;

        form.querySelectorAll('[required], .adm-input:not([type=file])').forEach(field => {
            clearError(field);
            const val = field.value.trim();

            if (field.hasAttribute('required') && !val) {
                const label = form.querySelector(`label[for="${field.id}"]`)?.textContent?.replace('*','').trim()
                              || field.placeholder
                              || 'Ce champ';
                showError(field, `${label} est obligatoire`);
                valid = false;
            }

            if (field.type === 'email' && val && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
                showError(field, 'Adresse email invalide');
                valid = false;
            }

            if (field.type === 'number' && val) {
                const min = parseInt(field.getAttribute('min') || '0');
                if (parseInt(val) < min) {
                    showError(field, `La valeur minimum est ${min}`);
                    valid = false;
                }
            }
        });

        if (!valid) {
            const firstErr = form.querySelector('.fv-error');
            firstErr?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        return valid;
    };

   
    const bindLiveClearing = (formId) => {
        const form = document.getElementById(formId);
        if (!form) return;
        form.querySelectorAll('.adm-input, .adm-textarea').forEach(field => {
            field.addEventListener('input', () => clearError(field));
            field.addEventListener('change', () => clearError(field));
        });
    };

    const init = () => {
        bindLiveClearing('form-new');
        bindLiveClearing('form-edit');
    };

    return { validate, init };
})();


const ConfirmDelete = (() => {

    let pendingForm = null;

    const DIALOG_HTML = `
    <div id="confirm-dialog" style="
        position:fixed;inset:0;
        background:rgba(0,0,0,.82);
        backdrop-filter:blur(10px);
        z-index:9999;
        display:none;
        align-items:center;justify-content:center;
        padding:1.5rem;
        opacity:0;
        transition:opacity .2s ease;
    ">
        <div style="
            background:var(--c-bg2, #13171c);
            border:1px solid rgba(224,82,82,.3);
            border-radius:14px;
            width:100%;max-width:400px;
            overflow:hidden;
            transform:scale(.94) translateY(10px);
            transition:transform .28s cubic-bezier(.34,1.56,.64,1);
            box-shadow:0 24px 64px rgba(0,0,0,.8);
        " id="confirm-box">

            <div style="height:2px;background:linear-gradient(90deg,transparent,var(--c-red, #e05252),transparent);"></div>

            <div style="padding:1.5rem 1.5rem 1.1rem;">
                <div style="display:flex;align-items:center;gap:.9rem;margin-bottom:1rem;">
                    <div style="
                        width:42px;height:42px;border-radius:10px;
                        background:rgba(224,82,82,.1);
                        border:1px solid rgba(224,82,82,.25);
                        display:flex;align-items:center;justify-content:center;
                        flex-shrink:0;
                    ">
                        <i class="bi bi-trash3-fill" style="color:var(--c-red,#e05252);font-size:1.15rem;"></i>
                    </div>
                    <div>
                        <div style="font-size:.95rem;font-weight:700;color:var(--c-text,#e8e6e0);margin-bottom:.2rem;letter-spacing:-.01em;">
                            Confirmer la suppression
                        </div>
                        <div style="font-size:.6rem;font-family:monospace;color:var(--c-red,#e05252);letter-spacing:.1em;text-transform:uppercase;opacity:.8;">
                            ⚠ Action irréversible
                        </div>
                    </div>
                </div>

                <div style="
                    background:var(--c-bg3,#1a1f26);
                    border-radius:8px;
                    border-left:3px solid var(--c-red,#e05252);
                    padding:.85rem 1rem;
                    font-size:.8rem;
                    color:var(--c-text2,#7a8494);
                    line-height:1.6;
                " id="confirm-message"></div>
            </div>

            <div style="
                display:flex;gap:.55rem;justify-content:flex-end;
                padding:.9rem 1.5rem;
                border-top:1px solid rgba(255,255,255,.06);
                background:var(--c-bg1,#0d1014);
            ">
                <button id="confirm-cancel-btn" style="
                    display:inline-flex;align-items:center;gap:.4rem;
                    padding:.5rem 1rem;border-radius:6px;
                    font-size:.78rem;font-weight:500;cursor:pointer;
                    background:transparent;
                    color:var(--c-text2,#7a8494);
                    border:1px solid rgba(255,255,255,.1);
                    font-family:inherit;transition:all .15s;
                " onmouseover="this.style.background='rgba(255,255,255,.06)'"
                   onmouseout="this.style.background='transparent'">
                    <i class="bi bi-x-lg"></i> Annuler
                </button>
                <button id="confirm-ok-btn" style="
                    display:inline-flex;align-items:center;gap:.4rem;
                    padding:.5rem 1.1rem;border-radius:6px;
                    font-size:.78rem;font-weight:600;cursor:pointer;
                    background:rgba(224,82,82,.15);
                    color:var(--c-red,#e05252);
                    border:1px solid rgba(224,82,82,.35);
                    font-family:inherit;transition:all .15s;
                " onmouseover="this.style.background='rgba(224,82,82,.25)'"
                   onmouseout="this.style.background='rgba(224,82,82,.15)'">
                    <i class="bi bi-trash3"></i> Supprimer définitivement
                </button>
            </div>
        </div>
    </div>`;

    const open = (form) => {
        pendingForm = form;
        document.getElementById('confirm-message').textContent =
            form.dataset.confirm || 'Confirmer la suppression ?';

        const dialog = document.getElementById('confirm-dialog');
        const box    = document.getElementById('confirm-box');
        dialog.style.display = 'flex';
        requestAnimationFrame(() => {
            dialog.style.opacity = '1';
            box.style.transform  = 'scale(1) translateY(0)';
        });
        document.body.style.overflow = 'hidden';
    };

    const close = () => {
        const dialog = document.getElementById('confirm-dialog');
        const box    = document.getElementById('confirm-box');
        dialog.style.opacity = '0';
        box.style.transform  = 'scale(.94) translateY(10px)';
        setTimeout(() => {
            dialog.style.display = 'none';
            document.body.style.overflow = '';
            pendingForm = null;
        }, 220);
    };

    const init = () => {
        document.body.insertAdjacentHTML('beforeend', DIALOG_HTML);

        document.getElementById('confirm-cancel-btn').addEventListener('click', close);
        document.getElementById('confirm-ok-btn').addEventListener('click', () => {
            close();
            setTimeout(() => pendingForm?.submit(), 230);
        });

        document.getElementById('confirm-dialog').addEventListener('click', e => {
            if (e.target === document.getElementById('confirm-dialog')) close();
        });

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' &&
                document.getElementById('confirm-dialog').style.display === 'flex') {
                close();
            }
        });

        document.querySelectorAll('form[data-confirm]').forEach(form => {
            form.addEventListener('submit', e => {
                e.preventDefault();
                open(form);
            });
        });
    };

    return { init };
})();


const submitWithValidation = (formId) => {
    if (FormValidation.validate(formId)) {
        document.getElementById(formId)?.submit();
    }
};


document.addEventListener('DOMContentLoaded', () => {
    Theme.init();
    Clock.init();
    Modal.init();
    Flash.init();
    Sidebar.init();
    FormValidation.init();
    ConfirmDelete.init();
    TableSearch.bind('evtSearch', 'evtTable', 1);
});

window.Theme              = Theme;
window.Modal              = Modal;
window.submitWithValidation = submitWithValidation;