<?php
require_once 'config.php'; // Include config for isLoggedIn() and isSeniorOfficer()

// Helper function for redirection (same as in other files)
// function redirect($page, $params = [])
// {
//     $url = $page;
//     if (!empty($params)) {
//         $url .= '?' . http_build_query($params);
//     }
//     header("Location: " . $url);
//     exit();
// }

// Function to display messages (same as in other files)
function displayMessage()
{
    if (isset($_SESSION['displayMessage']) && !empty($_SESSION['displayMessage'])) {
        $message = htmlspecialchars($_SESSION['displayMessage']);
        $type = htmlspecialchars($_SESSION['messageType'] ?? 'info');
        unset($_SESSION['displayMessage']);
        unset($_SESSION['messageType']);
        echo "
        <div class='toast-container position-fixed top-0 end-0 p-3'>
            <div class='toast show align-items-center text-white bg-{$type} border-0' role='alert' aria-live='assertive' aria-atomic='true'>
                <div class='d-flex'>
                    <div class='toast-body'>
                        {$message}
                    </div>
                    <button type='button' class='btn-close btn-close-white me-2 m-auto' data-bs-dismiss='toast' aria-label='Close'></button>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var toastEl = document.querySelector('.toast');
                if (toastEl) {
                    var toast = new bootstrap.Toast(toastEl);
                    toast.show();
                    setTimeout(function() {
                        toast.hide();
                    }, 5000); // Hide after 5 seconds
                }
            });
        </script>";
    }
}

// If user is already logged in, redirect to their respective portal
if (isLoggedIn()) {
    redirect(isSeniorOfficer() ? 'dashboard.php' : 'user_dashboard.php');
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Help Desk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: linear-gradient(135deg, #f4f7fa, #e0e7ff);
            /* Soft gradient background */
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, 'Roboto', sans-serif;
            color: #333;
            overflow-x: hidden;
        }

        .container {
            padding-top: 80px;
            /* Adjust for top-nav and hero padding */
        }

        .hero-section {
            position: relative;
            padding: 80px 20px 40px;
            text-align: center;
            background: linear-gradient(135deg, #ffffff, #eef2f7);
            /* Subtle hero gradient */
            border-bottom: 1px solid #e0e7eb;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border-radius: 0 0 15px 15px;
        }

        .logo {
            width: 180px;
            height: auto;
            margin-bottom: 30px;
            animation: bounceIn 1s ease-out;
            background: none;
            border: none;
            padding: 0;
            box-shadow: none;
        }

        h1 {
            font-size: 3rem;
            font-weight: 800;
            color: #1e3a8a;
            margin-bottom: 15px;
            animation: fadeInDown 1s ease-out;
        }

        h3 {
            font-size: 1.8rem;
            color: #3b82f6;
            margin-bottom: 20px;
            font-weight: 600;
            animation: fadeInUp 1s ease-out 0.2s;
        }

        p {
            font-size: 1.2rem;
            color: #4b5563;
            max-width: 700px;
            margin: 0 auto 30px;
            line-height: 1.6;
            animation: fadeIn 1s ease-out 0.4s;
        }

        .top-nav {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        .top-nav a {
            margin-left: 10px;
            padding: 10px 25px;
            font-weight: 600;
            text-decoration: none;
            border-radius: 25px;
            transition: all 0.3s ease;
            font-size: 1.1rem;
            background-color: #e0e7ff;
            color: #1e3a8a;
            border: 1px solid #c7d2fe;
        }

        .top-nav a:hover {
            background-color: #c7d2fe;
            color: #1e3a8a;
            transform: translateY(-2px);
        }

        .card {
            border: none;
            border-radius: 12px;
            background-color: #ffffff;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .btn-primary,
        .btn-outline-primary {
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #1d4ed8;
            transform: scale(1.05);
        }

        .btn-outline-primary:hover {
            background-color: #e0e7ff;
            color: #1e3a8a;
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            .logo {
                width: 130px;
            }

            h1 {
                font-size: 2.2rem;
            }

            h3 {
                font-size: 1.4rem;
            }

            p {
                font-size: 1rem;
            }

            .top-nav {
                top: 10px;
                right: 10px;
            }

            .top-nav a {
                font-size: 0.9rem;
                padding: 8px 20px;
            }
        }

        @keyframes bounceIn {
            0% {
                transform: scale(0);
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
            }
        }
    </style>
</head>

<body>
    <div class="top-nav">
        <?php if (!isLoggedIn()): ?>
            <a href="login.php" class="btn btn-login">Login</a>
            <a href="register.php" class="btn btn-signup">Register</a>
        <?php else: ?>
            <a href="<?php echo isSeniorOfficer() ? 'dashboard.php' : 'user_dashboard.php'; ?>" class="btn btn-login">Go to Dashboard</a>
            <a href="logout.php" class="btn btn-signup">Logout</a>
        <?php endif; ?>
    </div>

    <?php displayMessage(); ?>

    <div class="hero-section">
        <img src="assets/images/ESSA.png" alt="ESSA Logo" class="logo">
        <h1>Welcome to ESSA Helpdesk</h1>
        <h3>Ethiopian Statistical Service</h3>
        <p>Your reliable, fast, and modern IT Help Request Tracking System. Built to simplify your support journey and make your work easier.</p>
    </div>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <h3>Get Started</h3>
                        <?php if (isLoggedIn()) { ?>
                            <p>Access your dashboard to manage or track tickets.</p>
                            <a href="<?php echo isSeniorOfficer() ? 'dashboard.php' : 'user_dashboard.php'; ?>" class="btn btn-primary">Go to Dashboard</a>
                        <?php } else { ?>
                            <p>Log in or register to submit and track IT support tickets.</p>
                            <a href="login.php" class="btn btn-primary me-2">Login</a>
                            <a href="register.php" class="btn btn-outline-primary">Register</a>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
</body>

</html>