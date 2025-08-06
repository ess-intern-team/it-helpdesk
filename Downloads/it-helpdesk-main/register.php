<?php

require_once 'config.php'; // Contains database connection ($pdo), isLoggedIn(), validateCsrfToken(), generateCsrfToken(), t(), getThemeClass()

// Redirect logged-in users to the dashboard
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

// Initialize variables for form data and error messages
$name = $email = $password = $confirm_password = $role = '';
$team_id = null; // Initialize team_id as null
$name_err = $email_err = $password_err = $confirm_password_err = $role_err = $team_id_err = '';
$success_message = '';
$general_error = ''; // For general errors like CSRF token issues or database errors
$redirect_to_login_after_delay = false; // Flag to trigger delayed redirect

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF Token Validation
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) { // Added null coalescing for robustness
        $general_error = t('invalid_csrf'); // Use t()
    } else {
        // Sanitize and validate Name
        if (empty(trim($_POST['name']))) {
            $name_err = t('please_enter_full_name'); // Use t()
        } else {
            $name = htmlspecialchars(trim($_POST['name']));
        }

        // Sanitize and validate Email
        if (empty(trim($_POST['email']))) {
            $email_err = t('please_enter_email'); // Use t()
        } else {
            $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $email_err = t('invalid_email_format'); // Use t()
            } else {
                // Check if email already exists using PDO
                try {
                    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email");
                    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                    $stmt->execute();

                    if ($stmt->rowCount() > 0) { // Check if any rows were returned
                        // Email exists, show message and set flag for delayed redirect
                        $email_err = t('Email Exists Please Login'); // Use t()
                        $redirect_to_login_after_delay = true; // Set flag
                    }
                } catch (PDOException $e) {
                    $general_error = t('database_error') . ": " . $e->getMessage(); // Use t()
                }
            }
        }

        // Validate Password
        if (empty(trim($_POST['password']))) {
            $password_err = t('please_enter_password'); // Use t()
        } elseif (strlen(trim($_POST['password'])) < 6) {
            $password_err = t('password_min_length'); // Use t()
        } else {
            $password = trim($_POST['password']);
        }

        // Validate Confirm Password
        if (empty(trim($_POST['confirm_password']))) {
            $confirm_password_err = t('please_confirm_password'); // Use t()
        } else {
            $confirm_password = trim($_POST['confirm_password']);
            if (empty($password_err) && ($password !== $confirm_password)) {
                $confirm_password_err = t('password_mismatch'); // Use t()
            }
        }

        // Validate Role
        $allowed_roles = ['submitter', 'senior_officer']; // Define allowed roles
        if (empty($_POST['role']) || !in_array($_POST['role'], $allowed_roles)) {
            $role_err = t('please_select_role'); // Use t()
        } else {
            $role = $_POST['role'];
        }

        // Validate Team ID if role is senior_officer
        if ($role === 'senior_officer') {
            if (empty($_POST['team_id'])) {
                $team_id_err = t('please_select_team'); // Use t()
            } else {
                $team_id = (int)$_POST['team_id'];
                // Optionally, verify if team_id exists in the 'teams' table using PDO
                try {
                    $stmt_team = $pdo->prepare("SELECT team_id FROM teams WHERE team_id = :team_id");
                    $stmt_team->bindParam(':team_id', $team_id, PDO::PARAM_INT);
                    $stmt_team->execute();

                    if ($stmt_team->rowCount() === 0) {
                        $team_id_err = t('invalid_team_selected'); // Use t()
                    }
                } catch (PDOException $e) {
                    $general_error = t('database_error') . ": " . $e->getMessage(); // Use t()
                }
            }
        } else {
            $team_id = null; // Ensure team_id is null for non-senior_officer roles
        }

        // If no validation errors (and not a delayed redirect), proceed with user registration
        if (empty($name_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($role_err) && empty($team_id_err) && empty($general_error) && !$redirect_to_login_after_delay) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $default_avatar_path = 'assets/default_avatar.png'; // Set default avatar path

            try {
                // Insert user into database using PDO
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, team_id, avatar_path) VALUES (:name, :email, :password, :role, :team_id, :avatar_path)");
                $stmt->bindParam(':name', $name, PDO::PARAM_STR);
                $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
                $stmt->bindParam(':role', $role, PDO::PARAM_STR);
                // For NULL values with PDO, you must explicitly bind them as PDO::PARAM_NULL
                if ($team_id === null) {
                    $stmt->bindValue(':team_id', null, PDO::PARAM_NULL);
                } else {
                    $stmt->bindParam(':team_id', $team_id, PDO::PARAM_INT);
                }
                $stmt->bindParam(':avatar_path', $default_avatar_path, PDO::PARAM_STR);

                if ($stmt->execute()) {
                    $success_message = t('registration_success'); // Use t()
                    // Clear form fields after successful registration
                    $name = $email = $password = $confirm_password = $role = '';
                    $team_id = null;
                } else {
                    $general_error = t('registration_failed'); // Use t()
                }
            } catch (PDOException $e) {
                $general_error = t('database_error') . ": " . $e->getMessage(); // Use t()
            }
        }
    }
}

// Fetch teams for the dropdown using PDO
$teams = [];
try {
    $stmt_teams = $pdo->query("SELECT team_id, team_name FROM teams ORDER BY team_name ASC");
    $teams = $stmt_teams->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $general_error = t('error_fetching_teams') . ": " . $e->getMessage(); // Use t()
}

// Generate CSRF token for the form
$csrf_token = generateCsrfToken();
// Generate a new CSRF token for the form
?>

<!DOCTYPE html>
<html lang="<?php echo getHtmlLangAttribute(); ?>" dir="<?php echo getHtmlDirAttribute(); ?>" class="<?php echo getThemeClass(); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('register'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card p-4 shadow-sm" style="max-width: 450px; width: 100%;">
            <h2 class="card-title text-center mb-4"><?php echo t('New User Registration'); ?></h2>

            <?php if ($general_error) : ?>
                <div class="alert alert-danger"><?php echo $general_error; ?></div>
            <?php endif; ?>

            <?php if ($success_message) : ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <?php if ($email_err && $redirect_to_login_after_delay) : ?>
                <div id="redirectMessage" class="alert alert-warning"><?php echo $email_err; ?></div>
            <?php elseif ($email_err) : // Display if email_err exists but no redirect is needed (e.g., invalid format)
            ?>
                <div class="alert alert-danger"><?php echo $email_err; ?></div>
            <?php endif; ?>


            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                <div class="mb-3">
                    <label for="name" class="form-label"><?php echo t('Full Name'); ?></label>
                    <input type="text" name="name" id="name" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($name); ?>" required>
                    <div class="invalid-feedback"><?php echo $name_err; ?></div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label"><?php echo t('Email'); ?></label>
                    <input type="email" name="email" id="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($email); ?>" required>
                    <div class="invalid-feedback"><?php echo $email_err; ?></div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label"><?php echo t('Password'); ?></label>
                    <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" required minlength="6">
                    <div class="invalid-feedback"><?php echo $password_err; ?></div>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label"><?php echo t('Confirm Password'); ?></label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" required>
                    <div class="invalid-feedback"><?php echo $confirm_password_err; ?></div>
                </div>

                <div class="mb-3">
                    <label for="role" class="form-label"><?php echo t('Role'); ?></label>
                    <select name="role" id="role" class="form-select <?php echo (!empty($role_err)) ? 'is-invalid' : ''; ?>" required>
                        <option value=""><?php echo t('Select Role'); ?></option>
                        <option value="submitter" <?php echo ($role === 'submitter' ? 'selected' : ''); ?>><?php echo t('Submitter'); ?></option>
                        <option value="senior_officer" <?php echo ($role === 'senior_officer' ? 'selected' : ''); ?>><?php echo t('Senior Officer'); ?></option>
                    </select>
                    <div class="invalid-feedback"><?php echo $role_err; ?></div>
                </div>

                <div class="mb-3" id="team_id_container" style="display: <?php echo ($role === 'senior_officer' ? 'block' : 'none'); ?>;">
                    <label for="team_id" class="form-label"><?php echo t('Team For Officers'); ?></label>
                    <select name="team_id" id="team_id" class="form-select <?php echo (!empty($team_id_err)) ? 'is-invalid' : ''; ?>">
                        <option value=""><?php echo t('Select Team'); ?></option>
                        <?php foreach ($teams as $team_option) : ?>
                            <option value="<?php echo htmlspecialchars($team_option['team_id']); ?>" <?php echo ($team_option['team_id'] == $team_id ? 'selected' : ''); ?>>
                                <?php echo htmlspecialchars($team_option['team_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback"><?php echo $team_id_err; ?></div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-success"><?php echo t('register'); ?></button>
                </div>
                <p class="mt-3 text-center"><?php echo t('Already Have Account'); ?> <a href="login.php"><?php echo t('Click Here To Login'); ?></a></p>
            </form>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Bootstrap form validation
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

        // Show/hide team_id based on role and set required attribute
        document.getElementById('role').addEventListener('change', function() {
            const teamContainer = document.getElementById('team_id_container');
            const teamSelect = document.getElementById('team_id');
            if (this.value === 'senior_officer') {
                teamContainer.style.display = 'block';
                teamSelect.setAttribute('required', 'required'); // Make required for senior officer
            } else {
                teamContainer.style.display = 'none';
                teamSelect.removeAttribute('required'); // Remove required for other roles
                teamSelect.value = ""; // Clear selected team when role changes
                teamSelect.classList.remove('is-invalid'); // Clear validation state
            }
        });

        // Initialize display state of team_id_container on page load
        document.addEventListener('DOMContentLoaded', function() {
            const roleSelect = document.getElementById('role');
            const teamContainer = document.getElementById('team_id_container');
            const teamSelect = document.getElementById('team_id');

            if (roleSelect.value === 'senior_officer') {
                teamContainer.style.display = 'block';
                teamSelect.setAttribute('required', 'required');
            } else {
                teamContainer.style.display = 'none';
                teamSelect.removeAttribute('required');
            }

            // --- NEW: Delayed redirect logic ---
            const redirectMessage = document.getElementById('redirectMessage');
            if (redirectMessage) {
                setTimeout(function() {
                    window.location.href = 'login.php';
                }, 3000); // Redirect after 3 seconds (3000 milliseconds)
            }
            // --- END NEW ---
        });
    </script>
</body>

</html>