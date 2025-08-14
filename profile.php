<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'];
$email = $_SESSION['email'];
$role = $_SESSION['role'];
$team_id = $_SESSION['team_id'] ?? null;
$avatar_path = $_SESSION['avatar_path'] ?? 'assets/default_avatar.png';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error_message = t('invalid_csrf');
    } else {
        $new_name = trim($_POST['name']);
        $new_email = trim($_POST['email']);

        $update_fields = [];
        $update_params = [];

        // Update name if changed
        if ($new_name !== $name) {
            $update_fields[] = "name = :name";
            $update_params[':name'] = $new_name;
        }

        // Update email if changed
        if ($new_email !== $email) {
            // Check if new email already exists for another user
            try {
                $check_email_stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email AND user_id != :user_id");
                $check_email_stmt->execute([
                    ':email' => $new_email,
                    ':user_id' => $user_id
                ]);

                if ($check_email_stmt->rowCount() > 0) {
                    $error_message .= t('Email Exists') . "<br>";
                } else {
                    $update_fields[] = "email = :email";
                    $update_params[':email'] = $new_email;
                }
            } catch (PDOException $e) {
                $error_message .= "Database error on email check: " . $e->getMessage() . "<br>";
            }
        }

        // Handle avatar upload
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $file_tmp_name = $_FILES['avatar']['tmp_name'];
            $file_name = basename($_FILES['avatar']['name']);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            $max_file_size = 5 * 1024 * 1024; // 5 MB

            if ($_FILES['avatar']['size'] > $max_file_size) {
                $error_message .= t('file_too_large') . "<br>";
            } elseif (!in_array($file_ext, $allowed_ext)) {
                $error_message .= t('invalid_file_type') . "<br>";
            } else {
                $upload_dir = 'assets/avatars/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $new_avatar_name = $user_id . '_' . uniqid() . '.' . $file_ext;
                $upload_path = $upload_dir . $new_avatar_name;

                if (move_uploaded_file($file_tmp_name, $upload_path)) {
                    // Delete old avatar if it's not the default and exists
                    if ($avatar_path && $avatar_path !== 'assets/default_avatar.png' && file_exists($avatar_path)) {
                        unlink($avatar_path);
                    }
                    $update_fields[] = "avatar_path = :avatar_path";
                    $update_params[':avatar_path'] = $upload_path;
                    $_SESSION['avatar_path'] = $upload_path;
                    $avatar_path = $upload_path;
                } else {
                    $error_message .= t('upload_failed') . "<br>";
                }
            }
        }

        if (empty($error_message)) {
            if (!empty($update_fields)) {
                $update_query = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE user_id = :user_id";
                $update_params[':user_id'] = $user_id;

                try {
                    $stmt = $pdo->prepare($update_query);
                    if ($stmt->execute($update_params)) {
                        $success_message = t('Profile updated success');
                        // Update session variables
                        $_SESSION['name'] = $new_name;
                        $_SESSION['email'] = $new_email;
                    } else {
                        $error_message = t('Failed to update profile');
                    }
                } catch (PDOException $e) {
                    $error_message = "Database error: " . $e->getMessage();
                }
            } else {
                $success_message = t('No changes');
            }
        }
    }
}

// Check referer to hide Back to Dashboard button
$referer = isset($_SERVER['HTTP_REFERER']) ? basename(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH)) : '';
$hide_dashboard_button = in_array($referer, ['admin_register_senior.php', 'contact_senior_officer.php']);
?>

<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(getHtmlLangAttribute()); ?>" dir="<?php echo htmlspecialchars(getHtmlDirAttribute()); ?>" class="<?php echo htmlspecialchars(getThemeClass()); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('profile_management'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous">
    <style>
        .card {
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .dark-theme .card {
            box-shadow: 0 4px 20px rgba(255, 255, 255, 0.1);
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .back-button {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .back-button:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .dark-theme .back-button:hover {
            box-shadow: 0 4px 8px rgba(255, 255, 255, 0.2);
        }

        .avatar-preview {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--card-border);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: transform 0.2s ease;
        }

        .dark-theme .avatar-preview {
            box-shadow: 0 4px 10px rgba(255, 255, 255, 0.2);
        }

        .avatar-preview:hover {
            transform: scale(1.05);
        }
    </style>
</head>

<body>
    <?php include 'includes/custom_header.php'; ?>
    <div class="container mt-5">
        <?php if (!$hide_dashboard_button): ?>
            <div class="mb-3">
                <a href="dashboard.php" class="btn btn-secondary back-button"><?php echo t('Back to Dashboard'); ?></a>
            </div>
        <?php endif; ?>
        <div id="flashMessageContainer">
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
        </div>
        <div class="card p-4">
            <h2 class="mb-4 text-center"><?php echo t('Profile Management'); ?></h2>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                <div class="mb-4 d-flex justify-content-center align-items-center flex-column">
                    <img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="Profile Avatar" class="avatar-preview mb-3">
                    <label for="avatar" class="form-label"><?php echo t('Update Profile Photo'); ?></label>
                    <input type="file" name="avatar" id="avatar" class="form-control w-50 text-center" accept="image/jpeg,image/png,image/gif">
                </div>
                <div class="mb-3">
                    <label for="name" class="form-label"><?php echo t('Full Name'); ?></label>
                    <input type="text" name="name" id="name" class="form-control" value="<?php echo htmlspecialchars($name); ?>" required>
                    <div class="invalid-feedback"><?php echo t('Please provide your name.'); ?></div>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label"><?php echo t('email'); ?></label>
                    <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
                    <div class="invalid-feedback"><?php echo t('Please provide a valid email.'); ?></div>
                </div>
                <div class="mb-3">
                    <label for="role" class="form-label"><?php echo t('role'); ?></label>
                    <input type="text" id="role" class="form-control" value="<?php echo htmlspecialchars($role); ?>" disabled>
                </div>
                <?php if ($team_id): ?>
                    <div class="mb-3">
                        <label for="teams" class="form-label"><?php echo t('teams'); ?></label>
                        <input type="text" id="team" class="form-control" value="Team ID: <?php echo htmlspecialchars($team_id); ?>" disabled>
                    </div>
                <?php endif; ?>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary"><?php echo t('Update Profile'); ?></button>
                </div>
            </form>
        </div>
    </div>
    <footer class="my-footer">
        <p>&copy; <?php echo date('Y'); ?> Your Ethiopian Statistical Service. All rights reserved.</p>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="js/script.js" defer></script>
</body>

</html>