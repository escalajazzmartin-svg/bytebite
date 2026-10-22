
<?php
$page = $page ?? strtolower(trim($_GET['page'] ?? 'home'));
?>

<header class="app-header">

    <a href="index.php?page=home"
       class="brand"
       aria-label="ByteBite Home">

        <img src="../assets/icons/Logo.png" alt="ByteBite logo">

        <div>
            <h1>Byte<span>Bite</span></h1>
            <p>Smart meals. Better choices.</p>
        </div>
    </a>

    <a href="../logout.php" class="logout-button">
        <i class="fa-solid fa-right-from-bracket"></i>
        <span>Logout</span>
    </a>

</header>
