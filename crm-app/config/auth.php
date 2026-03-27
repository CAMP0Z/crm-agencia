<?php
// ============================================================
// config/auth.php - Autenticação por Sessão
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Verifica se o usuário está logado; redireciona caso não esteja.
 */
function requireAuth(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
}

/**
 * Retorna os dados do usuário logado (array)
 */
function currentUser(): array {
    return [
        'id'    => $_SESSION['user_id']    ?? null,
        'nome'  => $_SESSION['user_nome']  ?? 'Usuário',
        'email' => $_SESSION['user_email'] ?? '',
        'perfil'=> $_SESSION['user_perfil']?? 'analista',
    ];
}

/**
 * Faz o login do usuário: valida no banco, grava sessão.
 */
function loginUser(string $email, string $senha): array {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND ativo = 1 LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($senha, $user['senha'])) {
        return ['success' => false, 'message' => 'E-mail ou senha inválidos.'];
    }

    // Atualiza último login
    $pdo->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?")->execute([$user['id']]);

    $_SESSION['user_id']     = $user['id'];
    $_SESSION['user_nome']   = $user['nome'];
    $_SESSION['user_email']  = $user['email'];
    $_SESSION['user_perfil'] = $user['perfil'];

    return ['success' => true];
}

/**
 * Destrói a sessão (logout)
 */
function logoutUser(): void {
    session_unset();
    session_destroy();
}
