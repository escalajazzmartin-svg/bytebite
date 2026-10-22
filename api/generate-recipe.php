<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| BYTEBITE — GEMINI RECIPE GENERATION API
|--------------------------------------------------------------------------
| Method: POST
| Content-Type: application/json
|
| Required JSON:
| {
|   "ingredients": ["Chicken", "Broccoli", "Rice"],
|   "meal_type": "dinner",
|   "diet_preference": "none",
|   "cooking_time": "30",
|   "servings": 2,
|   "appliances": ["pan"]
| }
|--------------------------------------------------------------------------
*/

header('Content-Type: application/json; charset=utf-8');

function sendJson(int $status, array $data): never
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| 1. Validate HTTP method
|--------------------------------------------------------------------------
*/

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');

    sendJson(405, [
        'success' => false,
        'message' => 'POST requests only.'
    ]);
}

/*
|--------------------------------------------------------------------------
| 2. Load Gemini configuration
|--------------------------------------------------------------------------
*/

$configPath = __DIR__ . '/../config/gemini.php';

if (!is_file($configPath)) {
    error_log('ByteBite: Gemini configuration file is missing.');

    sendJson(500, [
        'success' => false,
        'message' => 'Gemini configuration is missing.'
    ]);
}

$config = require $configPath;

if (!is_array($config)) {
    sendJson(500, [
        'success' => false,
        'message' => 'Invalid Gemini configuration.'
    ]);
}

$apiKey = trim((string)($config['api_key'] ?? ''));

/*
| Use a model that is available to your Google AI Studio project.
| Set this in config/gemini.php.
*/

$model = trim((string)($config['model'] ?? ''));

if (
    $apiKey === '' ||
    $apiKey === 'PASTE_YOUR_GEMINI_API_KEY_HERE'
) {
    sendJson(500, [
        'success' => false,
        'message' => 'Gemini API key is not configured.'
    ]);
}

if ($model === '') {
    sendJson(500, [
        'success' => false,
        'message' => 'Gemini model is not configured.'
    ]);
}

/*
|--------------------------------------------------------------------------
| 3. Check cURL availability
|--------------------------------------------------------------------------
*/

if (!function_exists('curl_init')) {
    error_log('ByteBite: PHP cURL extension is not enabled.');

    sendJson(500, [
        'success' => false,
        'message' => 'PHP cURL is not enabled. Enable it in XAMPP.'
    ]);
}

/*
|--------------------------------------------------------------------------
| 4. Read JSON request
|--------------------------------------------------------------------------
*/

$rawInput = file_get_contents('php://input');

$input = json_decode(
    $rawInput !== false ? $rawInput : '',
    true
);

if (!is_array($input)) {
    sendJson(400, [
        'success' => false,
        'message' => 'Invalid JSON request.'
    ]);
}

/*
|--------------------------------------------------------------------------
| 5. Read and validate preferences
|--------------------------------------------------------------------------
*/

$ingredients = $input['ingredients'] ?? [];

$allowedMeals = [
    'any',
    'breakfast',
    'lunch',
    'dinner',
    'snack'
];

$allowedDiets = [
    'none',
    'vegetarian',
    'high-protein',
    'low-carb',
    'low-calorie'
];

$allowedCookingTimes = [
    '15',
    '30',
    '60',
    'any'
];

$allowedAppliances = [
    'pan',
    'oven',
    'air-fryer'
];

$mealType = $input['meal_type'] ?? 'any';
$diet = $input['diet_preference'] ?? 'none';
$cookingTime = (string)($input['cooking_time'] ?? '30');

$servings = filter_var(
    $input['servings'] ?? 2,
    FILTER_VALIDATE_INT
);

if (
    !is_string($mealType) ||
    !is_string($diet) ||
    !in_array($mealType, $allowedMeals, true) ||
    !in_array($diet, $allowedDiets, true) ||
    !in_array($cookingTime, $allowedCookingTimes, true) ||
    $servings === false ||
    $servings < 1 ||
    $servings > 6
) {
    sendJson(422, [
        'success' => false,
        'message' => 'Invalid recipe preferences.'
    ]);
}

/*
|--------------------------------------------------------------------------
| 6. Sanitize ingredients
|--------------------------------------------------------------------------
*/

if (!is_array($ingredients)) {
    sendJson(422, [
        'success' => false,
        'message' => 'Ingredients must be provided as an array.'
    ]);
}

$cleanIngredients = [];

foreach (array_slice($ingredients, 0, 30) as $ingredient) {
    if (!is_string($ingredient)) {
        continue;
    }

    $ingredient = trim($ingredient);

    if ($ingredient === '') {
        continue;
    }

    $cleanIngredients[] = mb_substr(
        $ingredient,
        0,
        100,
        'UTF-8'
    );
}

$ingredients = array_values(array_unique($cleanIngredients));

if (count($ingredients) === 0) {
    sendJson(422, [
        'success' => false,
        'message' => 'Select at least one valid ingredient.'
    ]);
}

/*
|--------------------------------------------------------------------------
| 7. Validate cooking appliances
|--------------------------------------------------------------------------
*/

$appliances = $input['appliances'] ?? [];

if (!is_array($appliances)) {
    $appliances = [];
}

$appliances = array_values(
    array_unique(
        array_intersect(
            $allowedAppliances,
            $appliances
        )
    )
);

/*
|--------------------------------------------------------------------------
| 8. Build Gemini prompt
|--------------------------------------------------------------------------
*/

$ingredientsJson = json_encode(
    $ingredients,
    JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
);

$appliancesJson = json_encode(
    $appliances,
    JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
);

$prompt = <<<PROMPT
You are ByteBite, a practical recipe-generation assistant.

Generate exactly 3 different recipes using the user's available ingredients.

USER PREFERENCES
Available ingredients: {$ingredientsJson}
Meal type: {$mealType}
Dietary preference: {$diet}
Maximum cooking time: {$cookingTime} minutes
Servings: {$servings}
Available appliances: {$appliancesJson}

REQUIREMENTS
1. Return valid JSON only. Do not use Markdown fences.
2. Return a top-level object containing a "recipes" array.
3. Generate 3 different recipes when feasible.
4. Prefer the user's listed ingredients.
5. Mark additional pantry staples as optional.
6. Respect the selected dietary preference and appliances.
7. Do not exceed the cooking-time limit when it is numeric.
8. Use realistic ingredient quantities and clear cooking steps.
9. Nutrition values must be estimates, not exact medical advice.
10. Do not invent nutrition precision or guarantee allergy safety.
11. Each recipe must contain all fields shown in the schema.

REQUIRED JSON STRUCTURE
{
  "recipes": [
    {
      "title": "Recipe name",
      "description": "Short description",
      "meal_type": "dinner",
      "cooking_time_minutes": 20,
      "servings": 2,
      "ingredients": [
        {
          "name": "Chicken",
          "quantity": 200,
          "unit": "g",
          "optional": false
        }
      ],
      "instructions": [
        "Prepare the ingredients.",
        "Cook until done."
      ],
      "nutrition": {
        "calories_kcal": 300,
        "protein_g": 20,
        "carbs_g": 30,
        "fats_g": 10
      }
    }
  ]
}
PROMPT;

/*
|--------------------------------------------------------------------------
| 9. Build Gemini API request
|--------------------------------------------------------------------------
*/

$url = 'https://generativelanguage.googleapis.com/v1beta/models/'
    . rawurlencode($model)
    . ':generateContent';

$payload = [
    'contents' => [
        [
            'parts' => [
                [
                    'text' => $prompt
                ]
            ]
        ]
    ],
    'generationConfig' => [
        'responseMimeType' => 'application/json',
        'temperature' => 0.7
    ]
];

$requestBody = json_encode(
    $payload,
    JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
);

if ($requestBody === false) {
    sendJson(500, [
        'success' => false,
        'message' => 'Could not prepare the recipe request.'
    ]);
}

/*
|--------------------------------------------------------------------------
| 10. Send request to Gemini
|--------------------------------------------------------------------------
*/

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 60,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'x-goog-api-key: ' . $apiKey
    ],
    CURLOPT_POSTFIELDS => $requestBody
]);

$response = curl_exec($ch);
$status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

curl_close($ch);

if ($response === false) {
    error_log('ByteBite Gemini connection error: ' . $curlError);

    sendJson(502, [
        'success' => false,
        'message' => 'Could not connect to Gemini. Please try again.'
    ]);
}

/*
|--------------------------------------------------------------------------
| 11. Decode Gemini response and handle API errors
|--------------------------------------------------------------------------
*/

$result = json_decode($response, true);

if (!is_array($result)) {
    error_log(
        'ByteBite: Gemini returned invalid JSON. HTTP status: '
            . $status
    );

    sendJson(502, [
        'success' => false,
        'message' => 'Gemini returned an invalid response.'
    ]);
}

if ($status < 200 || $status >= 300) {
    // Log only the error message and status; never log the API key.
    $apiError = $result['error']['message'] ?? 'Unknown API error';

    error_log(
        'ByteBite Gemini API error. HTTP '
            . $status
            . ': '
            . substr((string)$apiError, 0, 500)
    );

    sendJson(502, [
        'success' => false,
        'message' => 'Gemini recipe generation failed. Check your API key, model, quota, and server configuration.'
    ]);
}

/*
|--------------------------------------------------------------------------
| 12. Extract generated recipe text
|--------------------------------------------------------------------------
*/

$text = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

if (!is_string($text) || trim($text) === '') {
    error_log('ByteBite: Gemini returned no recipe text.');

    sendJson(502, [
        'success' => false,
        'message' => 'Gemini returned an empty response. Please try again.'
    ]);
}

/*
|--------------------------------------------------------------------------
| 13. Validate recipe JSON
|--------------------------------------------------------------------------
*/

$recipeData = json_decode($text, true);

if (
    !is_array($recipeData) ||
    !isset($recipeData['recipes']) ||
    !is_array($recipeData['recipes']) ||
    count($recipeData['recipes']) === 0
) {
    error_log('ByteBite: Gemini recipe output failed JSON validation.');

    sendJson(502, [
        'success' => false,
        'message' => 'Gemini returned an invalid recipe format. Please try again.'
    ]);
}

/*
|--------------------------------------------------------------------------
| 14. Validate individual recipe fields
|--------------------------------------------------------------------------
*/

$validatedRecipes = [];

foreach ($recipeData['recipes'] as $recipe) {
    if (!is_array($recipe)) {
        continue;
    }

    if (
        !isset(
            $recipe['title'],
            $recipe['ingredients'],
            $recipe['instructions'],
            $recipe['nutrition']
        ) ||
        !is_string($recipe['title']) ||
        !is_array($recipe['ingredients']) ||
        !is_array($recipe['instructions']) ||
        !is_array($recipe['nutrition'])
    ) {
        continue;
    }

    $validatedRecipes[] = $recipe;
}

if (count($validatedRecipes) === 0) {
    sendJson(502, [
        'success' => false,
        'message' => 'No valid recipes were returned. Please try again.'
    ]);
}

/*
|--------------------------------------------------------------------------
| 15. Return recipes to the frontend
|--------------------------------------------------------------------------
*/

sendJson(200, [
    'success' => true,
    'data' => [
        'recipes' => $validatedRecipes
    ]
]);
