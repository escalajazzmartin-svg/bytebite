<?php
session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/mailer.php';

if (empty($_SESSION['pending_verification_user_id'])) {
    header('Location: register.php');
    exit;
}

$userId = (int) $_SESSION['pending_verification_user_id'];
$error = '';
$success = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$stmt = $pdo->prepare(
    'SELECT id, full_name, email, email_verified,
            email_verification_code_hash,
            email_verification_expires_at
     FROM users
     WHERE id = ?
     LIMIT 1'
);
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    unset($_SESSION['pending_verification_user_id']);
    header('Location: register.php');
    exit;
}

if ((int) $user['email_verified'] === 1) {
    unset($_SESSION['pending_verification_user_id']);
    header('Location: login.php?verified=1');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $error = 'Invalid request. Please refresh the page.';
    } elseif (isset($_POST['resend_code'])) {
        $lastSent = (int) ($_SESSION['verification_sent_at'] ?? 0);

        if (time() - $lastSent < 60) {
            $remaining = 60 - (time() - $lastSent);
            $error = "Please wait {$remaining} seconds before requesting another code.";
        } else {
            $code = (string) random_int(100000, 999999);
            $codeHash = password_hash($code, PASSWORD_DEFAULT);
            $expiresAt = date('Y-m-d H:i:s', time() + 600);

            $stmt = $pdo->prepare(
                'UPDATE users
                 SET email_verification_code_hash = ?,
                     email_verification_expires_at = ?
                 WHERE id = ? AND email_verified = 0'
            );
            $stmt->execute([$codeHash, $expiresAt, $userId]);

            if (sendVerificationEmail($user['email'], $code)) {
                $_SESSION['verification_sent_at'] = time();
                $success = 'A new verification code has been sent.';
            } else {
                $error = 'Could not send the email. Please check your mail configuration.';
            }
        }
    } else {
        $code = trim($_POST['code'] ?? '');

        if (!preg_match('/^\d{6}$/', $code)) {
            $error = 'Please enter all six digits.';
        } elseif (
            empty($user['email_verification_code_hash']) ||
            empty($user['email_verification_expires_at']) ||
            strtotime($user['email_verification_expires_at']) < time()
        ) {
            $error = 'Your code has expired. Please request a new one.';
        } elseif (!password_verify($code, $user['email_verification_code_hash'])) {
            $error = 'Incorrect verification code. Please try again.';
        } else {
            $stmt = $pdo->prepare(
                'UPDATE users
                 SET email_verified = 1,
                     email_verification_code_hash = NULL,
                     email_verification_expires_at = NULL
                 WHERE id = ? AND email_verified = 0'
            );
            $stmt->execute([$userId]);

            unset(
                $_SESSION['pending_verification_user_id'],
                $_SESSION['verification_sent_at'],
                $_SESSION['csrf_token']
            );

            header('Location: login.php?verified=1');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email | ByteBite</title>
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
</head>

<body>

    <main class="auth-card">

        <a href="index.php" class="auth-brand">
            <i class="fa-solid fa-leaf"></i>
            <span>
                <span class="brand-byte">Byte</span>
                <span class="brand-bite">Bite</span>
            </span>
        </a>

        <p class="auth-subtitle">
            Check the email sent to
            <strong><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></strong>.
        </p>

        <?php if ($error !== ''): ?>
            <div class="auth-error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="auth-success">
                <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <!-- Verification form -->
        <form method="POST" id="verificationForm">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <input type="hidden" name="code" id="verificationCode">

            <label class="verification-label">
                Enter 6-Digit Verification Code
            </label>

            <div class="verification-code" id="codeInputs">
                <?php for ($i = 0; $i < 6; $i++): ?>
                    <input
                        type="text"
                        class="code-digit"
                        inputmode="numeric"
                        pattern="[0-9]"
                        maxlength="1"
                        aria-label="Digit <?= $i + 1 ?>"
                        autocomplete="<?= $i === 0 ? 'one-time-code' : 'off' ?>"
                        required>
                <?php endfor; ?>
            </div>

            <p class="verification-hint">
                The code expires 10 minutes after it is sent.
            </p>

            <button type="submit" class="auth-button">
                Continue
            </button>
        </form>

        <!-- Separate resend form: do not nest inside verificationForm -->
        <div class="resend-wrapper">
            <span>Didn't receive a code?</span>

            <form method="POST" class="resend-form">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                <button
                    type="submit"
                    name="resend_code"
                    value="1"
                    class="resend-button">
                    Resend code
                </button>
            </form>
        </div>

        <p class="auth-switch">
            Wrong email?
            <a href="register.php">Register again</a>
        </p>

    </main>

    <script>
        const digitInputs = Array.from(
            document.querySelectorAll('.code-digit')
        );

        const hiddenCode = document.getElementById('verificationCode');
        const form = document.getElementById('verificationForm');

        function updateCode() {
            hiddenCode.value = digitInputs
                .map(input => input.value)
                .join('');
        }

        digitInputs.forEach((input, index) => {
            input.addEventListener('input', () => {
                input.value = input.value.replace(/\D/g, '').slice(-1);

                updateCode();

                if (input.value && index < digitInputs.length - 1) {
                    digitInputs[index + 1].focus();
                }
            });

            input.addEventListener('keydown', event => {
                if (
                    event.key === 'Backspace' &&
                    !input.value &&
                    index > 0
                ) {
                    digitInputs[index - 1].focus();
                }

                if (event.key === 'ArrowLeft' && index > 0) {
                    digitInputs[index - 1].focus();
                }

                if (
                    event.key === 'ArrowRight' &&
                    index < digitInputs.length - 1
                ) {
                    digitInputs[index + 1].focus();
                }
            });

            input.addEventListener('paste', event => {
                event.preventDefault();

                const pastedCode = event.clipboardData
                    .getData('text')
                    .replace(/\D/g, '')
                    .slice(0, 6);

                digitInputs.forEach((digitInput, i) => {
                    digitInput.value = pastedCode[i] || '';
                });

                updateCode();

                const firstEmpty = digitInputs.find(
                    digitInput => !digitInput.value
                );

                (firstEmpty || digitInputs[5]).focus();
            });
        });

        form.addEventListener('submit', event => {
            updateCode();

            if (!/^\d{6}$/.test(hiddenCode.value)) {
                event.preventDefault();

                const firstEmpty = digitInputs.find(
                    digitInput => !digitInput.value
                );

                (firstEmpty || digitInputs[0]).focus();
            }
        });
    </script>
</body>

</html>