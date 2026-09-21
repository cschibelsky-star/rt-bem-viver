<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/security.php';

function assert_true($condition, string $message): void {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$usersFile = data_file('users');
$requestsFile = data_file('password_reset_requests');
$usersBackup = file_exists($usersFile) ? file_get_contents($usersFile) : null;
$requestsBackup = file_exists($requestsFile) ? file_get_contents($requestsFile) : null;

try {
    $admin = find_user('admin');
    $sumare = find_user('sumare');

    assert_true(is_array($admin), 'admin geral não encontrado');
    assert_true(($admin['role'] ?? '') === 'general_admin', 'perfil do admin geral inválido');
    assert_true(is_array($sumare), 'admin de Sumaré não encontrado');
    assert_true(($sumare['role'] ?? '') === 'city_admin', 'perfil do admin de Sumaré inválido');
    assert_true(($sumare['city_slug'] ?? '') === 'sumare', 'vínculo da cidade de Sumaré inválido');

    $_SESSION['user'] = $sumare;
    assert_true(user_can_manage_city('sumare') === true, 'admin de Sumaré deveria administrar Sumaré');
    assert_true(user_can_manage_city('americana') === false, 'admin de Sumaré não pode administrar Americana');
    assert_true(is_general_admin() === false, 'admin de cidade não pode ser admin geral');

    $_SESSION['user'] = $admin;
    assert_true(is_general_admin() === true, 'admin geral não reconhecido');
    assert_true(user_can_manage_city('sumare') === true, 'admin geral deveria administrar Sumaré');
    assert_true(user_can_manage_city('americana') === true, 'admin geral deveria administrar Americana');

    assert_true(create_recovery_request($sumare), 'falha ao registrar recuperação');
    $pending = array_values(array_filter(
        recovery_requests(),
        fn($r) => ($r['username'] ?? '') === 'sumare' && ($r['status'] ?? '') === 'pending'
    ));
    assert_true(count($pending) === 1, 'solicitação pendente de Sumaré não encontrada');

    $temporary = random_temporary_password();
    assert_true(password_is_strong($temporary), 'senha temporária não atende à política');

    $changed = $sumare;
    $changed['password_hash'] = password_hash($temporary, PASSWORD_DEFAULT);
    $changed['must_change_password'] = true;
    assert_true(save_user($changed), 'falha ao salvar senha temporária');
    $saved = find_user('sumare');
    assert_true(password_verify($temporary, $saved['password_hash'] ?? ''), 'senha temporária não validou');
    assert_true(!empty($saved['must_change_password']), 'troca obrigatória de senha não foi marcada');

    assert_true(close_recovery_requests('sumare'), 'falha ao encerrar solicitação');
    $open = array_values(array_filter(
        recovery_requests(),
        fn($r) => ($r['username'] ?? '') === 'sumare' && ($r['status'] ?? '') === 'pending'
    ));
    assert_true(count($open) === 0, 'solicitação deveria estar resolvida');

    assert_true(password_is_strong('Aa!1234567'), 'senha forte deveria ser aceita');
    assert_true(!password_is_strong('fraca123'), 'senha fraca deveria ser rejeitada');

    echo "PASS: admin geral + isolamento por cidade + recuperação + senha temporária\n";
} finally {
    unset($_SESSION['user']);

    if ($usersBackup !== null) {
        file_put_contents($usersFile, $usersBackup, LOCK_EX);
    } elseif (file_exists($usersFile)) {
        unlink($usersFile);
    }

    if ($requestsBackup !== null) {
        file_put_contents($requestsFile, $requestsBackup, LOCK_EX);
    } elseif (file_exists($requestsFile)) {
        unlink($requestsFile);
    }
}
