
<?php

$localConfig = __DIR__ . '/gemini.local.php';

if (!is_file($localConfig)) {
    return [
        'api_key' => getenv('GEMINI_API_KEY') ?: '',
        'model' => getenv('GEMINI_MODEL') ?: '',
    ];
}

return require $localConfig;
