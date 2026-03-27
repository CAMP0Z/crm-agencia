<?php
// admin/clientes.php - Client List Page
require_once __DIR__ . '/../config/app.php';
requireAuth();
atualizarStatusTarefasAtrasadas();

$db = getDB();

// Filters
$search = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$nicho  = $_GET['nicho'] ?? '';

$where = ['1=1'];
$params = [];

if ($search) {
    $where[] = "(empresa LIKE ? OR responsavel LIKE ? OR email LIKE ? OR nicho LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
}
if ($status) {
    $where[] = "status = ?";
    $params[] = $status;
}
if ($nicho) {
    $where[] = "nicho LIKE ?";
    $params[] = "%$nicho%";
}

$whereStr = implode(' AND ', $where);
$stmt = $db->prepare("
    SELECT c.*,
        (SELECT COUNT(*) FROM tarefas t WHERE t.cliente_id=c.id AND t.status IN ('pendente','atrasada')) AS tarefas_pend,
        (SELECT COUNT(*) FROM demandas_cliente d WHERE d.cliente_id=c.id AND d.status='pendente') AS demandas_pend
    FROM clientes c WHERE $whereStr ORDER BY c.created_at DESC
");
$stmt->execute($params);
$clientes = $stmt->fetchAll();

// Distinct niches
$nichos = $db->query("SELECT DISTINCT nicho FROM clientes WHERE nicho IS NOT NULL AND nicho != '' ORDER BY nicho")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle    = 'Clientes';
$pageSubtitle = count($clientes) . ' clientes encontrados';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>
    <div class="app-main">
        <?php include __DIR__ . '/../components/header.php'; ?>
        <div class="app-content">

            <!-- Top actions -->
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
                <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
                    <!-- Filters -->
                    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;">
                        <input type="text" name="q" value="<?= clean($search) ?>" placeholder="Buscar clientes..."
                               class="form-control" style="width:220px;padding:8px 12px;">

                        <select name="status" class="form-control" style="width:170px;padding:8px 12px;" onchange="this.form.submit()">
                            <option value="">Todos os status</option>
                            <option value="onboarding"        <?= $status==='onboarding'         ? 'selected':'' ?>>Onboarding</option>
                            <option value="aguardando_cliente"<?= $status==='aguardando_cliente' ? 'selected':'' ?>>Aguardando Cliente</option>
                            <option value="em_execucao"       <?= $status==='em_execucao'        ? 'selected':'' ?>>Em Execução</option>
                            <option value="revisao"           <?= $status==='revisao'            ? 'selected':'' ?>>Revisão</option>
                            <option value="concluido"         <?= $status==='concluido'          ? 'selected':'' ?>>Concluído</option>
                            <option value="pausado"           <?= $status==='pausado'            ? 'selected':'' ?>>Pausado</option>
                        </select>

                        <select name="nicho" class="form-control" style="width:150px;padding:8px 12px;" onchange="this.form.submit()">
                            <option value="">Todos nichos</option>
                            <?php foreach ($nichos as $n): ?>
                            <option value="<?= clean($n) ?>" <?= $nicho===$n ? 'selected':'' ?>><?= clean($n) ?></option>
                            <?php endforeach; ?>
                        </select>

                        <?php if ($search || $status || $nicho): ?>
                        <a href="clientes.php" class="btn btn-secondary btn-sm">✕ Limpar</a>
                        <?php endif; ?>
                    </form>
                </div>

                <button onclick="openModal('modalCliente')" class="btn btn-primary">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Novo Cliente
                </button>
            </div>

            <!-- Clients table -->
            <div class="card">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Cliente</th>
                                <th>Contato</th>
                                <th>Nicho</th>
                                <th>Gestor</th>
                                <th>Status</th>
                                <th>Saúde</th>
                                <th>Valor/Mês</th>
                                <th>Pendências</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($clientes)): ?>
                            <tr><td colspan="9">
                                <div class="empty-state">
                                    <div class="empty-icon">👥</div>
                                    <h3>Nenhum cliente encontrado</h3>
                                    <p>Tente ajustar os filtros ou cadastre um novo cliente.</p>
                                </div>
                            </td></tr>
                            <?php else: ?>
                            <?php foreach ($clientes as $c): ?>
                            <tr>
                                <td>
                                    <a href="<?= BASE_URL ?>/admin/cliente-detalhe.php?id=<?= $c['id'] ?>"
                                       style="display:flex;align-items:center;gap:10px;text-decoration:none;">
                                        <div class="client-avatar" style="background:linear-gradient(135deg,<?= clean($c['cor_avatar']) ?>,var(--blue-vibrant));width:36px;height:36px;font-size:12px;">
                                            <?= initials($c['empresa']) ?>
                                        </div>
                                        <div>
                                            <div style="font-size:13px;font-weight:600;color:var(--text-primary);"><?= clean($c['empresa']) ?></div>
                                            <div style="font-size:11px;color:var(--text-muted);"><?= clean($c['responsavel']) ?></div>
                                        </div>
                                    </a>
                                </td>
                                <td>
                                    <div style="font-size:12px;color:var(--text-secondary);"><?= clean($c['email'] ?? '—') ?></div>
                                    <div style="font-size:11px;color:var(--text-muted);"><?= clean($c['telefone'] ?? '') ?></div>
                                </td>
                                <td>
                                    <?php if ($c['nicho']): ?>
                                    <span class="badge badge-gray"><?= clean($c['nicho']) ?></span>
                                    <?php else: ?><span style="color:var(--text-muted);">—</span><?php endif; ?>
                                </td>
                                <td style="font-size:12px;color:var(--text-secondary);"><?= clean($c['gestor_responsavel'] ?? '—') ?></td>
                                <td><?= statusClienteBadge($c['status']) ?></td>
                                <td>
                                    <div style="display:flex;align-items:center;gap:6px;">
                                        <span class="health-dot <?= $c['saude_operacional'] ?>"></span>
                                        <span style="font-size:12px;color:var(--text-secondary);"><?= ucfirst($c['saude_operacional']) ?></span>
                                    </div>
                                </td>
                                <td style="font-size:13px;font-weight:600;color:var(--text-primary);"><?= formatBRL((float)$c['valor_mensal']) ?></td>
                                <td>
                                    <?php $total_pend = $c['tarefas_pend'] + $c['demandas_pend']; ?>
                                    <span class="badge <?= $total_pend > 0 ? 'badge-yellow' : 'badge-green' ?>">
                                        <?= $total_pend ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display:flex;gap:6px;">
                                        <a href="<?= BASE_URL ?>/admin/cliente-detalhe.php?id=<?= $c['id'] ?>" class="btn btn-secondary btn-icon" title="Ver detalhes">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </a>
                                        <button class="btn btn-secondary btn-icon" title="Editar"
                                                onclick="editCliente(<?= htmlspecialchars(json_encode($c), ENT_QUOTES|ENT_HTML5) ?>)">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </button>
                                        <button class="btn btn-danger btn-icon" title="Excluir"
                                                onclick="deleteCliente(<?= $c['id'] ?>, '<?= addslashes($c['empresa']) ?>')">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
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

<!-- Modal: Criar/Editar Cliente -->
<div class="modal-overlay" id="modalCliente">
    <div class="modal" style="max-width:700px;">
        <div class="modal-header">
            <span class="modal-title" id="modalClienteTitulo">➕ Novo Cliente</span>
            <button class="modal-close" onclick="closeModal('modalCliente')">×</button>
        </div>
        <form id="formCliente" onsubmit="submitCliente(event)">
            <input type="hidden" name="id" id="clienteId">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Empresa *</label>
                        <input type="text" name="empresa" id="fEmpresa" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Responsável *</label>
                        <input type="text" name="responsavel" id="fResponsavel" class="form-control" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">E-mail</label>
                        <input type="email" name="email" id="fEmail" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="telefone" id="fTelefone" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Instagram</label>
                        <input type="text" name="instagram" id="fInstagram" class="form-control" placeholder="@perfil">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nicho/Ramo</label>
                        <input type="text" name="nicho" id="fNicho" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="fStatus" class="form-control">
                            <option value="onboarding">Onboarding</option>
                            <option value="aguardando_cliente">Aguardando Cliente</option>
                            <option value="em_execucao">Em Execução</option>
                            <option value="revisao">Revisão</option>
                            <option value="concluido">Concluído</option>
                            <option value="pausado">Pausado</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Origem</label>
                        <input type="text" name="origem" id="fOrigem" class="form-control" placeholder="Ex: Indicação, Google...">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Valor Mensal (R$)</label>
                        <input type="number" name="valor_mensal" id="fValor" class="form-control" step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Plataforma Principal</label>
                        <select name="plataforma_principal" id="fPlataforma" class="form-control">
                            <option value="">Selecionar</option>
                            <option value="meta_ads">Meta Ads</option>
                            <option value="google_ads">Google Ads</option>
                            <option value="tiktok_ads">TikTok Ads</option>
                            <option value="outros">Outros</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Gestor Responsável</label>
                    <input type="text" name="gestor_responsavel" id="fGestor" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Observações</label>
                    <textarea name="observacoes" id="fObs" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalCliente')">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="btnSalvarCliente">Salvar Cliente</button>
            </div>
        </form>
    </div>
</div>

<div class="toast-container" id="toastContainer"></div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
function editCliente(c) {
    document.getElementById('modalClienteTitulo').textContent = '✏️ Editar Cliente';
    document.getElementById('clienteId').value           = c.id;
    document.getElementById('fEmpresa').value            = c.empresa || '';
    document.getElementById('fResponsavel').value        = c.responsavel || '';
    document.getElementById('fEmail').value              = c.email || '';
    document.getElementById('fTelefone').value           = c.telefone || '';
    document.getElementById('fInstagram').value          = c.instagram || '';
    document.getElementById('fNicho').value              = c.nicho || '';
    document.getElementById('fStatus').value             = c.status || 'onboarding';
    document.getElementById('fOrigem').value             = c.origem || '';
    document.getElementById('fValor').value              = c.valor_mensal || 0;
    document.getElementById('fPlataforma').value         = c.plataforma_principal || '';
    document.getElementById('fGestor').value             = c.gestor_responsavel || '';
    document.getElementById('fObs').value                = c.observacoes || '';
    openModal('modalCliente');
}

async function submitCliente(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSalvarCliente');
    const id  = document.getElementById('clienteId').value;
    const url = id ? '<?= BASE_URL ?>/api/clients/update.php' : '<?= BASE_URL ?>/api/clients/create.php';
    btn.textContent = 'Salvando...'; btn.disabled = true;
    const fd = new FormData(document.getElementById('formCliente'));
    const r = await fetch(url, { method: 'POST', body: fd });
    const j = await r.json();
    btn.textContent = 'Salvar Cliente'; btn.disabled = false;
    if (j.success) {
        closeModal('modalCliente');
        showToast(id ? 'Cliente atualizado!' : 'Cliente cadastrado!', 'success');
        setTimeout(() => window.location.reload(), 1000);
    } else {
        showToast(j.message || 'Erro ao salvar.', 'error');
    }
}

async function deleteCliente(id, nome) {
    if (!confirm(`Excluir o cliente "${nome}"? Esta ação não pode ser desfeita.`)) return;
    const fd = new FormData(); fd.append('id', id);
    const r = await fetch('<?= BASE_URL ?>/api/clients/delete.php', { method: 'POST', body: fd });
    const j = await r.json();
    if (j.success) {
        showToast('Cliente excluído.', 'success');
        setTimeout(() => window.location.reload(), 1000);
    } else {
        showToast(j.message || 'Erro ao excluir.', 'error');
    }
}
</script>
</body>
</html>
