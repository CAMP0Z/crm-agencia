<?php
// api/notifications/list.php - Notification feed for header
require_once __DIR__ . '/../../config/app.php';
requireAuth();

$db = getDB();
$notifications = [];

// Overdue tasks
$stmt = $db->query("
    SELECT t.titulo, c.empresa, t.prazo
    FROM tarefas t
    LEFT JOIN clientes c ON c.id = t.cliente_id
    WHERE t.status = 'atrasada'
    ORDER BY t.prazo ASC
    LIMIT 5
");
foreach ($stmt->fetchAll() as $t) {
    $notifications[] = [
        'icon'  => '⏰',
        'title' => "Tarefa atrasada: {$t['titulo']}" . ($t['empresa'] ? " — {$t['empresa']}" : ''),
        'time'  => '📅 ' . dataBR($t['prazo']),
        'link'  => BASE_URL . '/admin/tarefas.php?status=atrasada',
    ];
}

// Critical clients
$stmt = $db->query("SELECT empresa, id FROM clientes WHERE saude_operacional='critico' LIMIT 3");
foreach ($stmt->fetchAll() as $c) {
    $notifications[] = [
        'icon'  => '🔴',
        'title' => "Cliente crítico: {$c['empresa']}",
        'time'  => 'Requer atenção urgente',
        'link'  => BASE_URL . '/admin/cliente-detalhe.php?id=' . $c['id'],
    ];
}

// Pending demands
$pend = (int)$db->query("SELECT COUNT(*) FROM demandas_cliente WHERE status='pendente'")->fetchColumn();
if ($pend > 0) {
    $notifications[] = [
        'icon'  => '📥',
        'title' => "$pend pendência(s) de clientes aguardando",
        'time'  => 'Acesse os detalhes dos clientes',
        'link'  => BASE_URL . '/admin/clientes.php',
    ];
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'data' => $notifications]);
