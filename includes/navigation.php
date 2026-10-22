<?php
$currentPage = strtolower(
    pathinfo($_SERVER['SCRIPT_NAME'] ?? '', PATHINFO_FILENAME)
);

$navItems = [
    'home'      => ['Home', 'fa-house'],
    'inventory' => ['Inventory', 'fa-boxes-stacked'],
    'generate'  => ['GENERATE', 'fa-wand-magic-sparkles'],
    'health'    => ['Health', 'fa-heart-pulse'],
    'profile'   => ['Profile', 'fa-user'],
];
?>

<nav class="bottom-nav" aria-label="Main navigation">

    <?php foreach ($navItems as $key => [$label, $icon]): ?>

        <a
            href="../pages/<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>.php"
            class="nav-item <?= $currentPage === $key ? 'active' : '' ?> <?= $key === 'generate' ? 'ai-nav' : '' ?>"
            aria-label="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>"
            <?= $currentPage === $key ? 'aria-current="page"' : '' ?>>

            <i
                class="fa-solid <?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>"
                aria-hidden="true"></i>

            <strong>
                <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
            </strong>

        </a>

    <?php endforeach; ?>

</nav>