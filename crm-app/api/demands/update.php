<?php
// api/demands/update.php - Toggle demand or delivery status
require_once __DIR__ . '/../../config/app.php';
requireAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(false, 'Método inválido.'); }

$id     = (int)($_POST['id']     ?? 0);
$status = trim($_POST['status']  ?? '');
$type   = trim($_POST['type']    ?? 'demand');
$allowed = ['pendente','em_andamento','concluido'];

if (!$id || !in_array($status, $allowed)) { jsonResponse(false, 'Dados inválidos.'); }

$db = getDB();
$table = $type === 'demand' ? 'demandas_cliente' : 'entregas_agencia';
$db->prepare("UPDATE $table SET status=? WHERE id=?")->execute([$status, $id]);
jsonResponse(true, 'Status atualizado!', ['new_status' => $status]);
