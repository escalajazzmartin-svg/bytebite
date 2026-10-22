<?php
session_start();
require_once __DIR__ . '/config/database.php';

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, full_name, email, password_hash, email_verified
             FROM users
             WHERE email = ?
             LIMIT 1'
        );

        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {

            // Require email verification before login.
            if ((int)$user['email_verified'] !== 1) {
                $error = 'Please verify your email before logging in.';

                // Allow the user to return to the verification page
                // if this browser session has the pending user ID.
                if (
                    isset($_SESSION['pending_verification_user_id']) &&
                    (int)$_SESSION['pending_verification_user_id'] === (int)$user['id']
                ) {
                    $error .= ' You can verify your email here.';
                }
            } else {
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];

                header('Location: pages/home.php');
                exit;
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | ByteBite</title>

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
</head>

<body>
    <main class="auth-card">

        <a href="index.php" class="auth-brand">
            <i class="fa-solid fa-leaf"></i> ByteBite
        </a>

        <h1>Welcome back!</h1>
        <p class="auth-subtitle">
            Sign in to continue to your kitchen.
        </p>

        <?php if (isset($_GET['verified'])): ?>
            <p class="auth-success">
                Email verified successfully! You can now sign in.
            </p>
        <?php endif; ?>

        <?php if (isset($_GET['registered'])): ?>
            <p class="auth-success">
                Account created. Please verify your email before signing in.
            </p>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <p class="auth-error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>

                <?php if (
                    isset($_SESSION['pending_verification_user_id']) &&
                    str_contains($error, 'verify your email')
                ): ?>
                    <a href="verify-email.php">Verify email</a>
                <?php endif; ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="login.php">

            <label for="email">Email address</label>
            <input
                id="email"
                type="email"
                name="email"
                value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                required
                autocomplete="email"
                placeholder="Enter your email">

            <label for="password">Password</label>
            <input
                id="password"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="Enter your password">

            <button type="submit" class="auth-button">
                Sign In
            </button>

        </form>

        <p class="auth-switch">
            Don't have an account?
            <a href="register.php">Create one</a>
        </p>

    </main>
</body>

</html>