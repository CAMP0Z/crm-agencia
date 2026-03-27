<?php
/*
 * integrations/meta/meta-service.php
 * Meta (Facebook) Ads API Service Layer
 * ─────────────────────────────────────────────────────────────
 * Ready for production integration.
 * 1. Set META_ACCESS_TOKEN and META_AD_ACCOUNT_ID in .env or config.
 * 2. Call MetaService::getCampaigns($adAccountId) to fetch real data.
 */

class MetaService {
    private const API_BASE = 'https://graph.facebook.com/v19.0';
    private string $token;

    public function __construct(string $token = '') {
        $this->token = $token ?: (defined('META_ACCESS_TOKEN') ? META_ACCESS_TOKEN : '');
    }

    /**
     * Fetch all campaigns for an Ad Account
     */
    public function getCampaigns(string $adAccountId): array {
        $url = self::API_BASE . "/{$adAccountId}/campaigns?" . http_build_query([
            'fields'      => 'id,name,status,objective,daily_budget',
            'access_token'=> $this->token,
            'limit'       => 100,
        ]);
        return $this->request($url);
    }

    /**
     * Fetch insights for a specific campaign or ad account
     */
    public function getInsights(string $objectId, string $datePreset = 'last_30d'): array {
        $url = self::API_BASE . "/{$objectId}/insights?" . http_build_query([
            'fields'      => 'impressions,clicks,ctr,cpc,cpm,spend,reach,frequency,actions',
            'date_preset' => $datePreset,
            'access_token'=> $this->token,
        ]);
        return $this->request($url);
    }

    /**
     * Fetch lead forms (Lead Ads)
     */
    public function getLeadForms(string $pageId): array {
        $url = self::API_BASE . "/{$pageId}/leadgen_forms?" . http_build_query([
            'fields'      => 'id,name,status,leads_count',
            'access_token'=> $this->token,
        ]);
        return $this->request($url);
    }

    private function request(string $url): array {
        $ctx = stream_context_create(['http' => ['timeout' => 15]]);
        $raw = file_get_contents($url, false, $ctx);
        if ($raw === false) return ['error' => 'Connection failed'];
        $data = json_decode($raw, true);
        return $data ?? ['error' => 'Invalid JSON'];
    }
}
