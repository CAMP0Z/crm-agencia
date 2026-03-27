<?php
/*
 * integrations/hotmart/webhook.php
 * Hotmart Webhook Receiver
 * ─────────────────────────────────────────────────
 * Configure this URL in Hotmart → Settings → Webhooks:
 * https://seu-dominio.com/crm-app/integrations/hotmart/webhook.php
 */

require_once __DIR__ . '/../../config/app.php';

// Verify Hotmart signature (optional but recommended)
// $receivedToken = $_SERVER['HTTP_X_HOTMART_HOTTOK'] ?? '';
// if ($receivedToken !== HOTMART_WEBHOOK_TOKEN) { http_response_code(401); exit; }

$payload = file_get_contents('php://input');
$event   = json_decode($payload, true);

if (!$event) { http_response_code(400); exit; }

$type = $event['event'] ?? '';

// Process sale approval
if ($type === 'PURCHASE_APPROVED') {
    $purchase = $event['data']['purchase'] ?? [];
    $product  = $event['data']['product']  ?? [];

    $valor_bruto   = (float)($purchase['original_offer_price']['value'] ?? 0);
    $comissao_rate = 0.10; // 10% de comissão (ajuste)

    $db = getDB();
    $db->prepare("
        INSERT INTO vendas_plataformas (plataforma,produto,comprador,valor_bruto,valor_liquido,comissao,status,data_venda,id_externo,criado_via)
        VALUES (?,?,?,?,?,?,?,?,?,?)
    ")->execute([
        'hotmart',
        $product['name'] ?? 'Produto Hotmart',
        $event['data']['buyer']['name'] ?? '',
        $valor_bruto,
        $valor_bruto * (1 - $comissao_rate),
        $valor_bruto * $comissao_rate,
        'aprovado',
        date('Y-m-d'),
        $purchase['transaction'] ?? null,
        'webhook',
    ]);
}

if ($type === 'PURCHASE_REFUNDED') {
    $transaction = $event['data']['purchase']['transaction'] ?? '';
    if ($transaction) {
        $db = getDB();
        $db->prepare("UPDATE vendas_plataformas SET status='reembolsado' WHERE id_externo=?")->execute([$transaction]);
    }
}

http_response_code(200);
echo 'OK';
