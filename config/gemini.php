<?php

$localConfig = __DIR__ . '/gemini.local.php';

if (!is_file($localConfig)) {
    throw new RuntimeException(
        'Missing config/gemini.local.php'
    );
}

$config = require $localConfig;

if (empty($config['api_key']) || empty($config['model'])) {
    throw new RuntimeException(
        'Gemini API key or model is missing.'
    );
}

return $config;
