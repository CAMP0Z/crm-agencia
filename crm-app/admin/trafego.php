<?php
// admin/trafego.php - Traffic Metrics Page
require_once __DIR__ . '/../config/app.php';
requireAuth();

$db = getDB();
$filter_cli  = (int)($_GET['cliente'] ?? 0);
$filter_plat = $_GET['plataforma'] ?? '';

$where = ['1=1'];
$params = [];
if ($filter_cli)  { $where[] = 'm.cliente_id = ?';  $params[] = $filter_cli; }
if ($filter_plat) { $where[] = 'm.plataforma = ?';  $params[] = $filter_plat; }

$stmt = $db->prepare("
    SELECT m.*, c.empresa AS cliente_nome
    FROM metricas_trafego m
    LEFT JOIN clientes c ON c.id = m.cliente_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY m.periodo_inicio DESC
");
$stmt->execute($params);
$metricas = $stmt->fetchAll();
$clientes = $db->query("SELECT id, empresa FROM clientes ORDER BY empresa")->fetchAll();

$total_invest = array_sum(array_column($metricas, 'valor_investido'));
$total_leads  = array_sum(array_column($metricas, 'leads'));
$total_impressoes = array_sum(array_column($metricas, 'impressoes'));
$avg_ctr = count($metricas) ? array_sum(array_column($metricas, 'ctr')) / count($metricas) : 0;
$avg_cpl = $total_leads > 0 ? $total_invest / $total_leads : 0;

$pageTitle = 'Tráfego Pago';
$pageSubtitle = 'Métricas de campanhas · Meta Ads e outras plataformas';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tráfego — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>
    <div class="app-main">
        <?php include __DIR__ . '/../components/header.php'; ?>
        <div class="app-content">

            <!-- KPIs -->
            <div class="kpi-grid" style="margin-bottom:24px;">
                <div class="kpi-card" style="--kpi-color:#f97316;--kpi-bg:rgba(249,115,22,0.1);">
                    <div class="kpi-icon">📣</div>
                    <div class="kpi-value" style="font-size:18px;"><?= formatBRL($total_invest) ?></div>
                    <div class="kpi-label">Total Investido</div>
                </div>
                <div class="kpi-card" style="--kpi-color:#0ea5e9;--kpi-bg:rgba(14,165,233,0.1);">
                    <div class="kpi-icon">👁</div>
                    <div class="kpi-value"><?= formatNum((int)$total_impressoes) ?></div>
                    <div class="kpi-label">Impressões Totais</div>
                </div>
                <div class="kpi-card" style="--kpi-color:#10b981;--kpi-bg:rgba(16,185,129,0.1);">
                    <div class="kpi-icon">🎯</div>
                    <div class="kpi-value"><?= formatNum((int)$total_leads) ?></div>
                    <div class="kpi-label">Leads Gerados</div>
                </div>
                <div class="kpi-card" style="--kpi-color:#a855f7;--kpi-bg:rgba(168,85,247,0.1);">
                    <div class="kpi-icon">📊</div>
                    <div class="kpi-value"><?= number_format($avg_ctr, 2) ?>%</div>
                    <div class="kpi-label">CTR Médio</div>
                </div>
                <div class="kpi-card" style="--kpi-color:#f59e0b;--kpi-bg:rgba(245,158,11,0.1);">
                    <div class="kpi-icon">💡</div>
                    <div class="kpi-value" style="font-size:18px;"><?= formatBRL($avg_cpl) ?></div>
                    <div class="kpi-label">Custo por Lead</div>
                </div>
            </div>

            <!-- API Integration Notice -->
            <div style="background:rgba(14,165,233,0.07);border:1px solid rgba(14,165,233,0.2);border-radius:10px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:12px;">
                <span style="font-size:22px;">⚡</span>
                <div>
                    <div style="font-size:13px;font-weight:600;color:var(--blue-primary);">Integração Meta Ads disponível</div>
                    <div style="font-size:12px;color:var(--text-muted);">Configure seu token de acesso em <strong>Configurações → Integrações</strong> para importar campanhas automaticamente.</div>
                </div>
                <a href="configuracoes.php" class="btn btn-secondary btn-sm" style="margin-left:auto;white-space:nowrap;">Configurar API</a>
            </div>

            <!-- Filters -->
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
                <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;">
                    <select name="cliente" class="form-control" style="width:190px;padding:8px 12px;" onchange="this.form.submit()">
                        <option value="">Todos clientes</option>
                        <?php foreach ($clientes as $cl): ?>
                        <option value="<?= $cl['id'] ?>" <?= $filter_cli===$cl['id']?'selected':''?>><?= clean($cl['empresa']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="plataforma" class="form-control" style="width:160px;padding:8px 12px;" onchange="this.form.submit()">
                        <option value="">Todas plataformas</option>
                        <option value="meta_ads"    <?= $filter_plat==='meta_ads'   ?'selected':''?>>Meta Ads</option>
                        <option value="google_ads"  <?= $filter_plat==='google_ads' ?'selected':''?>>Google Ads</option>
                        <option value="tiktok_ads"  <?= $filter_plat==='tiktok_ads' ?'selected':''?>>TikTok Ads</option>
                    </select>
                    <?php if ($filter_cli || $filter_plat): ?><a href="trafego.php" class="btn btn-secondary btn-sm">✕ Limpar</a><?php endif; ?>
                </form>
                <button onclick="openModal('modalNovaMetrica')" class="btn btn-primary">+ Registrar Métrica</button>
            </div>

            <div class="card">
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>Campanha</th><th>Cliente</th><th>Plataforma</th><th>Período</th><th>Investido</th><th>Impressões</th><th>CTR</th><th>Leads</th><th>CPL</th></tr></thead>
                        <tbody>
                            <?php if (empty($metricas)): ?>
                            <tr><td colspan="9"><div class="empty-state"><div class="empty-icon">📣</div><h3>Nenhuma métrica registrada</h3></div></td></tr>
                            <?php else: ?>
                            <?php foreach ($metricas as $m): ?>
                            <tr>
                                <td style="font-size:13px;font-weight:500;"><?= clean($m['campanha'] ?? '—') ?></td>
                                <td style="font-size:12px;color:var(--text-secondary);"><?= $m['cliente_nome'] ? clean($m['cliente_nome']) : '—' ?></td>
                                <td><span class="badge badge-orange"><?= str_replace('_',' ',ucwords($m['plataforma'],'_')) ?></span></td>
                                <td style="font-size:11px;color:var(--text-muted);"><?= dataBR($m['periodo_inicio']) ?> – <?= dataBR($m['periodo_fim']) ?></td>
                                <td style="font-weight:600;color:var(--orange);"><?= formatBRL((float)$m['valor_investido']) ?></td>
                                <td><?= formatNum((int)$m['impressoes']) ?></td>
                                <td><?= number_format((float)$m['ctr'], 2) ?>%</td>
                                <td style="font-weight:600;color:var(--blue-primary);"><?= (int)$m['leads'] ?></td>
                                <td><?= formatBRL((float)$m['custo_por_lead']) ?></td>
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

<!-- Modal: Nova Métrica -->
<div class="modal-overlay" id="modalNovaMetrica">
    <div class="modal"><div class="modal-header"><span class="modal-title">📣 Registrar Métrica</span><button class="modal-close" onclick="closeModal('modalNovaMetrica')">×</button></div>
    <form onsubmit="salvarMetrica(event)"><div class="modal-body">
        <div class="form-row">
            <div class="form-group"><label class="form-label">Cliente</label><select name="cliente_id" class="form-control" required><option value="">Selecione</option><?php foreach ($clientes as $cl): ?><option value="<?= $cl['id'] ?>"><?= clean($cl['empresa']) ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label class="form-label">Plataforma</label><select name="plataforma" class="form-control"><option value="meta_ads">Meta Ads</option><option value="google_ads">Google Ads</option><option value="tiktok_ads">TikTok Ads</option><option value="outros">Outros</option></select></div>
        </div>
        <div class="form-group"><label class="form-label">Campanha</label><input type="text" name="campanha" class="form-control" placeholder="Nome da campanha"></div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Período início *</label><input type="date" name="periodo_inicio" class="form-control" required></div>
            <div class="form-group"><label class="form-label">Período fim *</label><input type="date" name="periodo_fim" class="form-control" required></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Valor investido (R$)</label><input type="number" name="valor_investido" class="form-control" step="0.01" min="0"></div>
            <div class="form-group"><label class="form-label">Impressões</label><input type="number" name="impressoes" class="form-control" min="0"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Cliques</label><input type="number" name="cliques" class="form-control" min="0"></div>
            <div class="form-group"><label class="form-label">Leads</label><input type="number" name="leads" class="form-control" min="0"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">CTR (%)</label><input type="number" name="ctr" class="form-control" step="0.01"></div>
            <div class="form-group"><label class="form-label">CPC (R$)</label><input type="number" name="cpc" class="form-control" step="0.01"></div>
            <div class="form-group"><label class="form-label">Conversas WA</label><input type="number" name="conversas" class="form-control" min="0"></div>
        </div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('modalNovaMetrica')">Cancelar</button><button type="submit" class="btn btn-primary">Salvar Métrica</button></div></form></div>
</div>

<div class="toast-container" id="toastContainer"></div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
const BASE = '<?= BASE_URL ?>';
async function salvarMetrica(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    // Auto-calc CPL
    const invest = parseFloat(fd.get('valor_investido')) || 0;
    const leads  = parseInt(fd.get('leads')) || 0;
    fd.append('custo_por_lead', leads > 0 ? (invest/leads).toFixed(2) : 0);
    const r = await fetch(BASE+'/api/traffic/create.php', {method:'POST', body:fd});
    const j = await r.json();
    if (j.success) { showToast('Métrica salva!', 'success'); setTimeout(() => location.reload(), 900); }
    else showToast(j.message || 'Erro.', 'error');
}
</script>
</body>
</html>
