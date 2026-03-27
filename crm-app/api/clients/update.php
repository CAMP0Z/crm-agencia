<?php
// api/clients/update.php
require_once __DIR__ . '/../../config/app.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(false, 'Método inválido.'); }

$id          = (int)($_POST['id']          ?? 0);
$empresa     = trim($_POST['empresa']     ?? '');
$responsavel = trim($_POST['responsavel'] ?? '');

if (!$id || !$empresa || !$responsavel) { jsonResponse(false, 'Dados obrigatórios faltando.'); }

$db = getDB();

// Log status change
$old = $db->prepare("SELECT status FROM clientes WHERE id=?");
$old->execute([$id]);
$old = $old->fetchColumn();
$newStatus = $_POST['status'] ?? $old;

if ($old && $old !== $newStatus) {
    $db->prepare("INSERT INTO historico_status (cliente_id,status_anterior,status_novo,alterado_por) VALUES (?,?,?,?)")
       ->execute([$id, $old, $newStatus, currentUser()['nome']]);
}

$stmt = $db->prepare("
    UPDATE clientes SET empresa=?,responsavel=?,email=?,telefone=?,instagram=?,nicho=?,observacoes=?,status=?,origem=?,valor_mensal=?,plataforma_principal=?,gestor_responsavel=?
    WHERE id=?
");
$stmt->execute([
    $empresa, $responsavel,
    trim($_POST['email']               ?? ''),
    trim($_POST['telefone']            ?? ''),
    trim($_POST['instagram']           ?? ''),
    trim($_POST['nicho']               ?? ''),
    trim($_POST['observacoes']         ?? ''),
    $newStatus,
    trim($_POST['origem']              ?? ''),
    (float)($_POST['valor_mensal']     ?? 0),
    trim($_POST['plataforma_principal']?? ''),
    trim($_POST['gestor_responsavel']  ?? ''),
    $id
]);
jsonResponse(true, 'Cliente atualizado!');
