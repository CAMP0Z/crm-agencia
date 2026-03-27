<?php
// api/revenue/create.php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(false, 'Método inválido.'); }
$produto = trim($_POST['produto'] ?? '');
if (!$produto) { jsonResponse(false, 'Produto obrigatório.'); }
$db = getDB();
$vb = (float)($_POST['valor_bruto'] ?? 0);
$vl = (float)($_POST['valor_liquido'] ?? 0);
$db->prepare("
    INSERT INTO vendas_plataformas (cliente_id,plataforma,produto,comprador,valor_bruto,valor_liquido,comissao,status,data_venda,criado_via)
    VALUES (?,?,?,?,?,?,?,?,?,?)
")->execute([
    ($_POST['cliente_id'] ?? null) ?: null,
    $_POST['plataforma'] ?? 'outros',
    $produto,
    trim($_POST['comprador'] ?? ''),
    $vb, $vl, ($vb - $vl),
    $_POST['status'] ?? 'aprovado',
    $_POST['data_venda'] ?? date('Y-m-d'),
    'manual',
]);
jsonResponse(true, 'Venda registrada!', ['id' => (int)getDB()->lastInsertId()]);
