<?php
// api/clients/create.php
require_once __DIR__ . '/../../config/app.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(false, 'Método inválido.'); }

$empresa     = trim($_POST['empresa']     ?? '');
$responsavel = trim($_POST['responsavel'] ?? '');

if (!$empresa || !$responsavel) { jsonResponse(false, 'Empresa e responsável são obrigatórios.'); }

$db = getDB();
$stmt = $db->prepare("
    INSERT INTO clientes (empresa,responsavel,email,telefone,instagram,nicho,observacoes,status,origem,valor_mensal,plataforma_principal,gestor_responsavel)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
");
$stmt->execute([
    $empresa, $responsavel,
    trim($_POST['email']               ?? ''),
    trim($_POST['telefone']            ?? ''),
    trim($_POST['instagram']           ?? ''),
    trim($_POST['nicho']               ?? ''),
    trim($_POST['observacoes']         ?? ''),
    $_POST['status']                   ?? 'onboarding',
    trim($_POST['origem']              ?? ''),
    (float)($_POST['valor_mensal']     ?? 0),
    trim($_POST['plataforma_principal']?? ''),
    trim($_POST['gestor_responsavel']  ?? ''),
]);
jsonResponse(true, 'Cliente cadastrado!', ['id' => (int)$db->lastInsertId()]);
