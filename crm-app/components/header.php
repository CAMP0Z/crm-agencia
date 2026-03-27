<?php
// components/header.php - Top Header Component
// Accepts: $pageTitle, $pageSubtitle (optional)
$pageTitle    = $pageTitle    ?? 'Dashboard';
$pageSubtitle = $pageSubtitle ?? '';
?>
<header class="app-header">
    <!-- Mobile hamburger -->
    <button onclick="document.getElementById('appSidebar').classList.toggle('open');document.getElementById('mobileOverlay').classList.toggle('hidden');"
            style="display:none;background:none;border:none;color:var(--text-primary);cursor:pointer;padding:4px;" id="hamburgerBtn">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
    </button>

    <div>
        <h1 class="page-title"><?= clean($pageTitle) ?></h1>
        <?php if ($pageSubtitle): ?>
        <p class="page-subtitle"><?= clean($pageSubtitle) ?></p>
        <?php endif; ?>
    </div>

    <div class="header-spacer"></div>

    <!-- Search -->
    <div class="header-search">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input type="text" id="globalSearch" placeholder="Buscar clientes, tarefas..."
               onkeyup="globalSearchFn(this.value)" autocomplete="off">
    </div>

    <!-- Actions -->
    <div class="header-actions">
        <!-- Notification -->
        <div style="position:relative;" id="notifContainer">
            <button onclick="toggleNotif()" class="btn btn-secondary btn-icon" id="notifBtn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
            </button>
            <span id="notifDot" style="position:absolute;top:6px;right:6px;width:8px;height:8px;border-radius:50%;background:var(--red);border:2px solid var(--bg-secondary);display:none;"></span>
            <div id="notifDropdown" style="display:none;position:absolute;right:0;top:calc(100% + 8px);width:320px;background:var(--bg-secondary);border:1px solid var(--border);border-radius:12px;box-shadow:var(--shadow);z-index:200;overflow:hidden;">
                <div style="padding:14px 18px;border-bottom:1px solid var(--border);">
                    <span style="font-size:14px;font-weight:600;color:var(--text-primary);">Notificações</span>
                </div>
                <div id="notifList" style="max-height:300px;overflow-y:auto;padding:8px;"></div>
            </div>
        </div>

        <!-- Date -->
        <div style="font-size:12px;color:var(--text-muted);white-space:nowrap;">
            <?= date('d/m/Y H:i') ?>
        </div>
    </div>
</header>

<script>
function toggleNotif() {
    const d = document.getElementById('notifDropdown');
    d.style.display = d.style.display === 'none' ? 'block' : 'none';
    if (d.style.display === 'block') loadNotifs();
}
document.addEventListener('click', (e) => {
    if (!document.getElementById('notifContainer')?.contains(e.target)) {
        const d = document.getElementById('notifDropdown');
        if (d) d.style.display = 'none';
    }
});
async function loadNotifs() {
    try {
        const r = await fetch('<?= BASE_URL ?>/api/notifications/list.php');
        const j = await r.json();
        const list = document.getElementById('notifList');
        if (!j.data?.length) {
            list.innerHTML = '<div style="padding:20px;text-align:center;color:var(--text-muted);font-size:13px;">Sem notificações</div>';
            return;
        }
        document.getElementById('notifDot').style.display = 'block';
        list.innerHTML = j.data.map(n => `
            <a href="${n.link || '#'}" style="display:flex;gap:10px;padding:10px 12px;border-radius:8px;text-decoration:none;transition:background .15s;" onmouseover="this.style.background='rgba(14,165,233,0.07)'" onmouseout="this.style.background='transparent'">
                <span style="font-size:18px;">${n.icon || '📌'}</span>
                <div><div style="font-size:13px;font-weight:500;color:var(--text-primary);">${n.title}</div><div style="font-size:11px;color:var(--text-muted);margin-top:2px;">${n.time}</div></div>
            </a>
        `).join('');
    } catch(e) {}
}
function globalSearchFn(q) {
    if (q.length < 2) return;
    window.location.href = '<?= BASE_URL ?>/admin/clientes.php?q=' + encodeURIComponent(q);
}
// Show hamburger on mobile
if (window.innerWidth <= 768) {
    document.getElementById('hamburgerBtn').style.display = 'flex';
}
</script>
