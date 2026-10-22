<?php
require_once __DIR__ . '/../includes/auth.php';
?>

<?php
$page = 'profile';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="Personalize your ByteBite cooking experience and dietary preferences.">
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

    <?php require_once '../includes/header.php'; ?>

    <main class="page active-page profile-page">
        <div class="page-container">

            <section class="profile-heading">
                <h1>Your Profile</h1>
                <p>Personalize your cooking experience</p>
            </section>

            <form class="profile-form" id="profileForm">

                <!-- ALLERGIES -->
                <div class="profile-field">
                    <label for="allergies">
                        Allergies &amp; intolerances (strict exclusions, comma separated)
                    </label>

                    <input
                        type="text"
                        id="allergies"
                        name="allergies"
                        autocomplete="off"
                        aria-describedby="allergyHelp">

                    <small id="allergyHelp" class="field-help">
                        Separate each allergy or intolerance with a comma.
                    </small>
                </div>

                <!-- DIETARY PATTERN -->
                <div class="profile-field">
                    <label for="dietaryPattern">Dietary pattern</label>

                    <input
                        type="text"
                        id="dietaryPattern"
                        name="dietary_pattern"
                        placeholder="e.g. vegetarian, gluten-free"
                        autocomplete="off">
                </div>

                <!-- CALORIE TARGET -->
                <div class="profile-field">
                    <label for="calorieTarget">Daily calorie target</label>

                    <input
                        type="number"
                        id="calorieTarget"
                        name="calorie_target"
                        min="1"
                        max="10000"
                        step="1"
                        inputmode="numeric">
                </div>

                <!-- SAFETY NOTICE -->
                <aside class="profile-notice" role="note">
                    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>

                    <p>
                        ByteBite never promises a recipe is allergen-free.
                        Always check labels for cross-contact.
                    </p>
                </aside>

                <!-- FORM ACTIONS -->
                <div class="profile-actions">
                    <button type="submit" class="profile-save-button">
                        <i class="fa-solid fa-check" aria-hidden="true"></i>
                        Save Profile
                    </button>

                    <button type="button" class="profile-reset-button" id="resetProfile">
                        Reset demo data
                    </button>
                </div>

                <p class="profile-message" id="profileMessage" role="status" aria-live="polite"></p>

            </form>

        </div>
    </main>

    <?php require_once '../includes/navigation.php'; ?>

    <script src="../js/profile.js"></script>

</body>

</html>