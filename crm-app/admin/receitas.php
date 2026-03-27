<?php
// admin/receitas.php - Revenue Management Page
require_once __DIR__ . '/../config/app.php';
requireAuth();

$db = getDB();

$filter_plat = $_GET['plataforma'] ?? '';
$filter_cli  = (int)($_GET['cliente'] ?? 0);

$where = ['1=1'];
$params = [];
if ($filter_plat) { $where[] = 'v.plataforma = ?'; $params[] = $filter_plat; }
if ($filter_cli)  { $where[] = 'v.cliente_id = ?';  $params[] = $filter_cli; }

$stmt = $db->prepare("
    SELECT v.*, c.empresa AS cliente_nome
    FROM vendas_plataformas v
    LEFT JOIN clientes c ON c.id = v.cliente_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY v.data_venda DESC
");
$stmt->execute($params);
$vendas = $stmt->fetchAll();

$clientes  = $db->query("SELECT id, empresa FROM clientes ORDER BY empresa")->fetchAll();
$total_bruto  = array_sum(array_column(array_filter($vendas, fn($v)=>$v['status']==='aprovado'), 'valor_bruto'));
$total_liquido= array_sum(array_column(array_filter($vendas, fn($v)=>$v['status']==='aprovado'), 'valor_liquido'));

// By platform
$by_plat = $db->query("SELECT plataforma, SUM(valor_bruto) AS total, COUNT(*) AS qtd FROM vendas_plataformas WHERE status='aprovado' GROUP BY plataforma")->fetchAll();

$pageTitle    = 'Receitas';
$pageSubtitle = 'Controle de vendas por plataforma';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receitas — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>
    <div class="app-main">
        <?php include __DIR__ . '/../components/header.php'; ?>
        <div class="app-content">

            <!-- KPIs de receita -->
            <div class="kpi-grid" style="margin-bottom:24px;">
                <div class="kpi-card" style="--kpi-color:#10b981;--kpi-bg:rgba(16,185,129,0.1);">
                    <div class="kpi-icon">💰</div>
                    <div class="kpi-value" style="font-size:18px;"><?= formatBRL($total_bruto) ?></div>
                    <div class="kpi-label">Receita Bruta Total</div>
                </div>
                <div class="kpi-card" style="--kpi-color:#0ea5e9;--kpi-bg:rgba(14,165,233,0.1);">
                    <div class="kpi-icon">✅</div>
                    <div class="kpi-value" style="font-size:18px;"><?= formatBRL($total_liquido) ?></div>
                    <div class="kpi-label">Receita Líquida Total</div>
                </div>
                <?php foreach ($by_plat as $p): ?>
                <div class="kpi-card" style="--kpi-color:#a855f7;--kpi-bg:rgba(168,85,247,0.1);">
                    <div class="kpi-icon">🏪</div>
                    <div class="kpi-value" style="font-size:18px;"><?= formatBRL((float)$p['total']) ?></div>
                    <div class="kpi-label"><?= ucfirst($p['plataforma']) ?> (<?= $p['qtd'] ?> vendas)</div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Filters + add -->
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
                <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;">
                    <select name="plataforma" class="form-control" style="width:150px;padding:8px 12px;" onchange="this.form.submit()">
                        <option value="">Todas plataformas</option>
                        <option value="kiwify"  <?= $filter_plat==='kiwify' ?'selected':''?>>Kiwify</option>
                        <option value="tmb"     <?= $filter_plat==='tmb'    ?'selected':''?>>TMB</option>
                        <option value="hotmart" <?= $filter_plat==='hotmart'?'selected':''?>>Hotmart</option>
                        <option value="monetizze"<?= $filter_plat==='monetizze'?'selected':''?>>Monetizze</option>
                        <option value="eduzz"   <?= $filter_plat==='eduzz'  ?'selected':''?>>Eduzz</option>
                        <option value="outros"  <?= $filter_plat==='outros' ?'selected':''?>>Outros</option>
                    </select>
                    <select name="cliente" class="form-control" style="width:190px;padding:8px 12px;" onchange="this.form.submit()">
                        <option value="">Todos clientes</option>
                        <?php foreach ($clientes as $cl): ?>
                        <option value="<?= $cl['id'] ?>" <?= $filter_cli===$cl['id']?'selected':''?>><?= clean($cl['empresa']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($filter_plat || $filter_cli): ?><a href="receitas.php" class="btn btn-secondary btn-sm">✕ Limpar</a><?php endif; ?>
                </form>
                <button onclick="openModal('modalNovaVenda')" class="btn btn-primary">+ Nova Venda</button>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>Produto</th><th>Plataforma</th><th>Cliente</th><th>Valor Bruto</th><th>Valor Líquido</th><th>Status</th><th>Data</th><th>Ações</th></tr></thead>
                        <tbody>
                            <?php if (empty($vendas)): ?>
                            <tr><td colspan="8"><div class="empty-state"><div class="empty-icon">💳</div><h3>Nenhuma venda encontrada</h3></div></td></tr>
                            <?php else: ?>
                            <?php foreach ($vendas as $v): ?>
                            <tr>
                                <td style="font-size:13px;font-weight:500;"><?= clean($v['produto']) ?></td>
                                <td><?php
                                    $plat_colors = ['kiwify'=>'badge-blue','hotmart'=>'badge-purple','tmb'=>'badge-teal','outros'=>'badge-gray'];
                                    $pc = $plat_colors[$v['plataforma']] ?? 'badge-gray';
                                    echo '<span class="badge '.$pc.'">'.ucfirst($v['plataforma']).'</span>';
                                ?></td>
                                <td style="font-size:12px;color:var(--text-secondary);"><?= $v['cliente_nome'] ? clean($v['cliente_nome']) : '—' ?></td>
                                <td style="font-weight:600;color:var(--green);"><?= formatBRL((float)$v['valor_bruto']) ?></td>
                                <td style="color:var(--text-secondary);"><?= formatBRL((float)$v['valor_liquido']) ?></td>
                                <td><?php
                                    $sc=['aprovado'=>'badge-green','pendente'=>'badge-yellow','cancelado'=>'badge-red','reembolsado'=>'badge-orange'];
                                    echo '<span class="badge '.($sc[$v['status']]??'badge-gray').'">'.ucfirst($v['status']).'</span>';
                                ?></td>
                                <td style="font-size:12px;color:var(--text-muted);"><?= dataBR($v['data_venda']) ?></td>
                                <td><button class="btn btn-danger btn-icon" onclick="excluirVenda(<?= $v['id'] ?>)"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg></button></td>
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

<!-- Modal: Nova Venda -->
<div class="modal-overlay" id="modalNovaVenda">
    <div class="modal"><div class="modal-header"><span class="modal-title">💳 Nova Venda</span><button class="modal-close" onclick="closeModal('modalNovaVenda')">×</button></div>
    <form onsubmit="salvarVenda(event)"><div class="modal-body">
        <div class="form-row">
            <div class="form-group"><label class="form-label">Plataforma *</label>
                <select name="plataforma" class="form-control" required>
                    <option value="kiwify">Kiwify</option><option value="hotmart">Hotmart</option>
                    <option value="tmb">TMB</option><option value="monetizze">Monetizze</option>
                    <option value="eduzz">Eduzz</option><option value="outros">Outros</option>
                </select>
            </div>
            <div class="form-group"><label class="form-label">Cliente</label>
                <select name="cliente_id" class="form-control">
                    <option value="">Nenhum</option>
                    <?php foreach ($clientes as $cl): ?>
                    <option value="<?= $cl['id'] ?>"><?= clean($cl['empresa']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group"><label class="form-label">Produto *</label><input type="text" name="produto" class="form-control" required></div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Valor Bruto (R$) *</label><input type="number" name="valor_bruto" class="form-control" step="0.01" required></div>
            <div class="form-group"><label class="form-label">Valor Líquido (R$)</label><input type="number" name="valor_liquido" class="form-control" step="0.01"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Status</label><select name="status" class="form-control"><option value="aprovado">Aprovado</option><option value="pendente">Pendente</option><option value="cancelado">Cancelado</option><option value="reembolsado">Reembolsado</option></select></div>
            <div class="form-group"><label class="form-label">Data da Venda *</label><input type="date" name="data_venda" class="form-control" required value="<?= date('Y-m-d') ?>"></div>
        </div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('modalNovaVenda')">Cancelar</button><button type="submit" class="btn btn-primary">Salvar Venda</button></div></form></div>
</div>

<div class="toast-container" id="toastContainer"></div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
const BASE = '<?= BASE_URL ?>';
async function salvarVenda(e) {
    e.preventDefault();
    const r = await fetch(BASE+'/api/revenue/create.php', {method:'POST', body: new FormData(e.target)});
    const j = await r.json();
    if (j.success) { showToast('Venda registrada!', 'success'); setTimeout(() => location.reload(), 900); }
    else showToast(j.message || 'Erro.', 'error');
}
async function excluirVenda(id) {
    if (!confirm('Excluir esta venda?')) return;
    const fd = new FormData(); fd.append('id', id);
    const r = await fetch(BASE+'/api/revenue/delete.php', {method:'POST',body:fd});
    const j = await r.json();
    if (j.success) { showToast('Venda removida.', 'success'); setTimeout(() => location.reload(), 700); }
    else showToast('Erro.', 'error');
}
</script>
</body>
</html>
