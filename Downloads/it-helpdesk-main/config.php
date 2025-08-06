<?php
// C:\xampp\htdocs\it-helpdesk\config.php

// Ensure session is started only once
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// --- Database Connection (PDO) ---
$host = 'localhost';
$db   = 'it_helpdesk';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// --- TRANSLATION FUNCTIONALITY ---

/**
 * Loads translations from a language file.
 * @param string $lang_code The 2-letter language code (e.g., 'en', 'ar').
 * @return array An associative array of translations.
 */
function load_language_file($lang_code)
{
    $file = __DIR__ . '/lang/' . $lang_code . '.php';
    if (file_exists($file)) {
        return require $file;
    }
    $english_file = __DIR__ . '/lang/en.php';
    if (file_exists($english_file)) {
        return require $english_file;
    }
    return [];
}

/**
 * Check if a table exists in the database
 * @param PDO $pdo Database connection
 * @param string $tableName Table name to check
 * @return bool True if table exists, false otherwise
 */
function tableExists(PDO $pdo, string $tableName): bool
{
    try {
        $stmt = $pdo->query("SELECT 1 FROM $tableName LIMIT 1");
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Translation function.
 * @param string $key The translation key.
 * @param array $replacements Optional array of placeholder replacements.
 * @return string The translated string, or the key if not found.
 */
function t($key, $replacements = [])
{
    global $_translations;
    if (isset($_translations) && array_key_exists($key, $_translations)) {
        $message = $_translations[$key];
        foreach ($replacements as $placeholder => $value) {
            $message = str_replace(":$placeholder", $value, $message);
        }
        return $message;
    }
    return $key;
}

// Define available languages and their labels
$available_languages = [
    'en' => 'English',
    'am' => 'Amharic',
    'om' => 'Oromo',
    'so' => 'Somali',
    'ti' => 'Tigrinya',
    'ar' => 'العربية'
];

// Set default language or from session
if (!isset($_SESSION['lang']) || !array_key_exists($_SESSION['lang'], $available_languages)) {
    $_SESSION['lang'] = 'en';
}

// Handle language change request
if (isset($_GET['lang']) && array_key_exists($_GET['lang'], $available_languages)) {
    $_SESSION['lang'] = $_GET['lang'];
    $redirectUrl = strtok($_SERVER["REQUEST_URI"], '?');
    header("Location: " . $redirectUrl);
    exit();
}

// Load current language translations
$_translations = load_language_file($_SESSION['lang']);

// --- User Authentication & Authorization Functions ---

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function isAdmin()
{
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

function isSeniorOfficer()
{
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'senior_officer';
}

function isSubmitter()
{
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'submitter';
}

function canManageTicket()
{
    if (!isLoggedIn()) {
        return false;
    }
    $allowedRoles = [
        'admin',
        'senior_officer',
        'software_specialist',
        'hardware_specialist',
        'network_specialist',
        'database_specialist',
        'security_specialist',
        'support_specialist'
    ];
    return in_array(strtolower($_SESSION['role'] ?? ''), $allowedRoles);
}

function getThemeClass()
{
    if (!isset($_SESSION['theme'])) {
        $_SESSION['theme'] = 'light';
    }
    return $_SESSION['theme'] === 'dark' ? 'dark-theme' : '';
}

function setTheme($theme)
{
    if ($theme === 'dark' || $theme === 'light') {
        $_SESSION['theme'] = $theme;
        return true;
    }
    return false;
}

if (isset($_GET['theme'])) {
    setTheme($_GET['theme']);
    $redirectUrl = strtok($_SERVER["REQUEST_URI"], '?');
    header("Location: " . $redirectUrl);
    exit();
}

// --- HTML Attributes for Language Direction ---

function getHtmlLangAttribute()
{
    return $_SESSION['lang'] ?? 'en';
}

function getHtmlDirAttribute()
{
    return (($_SESSION['lang'] ?? 'en') == 'ar') ? 'rtl' : 'ltr';
}

// --- Helper Functions (General) ---

function redirect($url)
{
    header("Location: " . $url);
    exit();
}

function setFlashMessage($type, $message)
{
    $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
}

function getFlashMessage()
{
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return '<div class="alert alert-' . htmlspecialchars($message['type']) . ' alert-dismissible fade show" role="alert">' .
            htmlspecialchars($message['message']) .
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
    return '';
}

function verifyCsrfToken(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function getFileIcon($mimeType)
{
    $iconMap = [
        'image/jpeg' => 'image',
        'image/png' => 'image',
        'image/gif' => 'image',
        'application/pdf' => 'file-pdf',
        'application/msword' => 'file-word',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'file-word',
        'application/vnd.ms-excel' => 'file-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'file-excel',
        'text/plain' => 'file-alt'
    ];
    return $iconMap[$mimeType] ?? 'file';
}

/**
 * Gets color for file type icon
 */
function getFileColor($mimeType)
{
    $colorMap = [
        'image/jpeg' => '#4CAF50',
        'image/png' => '#2196F3',
        'image/gif' => '#FF9800',
        'application/pdf' => '#F44336',
        'application/msword' => '#2196F3',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => '#2196F3',
        'application/vnd.ms-excel' => '#4CAF50',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => '#4CAF50',
        'text/plain' => '#9E9E9E'
    ];
    return $colorMap[$mimeType] ?? '#607D8B';
}

/**
 * Formats file size in human readable format
 */
function formatFileSize($bytes)
{
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' bytes';
    } elseif ($bytes == 1) {
        return '1 byte';
    }
    return '0 bytes';
}

/**
 * Formats date in a user-friendly way
 */
function formatDate($dateString)
{
    $date = new DateTime($dateString);
    return $date->format('M j, Y \a\t g:i A');
}

// Add to config.php (best placed near other security functions)

/**
 * Generates and stores a CSRF token in session
 */
function generateCsrfToken(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates a CSRF token securely
 */
function validateCsrfToken(?string $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    return isset($_SESSION['csrf_token'], $token) &&
        hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Regenerates CSRF token (use after important actions)
 */
function regenerateCsrfToken(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    unset($_SESSION['csrf_token']);
    generateCsrfToken();
}
