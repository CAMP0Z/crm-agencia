<?php
/*
 * integrations/meta/fetch-campaigns.php
 * Fetch Meta Ads campaigns and store in metricas_trafego
 * ─────────────────────────────────────────────────────────────
 * Usage: Run via cron job or button click in the admin panel.
 * Requires: META_ACCESS_TOKEN, META_AD_ACCOUNT_ID in config.
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/meta-service.php';

// ← Substitute with real values or load from DB settings
$ACCESS_TOKEN  = defined('META_ACCESS_TOKEN')  ? META_ACCESS_TOKEN  : '';
$AD_ACCOUNT_ID = defined('META_AD_ACCOUNT_ID') ? META_AD_ACCOUNT_ID : '';
$CLIENT_ID     = (int)($_GET['cliente_id'] ?? 0); // link to CRM client

if (!$ACCESS_TOKEN || !$AD_ACCOUNT_ID) {
    exit("❌ Configure META_ACCESS_TOKEN e META_AD_ACCOUNT_ID.\n");
}

$meta     = new MetaService($ACCESS_TOKEN);
$insights = $meta->getInsights($AD_ACCOUNT_ID);

if (isset($insights['error'])) {
    echo "❌ Erro API: " . print_r($insights['error'], true);
    exit;
}

$data = $insights['data'][0] ?? [];

if ($data && $CLIENT_ID) {
    $db    = getDB();
    $spend = (float)($data['spend']       ?? 0);
    $imps  = (int)  ($data['impressions'] ?? 0);
    $clks  = (int)  ($data['clicks']      ?? 0);
    $ctr   = (float)($data['ctr']         ?? 0);
    $cpc   = (float)($data['cpc']         ?? 0);
    $cpm   = (float)($data['cpm']         ?? 0);

    // Count leads from actions
    $leads = 0;
    foreach (($data['actions'] ?? []) as $action) {
        if ($action['action_type'] === 'lead') $leads = (int)$action['value'];
    }

    $db->prepare("
        INSERT INTO metricas_trafego
            (cliente_id,plataforma,campanha,periodo_inicio,periodo_fim,valor_investido,impressoes,cliques,ctr,cpc,cpm,leads,custo_por_lead,criado_via)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ")->execute([
        $CLIENT_ID, 'meta_ads', 'Importado via API',
        date('Y-m-01'), date('Y-m-d'),
        $spend, $imps, $clks, $ctr, $cpc, $cpm,
        $leads, $leads > 0 ? $spend / $leads : 0,
        'api'
    ]);
    echo "✅ Métricas importadas com sucesso!\n";
} else {
    echo "⚠️ Sem dados disponíveis para importar.\n";
}
