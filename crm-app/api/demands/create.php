<?php
// api/demands/create.php - Create demand or delivery
require_once __DIR__ . '/../../config/app.php';
requireAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(false, 'Método inválido.'); }

$type       = $_GET['type'] ?? 'demand';
$cliente_id = (int)($_POST['cliente_id'] ?? 0);
$titulo     = trim($_POST['titulo'] ?? '');
if (!$cliente_id || !$titulo) { jsonResponse(false, 'Cliente e título são obrigatórios.'); }

$db = getDB();
if ($type === 'demand') {
    $db->prepare("INSERT INTO demandas_cliente (cliente_id,titulo,descricao) VALUES (?,?,?)")
       ->execute([$cliente_id, $titulo, trim($_POST['descricao'] ?? '')]);
} else {
    $db->prepare("INSERT INTO entregas_agencia (cliente_id,titulo,descricao,prazo) VALUES (?,?,?,?)")
       ->execute([$cliente_id, $titulo, trim($_POST['descricao'] ?? ''), $_POST['prazo'] ?: null]);
}
jsonResponse(true, 'Item adicionado!');
