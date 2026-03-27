<?php
// components/sidebar.php - Sidebar Navigation Component
$current_page = basename($_SERVER['PHP_SELF']);
$user = currentUser();

$nav = [
    'principal' => [
        ['href' => 'index.php',      'label' => 'Dashboard',   'icon' => 'grid',        'file' => 'index.php'],
        ['href' => 'clientes.php',   'label' => 'Clientes',    'icon' => 'users',       'file' => 'clientes.php'],
        ['href' => 'kanban.php',     'label' => 'Kanban',      'icon' => 'kanban',      'file' => 'kanban.php'],
        ['href' => 'tarefas.php',    'label' => 'Tarefas',     'icon' => 'check-square','file' => 'tarefas.php'],
    ],
    'financeiro' => [
        ['href' => 'receitas.php',   'label' => 'Receitas',    'icon' => 'dollar-sign', 'file' => 'receitas.php'],
        ['href' => 'trafego.php',    'label' => 'Tráfego',     'icon' => 'trending-up', 'file' => 'trafego.php'],
    ],
    'sistema' => [
        ['href' => 'configuracoes.php', 'label' => 'Configurações', 'icon' => 'settings', 'file' => 'configuracoes.php'],
    ],
];

// Count badges
$db = getDB();
$tarefas_pendentes = (int)$db->query("SELECT COUNT(*) FROM tarefas WHERE status IN ('pendente','atrasada')")->fetchColumn();
$clientes_total    = (int)$db->query("SELECT COUNT(*) FROM clientes")->fetchColumn();

$icons = [
    'grid'        => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
    'users'       => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    'kanban'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="5" height="18" rx="1"/><rect x="10" y="3" width="5" height="12" rx="1"/><rect x="17" y="3" width="5" height="15" rx="1"/></svg>',
    'check-square'=> '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>',
    'dollar-sign' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>',
    'trending-up' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>',
    'settings'    => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
];
?>
<aside class="app-sidebar" id="appSidebar">
    <!-- Logo -->
    <div class="sidebar-logo">
        <div class="logo-icon">⚡</div>
        <div>
            <div class="logo-text">CRM Agência</div>
            <div class="logo-sub">OPERACIONAL</div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <div class="nav-section-label">Principal</div>
        <?php foreach ($nav['principal'] as $item): ?>
        <a href="<?= BASE_URL ?>/admin/<?= $item['href'] ?>"
           class="nav-item <?= $current_page === $item['file'] ? 'active' : '' ?>">
            <span class="nav-icon"><?= $icons[$item['icon']] ?></span>
            <span><?= $item['label'] ?></span>
            <?php if ($item['file'] === 'tarefas.php' && $tarefas_pendentes > 0): ?>
                <span class="nav-badge"><?= $tarefas_pendentes ?></span>
            <?php endif; ?>
            <?php if ($item['file'] === 'clientes.php'): ?>
                <span class="nav-badge" style="background: rgba(14,165,233,0.15);color:var(--blue-primary);"><?= $clientes_total ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>

        <div class="nav-section-label" style="margin-top:8px;">Financeiro</div>
        <?php foreach ($nav['financeiro'] as $item): ?>
        <a href="<?= BASE_URL ?>/admin/<?= $item['href'] ?>"
           class="nav-item <?= $current_page === $item['file'] ? 'active' : '' ?>">
            <span class="nav-icon"><?= $icons[$item['icon']] ?></span>
            <span><?= $item['label'] ?></span>
        </a>
        <?php endforeach; ?>

        <div class="nav-section-label" style="margin-top:8px;">Sistema</div>
        <?php foreach ($nav['sistema'] as $item): ?>
        <a href="<?= BASE_URL ?>/admin/<?= $item['href'] ?>"
           class="nav-item <?= $current_page === $item['file'] ? 'active' : '' ?>">
            <span class="nav-icon"><?= $icons[$item['icon']] ?></span>
            <span><?= $item['label'] ?></span>
        </a>
        <?php endforeach; ?>
    </nav>

    <!-- User Footer -->
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="user-avatar"><?= initials($user['nome']) ?></div>
            <div class="user-info">
                <div class="user-name"><?= clean($user['nome']) ?></div>
                <div class="user-role"><?= ucfirst($user['perfil']) ?></div>
            </div>
            <a href="<?= BASE_URL ?>/admin/logout.php" title="Sair"
               style="color:var(--text-muted); display:flex; align-items:center;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
            </a>
        </div>
    </div>
</aside>

<!-- Mobile overlay -->
<div class="mobile-overlay hidden" id="mobileOverlay"
     onclick="document.getElementById('appSidebar').classList.remove('open');this.classList.add('hidden');"
     style="position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:99;backdrop-filter:blur(2px);"></div>
