<?php
// admin/configuracoes.php - Settings Page
require_once __DIR__ . '/../config/app.php';
requireAuth();

$db = getDB();
$user = currentUser();
$success_msg = '';
$error_msg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_password') {
        $senha_atual = $_POST['senha_atual'] ?? '';
        $nova_senha  = $_POST['nova_senha']  ?? '';
        if (strlen($nova_senha) < 6) {
            $error_msg = 'Nova senha deve ter ao menos 6 caracteres.';
        } else {
            $stmt = $db->prepare("SELECT senha FROM usuarios WHERE id=?");
            $stmt->execute([$user['id']]);
            $hash = $stmt->fetchColumn();
            if (!password_verify($senha_atual, $hash)) {
                $error_msg = 'Senha atual incorreta.';
            } else {
                $db->prepare("UPDATE usuarios SET senha=? WHERE id=?")->execute([password_hash($nova_senha, PASSWORD_DEFAULT), $user['id']]);
                $success_msg = 'Senha atualizada com sucesso!';
            }
        }
    }
}

$pageTitle    = 'Configurações';
$pageSubtitle = 'Preferências e integrações do sistema';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
</head>
<body>
<div class="app-layout">
    <?php include __DIR__ . '/../components/sidebar.php'; ?>
    <div class="app-main">
        <?php include __DIR__ . '/../components/header.php'; ?>
        <div class="app-content">

            <?php if ($success_msg): ?>
            <div style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.25);color:#34d399;border-radius:8px;padding:12px 16px;margin-bottom:16px;"><?= clean($success_msg) ?></div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
            <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:#f87171;border-radius:8px;padding:12px 16px;margin-bottom:16px;"><?= clean($error_msg) ?></div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

                <!-- Change Password -->
                <div class="card">
                    <div class="card-header"><span class="card-title">🔐 Alterar Senha</span></div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_password">
                            <div class="form-group"><label class="form-label">Senha Atual</label><input type="password" name="senha_atual" class="form-control" required></div>
                            <div class="form-group"><label class="form-label">Nova Senha</label><input type="password" name="nova_senha" class="form-control" required minlength="6"></div>
                            <button type="submit" class="btn btn-primary">Atualizar Senha</button>
                        </form>
                    </div>
                </div>

                <!-- Meta Ads Integration -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">📣 Integração Meta Ads</span>
                        <span class="badge badge-yellow">Em breve</span>
                    </div>
                    <div class="card-body">
                        <div class="form-group"><label class="form-label">Access Token</label><input type="text" class="form-control" placeholder="EAAZAp..." disabled></div>
                        <div class="form-group"><label class="form-label">Ad Account ID</label><input type="text" class="form-control" placeholder="act_000000000" disabled></div>
                        <div class="form-group"><label class="form-label">Pixel ID</label><input type="text" class="form-control" placeholder="000000000" disabled></div>
                        <button class="btn btn-secondary" disabled>Conectar Meta Ads</button>
                        <p style="font-size:11px;color:var(--text-muted);margin-top:8px;">Configure seu App no Meta for Developers e insira o token de acesso aqui.</p>
                    </div>
                </div>

                <!-- Kiwify Integration -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">🛍 Integração Kiwify</span>
                        <span class="badge badge-yellow">Em breve</span>
                    </div>
                    <div class="card-body">
                        <div class="form-group"><label class="form-label">API Key Kiwify</label><input type="text" class="form-control" placeholder="kw_..." disabled></div>
                        <button class="btn btn-secondary" disabled>Conectar Kiwify</button>
                        <p style="font-size:11px;color:var(--text-muted);margin-top:8px;">Acesse Kiwify → Conta → API para gerar sua chave.</p>
                    </div>
                </div>

                <!-- Hotmart Integration -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">🔥 Integração Hotmart</span>
                        <span class="badge badge-yellow">Em breve</span>
                    </div>
                    <div class="card-body">
                        <div class="form-group"><label class="form-label">Client ID</label><input type="text" class="form-control" placeholder="Client ID" disabled></div>
                        <div class="form-group"><label class="form-label">Client Secret</label><input type="text" class="form-control" placeholder="Client Secret" disabled></div>
                        <div class="form-group"><label class="form-label">Webhook URL</label>
                            <input type="text" class="form-control" value="<?= BASE_URL ?>/integrations/hotmart/webhook.php" readonly onclick="this.select()">
                        </div>
                        <button class="btn btn-secondary" disabled>Conectar Hotmart</button>
                    </div>
                </div>

                <!-- System Info -->
                <div class="card">
                    <div class="card-header"><span class="card-title">ℹ️ Informações do Sistema</span></div>
                    <div class="card-body">
                        <?php $infoItems = [
                            ['App', APP_NAME . ' v' . APP_VERSION],
                            ['PHP', phpversion()],
                            ['Servidor', $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'],
                            ['Banco', DB_NAME],
                            ['Timezone', date_default_timezone_get()],
                            ['Data/Hora', date('d/m/Y H:i:s')],
                        ]; ?>
                        <?php foreach ($infoItems as [$k, $v]): ?>
                        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);font-size:13px;">
                            <span style="color:var(--text-muted);"><?= $k ?></span>
                            <span style="color:var(--text-primary);font-weight:500;"><?= clean($v) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<div class="toast-container" id="toastContainer"></div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
</body>
</html>
