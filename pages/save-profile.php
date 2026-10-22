
<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

function respond(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, [
        'success' => false,
        'message' => 'Method not allowed.'
    ]);
}

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    respond(401, [
        'success' => false,
        'message' => 'Your session has expired. Please log in again.'
    ]);
}

// Verify CSRF token.
$submittedToken = $_POST['csrf_token'] ?? '';
$sessionToken = $_SESSION['profile_csrf'] ?? '';

if (
    !is_string($submittedToken) ||
    !is_string($sessionToken) ||
    $sessionToken === '' ||
    !hash_equals($sessionToken, $submittedToken)
) {
    respond(403, [
        'success' => false,
        'message' => 'Session verification failed. Refresh the page.'
    ]);
}

// Read and validate form values.
$fullName = trim((string) ($_POST['full_name'] ?? ''));
$dietaryGoal = trim((string) ($_POST['dietary_goal'] ?? ''));
$portionSize = (string) ($_POST['portion_size'] ?? 'Medium');

if ($fullName === '' || mb_strlen($fullName) > 100) {
    respond(422, [
        'success' => false,
        'message' => 'Enter a valid name of up to 100 characters.'
    ]);
}

if (!in_array($portionSize, ['Small', 'Medium', 'Large'], true)) {
    respond(422, [
        'success' => false,
        'message' => 'Select a valid portion size.'
    ]);
}

if (!in_array($dietaryGoal, [
    '',
    'Weight Loss',
    'Weight Maintenance',
    'Muscle Gain',
    'Healthy Eating'
], true)) {
    respond(422, [
        'success' => false,
        'message' => 'Select a valid dietary goal.'
    ]);
}

/**
 * Return null for an empty field so the database default
 * can be used instead of inserting NULL into a NOT NULL column.
 */
function nullableNumber(
    mixed $value,
    float $min,
    float $max,
    string $label
): ?float {
    if (!is_scalar($value)) {
        throw new InvalidArgumentException(
            'Enter a valid value for ' . $label . '.'
        );
    }

    $value = trim((string) $value);

    if ($value === '') {
        return null;
    }

    if (
        !is_numeric($value) ||
        !is_finite((float) $value) ||
        (float) $value < $min ||
        (float) $value > $max
    ) {
        throw new InvalidArgumentException(
            'Enter a valid value for ' . $label . '.'
        );
    }

    return (float) $value;
}

try {
    $calorieTarget = nullableNumber(
        $_POST['calorie_target'] ?? '',
        1,
        10000,
        'daily calories'
    );

    $proteinTarget = nullableNumber(
        $_POST['protein'] ?? '',
        0,
        1000,
        'protein'
    );

    $carbsTarget = nullableNumber(
        $_POST['carbs'] ?? '',
        0,
        1000,
        'carbohydrates'
    );

    $fatTarget = nullableNumber(
        $_POST['fats'] ?? '',
        0,
        1000,
        'fat'
    );

    // Your database defines daily_calorie_target as INT.
    if (
        $calorieTarget !== null &&
        floor($calorieTarget) !== $calorieTarget
    ) {
        throw new InvalidArgumentException(
            'Daily calories must be a whole number.'
        );
    }

    $pdo->beginTransaction();

    // Update the user's account name.
    $updateUser = $pdo->prepare(
        'UPDATE users
         SET full_name = ?
         WHERE id = ?'
    );

    $updateUser->execute([$fullName, $userId]);

    // Check whether this user already has a health profile.
    $checkProfile = $pdo->prepare(
        'SELECT id
         FROM health_profiles
         WHERE user_id = ?
         LIMIT 1'
    );

    $checkProfile->execute([$userId]);
    $profileExists = $checkProfile->fetchColumn() !== false;

    $targets = [
        'daily_calorie_target' => $calorieTarget,
        'protein_target' => $proteinTarget,
        'carbs_target' => $carbsTarget,
        'fat_target' => $fatTarget
    ];

    if ($profileExists) {
        // Update regular profile fields.
        $setParts = [
            'dietary_goal = ?',
            'portion_size = ?'
        ];

        $params = [
            $dietaryGoal !== '' ? $dietaryGoal : null,
            $portionSize
        ];

        // Use the database's DEFAULT for blank target fields.
        foreach ($targets as $column => $value) {
            if ($value === null) {
                $setParts[] = "`$column` = DEFAULT";
            } else {
                $setParts[] = "`$column` = ?";
                $params[] = $value;
            }
        }

        $params[] = $userId;

        $sql = 'UPDATE health_profiles
                SET ' . implode(', ', $setParts) . '
                WHERE user_id = ?';

        $saveProfile = $pdo->prepare($sql);
        $saveProfile->execute($params);
    } else {
        // Omit blank targets on INSERT so MariaDB applies
        // the defaults defined in the table schema.
        $columns = [
            'user_id',
            'dietary_goal',
            'portion_size'
        ];

        $values = [
            $userId,
            $dietaryGoal !== '' ? $dietaryGoal : null,
            $portionSize
        ];

        foreach ($targets as $column => $value) {
            if ($value !== null) {
                $columns[] = $column;
                $values[] = $value;
            }
        }

        $columnSql = implode(
            ', ',
            array_map(
                fn($column) => "`$column`",
                $columns
            )
        );

        $placeholders = implode(
            ', ',
            array_fill(0, count($values), '?')
        );

        $sql = "INSERT INTO health_profiles
                ($columnSql)
                VALUES ($placeholders)";

        $saveProfile = $pdo->prepare($sql);
        $saveProfile->execute($values);
    }

    $pdo->commit();

    respond(200, [
        'success' => true,
        'message' => 'All changes saved.',
        'full_name' => $fullName
    ]);
} catch (InvalidArgumentException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    respond(422, [
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        'Profile autosave error: ' .
            $e->getMessage()
    );

    respond(500, [
        'success' => false,
        'message' => 'Unable to save changes. Please try again.'
    ]);
}
