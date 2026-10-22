<?php
require_once __DIR__ . '/../includes/auth.php';
?>

<?php
$page = 'health';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="Track your daily nutrition and health goals with ByteBite.">
    <meta name="theme-color" content="#15734B">

    <title>Health & Nutrition | ByteBite</title>

    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/health.css">
    <link rel="stylesheet" href="../css/navigation.css">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>

    <?php require_once '../includes/header.php'; ?>

    <main class="page active-page health-page">
        <div class="page-container">

            <!-- PAGE HEADING -->
            <section class="section health-heading">
                <span class="eyebrow">YOUR WELLNESS</span>
                <h1>Health & Nutrition</h1>
                <p>Make mindful choices and keep track of your daily nutrition.</p>
            </section>

            <!-- DAILY NUTRITION -->
            <section class="section nutrition-card">

                <div class="nutrition-header">
                    <div>
                        <span class="health-card-label">DAILY CALORIE INTAKE</span>
                        <h2>
                            <span id="caloriesConsumed">850</span>
                            <small>kcal consumed</small>
                        </h2>
                    </div>

                    <span class="calorie-icon">
                        <i class="fa-solid fa-fire-flame-curved" aria-hidden="true"></i>
                    </span>
                </div>

                <div class="calorie-summary">
                    <span>Daily goal</span>
                    <strong><span id="calorieGoalLabel">2,000</span> kcal</strong>
                </div>

                <div
                    class="progress"
                    id="calorieProgress"
                    role="progressbar"
                    aria-label="Daily calorie goal progress"
                    aria-valuemin="0"
                    aria-valuemax="2000"
                    aria-valuenow="850">
                    <div class="progress-value" id="calorieProgressValue"></div>
                </div>

                <div class="calorie-footer">
                    <span><strong id="calorieRemaining">1,150</strong> kcal remaining</span>
                    <span id="caloriePercentage">43% of goal</span>
                </div>

            </section>

            <!-- MACRONUTRIENTS -->
            <section class="section">
                <div class="section-title">
                    <h3>Macronutrients</h3>
                    <span class="section-caption">Today's intake</span>
                </div>

                <div class="macro-grid">

                    <article class="macro-card">
                        <span class="macro-icon protein-icon">
                            <i class="fa-solid fa-drumstick-bite" aria-hidden="true"></i>
                        </span>

                        <div class="macro-details">
                            <span>Protein</span>
                            <strong><span id="proteinValue">35</span> g</strong>
                        </div>

                        <div class="macro-progress">
                            <div id="proteinProgress"></div>
                        </div>

                        <small><span id="proteinGoal">100</span> g goal</small>
                    </article>

                    <article class="macro-card">
                        <span class="macro-icon carbs-icon">
                            <i class="fa-solid fa-bread-slice" aria-hidden="true"></i>
                        </span>

                        <div class="macro-details">
                            <span>Carbs</span>
                            <strong><span id="carbsValue">90</span> g</strong>
                        </div>

                        <div class="macro-progress">
                            <div id="carbsProgress"></div>
                        </div>

                        <small><span id="carbsGoal">250</span> g goal</small>
                    </article>

                    <article class="macro-card">
                        <span class="macro-icon fats-icon">
                            <i class="fa-solid fa-droplet" aria-hidden="true"></i>
                        </span>

                        <div class="macro-details">
                            <span>Fats</span>
                            <strong><span id="fatsValue">25</span> g</strong>
                        </div>

                        <div class="macro-progress">
                            <div id="fatsProgress"></div>
                        </div>

                        <small><span id="fatsGoal">65</span> g goal</small>
                    </article>

                </div>
            </section>

            <!-- DAILY HEALTH GOALS -->
            <section class="section">
                <div class="section-title">
                    <h3>Daily Health Goals</h3>
                    <span class="section-caption">Build healthy habits</span>
                </div>

                <div class="health-goals">

                    <article class="health-goal-card">
                        <span class="goal-icon water-icon">
                            <i class="fa-solid fa-glass-water" aria-hidden="true"></i>
                        </span>

                        <div class="goal-content">
                            <div class="goal-title-row">
                                <h4>Water Intake</h4>
                                <strong><span id="waterValue">4</span> / <span id="waterGoalLabel">8</span> glasses</strong>
                            </div>

                            <div
                                class="goal-progress"
                                id="waterProgress"
                                role="progressbar"
                                aria-label="Water intake goal"
                                aria-valuemin="0"
                                aria-valuemax="8"
                                aria-valuenow="4">
                                <div id="waterProgressValue"></div>
                            </div>

                            <p>Stay hydrated throughout the day.</p>
                        </div>
                    </article>

                    <article class="health-goal-card">
                        <span class="goal-icon vegetable-icon">
                            <i class="fa-solid fa-carrot" aria-hidden="true"></i>
                        </span>

                        <div class="goal-content">
                            <div class="goal-title-row">
                                <h4>Vegetable Servings</h4>
                                <strong><span id="vegetableValue">2</span> / <span id="vegetableGoalLabel">5</span></strong>
                            </div>

                            <div
                                class="goal-progress"
                                id="vegetableProgress"
                                role="progressbar"
                                aria-label="Vegetable servings goal"
                                aria-valuemin="0"
                                aria-valuemax="5"
                                aria-valuenow="2">
                                <div id="vegetableProgressValue"></div>
                            </div>

                            <p>Add more colorful vegetables to your meals.</p>
                        </div>
                    </article>

                </div>
            </section>

            <!-- DIETARY PREFERENCES -->
            <section class="section">
                <div class="section-title">
                    <h3>Dietary Preferences</h3>
                </div>

                <form class="health-preferences" id="healthPreferences">

                    <div class="form-group">
                        <label for="dietType">Preferred Diet</label>
                        <select id="dietType" name="diet_type">
                            <option value="balanced">Balanced Diet</option>
                            <option value="vegetarian">Vegetarian</option>
                            <option value="high-protein">High Protein</option>
                            <option value="low-carb">Low Carb</option>
                            <option value="low-calorie">Low Calorie</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="healthGoal">Primary Health Goal</label>
                        <select id="healthGoal" name="health_goal">
                            <option value="maintain">Maintain Overall Health</option>
                            <option value="weight-loss">Weight Management</option>
                            <option value="muscle">Build Muscle</option>
                            <option value="energy">Improve Daily Energy</option>
                            <option value="balanced">Improve Nutrition Balance</option>
                        </select>
                    </div>

                    <button type="submit" class="health-save-button">
                        <i class="fa-solid fa-check" aria-hidden="true"></i>
                        Save Preferences
                    </button>

                    <p class="health-form-message" id="healthFormMessage" role="status" aria-live="polite"></p>

                </form>
            </section>

            <!-- NUTRITION TIP -->
            <section class="section nutrition-tip">
                <span class="tip-icon">
                    <i class="fa-solid fa-lightbulb" aria-hidden="true"></i>
                </span>

                <div>
                    <span class="tip-label">HEALTHY HABIT</span>
                    <h3>Make every meal count</h3>
                    <p>
                        Aim for a variety of vegetables, protein sources, whole grains,
                        and enough water throughout your day.
                    </p>
                </div>
            </section>

        </div>
    </main>

    <?php require_once '../includes/navigation.php'; ?>

    <script src="../js/health.js"></script>

</body>

</html>