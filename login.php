<?php
require_once 'config.php';

// If already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    if (isAdmin() || isSeniorOfficer()) {
        header("Location: dashboard.php");
    } else {
        header("Location: user_dashboard.php");
    }
    exit();
}

$email = $password = '';
$email_err = $password_err = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $email_err = t('invalid_csrf');
    } else {
        // Validate email input
        if (empty(trim($_POST['email']))) {
            $email_err = t('please_enter_email');
        } else {
            $email = trim($_POST['email']);
        }

        // Validate password input
        if (empty(trim($_POST['password']))) {
            $password_err = t('please_enter_password');
        } else {
            $password = trim($_POST['password']);
        }

        // If no input errors, attempt to log in
        if (empty($email_err) && empty($password_err)) {
            try {
                // Prepare a SELECT statement using PDO
                $stmt = $pdo->prepare("SELECT user_id, name, email, password, role, team_id, avatar_path FROM users WHERE email = :email");
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->execute();

                // Fetch the user record
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Check if user exists and password is correct
                if ($user) {
                    if (password_verify($password, $user['password'])) {
                        // Password is valid, set session variables
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['name'] = $user['name'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['team_id'] = $user['team_id'];
                        $_SESSION['avatar_path'] = $user['avatar_path'] ?? 'assets/default_avatar.png';

                        // Regenerate session ID to prevent session fixation attacks
                        session_regenerate_id(true);

                        // Redirect based on user role
                        switch ($user['role']) {
                            case 'admin':
                            case 'senior_officer':
                                header("Location: dashboard.php");
                                break;
                            case 'ithead':
                                header("Location: ithead.php?user=" . urlencode($user['email']));
                                break;
                            case 'specialist':
                                header("Location: specialist.php?user=" . urlencode($user['email']));
                                break;
                            case 'employee':
                                header("Location: employee.php?user=" . urlencode($user['email']));
                                break;
                            default:
                                header("Location: user_dashboard.php");
                        }
                        exit();
                    } else {
                        // Password is not valid
                        $password_err = t('invalid_credentials');
                    }
                } else {
                    // Email not found
                    $email_err = t('invalid_credentials');
                }
            } catch (PDOException $e) {
                // Handle database errors
                error_log("Login error: " . $e->getMessage());
                $email_err = t('database_error');
            }
        }
    }
}

// Generate a new CSRF token for the form
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="<?php echo getHtmlLangAttribute(); ?>" dir="<?php echo getHtmlDirAttribute(); ?>" class="<?php echo getThemeClass(); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('login'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card p-4 shadow-sm" style="max-width: 400px; width: 100%;">
            <h2 class="card-title text-center mb-4"><?php echo t('login to your account'); ?></h2>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="mb-3">
                    <label for="email" class="form-label"><?php echo t('email'); ?></label>
                    <input type="email" name="email" id="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($email); ?>" required>
                    <div class="invalid-feedback"><?php echo $email_err; ?></div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label"><?php echo t('password'); ?></label>
                    <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" required>
                    <div class="invalid-feedback"><?php echo $password_err; ?></div>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary"><?php echo t('login'); ?></button>
                </div>
                <p class="mt-3 text-center"><?php echo t('dont have an account'); ?> <a href="register.php"><?php echo t('click_here_to_register'); ?></a></p>
            </form>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/scripts.js"></script>
</body>

</html>