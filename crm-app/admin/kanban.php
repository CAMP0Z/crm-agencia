<?php
// admin/kanban.php - Kanban Board
require_once __DIR__ . '/../config/app.php';
requireAuth();
atualizarStatusTarefasAtrasadas();

$db = getDB();
$clientes = $db->query("
    SELECT c.*,
        (SELECT COUNT(*) FROM tarefas t WHERE t.cliente_id=c.id AND t.status IN ('pendente','atrasada')) AS pend,
        (SELECT COUNT(*) FROM tarefas t WHERE t.cliente_id=c.id AND t.status='concluida') AS done
    FROM clientes c ORDER BY c.created_at DESC
")->fetchAll();

$cols = [
    'onboarding'         => ['label'=>'Onboarding',          'color'=>'#0ea5e9', 'icon'=>'🚀'],
    'aguardando_cliente' => ['label'=>'Aguardando Cliente',   'color'=>'#f59e0b', 'icon'=>'⏳'],
    'em_execucao'        => ['label'=>'Em Execução',          'color'=>'#10b981', 'icon'=>'▶️'],
    'revisao'            => ['label'=>'Revisão',              'color'=>'#a855f7', 'icon'=>'🔍'],
    'concluido'          => ['label'=>'Concluído',            'color'=>'#14b8a6', 'icon'=>'✅'],
    'pausado'            => ['label'=>'Pausado',              'color'=>'#64748b', 'icon'=>'⏸️'],
];

$grouped = [];
foreach ($cols as $k => $_) $grouped[$k] = [];
foreach ($clientes as $c) {
    $key = in_array($c['status'], array_keys($cols)) ? $c['status'] : 'onboarding';
    $grouped[$key][] = $c;
}

$pageTitle = 'Kanban';
$pageSubtitle = 'Gestão visual de clientes por status';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kanban — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>
    <div class="app-main">
        <?php include __DIR__ . '/../components/header.php'; ?>
        <div class="app-content">

            <div class="kanban-board">
                <?php foreach ($cols as $status => $col): ?>
                <div class="kanban-col" data-status="<?= $status ?>">
                    <div class="kanban-col-header">
                        <span class="kanban-col-title" style="--col-color:<?= $col['color'] ?>;">
                            <span><?= $col['icon'] ?></span>
                            <span><?= $col['label'] ?></span>
                        </span>
                        <span class="kanban-col-count"><?= count($grouped[$status]) ?></span>
                    </div>

                    <div class="kanban-cards" data-status="<?= $status ?>" id="col-<?= $status ?>">
                        <?php foreach ($grouped[$status] as $c): ?>
                        <div class="kanban-card" data-id="<?= $c['id'] ?>"
                             style="--k-color:<?= $col['color'] ?>;">
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:6px;">
                                <div class="kcard-company"><?= clean($c['empresa']) ?></div>
                                <span class="health-dot <?= $c['saude_operacional'] ?>" style="width:8px;height:8px;margin-top:4px;"></span>
                            </div>
                            <div class="kcard-resp"><?= clean($c['responsavel']) ?></div>
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                <?php if ($c['nicho']): ?>
                                <span class="badge badge-gray" style="font-size:10px;"><?= clean($c['nicho']) ?></span>
                                <?php endif; ?>
                                <?php if ($c['valor_mensal'] > 0): ?>
                                <span class="badge badge-blue" style="font-size:10px;"><?= formatBRL((float)$c['valor_mensal']) ?>/mês</span>
                                <?php endif; ?>
                            </div>
                            <div class="kcard-stats">
                                <span class="kcard-stat">
                                    <span class="dot" style="background:var(--yellow);"></span>
                                    <?= $c['pend'] ?> pend.
                                </span>
                                <span class="kcard-stat">
                                    <span class="dot" style="background:var(--green);"></span>
                                    <?= $c['done'] ?> ok
                                </span>
                                <a href="<?= BASE_URL ?>/admin/cliente-detalhe.php?id=<?= $c['id'] ?>"
                                   style="margin-left:auto;font-size:11px;color:var(--blue-primary);text-decoration:none;">
                                   Ver →
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
// ── SortableJS: drag & drop between columns ──────────────────
const BASE = '<?= BASE_URL ?>';

document.querySelectorAll('.kanban-cards').forEach(container => {
    const sortable = new Sortable(container, {
        group: 'kanban',
        animation: 180,
        ghostClass: 'sortable-ghost',
        dragClass: 'sortable-drag',
        onEnd: async (evt) => {
            const cardId  = evt.item.dataset.id;
            const newStatus = evt.to.dataset.status;

            // Update count badges
            document.querySelectorAll('.kanban-col').forEach(col => {
                const s = col.dataset.status;
                const cnt = col.querySelectorAll('.kanban-card').length;
                col.querySelector('.kanban-col-count').textContent = cnt;
            });

            // Save to DB
            const fd = new FormData();
            fd.append('id', cardId);
            fd.append('status', newStatus);
            try {
                const r = await fetch(BASE + '/api/kanban/update-status.php', { method: 'POST', body: fd });
                const j = await r.json();
                if (j.success) showToast('Status atualizado!', 'success');
                else showToast('Erro ao salvar.', 'error');
            } catch (err) {
                showToast('Erro de conexão.', 'error');
            }
        }
    });
});
</script>
</body>
</html>
