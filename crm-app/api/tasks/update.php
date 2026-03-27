<?php
// api/tasks/update.php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(false, 'Método inválido.'); }
$id     = (int)($_POST['id'] ?? 0);
$status = trim($_POST['status'] ?? '');
if (!$id) { jsonResponse(false, 'ID inválido.'); }
$db = getDB();
$fields = ['status = ?']; $params = [$status];
if (isset($_POST['titulo'])) { $fields[] = 'titulo = ?';    $params[] = trim($_POST['titulo']); }
if (isset($_POST['prioridade'])) { $fields[] = 'prioridade = ?'; $params[] = $_POST['prioridade']; }
if (isset($_POST['prazo']))  { $fields[] = 'prazo = ?';     $params[] = $_POST['prazo'] ?: null; }
$params[] = $id;
$db->prepare("UPDATE tarefas SET " . implode(', ', $fields) . " WHERE id=?")->execute($params);
jsonResponse(true, 'Tarefa atualizada!');
