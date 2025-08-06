<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | IT Help Desk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Modern Color Scheme */
        :root {
            --primary: #4F46E5;
            --primary-dark: #4338CA;
            --primary-light: #C7D2FE;
            --secondary: #10B981;
            --accent: #F59E0B;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
            --dark: #1F2937;
            --light: #F9FAFB;
            --bg-main: #F8FAFC;
        }

        body {
            background-color: var(--bg-main);
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }

        .auth-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .input-group {
            position: relative;
        }

        .input-group input {
            padding-left: 50px;
            background-color: white;
            border: 1px solid #E2E8F0;
            transition: all 0.3s ease;
            height: 52px;
            font-size: 16px;
        }

        .input-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #94A3B8;
            font-size: 18px;
        }

        .btn-primary {
            background-color: var(--primary);
            transition: all 0.3s ease;
            height: 52px;
            font-size: 16px;
            font-weight: 600;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .help-image-container {
            background: linear-gradient(135deg, #6366F1 0%, #8B5CF6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .help-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .help-image:hover {
            transform: scale(1.05);
        }

        .help-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.7) 0%, transparent 100%);
            padding: 2rem;
            color: white;
        }

        @media (max-width: 768px) {
            .help-image-container {
                display: none;
            }

            .auth-container {
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            }
        }

        /* Animation for the form */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-content {
            animation: fadeInUp 0.6s ease-out forwards;
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4 md:p-8">
    <div class="auth-container w-full max-w-6xl flex flex-col md:flex-row overflow-hidden">
        <!-- Left Side - Form -->
        <div class="w-full md:w-1/2 p-8 md:p-12 lg:p-16 form-content">
            <div class="text-center md:text-left">
                <div class="flex justify-center md:justify-start items-center mb-8">
                    <div class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center shadow-md">
                        <i class="fas fa-key text-indigo-600 text-2xl"></i>
                    </div>
                    <span class="ml-4 text-2xl font-bold text-gray-800">HelpDesk Pro</span>
                </div>
                <h1 class="mt-8 text-3xl font-bold text-gray-800 leading-tight">Reset Your Password</h1>
                <p class="mt-3 text-lg text-gray-600">Enter your email and we'll send you a secure link to reset your password</p>
            </div>

            <form class="mt-10 space-y-6" action="process_reset.php" method="POST">
                <div class="rounded-md space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email address</label>
                        <div class="input-group">
                            <i class="fas fa-envelope input-icon"></i>
                            <input id="email" name="email" type="email" autocomplete="email" required
                                class="appearance-none block w-full px-4 py-3 border rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary focus:border-primary text-lg"
                                placeholder="you@example.com">
                        </div>
                    </div>
                </div>

                <div>
                    <button type="submit"
                        class="w-full flex justify-center items-center py-3 px-6 border border-transparent rounded-lg shadow-md text-lg font-semibold text-white btn-primary focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                        <i class="fas fa-paper-plane mr-3"></i> Send Reset Link
                    </button>
                </div>
            </form>

            <div class="mt-8 text-center md:text-left">
                <a href="login.php" class="text-lg font-medium text-indigo-600 hover:text-indigo-500 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i> Back to login
                </a>
            </div>
        </div>

        <!-- Right Side - Help Image -->
        <div class="hidden md:block md:w-1/2 help-image-container">
            <img src="./assets/images/image.png" alt="Help Desk Support" class="help-image">

            </a>
        </div>
    </div>
    </div>
    </div>

    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!email) {
                e.preventDefault();
                alert('Please enter your email address');
                return false;
            }

            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return false;
            }

            // If validation passes, you could add a loading animation
            const submitBtn = e.target.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Sending...';
            submitBtn.disabled = true;

            return true;
        });

        // Add hover effect to back to login link
        const backLink = document.querySelector('a[href="login.php"]');
        if (backLink) {
            backLink.addEventListener('mouseenter', function() {
                this.querySelector('i').style.transform = 'translateX(-4px)';
            });
            backLink.addEventListener('mouseleave', function() {
                this.querySelector('i').style.transform = 'translateX(0)';
            });
        }
    </script>
</body>

</html>