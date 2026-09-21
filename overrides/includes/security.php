<?php
require_once __DIR__ . '/functions.php';

define('OWNER_RECOVERY_HASH', '$2y$12$oIIWHBA8kOCd.Fciyi81p.Srn8ELcSjuvb61mPvSLs.gHp7KTZS46');

function find_user(string $username): ?array {
    $needle = strtolower(trim($username));
    foreach (read_json('users', []) as $user) {
        if (strtolower((string)($user['username'] ?? '')) === $needle) return $user;
    }
    return null;
}

function save_user(array $updated): bool {
    $users = read_json('users', []);
    $found = false;
    foreach ($users as &$user) {
        if (($user['username'] ?? '') === ($updated['username'] ?? '')) {
            $user = $updated;
            $found = true;
            break;
        }
    }
    unset($user);
    if (!$found) $users[] = $updated;
    return write_json('users', $users);
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void {
    $token = (string)($_POST['csrf_token'] ?? '');
    if (!$token || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Sessão expirada. Atualize a página e tente novamente.');
    }
}

function password_is_strong(string $password): bool {
    return strlen($password) >= 10
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[a-z]/', $password)
        && preg_match('/\d/', $password)
        && preg_match('/[^A-Za-z0-9]/', $password);
}

function owner_recovery_code_valid(string $code): bool {
    return password_verify($code, OWNER_RECOVERY_HASH);
}

function random_temporary_password(): string {
    return 'RT!' . bin2hex(random_bytes(6)) . 'aA9';
}

function recovery_requests(): array {
    return read_json('password_reset_requests', []);
}

function create_recovery_request(array $user): bool {
    $requests = recovery_requests();
    $username = (string)($user['username'] ?? '');
    $requests = array_values(array_filter(
        $requests,
        fn($r) => ($r['username'] ?? '') !== $username || ($r['status'] ?? '') !== 'pending'
    ));
    $requests[] = [
        'id' => uuid(),
        'username' => $username,
        'name' => (string)($user['name'] ?? $username),
        'city_slug' => (string)($user['city_slug'] ?? ''),
        'role' => (string)($user['role'] ?? ''),
        'created_at' => date(DATE_ATOM),
        'status' => 'pending'
    ];
    return write_json('password_reset_requests', $requests);
}

function close_recovery_requests(string $username): bool {
    $requests = recovery_requests();
    foreach ($requests as &$request) {
        if (($request['username'] ?? '') === $username && ($request['status'] ?? '') === 'pending') {
            $request['status'] = 'resolved';
            $request['resolved_at'] = date(DATE_ATOM);
        }
    }
    unset($request);
    return write_json('password_reset_requests', $requests);
}

function enforce_password_change(): void {
    $u = current_user();
    if ($u && !empty($u['must_change_password'])) {
        $page = basename($_SERVER['PHP_SELF'] ?? '');
        if (!in_array($page, ['minha_senha.php', 'logout.php'], true)) {
            header('Location: minha_senha.php?obrigatorio=1');
            exit;
        }
    }
}
