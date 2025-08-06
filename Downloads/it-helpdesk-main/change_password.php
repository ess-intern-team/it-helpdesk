<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = t('error_token');
    } else {
        $current_password = filter_input(INPUT_POST, 'current_password') ?: '';
        $new_password = filter_input(INPUT_POST, 'new_password') ?: '';
        $confirm_password = filter_input(INPUT_POST, 'confirm_password') ?: '';

        $user_id = $_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password && !empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                $success = t('success');
            } else {
                $error = t('error_match');
            }
        } else {
            $error = t('error_current');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo getHtmlLangAttribute(); ?>" dir="<?php echo getHtmlDirAttribute(); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('Change Password'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .content {
            flex: 1;
        }

        footer {
            position: fixed;
            bottom: 0;
            width: 100%;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <div class="content container mt-5">
        <h2 class="mb-4"><?php echo t('Change Password'); ?></h2>
        <?php if ($error) { ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php } ?>
        <?php if ($success) { ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php } ?>
        <form action="change_password.php" method="POST" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <div class="mb-3">
                <label for="current_password" class="form-label"><?php echo t('Current Password'); ?></label>
                <input type="password" name="current_password" id="current_password" class="form-control" required>
                <div class="invalid-feedback"><?php echo t('Please Provide Your Current Password.'); ?></div>
            </div>
            <div class="mb-3">
                <label for="new_password" class="form-label"><?php echo t('New Password'); ?></label>
                <input type="password" name="new_password" id="new_password" class="form-control" required>
                <div class="invalid-feedback"><?php echo t('Please Provide A New Password.'); ?></div>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label"><?php echo t('Confirm Password'); ?></label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                <div class="invalid-feedback"><?php echo t('Please Confirm Your New Password.'); ?></div>
            </div>
            <button type="submit" class="btn btn-primary"><?php echo t('update'); ?></button>
        </form>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function() {
            'use strict';
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>

</html>