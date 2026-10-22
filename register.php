<?php
session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/mailer.php';

$error = '';
$name = '';
$email = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $error = 'Invalid request. Please refresh the page.';
    } elseif ($name === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $error = 'Please complete all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'SELECT id, email_verified FROM users WHERE email = ? LIMIT 1'
            );
            $stmt->execute([$email]);
            $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existingUser && (int)$existingUser['email_verified'] === 1) {
                $error = 'An account with this email already exists.';
            } else {
                $code = (string) random_int(100000, 999999);
                $codeHash = password_hash($code, PASSWORD_DEFAULT);
                $expiresAt = date('Y-m-d H:i:s', time() + 600);
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                if ($existingUser) {
                    $stmt = $pdo->prepare(
                        'UPDATE users
                         SET full_name = ?,
                             password_hash = ?,
                             email_verification_code_hash = ?,
                             email_verification_expires_at = ?
                         WHERE id = ? AND email_verified = 0'
                    );

                    $stmt->execute([
                        $name,
                        $passwordHash,
                        $codeHash,
                        $expiresAt,
                        $existingUser['id']
                    ]);

                    $userId = (int)$existingUser['id'];
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO users
                            (full_name, email, password_hash, email_verified,
                             email_verification_code_hash,
                             email_verification_expires_at)
                         VALUES (?, ?, ?, 0, ?, ?)'
                    );

                    $stmt->execute([
                        $name,
                        $email,
                        $passwordHash,
                        $codeHash,
                        $expiresAt
                    ]);

                    $userId = (int)$pdo->lastInsertId();
                }

                if (sendVerificationEmail($email, $code)) {
                    session_regenerate_id(true);
                    $_SESSION['pending_verification_user_id'] = $userId;
                    $_SESSION['verification_sent_at'] = time();

                    unset($_SESSION['csrf_token']);

                    header('Location: verify-email.php');
                    exit;
                }

                $error = 'We could not send the verification email. Check your mail configuration and try again.';
            }
        } catch (PDOException $e) {
            error_log('ByteBite registration error: ' . $e->getMessage());
            $error = 'Registration failed. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | ByteBite</title>
    <link rel="stylesheet" href="css/auth.css">
</head>

<body>
    <main class="auth-card">
        <div class="auth-brand">ByteBite</div>
        <h1>Create Account</h1>
        <p class="auth-subtitle">Sign up to get started.</p>

        <?php if ($error !== ''): ?>
            <div class="auth-error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name"
                value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                autocomplete="name" placeholder="Enter your full name" required>

            <label for="email">Email Address</label>
            <input type="email" id="email" name="email"
                value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                autocomplete="email" placeholder="Enter your email address" required> 

            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                minlength="8" autocomplete="new-password" placeholder="Enter your password" required>

            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password"
                minlength="8" autocomplete="new-password" placeholder="Confirm your password" required>

            <button type="submit" class="auth-button">Create Account</button>
        </form>

        <p class="auth-switch">
            Already have an account?
            <a href="login.php">Log in</a>
        </p>
    </main>
</body>

</html>