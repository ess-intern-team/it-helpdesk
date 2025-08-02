/**
* Check if the current user's password needs to be changed
*/
function is_password_expired($user_id) {
global $pdo;

$stmt = $pdo->prepare("SELECT last_password_change FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$last_change = $stmt->fetchColumn();

if (!$last_change) {
return true; // Never changed
}

// Password expires after 90 days
$expiry_date = new DateTime($last_change);
$expiry_date->add(new DateInterval('P90D'));

return (new DateTime()) > $expiry_date;
}

/**
* Generate a password reset token
*/
function generate_password_reset_token($email) {
global $pdo;

// Check if user exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
return false;
}

// Generate token
$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

// Store token in database
$stmt = $pdo->prepare("UPDATE users SET password_reset_token = ?, password_reset_expires = ? WHERE id = ?");
$stmt->execute([$token, $expires, $user['id']]);

return $token;
}

/**
* Validate password reset token
*/
function validate_password_reset_token($token) {
global $pdo;

$stmt = $pdo->prepare("SELECT id FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW()");
$stmt->execute([$token]);
return $stmt->fetch();
}

/**
* Reset password using token
*/
function reset_password_with_token($token, $new_password) {
global $pdo;

// Validate token
$stmt = $pdo->prepare("SELECT id FROM users WHERE password_reset_token = ? AND password_reset_expires > NOW()");
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
return false;
}

// Update password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password = ?, last_password_change = NOW(), password_reset_token = NULL, password_reset_expires = NULL WHERE id = ?");
$success = $stmt->execute([$hashed_password, $user['id']]);

return $success;
}