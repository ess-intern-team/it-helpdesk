<?php
// includes/lang.php
// This file should be included at the very top of any page that needs translations.

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define available languages and their friendly names
$available_languages = [
    'en' => 'English',
    'am' => 'Amharic',
    'ar' => 'Arabic',
    'aa' => 'Afar',    // ISO 639-1 code for Afar
    'om' => 'Oromo',   // ISO 639-1 code for Oromo
    'ti' => 'Tigrinya', // ISO 639-1 code for Tigrinya
    'zh' => 'Chinese', // Simplified Chinese (can use 'zh-hans' if you differentiate)
];

// Determine current language
if (isset($_GET['lang']) && array_key_exists($_GET['lang'], $available_languages)) {
    $_SESSION['lang'] = $_GET['lang'];
    // Redirect to clean the URL after setting language (optional, but good for clean URLs)
    $current_url = strtok($_SERVER["REQUEST_URI"], '?');
    header('Location: ' . $current_url);
    exit();
} elseif (!isset($_SESSION['lang'])) {
    // Try to detect browser language, or set default to 'en'
    $browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '', 0, 2); // Null coalescing operator for PHP 7+
    if (array_key_exists($browser_lang, $available_languages)) {
        $_SESSION['lang'] = $browser_lang;
    } else {
        $_SESSION['lang'] = 'en'; // Default language if no preference or not supported
    }
}

$current_lang = $_SESSION['lang'];

// Load translations from the appropriate language file
$translations = [];
// Use __DIR__ to ensure the path is relative to the current file
$lang_file_path = __DIR__ . '/../languages/' . $current_lang . '.php';

if (file_exists($lang_file_path)) {
    $translations = include $lang_file_path;
} else {
    // Fallback to English if the specific language file is not found
    $translations = include __DIR__ . '/../languages/en.php';
    error_log("Language file not found: " . $lang_file_path); // Log if a language file is missing
}

// Translation function: looks up the key in the loaded translations
function __($key)
{
    global $translations;
    return $translations[$key] ?? $key; // Return translation if exists, else the key itself
}
