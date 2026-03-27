<?php
// api/traffic/create.php
require_once __DIR__ . '/../../config/app.php';
requireAuth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(false, 'Método inválido.'); }
$cliente_id = (int)($_POST['cliente_id'] ?? 0);
if (!$cliente_id) { jsonResponse(false, 'Cliente obrigatório.'); }
$db = getDB();
$invest = (float)($_POST['valor_investido'] ?? 0);
$leads  = (int)($_POST['leads'] ?? 0);
$cpl    = (float)($_POST['custo_por_lead'] ?? ($leads > 0 ? $invest/$leads : 0));
$db->prepare("
    INSERT INTO metricas_trafego (cliente_id,plataforma,campanha,periodo_inicio,periodo_fim,valor_investido,impressoes,cliques,ctr,cpc,cpm,leads,conversas,custo_por_lead,criado_via)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
")->execute([
    $cliente_id,
    $_POST['plataforma'] ?? 'meta_ads',
    trim($_POST['campanha'] ?? ''),
    $_POST['periodo_inicio'] ?? date('Y-m-01'),
    $_POST['periodo_fim']    ?? date('Y-m-d'),
    $invest,
    (int)($_POST['impressoes']  ?? 0),
    (int)($_POST['cliques']     ?? 0),
    (float)($_POST['ctr']       ?? 0),
    (float)($_POST['cpc']       ?? 0),
    (float)($_POST['cpm']       ?? 0),
    $leads,
    (int)($_POST['conversas']   ?? 0),
    $cpl,
    'manual',
]);
jsonResponse(true, 'Métrica registrada!');
