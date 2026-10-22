<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$page = 'profile';

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    header('Location: ../login.php');
    exit;
}

if (empty($_SESSION['profile_csrf'])) {
    $_SESSION['profile_csrf'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['profile_csrf'];
$message = '';
$messageType = '';

/*
|--------------------------------------------------------------------------
| LOAD CURRENT ACCOUNT
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare(
    'SELECT id, full_name, email, password_hash
     FROM users
     WHERE id = ?
     LIMIT 1'
);
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header('Location: ../login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| SAVE PROFILE AND CHANGE PASSWORD
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        !hash_equals($csrfToken, $submittedToken)
    ) {
        $message = 'Your session expired. Refresh the page and try again.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        try {
            if ($action === 'save_profile') {
                $fullName = trim($_POST['full_name'] ?? '');
                $calories = $_POST['calorie_target'] ?? '';
                $protein = $_POST['protein'] ?? '';
                $carbs = $_POST['carbs'] ?? '';
                $fats = $_POST['fats'] ?? '';
                $dietaryGoal = trim($_POST['dietary_goal'] ?? '');
                $portionSize = $_POST['portion_size'] ?? 'Medium';

                $allowedPortions = ['Small', 'Medium', 'Large'];

                if ($fullName === '' || mb_strlen($fullName) > 100) {
                    $message = 'Enter a valid name with up to 100 characters.';
                    $messageType = 'error';
                } elseif (
                    !in_array($portionSize, $allowedPortions, true)
                ) {
                    $message = 'Select a valid portion size.';
                    $messageType = 'error';
                } elseif (
                    $calories !== '' &&
                    (!is_numeric($calories) ||
                        (float) $calories < 1 ||
                        (float) $calories > 10000)
                ) {
                    $message = 'Enter a valid daily calorie target.';
                    $messageType = 'error';
                } else {
                    $targets = [];

                    foreach (
                        [
                            'protein' => $protein,
                            'carbs' => $carbs,
                            'fats' => $fats
                        ] as $key => $value
                    ) {
                        if (
                            $value !== '' &&
                            (!is_numeric($value) ||
                                (float) $value < 0 ||
                                (float) $value > 1000)
                        ) {
                            $message = 'Enter valid nutrition targets.';
                            $messageType = 'error';
                            break;
                        }

                        $targets[$key] = $value === ''
                            ? null
                            : (float) $value;
                    }

                    if ($messageType !== 'error') {
                        $pdo->beginTransaction();

                        $updateUser = $pdo->prepare(
                            'UPDATE users
                             SET full_name = ?
                             WHERE id = ?'
                        );
                        $updateUser->execute([$fullName, $userId]);

                        /*
                         * These columns exist in the provided
                         * health_profiles database schema.
                         */
                        $checkProfile = $pdo->prepare(
                            'SELECT id
                             FROM health_profiles
                             WHERE user_id = ?
                             LIMIT 1'
                        );
                        $checkProfile->execute([$userId]);

                        $existingProfile = $checkProfile->fetchColumn();

                        if ($existingProfile) {
                            $saveHealth = $pdo->prepare(
                                'UPDATE health_profiles
                                 SET dietary_goal = ?,
                                     portion_size = ?,
                                     daily_calorie_target = ?,
                                     protein_target = ?,
                                     carbs_target = ?,
                                     fat_target = ?
                                 WHERE user_id = ?'
                            );
                        } else {
                            $saveHealth = $pdo->prepare(
                                'INSERT INTO health_profiles
                                 (dietary_goal, portion_size,
                                  daily_calorie_target, protein_target,
                                  carbs_target, fat_target, user_id)
                                 VALUES (?, ?, ?, ?, ?, ?, ?)'
                            );
                        }

                        $saveHealth->execute([
                            $dietaryGoal !== '' ? $dietaryGoal : null,
                            $portionSize,
                            $calories !== '' ? (float) $calories : null,
                            $targets['protein'],
                            $targets['carbs'],
                            $targets['fats'],
                            $userId
                        ]);

                        $pdo->commit();

                        $message = 'Your profile was saved successfully.';
                        $messageType = 'success';

                        $user['full_name'] = $fullName;
                    }
                }
            } elseif ($action === 'change_password') {
                $currentPassword = $_POST['current_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';

                if (
                    $currentPassword === '' ||
                    $newPassword === '' ||
                    $confirmPassword === ''
                ) {
                    $message = 'Complete all password fields.';
                    $messageType = 'error';
                } elseif (
                    !password_verify(
                        $currentPassword,
                        $user['password_hash']
                    )
                ) {
                    $message = 'Your current password is incorrect.';
                    $messageType = 'error';
                } elseif (strlen($newPassword) < 8) {
                    $message = 'Your new password must contain at least 8 characters.';
                    $messageType = 'error';
                } elseif ($newPassword !== $confirmPassword) {
                    $message = 'The new passwords do not match.';
                    $messageType = 'error';
                } elseif ($currentPassword === $newPassword) {
                    $message = 'Choose a password different from your current one.';
                    $messageType = 'error';
                } else {
                    $newHash = password_hash(
                        $newPassword,
                        PASSWORD_DEFAULT
                    );

                    $updatePassword = $pdo->prepare(
                        'UPDATE users
                         SET password_hash = ?
                         WHERE id = ?'
                    );

                    $updatePassword->execute([$newHash, $userId]);

                    $message = 'Password changed successfully.';
                    $messageType = 'success';
                }
            } else {
                $message = 'Invalid request.';
                $messageType = 'error';
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log('Profile error: ' . $e->getMessage());

            $message = 'Unable to save your changes. Please try again.';
            $messageType = 'error';
        }
    }
}

/*
|--------------------------------------------------------------------------
| LOAD SAVED HEALTH PROFILE
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare(
    'SELECT dietary_goal, portion_size, daily_calorie_target,
            protein_target, carbs_target, fat_target
     FROM health_profiles
     WHERE user_id = ?
     LIMIT 1'
);
$stmt->execute([$userId]);
$healthProfile = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

function profileValue(array $data, string $key, string $default = ''): string
{
    return htmlspecialchars(
        (string) ($data[$key] ?? $default),
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, viewport-fit=cover">

    <meta name="description"
        content="Manage your ByteBite account and nutrition goals.">

    <meta name="theme-color" content="#15734B">

    <title>Your Profile | ByteBite</title>

    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/profile.css">
    <link rel="stylesheet" href="../css/navigation.css">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>

    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <main class="page active-page profile-page">
        <div class="page-container">

            <section class="profile-heading">
                <h1>Your Profile</h1>
                <p>Manage your account information and nutrition goals.</p>
            </section>

            <?php if ($message !== ''): ?>
                <div class="profile-message <?= profileValue(
                                                ['type' => $messageType],
                                                'type'
                                            ) ?>"
                    role="status"
                    aria-live="polite">
                    <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <!-- ACCOUNT DETAILS -->
            <section class="profile-section" id="accountSection">
                <div class="section-content">
                    <h2>
                        <i class="fa-solid fa-user"></i>
                        Account Information
                    </h2>

                    <div class="profile-field">
                        <label for="accountName">Full Name</label>
                        <input
                            type="text"
                            id="accountName"
                            value="<?= profileValue($user, 'full_name') ?>"
                            readonly>
                    </div>

                    <div class="profile-field">
                        <label for="accountEmail">Email Address</label>
                        <input
                            type="email"
                            id="accountEmail"
                            value="<?= profileValue($user, 'email') ?>"
                            readonly>
                    </div>

                    <p class="field-help">
                        These are the details associated with your logged-in account.
                        Your password is not displayed for security reasons.
                    </p>
                </div>
            </section>

            <!-- EDIT PROFILE AND NUTRITION GOALS -->
            <form class="profile-form" method="POST" id="profileForm">
                <input type="hidden" name="csrf_token"
                    value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="save_profile">

                <details class="profile-section" open>
                    <summary class="section-heading">
                        <span class="section-icon">
                            <i class="fa-solid fa-user-pen"></i>
                        </span>

                        <span class="section-title">
                            <span class="section-name">Edit Profile</span>
                            <span class="section-description">
                                Update your name and dietary goals
                            </span>
                        </span>

                        <i class="fa-solid fa-chevron-down section-chevron"></i>
                    </summary>

                    <div class="section-content">
                        <div class="profile-field">
                            <label for="fullName">Full Name</label>
                            <input
                                type="text"
                                id="fullName"
                                name="full_name"
                                maxlength="100"
                                required
                                autocomplete="name"
                                value="<?= profileValue($user, 'full_name') ?>">
                        </div>

                        <div class="profile-field">
                            <label for="dietaryGoal">Dietary Goal</label>
                            <select id="dietaryGoal" name="dietary_goal">
                                <option value="">Select a goal</option>
                                <?php
                                $goals = [
                                    'Weight Loss',
                                    'Weight Maintenance',
                                    'Muscle Gain',
                                    'Healthy Eating'
                                ];

                                foreach ($goals as $goal):
                                ?>
                                    <option
                                        value="<?= htmlspecialchars($goal, ENT_QUOTES, 'UTF-8') ?>"
                                        <?= (($healthProfile['dietary_goal'] ?? '') === $goal)
                                            ? 'selected'
                                            : '' ?>>
                                        <?= htmlspecialchars($goal, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="profile-field">
                            <label for="portionSize">Default Portion Size</label>
                            <select id="portionSize" name="portion_size">
                                <?php foreach (['Small', 'Medium', 'Large'] as $size): ?>
                                    <option
                                        value="<?= $size ?>"
                                        <?= (($healthProfile['portion_size'] ?? 'Medium') === $size)
                                            ? 'selected'
                                            : '' ?>>
                                        <?= $size ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </details>

                <details class="profile-section">
                    <summary class="section-heading">
                        <span class="section-icon">
                            <i class="fa-solid fa-chart-pie"></i>
                        </span>

                        <span class="section-title">
                            <span class="section-name">Nutrition Goals</span>
                            <span class="section-description">
                                Set your daily nutrition targets
                            </span>
                        </span>

                        <i class="fa-solid fa-chevron-down section-chevron"></i>
                    </summary>

                    <div class="section-content">

                        <div class="profile-row">
                            <div class="profile-field">
                                <label for="protein">Protein (g)</label>
                                <input
                                    type="number"
                                    id="protein"
                                    name="protein"
                                    min="0"
                                    max="1000"
                                    step="1"
                                    value="<?= profileValue($healthProfile, 'protein_target') ?>">
                            </div>

                            <div class="profile-field">
                                <label for="carbs">Carbohydrates (g)</label>
                                <input
                                    type="number"
                                    id="carbs"
                                    name="carbs"
                                    min="0"
                                    max="1000"
                                    step="1"
                                    value="<?= profileValue($healthProfile, 'carbs_target') ?>">
                            </div>
                        </div>

                        <div class="profile-row">
                            <div class="profile-field">
                                <label for="fats">Fat (g)</label>
                                <input
                                    type="number"
                                    id="fats"
                                    name="fats"
                                    min="0"
                                    max="1000"
                                    step="1"
                                    value="<?= profileValue($healthProfile, 'fat_target') ?>">
                            </div>

                            <div class="profile-field">
                                <label for="calorieTarget">Daily Calories (kcal)</label>
                                <input
                                    type="number"
                                    id="calorieTarget"
                                    name="calorie_target"
                                    min="1"
                                    max="10000"
                                    step="1"
                                    value="<?= profileValue($healthProfile, 'daily_calorie_target') ?>">
                            </div>
                        </div>

                        <p class="nutrition-help">
                            These are personal nutrition targets and are not medical advice.
                        </p>
                    </div>
                </details>

                <div
                    class="profile-autosave-status"
                    id="profileAutosaveStatus"
                    role="status"
                    aria-live="polite">
                    <i class="fa-solid fa-cloud"></i>
                    <span>Changes save automatically.</span>
                </div>
            </form>

            <!-- CHANGE PASSWORD -->
            <details class="profile-section password-section">
                <summary class="section-heading">
                    <span class="section-icon">
                        <i class="fa-solid fa-lock"></i>
                    </span>

                    <span class="section-title">
                        <span class="section-name">Change Password</span>
                        <span class="section-description">
                            Update your account password securely
                        </span>
                    </span>

                    <i class="fa-solid fa-chevron-down section-chevron"></i>
                </summary>

                <form method="POST" class="section-content" autocomplete="off">
                    <input type="hidden" name="csrf_token"
                        value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="change_password">

                    <div class="profile-field">
                        <label for="currentPassword">Current Password</label>
                        <input
                            type="password"
                            id="currentPassword"
                            name="current_password"
                            required
                            autocomplete="current-password">
                    </div>

                    <div class="profile-field">
                        <label for="newPassword">New Password</label>
                        <input
                            type="password"
                            id="newPassword"
                            name="new_password"
                            minlength="8"
                            required
                            autocomplete="new-password">
                        <small class="field-help">
                            Use at least 8 characters.
                        </small>
                    </div>

                    <div class="profile-field">
                        <label for="confirmPassword">Confirm New Password</label>
                        <input
                            type="password"
                            id="confirmPassword"
                            name="confirm_password"
                            minlength="8"
                            required
                            autocomplete="new-password">
                    </div>

                    <div class="profile-actions">
                        <button type="submit" class="profile-save-button">
                            <i class="fa-solid fa-key"></i>
                            Update Password
                        </button>
                    </div>
                </form>
            </details>

            <!-- LOGOUT -->
            <section class="profile-logout">
                <div class="logout-info">
                    <span class="logout-icon">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </span>

                    <span class="logout-text">
                        <span class="logout-title">Logout</span>
                        <span class="logout-description">
                            Sign out of your ByteBite account.
                        </span>
                    </span>
                </div>

                <a href="../logout.php" class="profile-logout-button">
                    Logout
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                </a>
            </section>

        </div>
    </main>

    <?php require_once __DIR__ . '/../includes/navigation.php'; ?>

    <script src="../js/profile.js"></script>

</body>

</html>