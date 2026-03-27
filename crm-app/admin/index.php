<?php
// admin/index.php - Main Dashboard
require_once __DIR__ . '/../config/app.php';
requireAuth();
atualizarStatusTarefasAtrasadas();

$db = getDB();

// ── KPI Queries ──────────────────────────────────────────────
$total_clientes   = (int)$db->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
$clientes_ativos  = (int)$db->query("SELECT COUNT(*) FROM clientes WHERE status = 'em_execucao'")->fetchColumn();
$clientes_pausa   = (int)$db->query("SELECT COUNT(*) FROM clientes WHERE status IN ('pausado','aguardando_cliente')")->fetchColumn();
$tarefas_pend     = (int)$db->query("SELECT COUNT(*) FROM tarefas WHERE status IN ('pendente','atrasada')")->fetchColumn();
$tarefas_done     = (int)$db->query("SELECT COUNT(*) FROM tarefas WHERE status = 'concluida'")->fetchColumn();
$tarefas_atrs     = (int)$db->query("SELECT COUNT(*) FROM tarefas WHERE status = 'atrasada'")->fetchColumn();

// Revenue by platform
$rev_kiwify  = (float)$db->query("SELECT COALESCE(SUM(valor_bruto),0) FROM vendas_plataformas WHERE plataforma='kiwify' AND status='aprovado'")->fetchColumn();
$rev_hotmart = (float)$db->query("SELECT COALESCE(SUM(valor_bruto),0) FROM vendas_plataformas WHERE plataforma='hotmart' AND status='aprovado'")->fetchColumn();
$rev_tmb     = (float)$db->query("SELECT COALESCE(SUM(valor_bruto),0) FROM vendas_plataformas WHERE plataforma='tmb' AND status='aprovado'")->fetchColumn();
$rev_total   = $rev_kiwify + $rev_hotmart + $rev_tmb;

$inv_trafego = (float)$db->query("SELECT COALESCE(SUM(valor_investido),0) FROM metricas_trafego")->fetchColumn();
$total_leads  = (int)$db->query("SELECT COALESCE(SUM(leads),0) FROM metricas_trafego")->fetchColumn();

$demandas_pendentes  = (int)$db->query("SELECT COUNT(*) FROM demandas_cliente WHERE status='pendente'")->fetchColumn();
$entregas_pendentes  = (int)$db->query("SELECT COUNT(*) FROM entregas_agencia WHERE status='pendente'")->fetchColumn();

// ── Charts Data ───────────────────────────────────────────────
// Revenue by client (top 5)
$stmt = $db->query("
    SELECT c.empresa, COALESCE(SUM(v.valor_bruto),0) AS total
    FROM clientes c
    LEFT JOIN vendas_plataformas v ON v.cliente_id = c.id AND v.status='aprovado'
    GROUP BY c.id ORDER BY total DESC LIMIT 6
");
$rev_by_client = $stmt->fetchAll();

// Traffic per cliente
$stmt = $db->query("
    SELECT c.empresa, COALESCE(SUM(m.valor_investido),0) AS total
    FROM clientes c
    LEFT JOIN metricas_trafego m ON m.cliente_id = c.id
    GROUP BY c.id ORDER BY total DESC LIMIT 6
");
$traffic_by_client = $stmt->fetchAll();

// Client status distribution
$stmt = $db->query("SELECT status, COUNT(*) as total FROM clientes GROUP BY status");
$status_dist = $stmt->fetchAll();

// Revenue by platform
$plat_data = [
    ['name' => 'Kiwify',  'value' => $rev_kiwify],
    ['name' => 'Hotmart', 'value' => $rev_hotmart],
    ['name' => 'TMB',     'value' => $rev_tmb],
];

// Tasks summary
$tasks_data = [
    ['name' => 'Concluídas', 'value' => $tarefas_done],
    ['name' => 'Pendentes',  'value' => $tarefas_pend - $tarefas_atrs],
    ['name' => 'Atrasadas',  'value' => $tarefas_atrs],
];

// ── Recent Clients ────────────────────────────────────────────
$recent_clients = $db->query("
    SELECT c.*, 
           (SELECT COUNT(*) FROM tarefas t WHERE t.cliente_id=c.id AND t.status IN ('pendente','atrasada')) as pendencias
    FROM clientes c ORDER BY c.created_at DESC LIMIT 5
")->fetchAll();

// ── Critical clients ─────────────────────────────────────────
$critical = $db->query("SELECT * FROM clientes WHERE saude_operacional IN ('critico','atencao') LIMIT 4")->fetchAll();

$pageTitle    = 'Dashboard';
$pageSubtitle = 'Visão geral da operação — ' . date('d/m/Y');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.1/dist/apexcharts.min.js"></script>
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>
    <div class="app-main">
        <?php include __DIR__ . '/../components/header.php'; ?>
        <div class="app-content">

            <!-- ── KPI Cards ───────────────────────────── -->
            <div class="kpi-grid">

                <div class="kpi-card" style="--kpi-color:#0ea5e9;--kpi-bg:rgba(14,165,233,0.1);--glow:rgba(14,165,233,0.15);">
                    <div class="kpi-icon">👥</div>
                    <div class="kpi-value"><?= $total_clientes ?></div>
                    <div class="kpi-label">Total de Clientes</div>
                    <div class="kpi-trend trend-up">▲ <?= $clientes_ativos ?> ativos</div>
                </div>

                <div class="kpi-card" style="--kpi-color:#10b981;--kpi-bg:rgba(16,185,129,0.1);--glow:rgba(16,185,129,0.15);">
                    <div class="kpi-icon">✅</div>
                    <div class="kpi-value"><?= $clientes_ativos ?></div>
                    <div class="kpi-label">Em Execução</div>
                    <div class="kpi-trend <?= $clientes_pausa > 0 ? 'trend-down' : 'trend-up' ?>">
                        <?= $clientes_pausa ?> em pausa
                    </div>
                </div>

                <div class="kpi-card" style="--kpi-color:#f59e0b;--kpi-bg:rgba(245,158,11,0.1);--glow:rgba(245,158,11,0.15);">
                    <div class="kpi-icon">📋</div>
                    <div class="kpi-value"><?= $tarefas_pend ?></div>
                    <div class="kpi-label">Tarefas Pendentes</div>
                    <?php if ($tarefas_atrs > 0): ?>
                    <div class="kpi-trend trend-down">⚠ <?= $tarefas_atrs ?> atrasadas</div>
                    <?php else: ?>
                    <div class="kpi-trend trend-up">✓ sem atrasos</div>
                    <?php endif; ?>
                </div>

                <div class="kpi-card" style="--kpi-color:#10b981;--kpi-bg:rgba(16,185,129,0.1);--glow:rgba(16,185,129,0.15);">
                    <div class="kpi-icon">🏆</div>
                    <div class="kpi-value"><?= $tarefas_done ?></div>
                    <div class="kpi-label">Tarefas Concluídas</div>
                    <div class="kpi-trend trend-up">✓ realizadas</div>
                </div>

                <div class="kpi-card" style="--kpi-color:#a855f7;--kpi-bg:rgba(168,85,247,0.1);--glow:rgba(168,85,247,0.15);">
                    <div class="kpi-icon">💰</div>
                    <div class="kpi-value" style="font-size:20px;"><?= formatBRL($rev_total) ?></div>
                    <div class="kpi-label">Receita (Plataformas)</div>
                    <div class="kpi-trend trend-up">▲ total vendido</div>
                </div>

                <div class="kpi-card" style="--kpi-color:#f97316;--kpi-bg:rgba(249,115,22,0.1);--glow:rgba(249,115,22,0.15);">
                    <div class="kpi-icon">📣</div>
                    <div class="kpi-value" style="font-size:20px;"><?= formatBRL($inv_trafego) ?></div>
                    <div class="kpi-label">Investimento Tráfego</div>
                    <div class="kpi-trend trend-neu"><?= formatNum($total_leads) ?> leads</div>
                </div>

                <div class="kpi-card" style="--kpi-color:#0ea5e9;--kpi-bg:rgba(14,165,233,0.1);">
                    <div class="kpi-icon">📥</div>
                    <div class="kpi-value"><?= $demandas_pendentes ?></div>
                    <div class="kpi-label">Pendências de Clientes</div>
                    <div class="kpi-trend <?= $demandas_pendentes > 5 ? 'trend-down' : 'trend-up' ?>">docs/acessos</div>
                </div>

                <div class="kpi-card" style="--kpi-color:#14b8a6;--kpi-bg:rgba(20,184,166,0.1);">
                    <div class="kpi-icon">📤</div>
                    <div class="kpi-value"><?= $entregas_pendentes ?></div>
                    <div class="kpi-label">Entregas Pendentes</div>
                    <div class="kpi-trend <?= $entregas_pendentes > 5 ? 'trend-down' : 'trend-up' ?>">a concluir</div>
                </div>

            </div>

            <!-- ── Charts Row 1 ────────────────────────── -->
            <div class="grid grid-2-3 gap-4 mb-6">
                <!-- Revenue by client -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--blue-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                            Receita por Cliente
                        </span>
                        <span class="badge badge-blue">Top 6</span>
                    </div>
                    <div class="chart-wrap">
                        <div id="chartRevClient" style="min-height:240px;"></div>
                    </div>
                </div>

                <!-- Platform Revenue + Status -->
                <div style="display:flex;flex-direction:column;gap:18px;">
                    <div class="card">
                        <div class="card-header">
                            <span class="card-title">💳 Receita por Plataforma</span>
                        </div>
                        <div class="chart-wrap">
                            <div id="chartPlatform" style="min-height:160px;"></div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header">
                            <span class="card-title">📊 Status dos Clientes</span>
                        </div>
                        <div class="chart-wrap">
                            <div id="chartStatus" style="min-height:140px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Charts Row 2 ────────────────────────── -->
            <div class="grid grid-2 gap-4 mb-6">
                <!-- Traffic investment -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">📣 Investimento em Tráfego</span>
                        <span class="badge badge-orange">Meta Ads</span>
                    </div>
                    <div class="chart-wrap">
                        <div id="chartTraffic" style="min-height:200px;"></div>
                    </div>
                </div>

                <!-- Tasks summary -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">✅ Status das Tarefas</span>
                    </div>
                    <div class="chart-wrap">
                        <div id="chartTasks" style="min-height:200px;"></div>
                    </div>
                </div>
            </div>

            <!-- ── Bottom Row ──────────────────────────── -->
            <div class="grid grid-2-3 gap-4">
                <!-- Recent Clients -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">👥 Últimos Clientes</span>
                        <a href="<?= BASE_URL ?>/admin/clientes.php" class="btn btn-secondary btn-sm">Ver todos</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th>Status</th>
                                    <th>Saúde</th>
                                    <th>Pendências</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_clients as $c): ?>
                                <tr>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:10px;">
                                            <div class="client-avatar" style="background:linear-gradient(135deg,<?= clean($c['cor_avatar']) ?>,var(--blue-vibrant));width:32px;height:32px;font-size:11px;">
                                                <?= initials($c['empresa']) ?>
                                            </div>
                                            <div>
                                                <a href="<?= BASE_URL ?>/admin/cliente-detalhe.php?id=<?= $c['id'] ?>"
                                                   style="font-size:13px;font-weight:600;color:var(--text-primary);text-decoration:none;">
                                                    <?= clean($c['empresa']) ?>
                                                </a>
                                                <div style="font-size:11px;color:var(--text-muted);"><?= clean($c['responsavel']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= statusClienteBadge($c['status']) ?></td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:6px;">
                                            <span class="health-dot <?= $c['saude_operacional'] ?>"></span>
                                            <span style="font-size:12px;color:var(--text-secondary);"><?= ucfirst($c['saude_operacional']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($c['pendencias'] > 0): ?>
                                            <span class="badge badge-yellow"><?= $c['pendencias'] ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-green">0</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Critical / Attention clients -->
                <div style="display:flex;flex-direction:column;gap:16px;">
                    <div class="card">
                        <div class="card-header">
                            <span class="card-title">⚠️ Clientes que Precisam de Atenção</span>
                        </div>
                        <div class="card-body" style="padding-top:12px;">
                            <?php if (empty($critical)): ?>
                                <div class="empty-state" style="padding:20px;">
                                    <div class="empty-icon">✅</div>
                                    <p>Todos os clientes estão saudáveis!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($critical as $c): ?>
                                <a href="<?= BASE_URL ?>/admin/cliente-detalhe.php?id=<?= $c['id'] ?>"
                                   style="display:flex;align-items:center;gap:12px;padding:12px;border-radius:10px;border:1px solid var(--border);background:var(--bg-primary);margin-bottom:8px;text-decoration:none;transition:all .2s;"
                                   onmouseover="this.style.borderColor='var(--border-hover)'" onmouseout="this.style.borderColor='var(--border)'">
                                    <span class="health-dot <?= $c['saude_operacional'] ?>" style="width:12px;height:12px;flex-shrink:0;"></span>
                                    <div style="flex:1;min-width:0;">
                                        <div style="font-size:13px;font-weight:600;color:var(--text-primary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= clean($c['empresa']) ?></div>
                                        <div style="font-size:11px;color:var(--text-muted);"><?= clean($c['nicho'] ?? '—') ?></div>
                                    </div>
                                    <?= saudeBadge($c['saude_operacional']) ?>
                                </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick actions -->
                    <div class="card">
                        <div class="card-header">
                            <span class="card-title">⚡ Ações Rápidas</span>
                        </div>
                        <div class="card-body" style="display:flex;flex-direction:column;gap:8px;padding-top:12px;">
                            <button onclick="openModal('modalNovoCliente')" class="btn btn-primary btn-sm" style="justify-content:flex-start;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                Novo Cliente
                            </button>
                            <a href="<?= BASE_URL ?>/admin/tarefas.php" class="btn btn-secondary btn-sm" style="justify-content:flex-start;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                Ver Tarefas
                            </a>
                            <a href="<?= BASE_URL ?>/admin/kanban.php" class="btn btn-secondary btn-sm" style="justify-content:flex-start;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="5" height="18" rx="1"/><rect x="10" y="3" width="5" height="12" rx="1"/><rect x="17" y="3" width="5" height="15" rx="1"/></svg>
                                Abrir Kanban
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /app-content -->
    </div><!-- /app-main -->
</div><!-- /app-layout -->

<!-- ── Modal: Novo Cliente ─────────────────────────────────── -->
<div class="modal-overlay" id="modalNovoCliente">
    <div class="modal">
        <div class="modal-header">
            <span class="modal-title">➕ Novo Cliente</span>
            <button class="modal-close" onclick="closeModal('modalNovoCliente')">×</button>
        </div>
        <form id="formNovoCliente" onsubmit="submitNovoCliente(event)">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Empresa *</label>
                        <input type="text" name="empresa" class="form-control" required placeholder="Nome da empresa">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Responsável *</label>
                        <input type="text" name="responsavel" class="form-control" required placeholder="Nome do responsável">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">E-mail</label>
                        <input type="email" name="email" class="form-control" placeholder="email@empresa.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="telefone" class="form-control" placeholder="(11) 99999-9999">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nicho/Ramo</label>
                        <input type="text" name="nicho" class="form-control" placeholder="Ex: Saúde, Fitness...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Valor Mensal (R$)</label>
                        <input type="number" name="valor_mensal" class="form-control" step="0.01" placeholder="0.00">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="onboarding">Onboarding</option>
                            <option value="aguardando_cliente">Aguardando Cliente</option>
                            <option value="em_execucao">Em Execução</option>
                            <option value="revisao">Revisão</option>
                            <option value="concluido">Concluído</option>
                            <option value="pausado">Pausado</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gestor Responsável</label>
                        <input type="text" name="gestor_responsavel" class="form-control" placeholder="Nome do gestor">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Observações</label>
                    <textarea name="observacoes" class="form-control" rows="3" placeholder="Notas sobre este cliente..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalNovoCliente')">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnSalvarCliente">Salvar Cliente</button>
            </div>
        </form>
    </div>
</div>

<!-- ── Toast container ────────────────────────────────────── -->
<div class="toast-container" id="toastContainer"></div>

<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
// ── ApexCharts Configuration ──────────────────────────────────
const chartDefaults = {
    chart: { background: 'transparent', toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
    theme: { mode: 'dark' },
    grid: { borderColor: 'rgba(30,80,160,0.15)', strokeDashArray: 4 },
    tooltip: { theme: 'dark', style: { fontFamily: 'Inter, sans-serif', fontSize: '12px' } },
};

// Revenue by client
new ApexCharts(document.getElementById('chartRevClient'), {
    ...chartDefaults,
    chart: { ...chartDefaults.chart, type: 'bar', height: 240 },
    series: [{ name: 'Receita', data: [<?= implode(',', array_column($rev_by_client, 'total')) ?>] }],
    xaxis: { categories: [<?= implode(',', array_map(fn($r) => '"' . addslashes($r['empresa']) . '"', $rev_by_client)) ?>], labels: { style: { colors: '#94a3b8', fontSize: '11px' } } },
    yaxis: { labels: { style: { colors: '#94a3b8' }, formatter: v => 'R$ ' + Number(v).toLocaleString('pt-BR') } },
    colors: ['#0ea5e9'],
    plotOptions: { bar: { borderRadius: 5, columnWidth: '50%' } },
    fill: { type: 'gradient', gradient: { type: 'vertical', gradientToColors: ['#1d4ed8'], stops: [0, 100] } },
    dataLabels: { enabled: false },
}).render();

// Platform
new ApexCharts(document.getElementById('chartPlatform'), {
    ...chartDefaults,
    chart: { ...chartDefaults.chart, type: 'donut', height: 160 },
    series: [<?= $rev_kiwify ?>, <?= $rev_hotmart ?>, <?= $rev_tmb ?>],
    labels: ['Kiwify', 'Hotmart', 'TMB'],
    colors: ['#0ea5e9', '#7c3aed', '#10b981'],
    plotOptions: { pie: { donut: { size: '60%', labels: { show: true, total: { show: true, label: 'Total', formatter: () => 'R$ ' + <?= $rev_total ?>.toLocaleString('pt-BR') } } } } },
    legend: { position: 'right', fontSize: '12px', labels: { colors: '#94a3b8' } },
    dataLabels: { enabled: false },
}).render();

// Client status
const statusMap = { 'onboarding': 'Onboarding', 'aguardando_cliente': 'Aguardando', 'em_execucao': 'Em Exec.', 'revisao': 'Revisão', 'concluido': 'Concluído', 'pausado': 'Pausado' };
const statusDist = <?= json_encode($status_dist) ?>;
new ApexCharts(document.getElementById('chartStatus'), {
    ...chartDefaults,
    chart: { ...chartDefaults.chart, type: 'bar', height: 140 },
    series: [{ name: 'Clientes', data: statusDist.map(s => s.total) }],
    xaxis: { categories: statusDist.map(s => statusMap[s.status] || s.status), labels: { style: { colors: '#94a3b8', fontSize: '10px' } } },
    yaxis: { show: false },
    colors: ['#1d4ed8'],
    plotOptions: { bar: { borderRadius: 4, columnWidth: '60%' } },
    dataLabels: { enabled: false },
}).render();

// Traffic
const trafficData = <?= json_encode($traffic_by_client) ?>;
new ApexCharts(document.getElementById('chartTraffic'), {
    ...chartDefaults,
    chart: { ...chartDefaults.chart, type: 'area', height: 200 },
    series: [{ name: 'Investido', data: trafficData.map(t => t.total) }],
    xaxis: { categories: trafficData.map(t => t.empresa), labels: { style: { colors: '#94a3b8', fontSize: '11px' } } },
    yaxis: { labels: { style: { colors: '#94a3b8' }, formatter: v => 'R$ ' + v.toLocaleString('pt-BR') } },
    colors: ['#f97316'],
    fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.05 } },
    stroke: { curve: 'smooth', width: 2 },
    dataLabels: { enabled: false },
}).render();

// Tasks
new ApexCharts(document.getElementById('chartTasks'), {
    ...chartDefaults,
    chart: { ...chartDefaults.chart, type: 'radialBar', height: 200 },
    series: [
        Math.round(<?= $tarefas_done ?> / Math.max(<?= $tarefas_done + $tarefas_pend ?>, 1) * 100),
        Math.round(<?= $tarefas_atrs ?> / Math.max(<?= $tarefas_done + $tarefas_pend ?>, 1) * 100),
    ],
    labels: ['Concluídas', 'Atrasadas'],
    colors: ['#10b981', '#ef4444'],
    plotOptions: { radialBar: { hollow: { size: '40%' }, dataLabels: { total: { show: true, label: 'Total', formatter: () => <?= $tarefas_done + $tarefas_pend ?> } } } },
}).render();

// ── Form: Novo Cliente ────────────────────────────────────────
async function submitNovoCliente(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSalvarCliente');
    btn.textContent = 'Salvando...'; btn.disabled = true;
    const fd = new FormData(document.getElementById('formNovoCliente'));
    const r = await fetch('<?= BASE_URL ?>/api/clients/create.php', { method: 'POST', body: fd });
    const j = await r.json();
    btn.textContent = 'Salvar Cliente'; btn.disabled = false;
    if (j.success) {
        closeModal('modalNovoCliente');
        showToast('Cliente cadastrado com sucesso!', 'success');
        setTimeout(() => window.location.reload(), 1200);
    } else {
        showToast(j.message || 'Erro ao salvar.', 'error');
    }
}
</script>
</body>
</html>
