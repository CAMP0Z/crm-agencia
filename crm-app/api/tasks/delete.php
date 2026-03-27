<?php
// api/tasks/delete.php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(false, 'Método inválido.'); }
$id = (int)($_POST['id'] ?? 0);
if (!$id) { jsonResponse(false, 'ID inválido.'); }
getDB()->prepare("DELETE FROM tarefas WHERE id=?")->execute([$id]);
jsonResponse(true, 'Tarefa excluída.');
