<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);

if ($userId <= 0) {
    header('Location: ../login.php');
    exit;
}

$categories = [
    'Vegetables',
    'Fruits',
    'Meat',
    'Dairy',
    'Grains',
    'Pantry'
];

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$error = '';

if (!in_array($category, $categories, true)) {
    $category = '';
}

if (empty($_SESSION['inventory_csrf'])) {
    $_SESSION['inventory_csrf'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['inventory_csrf'];

/*
|--------------------------------------------------------------------------
| ADD OR DELETE INGREDIENTS
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        !hash_equals($csrfToken, $submittedToken)
    ) {
        $error = 'Your session has expired. Please refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        try {
            if ($action === 'add') {
                $name = trim($_POST['name'] ?? '');
                $quantity = $_POST['quantity'] ?? '';
                $unit = trim($_POST['unit'] ?? '');
                $newCategory = trim($_POST['category'] ?? '');
                $expiryDate = trim($_POST['expiry_date'] ?? '');

                $allowedUnits = [
                    'g',
                    'kg',
                    'ml',
                    'L',
                    'pcs',
                    'cans',
                    'packs'
                ];

                if ($name === '' || mb_strlen($name) > 150) {
                    $error = 'Enter an ingredient name with up to 150 characters.';
                } elseif (
                    !is_numeric($quantity) ||
                    (float) $quantity <= 0 ||
                    (float) $quantity > 99999999
                ) {
                    $error = 'Enter a valid quantity greater than zero.';
                } elseif (!in_array($unit, $allowedUnits, true)) {
                    $error = 'Please select a valid unit.';
                } elseif (!in_array($newCategory, $categories, true)) {
                    $error = 'Please select a valid category.';
                } elseif (
                    $expiryDate !== '' &&
                    (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiryDate) ||
                        !checkdate(
                            (int) substr($expiryDate, 5, 2),
                            (int) substr($expiryDate, 8, 2),
                            (int) substr($expiryDate, 0, 4)
                        ))
                ) {
                    $error = 'Please enter a valid expiration date.';
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO ingredients
                            (user_id, name, quantity, unit, category, expiry_date)
                         VALUES (?, ?, ?, ?, ?, ?)'
                    );

                    $stmt->execute([
                        $userId,
                        $name,
                        (float) $quantity,
                        $unit,
                        $newCategory,
                        $expiryDate !== '' ? $expiryDate : null
                    ]);

                    header('Location: inventory.php?added=1');
                    exit;
                }
            } elseif ($action === 'edit') {
                $ingredientId = filter_var($_POST['ingredient_id'] ?? null, FILTER_VALIDATE_INT);
                $name = trim($_POST['name'] ?? '');
                $quantity = $_POST['quantity'] ?? '';
                $unit = trim($_POST['unit'] ?? '');
                $newCategory = trim($_POST['category'] ?? '');
                $expiryDate = trim($_POST['expiry_date'] ?? '');
                $allowedUnits = ['g', 'kg', 'ml', 'L', 'pcs', 'cans', 'packs'];

                if (!$ingredientId || $ingredientId < 1) {
                    $error = 'Invalid ingredient.';
                } elseif ($name === '' || mb_strlen($name) > 150) {
                    $error = 'Enter an ingredient name with up to 150 characters.';
                } elseif (!is_numeric($quantity) || (float)$quantity <= 0 || (float)$quantity > 99999999) {
                    $error = 'Enter a valid quantity greater than zero.';
                } elseif (!in_array($unit, $allowedUnits, true)) {
                    $error = 'Please select a valid unit.';
                } elseif (!in_array($newCategory, $categories, true)) {
                    $error = 'Please select a valid category.';
                } elseif ($expiryDate !== '' && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiryDate) || !checkdate((int)substr($expiryDate, 5, 2), (int)substr($expiryDate, 8, 2), (int)substr($expiryDate, 0, 4)))) {
                    $error = 'Please enter a valid expiration date.';
                } else {
                    $stmt = $pdo->prepare(
                        'UPDATE ingredients
                         SET name = ?, quantity = ?, unit = ?, category = ?, expiry_date = ?
                         WHERE id = ? AND user_id = ?'
                    );
                    $stmt->execute([$name, (float)$quantity, $unit, $newCategory, $expiryDate !== '' ? $expiryDate : null, $ingredientId, $userId]);
                    header('Location: inventory.php?updated=1');
                    exit;
                }
            } elseif ($action === 'delete') {
                $ingredientId = filter_var(
                    $_POST['ingredient_id'] ?? null,
                    FILTER_VALIDATE_INT
                );

                if (!$ingredientId || $ingredientId < 1) {
                    $error = 'Invalid ingredient.';
                } else {
                    $stmt = $pdo->prepare(
                        'DELETE FROM ingredients
                         WHERE id = ? AND user_id = ?'
                    );

                    $stmt->execute([$ingredientId, $userId]);

                    header('Location: inventory.php?deleted=1');
                    exit;
                }
            }
        } catch (PDOException $e) {
            error_log('Inventory database error: ' . $e->getMessage());
            $error = 'Unable to save your changes. Please check your database configuration.';
        }
    }
}

/*
|--------------------------------------------------------------------------
| FETCH INGREDIENTS
|--------------------------------------------------------------------------
*/
$ingredients = [];

try {
    $sql = '
        SELECT id, name, quantity, unit, category, expiry_date
        FROM ingredients
        WHERE user_id = ?
    ';

    $params = [$userId];

    if ($search !== '') {
        $sql .= ' AND name LIKE ?';
        $params[] = '%' . $search . '%';
    }

    if ($category !== '') {
        $sql .= ' AND category = ?';
        $params[] = $category;
    }

    $sql .= '
        ORDER BY
            CASE
                WHEN expiry_date IS NOT NULL
                     AND expiry_date < CURDATE() THEN 0
                WHEN expiry_date IS NOT NULL
                     AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1
                ELSE 2
            END,
            name ASC
    ';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ingredients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Inventory fetch error: ' . $e->getMessage());
    $error = 'Unable to load your ingredients. Please check the database structure.';
}

function inventoryEscape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function ingredientStatus(?string $expiryDate): array
{
    if (!$expiryDate) {
        return ['Date unknown', 'unknown'];
    }

    $today = new DateTimeImmutable('today');
    $expiry = new DateTimeImmutable($expiryDate);

    if ($expiry < $today) {
        return ['Expired', 'expired'];
    }

    if ($expiry <= $today->modify('+7 days')) {
        return ['Use soon', 'use-soon'];
    }

    return ['Fresh', 'fresh'];
}

function inventoryFilterUrl(string $category, string $search): string
{
    $params = [];

    if ($category !== '') {
        $params['category'] = $category;
    }

    if ($search !== '') {
        $params['search'] = $search;
    }

    return 'inventory.php' . ($params
        ? '?' . http_build_query($params)
        : '');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, viewport-fit=cover">

    <meta name="description"
        content="Manage your kitchen ingredients and expiration dates with ByteBite.">

    <meta name="theme-color" content="#15734B">

    <title>ByteBite | Inventory</title>

    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/header.css">
    <link rel="stylesheet" href="../css/inventory.css">
    <link rel="stylesheet" href="../css/navigation.css">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>

    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <main class="page active-page inventory-page" id="inventoryPage">
        <div class="page-container">

            <section class="section inventory-heading">
                <h2>Inventory</h2>
            </section>

            <?php if ($error !== ''): ?>
                <div class="inventory-message error" role="alert">
                    <?= inventoryEscape($error) ?>
                </div>
            <?php elseif (isset($_GET['added'])): ?>
                <div class="inventory-message success" role="status">
                    Ingredient added successfully.
                </div>
            <?php elseif (isset($_GET['deleted'])): ?>
                <div class="inventory-message success" role="status">
                    Ingredient deleted successfully.
                </div>
            <?php elseif (isset($_GET['updated'])): ?>
                <div class="inventory-message success" role="status">
                    Ingredient updated successfully.
                </div>
            <?php endif; ?>

            <section class="section inventory-controls">

                <form class="inventory-search-row"
                    action="inventory.php"
                    method="get">

                    <?php if ($category !== ''): ?>
                        <input type="hidden"
                            name="category"
                            value="<?= inventoryEscape($category) ?>">
                    <?php endif; ?>

                    <label class="search-box">
                        <input
                            type="search"
                            name="search"
                            value="<?= inventoryEscape($search) ?>"
                            placeholder="Search ingredients..."
                            aria-label="Search ingredients">
                    </label>
                </form>

                <div class="filter-row">
                    <a class="filter <?= $category === '' ? 'active' : '' ?>"
                        href="<?= inventoryEscape(inventoryFilterUrl('', $search)) ?>">
                        All
                    </a>

                    <?php foreach ($categories as $itemCategory): ?>
                        <a
                            class="filter <?= $category === $itemCategory ? 'active' : '' ?>"
                            href="<?= inventoryEscape(inventoryFilterUrl($itemCategory, $search)) ?>">
                            <?= inventoryEscape($itemCategory) ?>
                        </a>
                    <?php endforeach; ?>
                </div>

            </section>

            <section class="section">
                <details class="add-ingredient-panel"
                    <?= $error !== '' ? 'open' : '' ?>>

                    <summary>
                        <span>+ Add ingredient</span>
                    </summary>

                    <form action="inventory.php" method="post">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden"
                            name="csrf_token"
                            value="<?= inventoryEscape($csrfToken) ?>">

                        <div class="form-group">
                            <label for="ingredientName">Ingredient Name</label>
                            <input
                                type="text"
                                id="ingredientName"
                                name="name"
                                placeholder="e.g. Rice"
                                maxlength="150"
                                required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="ingredientQuantity">Quantity</label>
                                <input
                                    type="number"
                                    id="ingredientQuantity"
                                    name="quantity"
                                    min="0.01"
                                    max="99999999"
                                    step="0.01"
                                    placeholder="500"
                                    required>
                            </div>

                            <div class="form-group">
                                <label for="ingredientUnit">Unit</label>
                                <select id="ingredientUnit" name="unit" required>
                                    <option value="">Select unit</option>
                                    <option value="g">Grams (g)</option>
                                    <option value="kg">Kilograms (kg)</option>
                                    <option value="ml">Milliliters (ml)</option>
                                    <option value="L">Liters (L)</option>
                                    <option value="pcs">Pieces</option>
                                    <option value="cans">Cans</option>
                                    <option value="packs">Packs</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="ingredientCategory">Category</label>
                            <select id="ingredientCategory"
                                name="category"
                                required>
                                <option value="">Select category</option>

                                <?php foreach ($categories as $itemCategory): ?>
                                    <option value="<?= inventoryEscape($itemCategory) ?>">
                                        <?= inventoryEscape($itemCategory) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="ingredientExpiry">Expiration Date</label>
                            <input
                                type="date"
                                id="ingredientExpiry"
                                name="expiry_date">
                        </div>

                        <button type="submit" class="primary-button">
                            Add to Kitchen
                        </button>
                    </form>
                </details>
            </section>

            <section class="section">
                <div class="inventory-list">

                    <?php if (empty($ingredients)): ?>
                        <div class="inventory-empty">
                            <i class="fa-solid fa-basket-shopping"
                                aria-hidden="true"></i>

                            <h3>No ingredients found</h3>

                            <p>
                                Add an ingredient or try a different search
                                or category.
                            </p>
                        </div>
                    <?php else: ?>

                        <?php foreach ($ingredients as $ingredient): ?>
                            <?php
                            [$statusLabel, $statusClass] =
                                ingredientStatus($ingredient['expiry_date']);
                            ?>

                            <article class="ingredient-card">
                                <div class="ingredient-info">
                                    <h4><?= inventoryEscape($ingredient['name']) ?></h4>

                                    <p>
                                        <?= inventoryEscape(
                                            rtrim(rtrim(
                                                number_format(
                                                    (float) $ingredient['quantity'],
                                                    2,
                                                    '.',
                                                    ''
                                                ),
                                                '0'
                                            ), '.')
                                        ) ?>
                                        <?= inventoryEscape($ingredient['unit']) ?>
                                        · <?= inventoryEscape($ingredient['category']) ?>
                                    </p>

                                    <?php if (!empty($ingredient['expiry_date'])): ?>
                                        <p class="ingredient-expiry">
                                            Expires:
                                            <?= inventoryEscape(
                                                date('M j, Y', strtotime($ingredient['expiry_date']))
                                            ) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <span class="ingredient-status <?= inventoryEscape($statusClass) ?>">
                                    <?= inventoryEscape($statusLabel) ?>
                                </span>

                                <div class="ingredient-actions">
                                    <button type="button" class="ingredient-action edit-action"
                                        aria-label="Edit <?= inventoryEscape($ingredient['name']) ?>"
                                        title="Edit ingredient"
                                        onclick="toggleIngredientEdit(<?= (int)$ingredient['id'] ?>)">
                                        <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                    </button>
                                    <form class="delete-ingredient-form" action="inventory.php" method="post"
                                        onsubmit="return confirm('Delete this ingredient?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="csrf_token" value="<?= inventoryEscape($csrfToken) ?>">
                                        <input type="hidden" name="ingredient_id" value="<?= (int)$ingredient['id'] ?>">
                                        <button type="submit" class="ingredient-action delete-action"
                                            aria-label="Delete <?= inventoryEscape($ingredient['name']) ?>"
                                            title="Delete ingredient">
                                            <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                                <div class="ingredient-edit-panel" id="edit-ingredient-<?= (int)$ingredient['id'] ?>" hidden>
                                    <form action="inventory.php" method="post" class="ingredient-edit-form">
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="csrf_token" value="<?= inventoryEscape($csrfToken) ?>">
                                        <input type="hidden" name="ingredient_id" value="<?= (int)$ingredient['id'] ?>">
                                        <div class="form-group">
                                            <label for="edit-name-<?= (int)$ingredient['id'] ?>">Ingredient name</label>
                                            <input id="edit-name-<?= (int)$ingredient['id'] ?>" name="name" type="text" maxlength="150" required value="<?= inventoryEscape($ingredient['name']) ?>">
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="edit-quantity-<?= (int)$ingredient['id'] ?>">Quantity</label>
                                                <input id="edit-quantity-<?= (int)$ingredient['id'] ?>" name="quantity" type="number" min="0.01" max="99999999" step="0.01" required value="<?= inventoryEscape($ingredient['quantity']) ?>">
                                            </div>
                                            <div class="form-group">
                                                <label for="edit-unit-<?= (int)$ingredient['id'] ?>">Unit</label>
                                                <select id="edit-unit-<?= (int)$ingredient['id'] ?>" name="unit" required>
                                                    <?php foreach (['g' => 'Grams (g)', 'kg' => 'Kilograms (kg)', 'ml' => 'Milliliters (ml)', 'L' => 'Liters (L)', 'pcs' => 'Pieces', 'cans' => 'Cans', 'packs' => 'Packs'] as $unitValue => $unitLabel): ?>
                                                        <option value="<?= inventoryEscape($unitValue) ?>" <?= $ingredient['unit'] === $unitValue ? 'selected' : '' ?>><?= inventoryEscape($unitLabel) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="edit-category-<?= (int)$ingredient['id'] ?>">Category</label>
                                                <select id="edit-category-<?= (int)$ingredient['id'] ?>" name="category" required>
                                                    <?php foreach ($categories as $itemCategory): ?>
                                                        <option value="<?= inventoryEscape($itemCategory) ?>" <?= $ingredient['category'] === $itemCategory ? 'selected' : '' ?>><?= inventoryEscape($itemCategory) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label for="edit-expiry-<?= (int)$ingredient['id'] ?>">Expiration date</label>
                                                <input id="edit-expiry-<?= (int)$ingredient['id'] ?>" name="expiry_date" type="date" value="<?= inventoryEscape($ingredient['expiry_date'] ?? '') ?>">
                                            </div>
                                        </div>
                                        <div class="ingredient-edit-buttons">
                                            <button type="button" class="edit-cancel-button" onclick="toggleIngredientEdit(<?= (int)$ingredient['id'] ?>)">Cancel</button>
                                            <button type="submit" class="edit-save-button"><i class="fa-solid fa-check" aria-hidden="true"></i> Save changes</button>
                                        </div>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>

                    <?php endif; ?>

                </div>
            </section>

        </div>
    </main>

    <?php require_once __DIR__ . '/../includes/navigation.php'; ?>

    <script>
        function toggleIngredientEdit(ingredientId) {
            const panel = document.getElementById('edit-ingredient-' + ingredientId);
            if (!panel) return;

            const willOpen = panel.hidden;

            // Close other open editors so the inventory remains tidy.
            document.querySelectorAll('.ingredient-edit-panel').forEach((item) => {
                item.hidden = true;
            });

            panel.hidden = !willOpen;

            if (willOpen) {
                const firstInput = panel.querySelector('input[name="name"]');
                if (firstInput) firstInput.focus();
                panel.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest'
                });
            }
        }
    </script>
</body>

</html>