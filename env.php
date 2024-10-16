<?php

function loadEnv($filePath = '.env')
{
    if (!file_exists(__DIR__ . "/$filePath")) {
        $filePath = __DIR__ . "/]$filePath";
    }

    if (!file_exists($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
        }
    }
}

function env($key, $default = null)
{
    global $_ENV;
    return $_ENV[$key] ?? $default;
}
loadEnv();
