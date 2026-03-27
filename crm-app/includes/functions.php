<?php
// ============================================================
// includes/functions.php - Funções Utilitárias Globais
// ============================================================

/**
 * Sanitiza input do usuário
 */
function clean(string $str): string {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

/**
 * Retorna o badge HTML de status do cliente
 */
function statusClienteBadge(string $status): string {
    $map = [
        'onboarding'        => ['label' => 'Onboarding',         'class' => 'badge-blue'],
        'aguardando_cliente'=> ['label' => 'Aguardando Cliente', 'class' => 'badge-yellow'],
        'em_execucao'       => ['label' => 'Em Execução',        'class' => 'badge-green'],
        'revisao'           => ['label' => 'Revisão',            'class' => 'badge-purple'],
        'concluido'         => ['label' => 'Concluído',          'class' => 'badge-teal'],
        'pausado'           => ['label' => 'Pausado',            'class' => 'badge-gray'],
    ];
    $s = $map[$status] ?? ['label' => ucfirst($status), 'class' => 'badge-gray'];
    return '<span class="badge ' . $s['class'] . '">' . $s['label'] . '</span>';
}

/**
 * Retorna o badge HTML de saúde operacional
 */
function saudeBadge(string $saude): string {
    $map = [
        'critico' => ['label' => '🔴 Crítico',  'class' => 'badge-red'],
        'atencao' => ['label' => '🟡 Atenção',  'class' => 'badge-yellow'],
        'estavel' => ['label' => '🟢 Estável',  'class' => 'badge-green'],
        'avancado'=> ['label' => '🔵 Avançado', 'class' => 'badge-blue'],
    ];
    $s = $map[$saude] ?? ['label' => ucfirst($saude), 'class' => 'badge-gray'];
    return '<span class="badge ' . $s['class'] . '">' . $s['label'] . '</span>';
}

/**
 * Retorna o badge HTML de status de tarefa
 */
function statusTarefaBadge(string $status): string {
    $map = [
        'pendente'     => ['label' => 'Pendente',     'class' => 'badge-yellow'],
        'em_andamento' => ['label' => 'Em Andamento', 'class' => 'badge-blue'],
        'concluida'    => ['label' => 'Concluída',    'class' => 'badge-green'],
        'atrasada'     => ['label' => 'Atrasada',     'class' => 'badge-red'],
    ];
    $s = $map[$status] ?? ['label' => ucfirst($status), 'class' => 'badge-gray'];
    return '<span class="badge ' . $s['class'] . '">' . $s['label'] . '</span>';
}

/**
 * Retorna o badge HTML de prioridade de tarefa
 */
function prioridadeBadge(string $prioridade): string {
    $map = [
        'baixa'   => ['label' => 'Baixa',   'class' => 'badge-gray'],
        'media'   => ['label' => 'Média',   'class' => 'badge-blue'],
        'alta'    => ['label' => 'Alta',    'class' => 'badge-yellow'],
        'urgente' => ['label' => 'Urgente', 'class' => 'badge-red'],
    ];
    $s = $map[$prioridade] ?? ['label' => ucfirst($prioridade), 'class' => 'badge-gray'];
    return '<span class="badge ' . $s['class'] . '">' . $s['label'] . '</span>';
}

/**
 * Formata valor em BRL
 */
function formatBRL(float $value): string {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

/**
 * Formata número (1200 → 1.2K, 1200000 → 1.2M)
 */
function formatNum(int|float $n): string {
    if ($n >= 1_000_000) return number_format($n / 1_000_000, 1) . 'M';
    if ($n >= 1_000)     return number_format($n / 1_000, 1) . 'K';
    return (string) $n;
}

/**
 * Iniciais do nome para avatar
 */
function initials(string $name): string {
    $words = explode(' ', trim($name));
    if (count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($name, 0, 2));
}

/**
 * Calcula a saúde operacional automaticamente
 */
function calcularSaude(int $clienteId): string {
    $db = getDB();

    // Pendências do cliente
    $stmt = $db->prepare("SELECT COUNT(*) FROM demandas_cliente WHERE cliente_id=? AND status='pendente'");
    $stmt->execute([$clienteId]);
    $demandas_pendentes = (int)$stmt->fetchColumn();

    // Entregas pendentes
    $stmt = $db->prepare("SELECT COUNT(*) FROM entregas_agencia WHERE cliente_id=? AND status='pendente'");
    $stmt->execute([$clienteId]);
    $entregas_pendentes = (int)$stmt->fetchColumn();

    // Tarefas atrasadas
    $stmt = $db->prepare("SELECT COUNT(*) FROM tarefas WHERE cliente_id=? AND status='atrasada'");
    $stmt->execute([$clienteId]);
    $tarefas_atrasadas = (int)$stmt->fetchColumn();

    // Tarefas concluídas
    $stmt = $db->prepare("SELECT COUNT(*) FROM tarefas WHERE cliente_id=? AND status='concluida'");
    $stmt->execute([$clienteId]);
    $concluidas = (int)$stmt->fetchColumn();

    $score = 100;
    $score -= ($demandas_pendentes * 5);
    $score -= ($entregas_pendentes * 5);
    $score -= ($tarefas_atrasadas * 15);
    $score += ($concluidas * 3);

    if ($score <= 20) return 'critico';
    if ($score <= 50) return 'atencao';
    if ($score >= 80) return 'avancado';
    return 'estavel';
}

/**
 * Retorna resposta JSON padronizada
 */
function jsonResponse(bool $success, string $message = '', array $data = []): void {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

/**
 * Verifica se a requisição é AJAX
 */
function isAjax(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Formata data pt-BR
 */
function dataBR(?string $date): string {
    if (!$date) return '—';
    return date('d/m/Y', strtotime($date));
}

/**
 * Verifica se tarefa está atrasada e atualiza status
 */
function atualizarStatusTarefasAtrasadas(): void {
    $db = getDB();
    $db->exec("
        UPDATE tarefas
        SET status = 'atrasada'
        WHERE prazo < CURDATE()
          AND status NOT IN ('concluida', 'atrasada')
    ");
}
