<?php
// api/notes/create.php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(false, 'Método inválido.'); }
$cliente_id = (int)($_POST['cliente_id'] ?? 0);
$conteudo   = trim($_POST['conteudo']   ?? '');
if (!$cliente_id || !$conteudo) { jsonResponse(false, 'Dados obrigatórios faltando.'); }
$db = getDB();
$db->prepare("INSERT INTO notas_cliente (cliente_id,titulo,conteudo,tipo,autor) VALUES (?,?,?,?,?)")
   ->execute([$cliente_id, trim($_POST['titulo'] ?? ''), $conteudo, $_POST['tipo'] ?? 'nota', currentUser()['nome']]);
jsonResponse(true, 'Nota registrada!');
