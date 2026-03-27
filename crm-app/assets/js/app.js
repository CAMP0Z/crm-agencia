// assets/js/app.js - Global JavaScript Utilities

// ── Modal ─────────────────────────────────────────────────────
function openModal(id) {
    const m = document.getElementById(id);
    if (!m) return;
    m.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    const m = document.getElementById(id);
    if (!m) return;
    m.classList.remove('open');
    document.body.style.overflow = '';
}

// Close modal on overlay click
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('open');
        document.body.style.overflow = '';
    }
});

// Close on Escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.open').forEach(m => {
            m.classList.remove('open');
            document.body.style.overflow = '';
        });
    }
});

// ── Toast Notifications ──────────────────────────────────────
function showToast(message, type = 'info', duration = 3500) {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const icons = { success: '✅', error: '❌', info: 'ℹ️' };
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `<span>${icons[type] || '📌'}</span><span>${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'fadeOut 0.3s ease forwards';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// ── Demand / Delivery status toggle ─────────────────────────
async function toggleDemandStatus(btn, id, type) {
    const states = ['pendente', 'em_andamento', 'concluido'];
    const current = btn.dataset.status;
    const next = states[(states.indexOf(current) + 1) % states.length];

    const fd = new FormData();
    fd.append('id', id);
    fd.append('status', next);
    fd.append('type', type); // 'demand' or 'delivery'

    const r = await fetch('/crm-app/api/demands/update.php', { method: 'POST', body: fd });
    const j = await r.json();

    if (j.success) {
        btn.dataset.status = next;
        btn.className = `demand-status-btn ${next}`;

        const icons  = { pendente: '', em_andamento: '●', concluido: '✓' };
        btn.textContent = icons[next];

        const title = btn.closest('.demand-item')?.querySelector('.demand-title');
        if (title) {
            title.classList.toggle('done', next === 'concluido');
        }
        showToast('Status atualizado!', 'success');
    } else {
        showToast('Erro ao atualizar.', 'error');
    }
}

// ── Confirm helper ──────────────────────────────────────────
function confirmAction(message, callback) {
    if (window.confirm(message)) callback();
}

// ── Format currency BRL ─────────────────────────────────────
function formatBRL(val) {
    return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(val);
}

// ── Debounce ────────────────────────────────────────────────
function debounce(fn, wait = 300) {
    let t;
    return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn.apply(this, args), wait);
    };
}

// ── Quick task complete  ─────────────────────────────────────
async function quickCompleteTask(id, btn) {
    const fd = new FormData();
    fd.append('id', id);
    fd.append('status', 'concluida');
    const r = await fetch('/crm-app/api/tasks/update.php', { method: 'POST', body: fd });
    const j = await r.json();
    if (j.success) {
        const row = btn.closest('tr') || btn.closest('.task-card');
        if (row) { row.style.opacity = '0.4'; row.style.pointerEvents = 'none'; }
        showToast('Tarefa concluída! ✅', 'success');
    } else {
        showToast('Erro ao concluir.', 'error');
    }
}

// ── Fade in animation for page elements ─────────────────────
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.card, .kpi-card').forEach((el, i) => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(12px)';
        el.style.transition = `opacity 0.4s ease ${i * 0.04}s, transform 0.4s ease ${i * 0.04}s`;
        requestAnimationFrame(() => {
            el.style.opacity = '1';
            el.style.transform = 'translateY(0)';
        });
    });
});
