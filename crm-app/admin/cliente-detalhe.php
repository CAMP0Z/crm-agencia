<?php
// admin/cliente-detalhe.php - Client Detail Page
require_once __DIR__ . '/../config/app.php';
requireAuth();
atualizarStatusTarefasAtrasadas();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: clientes.php'); exit; }

$db = getDB();
$client = $db->prepare("SELECT * FROM clientes WHERE id=?");
$client->execute([$id]);
$c = $client->fetch();
if (!$c) { header('Location: clientes.php'); exit; }

// Update health score
$saude = calcularSaude($id);
if ($saude !== $c['saude_operacional']) {
    $db->prepare("UPDATE clientes SET saude_operacional=? WHERE id=?")->execute([$saude, $id]);
    $c['saude_operacional'] = $saude;
}

$demandas  = $db->prepare("SELECT * FROM demandas_cliente WHERE cliente_id=? ORDER BY ordem,id");
$demandas->execute([$id]); $demandas = $demandas->fetchAll();

$entregas  = $db->prepare("SELECT * FROM entregas_agencia WHERE cliente_id=? ORDER BY ordem,id");
$entregas->execute([$id]); $entregas = $entregas->fetchAll();

$tarefas   = $db->prepare("SELECT * FROM tarefas WHERE cliente_id=? ORDER BY prazo ASC,prioridade DESC");
$tarefas->execute([$id]); $tarefas = $tarefas->fetchAll();

$notas     = $db->prepare("SELECT * FROM notas_cliente WHERE cliente_id=? ORDER BY created_at DESC");
$notas->execute([$id]); $notas = $notas->fetchAll();

$metricas  = $db->prepare("SELECT * FROM metricas_trafego WHERE cliente_id=? ORDER BY periodo_inicio DESC LIMIT 6");
$metricas->execute([$id]); $metricas = $metricas->fetchAll();

$vendas    = $db->prepare("SELECT * FROM vendas_plataformas WHERE cliente_id=? ORDER BY data_venda DESC LIMIT 10");
$vendas->execute([$id]); $vendas = $vendas->fetchAll();

$historico = $db->prepare("SELECT * FROM historico_status WHERE cliente_id=? ORDER BY alterado_em DESC LIMIT 8");
$historico->execute([$id]); $historico = $historico->fetchAll();

// Counts
$done_d  = count(array_filter($demandas,  fn($x) => $x['status']==='concluido'));
$done_e  = count(array_filter($entregas,  fn($x) => $x['status']==='concluido'));
$done_t  = count(array_filter($tarefas,   fn($x) => $x['status']==='concluida'));
$total_receita = array_sum(array_column($vendas, 'valor_bruto'));
$total_invest  = array_sum(array_column($metricas, 'valor_investido'));

$pageTitle = clean($c['empresa']);
$pageSubtitle = 'Detalhes do cliente · ' . clean($c['responsavel']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= clean($c['empresa']) ?> — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>
    <div class="app-main">
        <?php include __DIR__ . '/../components/header.php'; ?>
        <div class="app-content">

            <!-- Breadcrumb -->
            <div style="margin-bottom:16px;font-size:13px;color:var(--text-muted);">
                <a href="clientes.php" style="color:var(--text-muted);text-decoration:none;">Clientes</a>
                <span style="margin:0 8px;">/</span>
                <span style="color:var(--text-primary);"><?= clean($c['empresa']) ?></span>
            </div>

            <!-- ── Client Header Card ─────────────────────── -->
            <div class="card" style="margin-bottom:24px;">
                <div class="card-body">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:16px;">
                        <div style="display:flex;align-items:center;gap:16px;">
                            <div class="client-avatar" style="width:62px;height:62px;font-size:20px;font-weight:800;background:linear-gradient(135deg,<?= clean($c['cor_avatar']) ?>,var(--blue-vibrant));border-radius:14px;">
                                <?= initials($c['empresa']) ?>
                            </div>
                            <div>
                                <h2 style="font-size:22px;font-weight:800;color:var(--text-primary);letter-spacing:-0.5px;margin-bottom:4px;"><?= clean($c['empresa']) ?></h2>
                                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                                    <span style="font-size:14px;color:var(--text-secondary);"><?= clean($c['responsavel']) ?></span>
                                    <?= statusClienteBadge($c['status']) ?>
                                    <?= saudeBadge($c['saude_operacional']) ?>
                                    <?php if ($c['nicho']): ?>
                                    <span class="badge badge-gray"><?= clean($c['nicho']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <button onclick="openModal('modalEditarCliente')" class="btn btn-secondary btn-sm">✏️ Editar</button>
                            <select class="form-control" style="width:160px;padding:6px 10px;font-size:12px;" onchange="updateStatus(<?= $id ?>, this.value)">
                                <option value="onboarding"        <?= $c['status']==='onboarding'        ?'selected':'' ?>>Onboarding</option>
                                <option value="aguardando_cliente"<?= $c['status']==='aguardando_cliente'?'selected':'' ?>>Aguardando Cliente</option>
                                <option value="em_execucao"       <?= $c['status']==='em_execucao'       ?'selected':'' ?>>Em Execução</option>
                                <option value="revisao"           <?= $c['status']==='revisao'           ?'selected':'' ?>>Revisão</option>
                                <option value="concluido"         <?= $c['status']==='concluido'         ?'selected':'' ?>>Concluído</option>
                                <option value="pausado"           <?= $c['status']==='pausado'           ?'selected':'' ?>>Pausado</option>
                            </select>
                        </div>
                    </div>

                    <!-- Info Grid -->
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-top:20px;padding-top:20px;border-top:1px solid var(--border);">
                        <?php $infos = [
                            ['📧', 'E-mail', $c['email']],
                            ['📱', 'Telefone', $c['telefone']],
                            ['📸', 'Instagram', $c['instagram']],
                            ['💰', 'Valor/Mês', formatBRL((float)$c['valor_mensal'])],
                            ['🎯', 'Plataforma', $c['plataforma_principal']],
                            ['👤', 'Gestor', $c['gestor_responsavel']],
                            ['📅', 'Entrada', dataBR($c['created_at'])],
                            ['🌐', 'Origem', $c['origem']],
                        ]; ?>
                        <?php foreach ($infos as [$icon, $label, $val]): if (!$val) continue; ?>
                        <div>
                            <div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;"><?= $icon ?> <?= $label ?></div>
                            <div style="font-size:13px;font-weight:500;color:var(--text-primary);"><?= clean((string)$val) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ── Progress Summary ───────────────────────── -->
            <div class="kpi-grid" style="grid-template-columns:repeat(auto-fill,minmax(180px,1fr));margin-bottom:24px;">
                <div class="kpi-card" style="--kpi-color:#0ea5e9;--kpi-bg:rgba(14,165,233,0.1);">
                    <div class="kpi-icon">📥</div>
                    <div class="kpi-value"><?= $done_d ?>/<?= count($demandas) ?></div>
                    <div class="kpi-label">Pendências Recebidas</div>
                    <div class="progress-bar" style="margin-top:8px;">
                        <div class="progress-fill" style="width:<?= count($demandas) ? round($done_d/count($demandas)*100) : 0 ?>%;"></div>
                    </div>
                </div>
                <div class="kpi-card" style="--kpi-color:#10b981;--kpi-bg:rgba(16,185,129,0.1);">
                    <div class="kpi-icon">📤</div>
                    <div class="kpi-value"><?= $done_e ?>/<?= count($entregas) ?></div>
                    <div class="kpi-label">Entregas Concluídas</div>
                    <div class="progress-bar" style="margin-top:8px;">
                        <div class="progress-fill" style="width:<?= count($entregas) ? round($done_e/count($entregas)*100) : 0 ?>%;background:linear-gradient(90deg,#10b981,#065f46);"></div>
                    </div>
                </div>
                <div class="kpi-card" style="--kpi-color:#f59e0b;--kpi-bg:rgba(245,158,11,0.1);">
                    <div class="kpi-icon">✅</div>
                    <div class="kpi-value"><?= $done_t ?>/<?= count($tarefas) ?></div>
                    <div class="kpi-label">Tarefas Concluídas</div>
                    <div class="progress-bar" style="margin-top:8px;">
                        <div class="progress-fill" style="width:<?= count($tarefas) ? round($done_t/count($tarefas)*100) : 0 ?>%;background:linear-gradient(90deg,#f59e0b,#b45309);"></div>
                    </div>
                </div>
                <div class="kpi-card" style="--kpi-color:#a855f7;--kpi-bg:rgba(168,85,247,0.1);">
                    <div class="kpi-icon">💰</div>
                    <div class="kpi-value" style="font-size:18px;"><?= formatBRL($total_receita) ?></div>
                    <div class="kpi-label">Receita Total</div>
                </div>
                <div class="kpi-card" style="--kpi-color:#f97316;--kpi-bg:rgba(249,115,22,0.1);">
                    <div class="kpi-icon">📣</div>
                    <div class="kpi-value" style="font-size:18px;"><?= formatBRL($total_invest) ?></div>
                    <div class="kpi-label">Investimento Tráfego</div>
                </div>
            </div>

            <!-- ── Main 2-col grid ────────────────────────── -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">

                <!-- Demandas do Cliente -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">📥 O que preciso do cliente</span>
                        <button onclick="openModal('modalDemanda')" class="btn btn-secondary btn-sm">+ Adicionar</button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($demandas)): ?>
                        <div class="empty-state" style="padding:20px;"><p>Nenhuma demanda cadastrada.</p></div>
                        <?php else: ?>
                        <div class="demand-list">
                            <?php foreach ($demandas as $d): ?>
                            <div class="demand-item">
                                <button class="demand-status-btn <?= $d['status'] ?>"
                                        data-status="<?= $d['status'] ?>"
                                        onclick="toggleDemandStatus(this, <?= $d['id'] ?>, 'demand')"
                                        title="Clique para avançar status">
                                    <?= $d['status']==='concluido' ? '✓' : ($d['status']==='em_andamento' ? '●' : '') ?>
                                </button>
                                <span class="demand-title <?= $d['status']==='concluido' ? 'done' : '' ?>"><?= clean($d['titulo']) ?></span>
                                <button onclick="deleteDemand(<?= $d['id'] ?>, 'demand')" style="background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:14px;padding:2px 4px;" title="Remover">×</button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Entregas da Agência -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">📤 O que o cliente precisa de mim</span>
                        <button onclick="openModal('modalEntrega')" class="btn btn-secondary btn-sm">+ Adicionar</button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($entregas)): ?>
                        <div class="empty-state" style="padding:20px;"><p>Nenhuma entrega cadastrada.</p></div>
                        <?php else: ?>
                        <div class="demand-list">
                            <?php foreach ($entregas as $e): ?>
                            <div class="demand-item">
                                <button class="demand-status-btn <?= $e['status'] ?>"
                                        data-status="<?= $e['status'] ?>"
                                        onclick="toggleDemandStatus(this, <?= $e['id'] ?>, 'delivery')"
                                        title="Clique para avançar status">
                                    <?= $e['status']==='concluido' ? '✓' : ($e['status']==='em_andamento' ? '●' : '') ?>
                                </button>
                                <span class="demand-title <?= $e['status']==='concluido' ? 'done' : '' ?>"><?= clean($e['titulo']) ?></span>
                                <?php if ($e['prazo']): ?>
                                <span style="font-size:10px;color:var(--text-muted);"><?= dataBR($e['prazo']) ?></span>
                                <?php endif; ?>
                                <button onclick="deleteDemand(<?= $e['id'] ?>, 'delivery')" style="background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:14px;padding:2px 4px;" title="Remover">×</button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ── Tarefas ────────────────────────────────── -->
            <div class="card" style="margin-bottom:20px;">
                <div class="card-header">
                    <span class="card-title">📋 Tarefas</span>
                    <button onclick="openModal('modalTarefa')" class="btn btn-primary btn-sm">+ Nova Tarefa</button>
                </div>
                <?php if (empty($tarefas)): ?>
                <div class="empty-state"><p>Nenhuma tarefa cadastrada.</p></div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>Tarefa</th><th>Prioridade</th><th>Prazo</th><th>Status</th><th>Responsável</th><th>Ações</th></tr></thead>
                        <tbody>
                            <?php foreach ($tarefas as $t): ?>
                            <tr>
                                <td>
                                    <div style="font-size:13px;font-weight:600;color:var(--text-primary);"><?= clean($t['titulo']) ?></div>
                                    <?php if ($t['descricao']): ?>
                                    <div style="font-size:11px;color:var(--text-muted);"><?= clean(substr($t['descricao'], 0, 60)) ?>...</div>
                                    <?php endif; ?>
                                </td>
                                <td><?= prioridadeBadge($t['prioridade']) ?></td>
                                <td style="font-size:12px;<?= ($t['prazo'] && $t['prazo'] < date('Y-m-d') && $t['status'] !== 'concluida') ? 'color:var(--red);font-weight:600;' : 'color:var(--text-secondary);' ?>">
                                    <?= dataBR($t['prazo']) ?>
                                </td>
                                <td><?= statusTarefaBadge($t['status']) ?></td>
                                <td style="font-size:12px;color:var(--text-secondary);"><?= clean($t['responsavel'] ?? '—') ?></td>
                                <td>
                                    <div style="display:flex;gap:6px;">
                                        <?php if ($t['status'] !== 'concluida'): ?>
                                        <button class="btn btn-secondary btn-sm" onclick="quickCompleteTask(<?= $t['id'] ?>, this)" title="Marcar concluída">✓</button>
                                        <?php endif; ?>
                                        <button class="btn btn-danger btn-icon" onclick="deleteTask(<?= $t['id'] ?>)">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- ── Notas + Histórico ──────────────────────── -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
                <!-- Notas -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">📝 Notas e Registros</span>
                        <button onclick="openModal('modalNota')" class="btn btn-secondary btn-sm">+ Nota</button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($notas)): ?>
                        <div class="empty-state" style="padding:20px;"><p>Nenhuma nota registrada.</p></div>
                        <?php else: ?>
                        <?php foreach ($notas as $n): ?>
                        <div style="padding:12px;border:1px solid var(--border);border-radius:8px;margin-bottom:8px;background:var(--bg-primary);">
                            <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                                <span style="font-size:12px;font-weight:600;color:var(--text-primary);"><?= clean($n['titulo'] ?? 'Nota') ?></span>
                                <span style="font-size:10px;color:var(--text-muted);"><?= dataBR($n['created_at']) ?></span>
                            </div>
                            <p style="font-size:12px;color:var(--text-secondary);line-height:1.5;"><?= nl2br(clean($n['conteudo'])) ?></p>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Histórico de status -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">🕐 Histórico de Status</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($historico)): ?>
                        <div class="empty-state" style="padding:20px;"><p>Sem alterações registradas.</p></div>
                        <?php else: ?>
                        <?php foreach ($historico as $h): ?>
                        <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--border);">
                            <span class="badge badge-gray" style="font-size:10px;"><?= clean(str_replace('_',' ', $h['status_anterior'])) ?></span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="var(--text-muted)" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                            <span class="badge badge-blue" style="font-size:10px;"><?= clean(str_replace('_',' ', $h['status_novo'])) ?></span>
                            <span style="font-size:10px;color:var(--text-muted);margin-left:auto;"><?= dataBR($h['alterado_em']) ?></span>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ── Métricas de Tráfego ────────────────────── -->
            <?php if (!empty($metricas)): ?>
            <div class="card" style="margin-bottom:20px;">
                <div class="card-header">
                    <span class="card-title">📣 Métricas de Tráfego</span>
                    <a href="<?= BASE_URL ?>/admin/trafego.php?cliente=<?= $id ?>" class="btn btn-secondary btn-sm">Ver todas</a>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>Campanha</th><th>Período</th><th>Investido</th><th>Impressões</th><th>CTR</th><th>Leads</th><th>Custo/Lead</th></tr></thead>
                        <tbody>
                            <?php foreach ($metricas as $m): ?>
                            <tr>
                                <td><span style="font-size:13px;font-weight:500;"><?= clean($m['campanha'] ?? '—') ?></span></td>
                                <td style="font-size:12px;color:var(--text-secondary);"><?= dataBR($m['periodo_inicio']) ?> → <?= dataBR($m['periodo_fim']) ?></td>
                                <td style="font-weight:600;color:var(--orange);"><?= formatBRL((float)$m['valor_investido']) ?></td>
                                <td><?= formatNum((int)$m['impressoes']) ?></td>
                                <td><?= number_format((float)$m['ctr'], 2) ?>%</td>
                                <td style="font-weight:600;color:var(--blue-primary);"><?= (int)$m['leads'] ?></td>
                                <td><?= formatBRL((float)$m['custo_por_lead']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($vendas)): ?>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">💳 Últimas Vendas</span>
                    <span class="badge badge-green"><?= formatBRL($total_receita) ?> total</span>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>Produto</th><th>Plataforma</th><th>Valor Bruto</th><th>Valor Líquido</th><th>Status</th><th>Data</th></tr></thead>
                        <tbody>
                            <?php foreach ($vendas as $v): ?>
                            <tr>
                                <td style="font-size:13px;font-weight:500;"><?= clean($v['produto']) ?></td>
                                <td><span class="badge badge-blue"><?= ucfirst($v['plataforma']) ?></span></td>
                                <td style="font-weight:600;color:var(--green);"><?= formatBRL((float)$v['valor_bruto']) ?></td>
                                <td style="color:var(--text-secondary);"><?= formatBRL((float)$v['valor_liquido']) ?></td>
                                <td><?php
                                    $bs = ['aprovado'=>'badge-green','pendente'=>'badge-yellow','cancelado'=>'badge-red','reembolsado'=>'badge-orange','chargeback'=>'badge-red'];
                                    echo '<span class="badge '.($bs[$v['status']]??'badge-gray').'">'.ucfirst($v['status']).'</span>';
                                ?></td>
                                <td style="font-size:12px;color:var(--text-muted);"><?= dataBR($v['data_venda']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Modals -->
<!-- Modal: Demanda -->
<div class="modal-overlay" id="modalDemanda">
    <div class="modal"><div class="modal-header"><span class="modal-title">📥 Nova Demanda do Cliente</span><button class="modal-close" onclick="closeModal('modalDemanda')">×</button></div>
    <form onsubmit="addItem(event,'demand')"><div class="modal-body">
        <input type="hidden" name="cliente_id" value="<?= $id ?>">
        <div class="form-group"><label class="form-label">Título *</label><input type="text" name="titulo" class="form-control" required placeholder="Ex: Acesso ao BM"></div>
        <div class="form-group"><label class="form-label">Descrição</label><textarea name="descricao" class="form-control" rows="2"></textarea></div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('modalDemanda')">Cancelar</button><button type="submit" class="btn btn-primary">Salvar</button></div></form></div>
</div>

<!-- Modal: Entrega -->
<div class="modal-overlay" id="modalEntrega">
    <div class="modal"><div class="modal-header"><span class="modal-title">📤 Nova Entrega</span><button class="modal-close" onclick="closeModal('modalEntrega')">×</button></div>
    <form onsubmit="addItem(event,'delivery')"><div class="modal-body">
        <input type="hidden" name="cliente_id" value="<?= $id ?>">
        <div class="form-group"><label class="form-label">Título *</label><input type="text" name="titulo" class="form-control" required placeholder="Ex: Landing Page criada"></div>
        <div class="form-group"><label class="form-label">Prazo</label><input type="date" name="prazo" class="form-control"></div>
        <div class="form-group"><label class="form-label">Descrição</label><textarea name="descricao" class="form-control" rows="2"></textarea></div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('modalEntrega')">Cancelar</button><button type="submit" class="btn btn-primary">Salvar</button></div></form></div>
</div>

<!-- Modal: Tarefa -->
<div class="modal-overlay" id="modalTarefa">
    <div class="modal"><div class="modal-header"><span class="modal-title">📋 Nova Tarefa</span><button class="modal-close" onclick="closeModal('modalTarefa')">×</button></div>
    <form onsubmit="addTarefa(event)"><div class="modal-body">
        <input type="hidden" name="cliente_id" value="<?= $id ?>">
        <div class="form-group"><label class="form-label">Título *</label><input type="text" name="titulo" class="form-control" required></div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Prioridade</label><select name="prioridade" class="form-control"><option value="baixa">Baixa</option><option value="media" selected>Média</option><option value="alta">Alta</option><option value="urgente">Urgente</option></select></div>
            <div class="form-group"><label class="form-label">Prazo</label><input type="date" name="prazo" class="form-control"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Responsável</label><input type="text" name="responsavel" class="form-control"></div>
            <div class="form-group"><label class="form-label">Categoria</label><input type="text" name="categoria" class="form-control"></div>
        </div>
        <div class="form-group"><label class="form-label">Descrição</label><textarea name="descricao" class="form-control" rows="2"></textarea></div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('modalTarefa')">Cancelar</button><button type="submit" class="btn btn-primary">Criar Tarefa</button></div></form></div>
</div>

<!-- Modal: Nota -->
<div class="modal-overlay" id="modalNota">
    <div class="modal"><div class="modal-header"><span class="modal-title">📝 Nova Nota</span><button class="modal-close" onclick="closeModal('modalNota')">×</button></div>
    <form onsubmit="addNota(event)"><div class="modal-body">
        <input type="hidden" name="cliente_id" value="<?= $id ?>">
        <div class="form-group"><label class="form-label">Título</label><input type="text" name="titulo" class="form-control" placeholder="Ex: Reunião de alinhamento"></div>
        <div class="form-group"><label class="form-label">Tipo</label><select name="tipo" class="form-control"><option value="nota">Nota</option><option value="reuniao">Reunião</option><option value="ligacao">Ligação</option><option value="email">E-mail</option><option value="outro">Outro</option></select></div>
        <div class="form-group"><label class="form-label">Conteúdo *</label><textarea name="conteudo" class="form-control" rows="4" required></textarea></div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" onclick="closeModal('modalNota')">Cancelar</button><button type="submit" class="btn btn-primary">Salvar Nota</button></div></form></div>
</div>

<div class="toast-container" id="toastContainer"></div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
const BASE = '<?= BASE_URL ?>';
const CLIENT_ID = <?= $id ?>;

async function updateStatus(id, newStatus) {
    const fd = new FormData();
    fd.append('id', id); fd.append('status', newStatus);
    const r = await fetch(BASE+'/api/kanban/update-status.php', {method:'POST',body:fd});
    const j = await r.json();
    if (j.success) showToast('Status atualizado!', 'success');
    else showToast('Erro ao atualizar.', 'error');
}

async function addItem(e, type) {
    e.preventDefault();
    const fd = new FormData(e.target);
    const urlMap = { demand: BASE+'/api/demands/create.php?type=demand', delivery: BASE+'/api/demands/create.php?type=delivery' };
    const r = await fetch(urlMap[type], {method:'POST',body:fd});
    const j = await r.json();
    if (j.success) { showToast('Item adicionado!', 'success'); setTimeout(() => location.reload(), 800); }
    else showToast(j.message || 'Erro.', 'error');
}

async function deleteDemand(id, type) {
    if (!confirm('Remover este item?')) return;
    const fd = new FormData(); fd.append('id', id); fd.append('type', type);
    const r = await fetch(BASE+'/api/demands/delete.php', {method:'POST',body:fd});
    const j = await r.json();
    if (j.success) { showToast('Item removido.', 'success'); setTimeout(() => location.reload(), 600); }
    else showToast('Erro.', 'error');
}

async function addTarefa(e) {
    e.preventDefault();
    const r = await fetch(BASE+'/api/tasks/create.php', {method:'POST', body: new FormData(e.target)});
    const j = await r.json();
    if (j.success) { showToast('Tarefa criada!', 'success'); setTimeout(() => location.reload(), 800); }
    else showToast(j.message || 'Erro.', 'error');
}

async function deleteTask(id) {
    if (!confirm('Excluir esta tarefa?')) return;
    const fd = new FormData(); fd.append('id', id);
    const r = await fetch(BASE+'/api/tasks/delete.php', {method:'POST',body:fd});
    const j = await r.json();
    if (j.success) { showToast('Tarefa excluída.', 'success'); setTimeout(() => location.reload(), 700); }
    else showToast('Erro.', 'error');
}

async function addNota(e) {
    e.preventDefault();
    const r = await fetch(BASE+'/api/notes/create.php', {method:'POST', body: new FormData(e.target)});
    const j = await r.json();
    if (j.success) { showToast('Nota salva!', 'success'); setTimeout(() => location.reload(), 800); }
    else showToast(j.message || 'Erro.', 'error');
}

// Override toggleDemandStatus base URL
async function toggleDemandStatus(btn, id, type) {
    const states = ['pendente', 'em_andamento', 'concluido'];
    const current = btn.dataset.status;
    const next = states[(states.indexOf(current) + 1) % states.length];
    const fd = new FormData(); fd.append('id', id); fd.append('status', next); fd.append('type', type);
    const r = await fetch(BASE+'/api/demands/update.php', {method:'POST',body:fd});
    const j = await r.json();
    if (j.success) {
        btn.dataset.status = next; btn.className = `demand-status-btn ${next}`;
        const icons = {pendente:'', em_andamento:'●', concluido:'✓'};
        btn.textContent = icons[next];
        const title = btn.closest('.demand-item')?.querySelector('.demand-title');
        if (title) title.classList.toggle('done', next === 'concluido');
        showToast('Status atualizado!', 'success');
    } else showToast('Erro.', 'error');
}
</script>
</body>
</html>
