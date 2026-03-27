<?php
// admin/login.php - Authentication Page
require_once __DIR__ . '/../config/app.php';

// Already logged in?
if (!empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email && $senha) {
        $result = loginUser($email, $senha);
        if ($result['success']) {
            header('Location: ' . BASE_URL . '/admin/index.php');
            exit;
        }
        $error = $result['message'];
    } else {
        $error = 'Preencha todos os campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.1/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-primary);
            position: relative;
            overflow: hidden;
        }
        .login-bg {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse at 20% 50%, rgba(14,165,233,0.08) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 20%, rgba(29,78,216,0.10) 0%, transparent 50%),
                radial-gradient(ellipse at 60% 80%, rgba(124,58,237,0.06) 0%, transparent 50%);
        }
        .login-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 44px 40px;
            width: 100%;
            max-width: 400px;
            position: relative;
            box-shadow: 0 25px 60px rgba(0,0,0,0.5);
            animation: fadeIn 0.4s ease;
        }
        .login-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 32px;
        }
        .login-logo-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--blue-primary), var(--blue-vibrant));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            margin-bottom: 14px;
            box-shadow: 0 8px 24px rgba(14,165,233,0.4);
        }
        .login-title {
            font-size: 22px;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -0.5px;
        }
        .login-sub {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 4px;
        }
        .error-alert {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.25);
            color: #f87171;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .grid-lines {
            position: absolute;
            inset: 0;
            background-image: linear-gradient(rgba(14,165,233,0.04) 1px, transparent 1px),
                              linear-gradient(90deg, rgba(14,165,233,0.04) 1px, transparent 1px);
            background-size: 48px 48px;
            pointer-events: none;
        }
    </style>
</head>
<body>
<div class="login-page">
    <div class="login-bg"></div>
    <div class="grid-lines"></div>

    <div class="login-card">
        <div class="login-logo">
            <div class="login-logo-icon">⚡</div>
            <h1 class="login-title"><?= APP_NAME ?></h1>
            <p class="login-sub">Gestão operacional inteligente</p>
        </div>

        <?php if ($error): ?>
        <div class="error-alert">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?= clean($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <div class="form-group">
                <label class="form-label">E-mail</label>
                <input type="email" name="email" class="form-control"
                       value="<?= clean($_POST['email'] ?? '') ?>"
                       placeholder="seu@email.com" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">Senha</label>
                <div style="position:relative;">
                    <input type="password" name="senha" class="form-control" id="loginSenha"
                           placeholder="••••••••" required style="padding-right:44px;">
                    <button type="button" onclick="toggleSenha()" tabindex="-1"
                            style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);">
                        <svg id="eyeIcon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-full" style="width:100%;justify-content:center;margin-top:8px;padding:12px;" id="loginBtn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/>
                </svg>
                Entrar no Sistema
            </button>
        </form>

        <div style="margin-top:20px;padding:12px;background:rgba(14,165,233,0.05);border:1px solid rgba(14,165,233,0.15);border-radius:8px;font-size:12px;color:var(--text-muted);text-align:center;">
            Admin: <strong style="color:var(--text-secondary);">admin@agencia.com</strong> / <strong style="color:var(--text-secondary);">password</strong>
        </div>
    </div>
</div>

<script>
function toggleSenha() {
    const input = document.getElementById('loginSenha');
    input.type = input.type === 'password' ? 'text' : 'password';
}
document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('loginBtn');
    btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="animation:spin 1s linear infinite"><path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0"/></svg> Entrando...';
    btn.disabled = true;
});
</script>
<style>@keyframes spin { to { transform: rotate(360deg); } }</style>
</body>
</html>
