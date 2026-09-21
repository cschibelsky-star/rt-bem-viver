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
    $expectedCityAdmins = [
        'sumare' => 'sumare',
        'americana' => 'americana',
        'campinas' => 'campinas',
        'hortolandia' => 'hortolandia',
        'santabarbara' => 'santa-barbara-doeste',
    ];

    $admin = find_user('admin');
    assert_true(is_array($admin), 'admin geral não encontrado');
    assert_true(($admin['role'] ?? '') === 'general_admin', 'perfil do admin geral inválido');

    foreach ($expectedCityAdmins as $username => $citySlug) {
        $cityUser = find_user($username);
        assert_true(is_array($cityUser), "admin {$username} não encontrado");
        assert_true(($cityUser['role'] ?? '') === 'city_admin', "perfil de {$username} inválido");
        assert_true(($cityUser['city_slug'] ?? '') === $citySlug, "vínculo de {$username} com a cidade está incorreto");

        $_SESSION['user'] = $cityUser;
        assert_true(user_can_manage_city($citySlug) === true, "{$username} deveria administrar {$citySlug}");
        foreach ($expectedCityAdmins as $otherUsername => $otherSlug) {
            if ($otherSlug !== $citySlug) {
                assert_true(user_can_manage_city($otherSlug) === false, "{$username} não pode administrar {$otherSlug}");
            }
        }
        assert_true(is_general_admin() === false, "{$username} não pode ser admin geral");
    }

    $_SESSION['user'] = $admin;
    assert_true(is_general_admin() === true, 'admin geral não reconhecido');
    foreach ($expectedCityAdmins as $citySlug) {
        assert_true(user_can_manage_city($citySlug) === true, "admin geral deveria administrar {$citySlug}");
    }

    $sumare = find_user('sumare');
    assert_true(create_recovery_request($sumare), 'falha ao registrar recuperação de Sumaré');
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

    echo "PASS: admin geral + 5 admins municipais + isolamento entre cidades + recuperação + senha temporária\n";
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
