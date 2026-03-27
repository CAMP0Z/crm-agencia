<?php
// api/kanban/update-status.php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(false, 'Método inválido.'); }

$id     = (int)($_POST['id']     ?? 0);
$status = trim($_POST['status']  ?? '');

$allowed = ['onboarding','aguardando_cliente','em_execucao','revisao','concluido','pausado'];
if (!$id || !in_array($status, $allowed)) { jsonResponse(false, 'Dados inválidos.'); }

$db = getDB();

// Get previous status
$old = $db->prepare("SELECT status FROM clientes WHERE id=?");
$old->execute([$id]);
$old = $old->fetchColumn();

// Update
$db->prepare("UPDATE clientes SET status=? WHERE id=?")->execute([$status, $id]);

// Log history
if ($old && $old !== $status) {
    $db->prepare("INSERT INTO historico_status (cliente_id,status_anterior,status_novo,alterado_por) VALUES (?,?,?,?)")
       ->execute([$id, $old, $status, currentUser()['nome']]);
}

// Recalc health
$saude = calcularSaude($id);
$db->prepare("UPDATE clientes SET saude_operacional=? WHERE id=?")->execute([$saude, $id]);

jsonResponse(true, 'Status atualizado!', ['new_status' => $status, 'saude' => $saude]);
