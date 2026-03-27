<?php
// admin/tarefas.php - Tasks Management Page
require_once __DIR__ . '/../config/app.php';
requireAuth();
atualizarStatusTarefasAtrasadas();

$db = getDB();

$filter_status   = $_GET['status'] ?? '';
$filter_priority = $_GET['prioridade'] ?? '';
$filter_cliente  = (int)($_GET['cliente'] ?? 0);

$where = ['1=1'];
$params = [];

if ($filter_status)   { $where[] = 't.status = ?';    $params[] = $filter_status; }
if ($filter_priority) { $where[] = 't.prioridade = ?'; $params[] = $filter_priority; }
if ($filter_cliente)  { $where[] = 't.cliente_id = ?'; $params[] = $filter_cliente; }

$whereStr = implode(' AND ', $where);
$stmt = $db->prepare("
    SELECT t.*, c.empresa AS cliente_nome
    FROM tarefas t
    LEFT JOIN clientes c ON c.id = t.cliente_id
    WHERE $whereStr
    ORDER BY
        CASE t.status WHEN 'atrasada' THEN 0 WHEN 'em_andamento' THEN 1 WHEN 'pendente' THEN 2 ELSE 3 END,
        CASE t.prioridade WHEN 'urgente' THEN 0 WHEN 'alta' THEN 1 WHEN 'media' THEN 2 ELSE 3 END,
        t.prazo ASC
");
$stmt->execute($params);
$tarefas = $stmt->fetchAll();

$clientes = $db->query("SELECT id, empresa FROM clientes ORDER BY empresa")->fetchAll();

$pageTitle    = 'Tarefas';
$pageSubtitle = count($tarefas) . ' tarefas';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarefas — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>
    <div class="app-main">
        <?php include __DIR__ . '/../components/header.php'; ?>
        <div class="app-content">

            <!-- Filters -->
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
                <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;">
                    <select name="status" class="form-control" style="width:150px;padding:8px 12px;" onchange="this.form.submit()">
                        <option value="">Todos status</option>
                        <option value="pendente"     <?= $filter_status==='pendente'    ?'selected':'' ?>>Pendente</option>
                        <option value="em_andamento" <?= $filter_status==='em_andamento'?'selected':'' ?>>Em Andamento</option>
                        <option value="concluida"    <?= $filter_status==='concluida'   ?'selected':'' ?>>Concluída</option>
                        <option value="atrasada"     <?= $filter_status==='atrasada'    ?'selected':'' ?>>Atrasada</option>
                    </select>
                    <select name="prioridade" class="form-control" style="width:150px;padding:8px 12px;" onchange="this.form.submit()">
                        <option value="">Todas prioridades</option>
                        <option value="urgente" <?= $filter_priority==='urgente'?'selected':'' ?>>Urgente</option>
                        <option value="alta"    <?= $filter_priority==='alta'   ?'selected':'' ?>>Alta</option>
                        <option value="media"   <?= $filter_priority==='media'  ?'selected':'' ?>>Média</option>
                        <option value="baixa"   <?= $filter_priority==='baixa'  ?'selected':'' ?>>Baixa</option>
                    </select>
                    <select name="cliente" class="form-control" style="width:190px;padding:8px 12px;" onchange="this.form.submit()">
                        <option value="">Todos clientes</option>
                        <?php foreach ($clientes as $cl): ?>
                        <option value="<?= $cl['id'] ?>" <?= $filter_cliente===$cl['id']?'selected':'' ?>><?= clean($cl['empresa']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($filter_status || $filter_priority || $filter_cliente): ?>
                    <a href="tarefas.php" class="btn btn-secondary btn-sm">✕ Limpar</a>
                    <?php endif; ?>
                </form>
                <button onclick="openModal('modalNovaTarefa')" class="btn btn-primary">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Nova Tarefa
                </button>
            </div>

            <!-- Tasks table -->
            <div class="card">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr><th>Tarefa</th><th>Cliente</th><th>Prioridade</th><th>Prazo</th><th>Status</th><th>Responsável</th><th>Ações</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tarefas)): ?>
                            <tr><td colspan="7">
                                <div class="empty-state">
                                    <div class="empty-icon">📋</div>
                                    <h3>Nenhuma tarefa encontrada</h3>
                                    <p>Crie uma nova tarefa ou ajuste os filtros.</p>
                                </div>
                            </td></tr>
                            <?php else: ?>
                            <?php foreach ($tarefas as $t): ?>
                            <tr>
                                <td>
                                    <div style="font-size:13px;font-weight:600;color:var(--text-primary);"><?= clean($t['titulo']) ?></div>
                                    <?php if ($t['categoria']): ?>
                                    <span style="font-size:11px;color:var(--text-muted);"><?= clean($t['categoria']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($t['cliente_nome']): ?>
                                    <a href="<?= BASE_URL ?>/admin/cliente-detalhe.php?id=<?= $t['cliente_id'] ?>" style="font-size:12px;color:var(--blue-primary);text-decoration:none;"><?= clean($t['cliente_nome']) ?></a>
                                    <?php else: ?><span style="color:var(--text-muted);font-size:12px;">Geral</span><?php endif; ?>
                                </td>
                                <td><?= prioridadeBadge($t['prioridade']) ?></td>
                                <td style="font-size:12px;font-weight:<?= ($t['prazo'] && $t['prazo'] < date('Y-m-d') && $t['status'] !== 'concluida') ? '700' : '400' ?>;color:<?= ($t['prazo'] && $t['prazo'] < date('Y-m-d') && $t['status'] !== 'concluida') ? 'var(--red)' : 'var(--text-secondary)' ?>;">
                                    <?= dataBR($t['prazo']) ?>
                                </td>
                                <td><?= statusTarefaBadge($t['status']) ?></td>
                                <td style="font-size:12px;color:var(--text-secondary);"><?= clean($t['responsavel'] ?? '—') ?></td>
                                <td>
                                    <div style="display:flex;gap:6px;">
                                        <?php if ($t['status'] !== 'concluida'): ?>
                                        <button class="btn btn-secondary btn-sm" onclick="quickCompleteTask(<?= $t['id'] ?>, this)" title="Concluir">✓</button>
                                        <?php endif; ?>
                                        <button class="btn btn-danger btn-icon" onclick="excluirTarefa(<?= $t['id'] ?>)">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Nova Tarefa -->
<div class="modal-overlay" id="modalNovaTarefa">
    <div class="modal">
        <div class="modal-header"><span class="modal-title">📋 Nova Tarefa</span><button class="modal-close" onclick="closeModal('modalNovaTarefa')">×</button></div>
        <form onsubmit="criarTarefa(event)">
            <div class="modal-body">
                <div class="form-group"><label class="form-label">Título *</label><input type="text" name="titulo" class="form-control" required></div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Cliente (opcional)</label>
                        <select name="cliente_id" class="form-control">
                            <option value="">Tarefa Geral</option>
                            <?php foreach ($clientes as $cl): ?>
                            <option value="<?= $cl['id'] ?>"><?= clean($cl['empresa']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Prioridade</label>
                        <select name="prioridade" class="form-control">
                            <option value="baixa">Baixa</option>
                            <option value="media" selected>Média</option>
                            <option value="alta">Alta</option>
                            <option value="urgente">Urgente</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label class="form-label">Prazo</label><input type="date" name="prazo" class="form-control"></div>
                    <div class="form-group"><label class="form-label">Responsável</label><input type="text" name="responsavel" class="form-control"></div>
                </div>
                <div class="form-group"><label class="form-label">Categoria</label><input type="text" name="categoria" class="form-control" placeholder="Ex: Tráfego, Gestão..."></div>
                <div class="form-group"><label class="form-label">Descrição</label><textarea name="descricao" class="form-control" rows="3"></textarea></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalNovaTarefa')">Cancelar</button>
                <button type="submit" class="btn btn-primary">Criar Tarefa</button>
            </div>
        </form>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
const BASE = '<?= BASE_URL ?>';
async function criarTarefa(e) {
    e.preventDefault();
    const r = await fetch(BASE+'/api/tasks/create.php', {method:'POST', body: new FormData(e.target)});
    const j = await r.json();
    if (j.success) { showToast('Tarefa criada!', 'success'); setTimeout(() => location.reload(), 900); }
    else showToast(j.message || 'Erro.', 'error');
}
async function excluirTarefa(id) {
    if (!confirm('Excluir esta tarefa?')) return;
    const fd = new FormData(); fd.append('id', id);
    const r = await fetch(BASE+'/api/tasks/delete.php', {method:'POST',body:fd});
    const j = await r.json();
    if (j.success) { showToast('Tarefa excluída.', 'success'); setTimeout(() => location.reload(), 700); }
    else showToast('Erro.', 'error');
}
</script>
</body>
</html>
