
<?php
require_once __DIR__ . '/config/database.php';

$accounts = [
    ['admin@bytebite.com', 'admin123'],
    ['juan@example.com', 'juan123']
];

$stmt = $pdo->prepare(
    'UPDATE users SET password_hash = ? WHERE email = ?'
);

foreach ($accounts as [$email, $password]) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt->execute([$hash, $email]);

    echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8')
        . ': '
        . ($stmt->rowCount() ? 'Password updated' : 'No change or account not found')
        . '<br>';
}
