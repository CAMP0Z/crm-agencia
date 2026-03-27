<?php
// api/demands/delete.php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(false, 'Método inválido.'); }
$id   = (int)($_POST['id']   ?? 0);
$type = trim($_POST['type']  ?? 'demand');
if (!$id) { jsonResponse(false, 'ID inválido.'); }
$table = $type === 'demand' ? 'demandas_cliente' : 'entregas_agencia';
getDB()->prepare("DELETE FROM $table WHERE id=?")->execute([$id]);
jsonResponse(true, 'Item removido.');
