<?php
// api/tasks/create.php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(false, 'Método inválido.'); }
$titulo = trim($_POST['titulo'] ?? '');
if (!$titulo) { jsonResponse(false, 'Título obrigatório.'); }
$db = getDB();
$stmt = $db->prepare("
    INSERT INTO tarefas (cliente_id,titulo,descricao,categoria,prioridade,prazo,status,responsavel)
    VALUES (?,?,?,?,?,?,?,?)
");
$stmt->execute([
    ($_POST['cliente_id'] ?? null) ?: null,
    $titulo,
    trim($_POST['descricao'] ?? ''),
    trim($_POST['categoria'] ?? ''),
    $_POST['prioridade'] ?? 'media',
    $_POST['prazo'] ?: null,
    'pendente',
    trim($_POST['responsavel'] ?? ''),
]);
jsonResponse(true, 'Tarefa criada!', ['id' => (int)$db->lastInsertId()]);
