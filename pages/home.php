<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    header('Location: ../login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| USER INFORMATION
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare(
    'SELECT full_name FROM users WHERE id = ? LIMIT 1'
);
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$userName = $user['full_name'] ?? 'there';
$firstName = explode(' ', trim($userName))[0];

/*
|--------------------------------------------------------------------------
| GREETING
|--------------------------------------------------------------------------
*/
$hour = (int) date('G');

if ($hour < 12) {
    $greeting = 'Good morning!';
} elseif ($hour < 18) {
    $greeting = 'Good afternoon!';
} else {
    $greeting = 'Good evening!';
}

/*
|--------------------------------------------------------------------------
| INGREDIENT COUNTS
|--------------------------------------------------------------------------
| Assumptions:
| ingredients.user_id
| ingredients.expiration_date
*/
$ingredientCount = 0;
$expiringCount = 0;

try {
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM ingredients WHERE user_id = ?'
    );
    $stmt->execute([$userId]);
    $ingredientCount = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM ingredients
         WHERE user_id = ?
           AND expiration_date BETWEEN CURDATE()
               AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)'
    );
    $stmt->execute([$userId]);
    $expiringCount = (int) $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log('Ingredient dashboard query: ' . $e->getMessage());
}

/*
|--------------------------------------------------------------------------
| TODAY'S NUTRITION
|--------------------------------------------------------------------------
| Assumptions:
| nutrition_logs.user_id
| nutrition_logs.logged_at
| nutrition_logs.calories
| nutrition_logs.protein
| nutrition_logs.carbs
| nutrition_logs.fat
*/
$nutrition = [
    'calories' => 0,
    'protein' => 0,
    'carbs' => 0,
    'fat' => 0
];

try {
    $stmt = $pdo->prepare(
        'SELECT
            COALESCE(SUM(calories), 0) AS calories,
            COALESCE(SUM(protein), 0) AS protein,
            COALESCE(SUM(carbs), 0) AS carbs,
            COALESCE(SUM(fat), 0) AS fat
         FROM nutrition_logs
         WHERE user_id = ?
           AND logged_at >= CURDATE()
           AND logged_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY)'
    );

    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $nutrition = $result;
    }
} catch (PDOException $e) {
    error_log('Nutrition dashboard query: ' . $e->getMessage());
}

/*
|--------------------------------------------------------------------------
| HEALTH PROFILE
|--------------------------------------------------------------------------
| Assumptions:
| health_profiles.user_id
| health_profiles.health_goal
| health_profiles.daily_calorie_goal
| health_profiles.portion_preference
*/
$healthGoal = 'Set your health goal';
$dailyCalorieGoal = 2000;
$portionPreference = 'Not set';

try {
    $stmt = $pdo->prepare(
        'SELECT health_goal, daily_calorie_goal, portion_preference
         FROM health_profiles
         WHERE user_id = ?
         LIMIT 1'
    );

    $stmt->execute([$userId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($profile) {
        $healthGoal = $profile['health_goal'] ?: $healthGoal;
        $dailyCalorieGoal = (int) (
            $profile['daily_calorie_goal'] ?: 2000
        );
        $portionPreference =
            $profile['portion_preference'] ?: $portionPreference;
    }
} catch (PDOException $e) {
    error_log('Health profile query: ' . $e->getMessage());
}

$caloriesConsumed = (float) $nutrition['calories'];
$calorieProgress = $dailyCalorieGoal > 0
    ? min(100, ($caloriesConsumed / $dailyCalorieGoal) * 100)
    : 0;

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description"
        content="ByteBite helps you manage kitchen ingredients, track nutrition, and generate meal ideas from what you have.">
    <meta name="theme-color" content="#15734B">

    <title>ByteBite | Smart Kitchen</title>

    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/home.css">
    <link rel="stylesheet" href="../css/navigation.css">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>

    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <main class="page active-page" id="homePage">
        <div class="dashboard">

            <section class="greeting">
                <span class="eyebrow">WELCOME BACK, <?= h(strtoupper($firstName)) ?></span>
                <h2>
                    <?= h($greeting) ?>
                    <i class="fa-solid fa-hand-wave" aria-hidden="true"></i>
                </h2>
                <p>What are we cooking today?</p>
            </section>

            <section class="section">
                <div class="section-title">
                    <h3>Your Kitchen</h3>
                    <button type="button" data-page="inventory">View all</button>
                </div>

                <div class="kitchen-grid">
                    <button class="kitchen-card inventory"
                        type="button" data-page="inventory">
                        <div class="feature-icon" aria-hidden="true">
                            <i class="fa-solid fa-carrot"></i>
                        </div>
                        <div class="kitchen-card-content">
                            <div class="kitchen-title-row">
                                <h4><?= number_format($ingredientCount) ?></h4>
                                <strong>Ingredients</strong>
                            </div>
                            <p>Available in your kitchen</p>
                        </div>
                    </button>

                    <button class="kitchen-card expiring"
                        type="button" data-page="inventory">
                        <div class="feature-icon" aria-hidden="true">
                            <i class="fa-solid fa-clock"></i>
                        </div>
                        <div class="kitchen-card-content">
                            <div class="kitchen-title-row">
                                <h4><?= number_format($expiringCount) ?></h4>
                                <strong>Expiring Soon</strong>
                            </div>
                            <p>Expiring within 7 days</p>
                        </div>
                    </button>
                </div>
            </section>

            <section class="section">
                <div class="section-title">
                    <h3>Nutrition Today</h3>
                    <button type="button" data-page="health">Details</button>
                </div>

                <div class="nutrition-card">
                    <div class="nutrition-header">
                        <div>
                            <span class="eyebrow">DAILY CALORIES</span>
                            <h2>
                                <?= number_format($caloriesConsumed) ?>
                                <small>/ <?= number_format($dailyCalorieGoal) ?> kcal</small>
                            </h2>
                        </div>

                        <div class="calorie-icon" aria-hidden="true">
                            <i class="fa-solid fa-fire-flame-curved"></i>
                        </div>
                    </div>

                    <div class="progress"
                        role="progressbar"
                        aria-label="Daily calorie intake"
                        aria-valuenow="<?= (int) round($calorieProgress) ?>"
                        aria-valuemin="0"
                        aria-valuemax="100">
                        <div class="progress-value"
                            style="width: <?= $calorieProgress ?>%;"></div>
                    </div>

                    <div class="macro-grid">
                        <div class="macro protein">
                            <b aria-hidden="true">
                                <i class="fa-solid fa-drumstick-bite"></i>
                            </b>
                            <div>
                                <strong><?= number_format((float) $nutrition['protein'], 1) ?>g</strong>
                                <span>Protein</span>
                            </div>
                        </div>

                        <div class="macro carbs">
                            <b aria-hidden="true">
                                <i class="fa-solid fa-bread-slice"></i>
                            </b>
                            <div>
                                <strong><?= number_format((float) $nutrition['carbs'], 1) ?>g</strong>
                                <span>Carbs</span>
                            </div>
                        </div>

                        <div class="macro fat">
                            <b aria-hidden="true">
                                <i class="fa-solid fa-droplet"></i>
                            </b>
                            <div>
                                <strong><?= number_format((float) $nutrition['fat'], 1) ?>g</strong>
                                <span>Fat</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <button class="health-card"
                type="button" data-page="health">
                <div class="health-icon" aria-hidden="true">
                    <i class="fa-solid fa-heart-pulse"></i>
                </div>

                <div class="health-info">
                    <span class="eyebrow">YOUR CURRENT GOAL</span>
                    <h3><?= h($healthGoal) ?></h3>
                    <p>
                        <?= h($portionPreference) ?> portion
                        · <?= number_format($dailyCalorieGoal) ?> kcal/day
                    </p>
                </div>

                <span class="health-arrow" aria-hidden="true">
                    <i class="fa-solid fa-arrow-right"></i>
                </span>
            </button>

        </div>
    </main>

    <?php require_once __DIR__ . '/../includes/navigation.php'; ?>

</body>

</html>