<?php
// api/clients/delete.php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(false, 'Método inválido.'); }
$id = (int)($_POST['id'] ?? 0);
if (!$id) { jsonResponse(false, 'ID inválido.'); }
$db = getDB();
$db->prepare("DELETE FROM clientes WHERE id=?")->execute([$id]);
jsonResponse(true, 'Cliente excluído.');
