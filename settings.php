<?php
// settings.php
// Ensure session is started and language is loaded
require_once 'includes/lang.php'; // This also handles session_start()
require_once 'includes/config.php'; // Your database connection

// Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = ''; // For displaying success/error messages

// --- Handle Profile Image Upload ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image_upload'])) {
    $uploadDir = 'assets/images/profile_pics/'; // Relative to settings.php
    $file = $_FILES['profile_image_upload'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $unique_filename = md5(uniqid(rand(), true)) . '.' . $file_extension;
        $target_file = $uploadDir . $unique_filename;

        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_extension, $allowed_types)) {
            $message = __('Sorry, only JPG, JPEG, PNG & GIF files are allowed.');
        } elseif ($file['size'] > 5000000) { // 5MB limit
            $message = __('Sorry, your file is too large (max 5MB).');
        } elseif (move_uploaded_file($file['tmp_name'], $target_file)) {
            try {
                // Get old image name to delete it if it's not 'default.jpg'
                $stmt_old = $pdo->prepare("SELECT profile_image FROM users WHERE user_id = ?"); // Assuming user_id is the primary key
                $stmt_old->execute([$user_id]);
                $old_image = $stmt_old->fetchColumn();

                $stmt_update = $pdo->prepare("UPDATE users SET profile_image = ? WHERE user_id = ?");
                if ($stmt_update->execute([$unique_filename, $user_id])) {
                    $message = __('Profile picture updated successfully!');
                    $_SESSION['profile_image'] = $unique_filename; // Update session variable

                    // Delete old image if it's not the default one and exists
                    if ($old_image && $old_image != 'default.jpg' && file_exists($uploadDir . $old_image)) {
                        unlink($uploadDir . $old_image);
                    }
                } else {
                    $message = __('Error updating database.');
                }
            } catch (PDOException $e) {
                $message = __('Database error:') . ' ' . $e->getMessage();
            }
        } else {
            $message = __('Error uploading file to server.');
        }
    } else {
        $message = __('File upload error:') . ' ' . $file['error'];
    }
}

// --- Handle Password Change ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password_submit'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Fetch user's current hashed password from DB
    $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 8) { // Minimum password length
                $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                if ($update_stmt->execute([$hashed_new_password, $user_id])) {
                    $message = __('Password changed successfully!');
                } else {
                    $message = __('Error updating password.');
                }
            } else {
                $message = __('New password must be at least 8 characters long.');
            }
        } else {
            $message = __('New password and confirm password do not match.');
        }
    } else {
        $message = __('Incorrect current password.');
    }
}

// Include header (always include after processing POST to update session and messages)
require_once 'includes/header.php';
?>

<div class="container">
    <h2><?php echo __('Profile Settings'); ?></h2>

    <?php if ($message): ?>
        <p class="status-message" style="color: <?php echo strpos($message, 'successfully') !== false ? 'green' : 'red'; ?>;">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>

    <section class="profile-section" id="photo-section">
        <h3><?php echo __('Change Profile Photo'); ?></h3>
        <div class="profile-avatar-display">
            <img src="assets/images/profile_pics/<?php echo htmlspecialchars($_SESSION['profile_image'] ?? 'default.jpg'); ?>"
                alt="<?php echo __('Current Profile Avatar'); ?>"
                class="current-profile-avatar" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; margin-bottom: 20px;">
        </div>
        <form action="settings.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="profile_image_upload"><?php echo __('Upload New Photo:'); ?></label>
                <input type="file" name="profile_image_upload" id="profile_image_upload" accept="image/*" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary"><?php echo __('Upload Photo'); ?></button>
        </form>
    </section>

    <hr>

    <section class="profile-section" id="password-section">
        <h3><?php echo __('Change Password'); ?></h3>
        <form action="settings.php" method="POST">
            <div class="form-group">
                <label for="current_password"><?php echo __('Current Password'); ?>:</label>
                <input type="password" id="current_password" name="current_password" required class="form-control">
            </div>
            <div class="form-group">
                <label for="new_password"><?php echo __('New Password'); ?>:</label>
                <input type="password" id="new_password" name="new_password" required class="form-control">
            </div>
            <div class="form-group">
                <label for="confirm_password"><?php echo __('Confirm New Password'); ?>:</label>
                <input type="password" id="confirm_password" name="confirm_password" required class="form-control">
            </div>
            <button type="submit" name="change_password_submit" class="btn btn-primary"><?php echo __('Change Password'); ?></button>
        </form>
    </section>

</div>

<?php
require_once 'includes/footer.php';
?>