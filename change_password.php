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
    <title><?php echo t('change_password'); ?></title>
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
        <h2 class="mb-4"><?php echo t('change_password'); ?></h2>
        <?php if ($error) { ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php } ?>
        <?php if ($success) { ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php } ?>
        <form action="change_password.php" method="POST" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <div class="mb-3">
                <label for="current_password" class="form-label"><?php echo t('current_password'); ?></label>
                <input type="password" name="current_password" id="current_password" class="form-control" required>
                <div class="invalid-feedback"><?php echo t('Please provide your current password.'); ?></div>
            </div>
            <div class="mb-3">
                <label for="new_password" class="form-label"><?php echo t('new_password'); ?></label>
                <input type="password" name="new_password" id="new_password" class="form-control" required>
                <div class="invalid-feedback"><?php echo t('Please provide a new password.'); ?></div>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label"><?php echo t('confirm_password'); ?></label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                <div class="invalid-feedback"><?php echo t('Please confirm your new password.'); ?></div>
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