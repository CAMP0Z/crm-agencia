<?php
// api/revenue/delete.php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(false, 'Método inválido.'); }
$id = (int)($_POST['id'] ?? 0);
if (!$id) { jsonResponse(false, 'ID inválido.'); }
getDB()->prepare("DELETE FROM vendas_plataformas WHERE id=?")->execute([$id]);
jsonResponse(true, 'Venda excluída.');
