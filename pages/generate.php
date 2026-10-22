<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$page = 'generate';

$ingredients = [];
$inventoryError = false;

try {
    $stmt = $pdo->prepare(
        'SELECT id, name, quantity, unit, expiry_date
         FROM ingredients
         WHERE user_id = ?
         ORDER BY name ASC'
    );
    $stmt->execute([$userId]);
    $ingredients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('ByteBite inventory error: ' . $e->getMessage());
    $inventoryError = true;
}

function generateEscape(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function generateQuantity(mixed $quantity, mixed $unit): string
{
    $number = is_numeric($quantity) ? (float) $quantity : 0;
    $formatted = rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');

    return trim($formatted . ' ' . (string) $unit);
}
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
    <link rel="stylesheet" href="../css/generate.css">
    <link rel="stylesheet" href="../css/navigation.css">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>

    <?php require_once '../includes/header.php'; ?>

    <main class="page active-page generate-page">
        <div class="page-container">

            <div class="generate-heading section">
                <h1>Generate Recipes</h1>
                <p>Turn your available ingredients into something delicious.</p>
            </div>

            <section class="section" aria-live="polite">
                <div class="section-title">
                    <h3>Your Recipe Ideas</h3>
                </div>

                <div id="recipeResults">
                    <div class="recipe-empty">
                        <div class="recipe-empty-icon">
                            <i class="fa-solid fa-utensils"></i>
                        </div>
                        <h3>Ready to cook something good?</h3>
                        <p>
                            Select your ingredients and preferences to generate
                            personalized recipes.
                        </p>
                    </div>
                </div>
            </section>

            <?php if ($inventoryError): ?>
                <p class="helper-text" role="alert">
                    We couldn't load your inventory. Please refresh the page
                    or try again later.
                </p>
            <?php endif; ?>

            <form id="recipeForm">

                <section class="section">
                    <div class="section-title">
                        <h3>Available Ingredients</h3>

                        <button type="button"
                            class="text-action"
                            id="clearIngredients">
                            Clear selection
                        </button>
                    </div>

                    <?php if (!$inventoryError && count($ingredients) > 0): ?>

                        <div class="ingredient-selection" id="ingredientSelection">
                            <?php foreach ($ingredients as $ingredient): ?>
                                <?php
                                $name = (string) $ingredient['name'];
                                $expiryDate = $ingredient['expiry_date'] ?? null;

                                $expired = $expiryDate !== null
                                    && $expiryDate !== ''
                                    && $expiryDate < date('Y-m-d');
                                ?>

                                <label class="ingredient-option">
                                    <input
                                        type="checkbox"
                                        name="ingredients[]"
                                        value="<?= generateEscape($name) ?>"
                                        data-ingredient-id="<?= (int) $ingredient['id'] ?>"
                                        <?= $expired ? 'disabled' : '' ?>>

                                    <span class="ingredient-option-icon">
                                        <i class="fa-solid fa-carrot"></i>
                                    </span>

                                    <span class="ingredient-option-text">
                                        <strong><?= generateEscape($name) ?></strong>
                                        <small>
                                            <?= generateEscape(
                                                generateQuantity(
                                                    $ingredient['quantity'],
                                                    $ingredient['unit'] ?? ''
                                                )
                                            ) ?>
                                        </small>
                                        <?php if ($expired): ?>
                                            <small>Expired — unavailable</small>
                                        <?php endif; ?>
                                    </span>

                                    <span class="ingredient-check">
                                        <i class="fa-solid fa-check"></i>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <p class="helper-text" id="selectedIngredientCount">
                            Select the ingredients you want to use.
                        </p>

                    <?php elseif (!$inventoryError): ?>

                        <div class="recipe-empty">
                            <div class="recipe-empty-icon">
                                <i class="fa-solid fa-basket-shopping"></i>
                            </div>
                            <h3>No ingredients yet</h3>
                            <p>Add ingredients to your inventory before generating recipes.</p>
                            <a href="inventory.php" class="text-action">
                                Go to Inventory
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>

                    <?php endif; ?>
                </section>

                <section class="preference-section section">
                    <div class="section-title">
                        <h3>Recipe Preferences</h3>
                    </div>

                    <div class="form-group">
                        <label for="mealType">Meal Type</label>
                        <select id="mealType" name="meal_type">
                            <option value="any">Any meal</option>
                            <option value="breakfast">Breakfast</option>
                            <option value="lunch">Lunch</option>
                            <option value="dinner">Dinner</option>
                            <option value="snack">Snack</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="dietPreference">Dietary Preference</label>
                        <select id="dietPreference" name="diet_preference">
                            <option value="none">No specific preference</option>
                            <option value="vegetarian">Vegetarian</option>
                            <option value="high-protein">High protein</option>
                            <option value="low-carb">Low carb</option>
                            <option value="low-calorie">Low calorie</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="cookingTime">Maximum Cooking Time</label>
                        <select id="cookingTime" name="cooking_time">
                            <option value="15">15 minutes</option>
                            <option value="30" selected>30 minutes</option>
                            <option value="60">60 minutes</option>
                            <option value="any">Any duration</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="servings">Servings</label>
                        <select id="servings" name="servings">
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?= $i ?>" <?= $i === 2 ? 'selected' : '' ?>>
                                    <?= $i ?> <?= $i === 1 ? 'person' : 'people' ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Available Appliances</label>

                        <div class="choice-grid appliance-choices">
                            <label class="choice-option">
                                <input type="checkbox"
                                    name="appliances[]"
                                    value="pan">
                                <span>
                                    <i class="fa-solid fa-fire-burner"></i>
                                    Pan / Stove
                                </span>
                            </label>

                            <label class="choice-option">
                                <input type="checkbox"
                                    name="appliances[]"
                                    value="oven">
                                <span>
                                    <i class="fa-solid fa-temperature-high"></i>
                                    Oven
                                </span>
                            </label>

                            <label class="choice-option">
                                <input type="checkbox"
                                    name="appliances[]"
                                    value="air-fryer">
                                <span>
                                    <i class="fa-solid fa-fan"></i>
                                    Air Fryer
                                </span>
                            </label>
                        </div>

                        <p class="helper-text">
                            Select the appliances you have. Leave all unchecked
                            if you have no specific appliance preference.
                        </p>
                    </div>

                    <button type="submit"
                        class="generate-button"
                        id="generateButton"
                        <?= $inventoryError || count($ingredients) === 0 ? 'disabled' : '' ?>>
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                        <span>Generate Recipes</span>
                    </button>

                    <p class="form-note">
                        AI-generated recipes and nutrition values are estimates.
                    </p>
                </section>

            </form>

        </div>
    </main>

    <?php require_once __DIR__ . '/../includes/navigation.php'; ?>

    <script src="../js/generate.js" defer></script>
</body>

</html>