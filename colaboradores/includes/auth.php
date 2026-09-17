<?php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/colaboradores/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

const TEMPO_LIMITE_INATIVIDADE = 3600; // 1 hora sem uso encerra a sessão

function estaLogado(): bool {
    if (empty($_SESSION['membro_id'])) {
        return false;
    }
    if (!empty($_SESSION['ultimo_acesso']) && (time() - $_SESSION['ultimo_acesso']) > TEMPO_LIMITE_INATIVIDADE) {
        session_unset();
        session_destroy();
        return false;
    }
    $_SESSION['ultimo_acesso'] = time();
    return true;
}

function exigirLogin(): void {
    if (!estaLogado()) {
        header('Location: login.php');
        exit;
    }
}

function membroAtualId(): ?int {
    return $_SESSION['membro_id'] ?? null;
}

function membroAtualNome(): string {
    return $_SESSION['membro_nome'] ?? '';
}

function membroAtualFoto(): ?string {
    return $_SESSION['membro_foto'] ?? null;
}

function membroAtualPapel(): string {
    return $_SESSION['membro_papel'] ?? '';
}

function ehAdmin(): bool {
    return membroAtualPapel() === 'admin';
}

/** Pastores e líderes de ministério também administram o painel (cadastros, famílias, crianças). */
function ehGestor(): bool {
    return in_array(membroAtualPapel(), ['admin', 'pastor', 'lider'], true);
}

function exigirGestor(): void {
    exigirLogin();
    if (!ehGestor()) {
        http_response_code(403);
        echo 'Acesso restrito à liderança.';
        exit;
    }
}

/** Verdadeiro se a pessoa logada lidera especificamente esse ministério (ou já é admin/líder geral). */
function ehLiderDoMinisterio(PDO $pdo, int $ministerioId): bool {
    if (!estaLogado()) return false;
    if (ehGestor()) return true;
    $stmt = $pdo->prepare('SELECT lider_id FROM ministerios WHERE id = ?');
    $stmt->execute([$ministerioId]);
    $liderId = $stmt->fetchColumn();
    return $liderId !== false && $liderId !== null && (int) $liderId === membroAtualId();
}

function exigirAdmin(): void {
    exigirLogin();
    if (!ehAdmin()) {
        http_response_code(403);
        echo 'Acesso restrito a administradores.';
        exit;
    }
}

function gerarTokenCsrf(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validarTokenCsrf(?string $token): bool {
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
