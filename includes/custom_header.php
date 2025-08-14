<?php
// Note: config.php must be included *before* this custom_header.php on every page.
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavDropdown">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if (isLoggedIn() && isAdmin()) : ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_register_senior.php') ? 'active' : ''; ?>" href="admin_register_senior.php"><?php echo t('Manage Senior Officers'); ?></a>
                    </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto">
                <?php if (isLoggedIn()) : ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle avatar-link" href="#" id="navbarDropdownMenuLink" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?php echo htmlspecialchars($_SESSION['avatar_path'] ?? 'assets/default_avatar.png'); ?>" alt="Avatar" class="avatar-icon">
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownMenuLink">
                            <li>
                                <h6 class="dropdown-header"><?php echo t('Logged in as'); ?> <?php echo htmlspecialchars($_SESSION['name']); ?></h6>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle me-2"></i> <?php echo t('profile'); ?></a></li>
                            <li><a class="dropdown-item" href="change_password.php"><i class="fas fa-key me-2"></i> <?php echo t('Change Password'); ?></a></li>
                            <li>
                                <div class="dropdown-item d-flex align-items-center">
                                    <i class="fas fa-moon me-2"></i> <?php echo t('Dark Theme'); ?>
                                    <div class="form-check form-switch ms-auto">
                                        <input class="form-check-input" type="checkbox" id="darkThemeSwitch" <?php echo (($_SESSION['theme'] ?? 'light') == 'dark' ? 'checked' : ''); ?>>
                                    </div>
                                </div>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#deleteAccountModal"><i class="fas fa-trash-alt me-2"></i> <?php echo t('Delete Account'); ?></a></li>
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="fas fa-sign-out-alt me-2"></i> <?php echo t('logout'); ?></a></li>
                        </ul>
                    </li>
                <?php else : ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'login.php') ? 'active' : ''; ?>" href="login.php"><?php echo t('login'); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'register.php') ? 'active' : ''; ?>" href="register.php"><?php echo t('register'); ?></a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel"><?php echo t('Confirm Logout'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php echo t('Are you sure you want to log out?'); ?>
            </div>
            <div class="modal-footer">
                <form action="logout.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('Cancel'); ?></button>
                    <button type="submit" class="btn btn-primary"><?php echo t('Logout'); ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteAccountModalLabel"><?php echo t('Confirm Account Deletion'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php echo t('This will permanently delete your account and all associated data, including email and password. This action cannot be undone. Are you sure?'); ?>
            </div>
            <div class="modal-footer">
                <form action="delete_account.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo t('Cancel'); ?></button>
                    <button type="submit" class="btn btn-danger"><?php echo t('Delete Account'); ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    /* Base styles for all devices */
    .navbar {
        padding: 0.15rem 0.8rem;
        /* Reduced padding to make header smaller */
    }

    .nav-link {
        font-size: 0.85rem;
        /* Slightly smaller font size */
        padding: 0.3rem 0.6rem;
        /* Reduced padding for nav links */
    }

    .avatar-icon {
        width: 24px;
        /* Reduced avatar size */
        height: 24px;
        border-radius: 50%;
        object-fit: cover;
        transition: transform 0.2s ease;
    }

    .avatar-icon:hover {
        transform: scale(1.1);
    }

    .nav-item.dropdown {
        position: relative;
    }

    .dropdown-menu {
        min-width: 180px;
        max-width: 90vw;
        overflow-x: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease, transform 0.3s ease;
    }

    .dropdown-menu.show {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .dropdown-item.text-danger:hover {
        background-color: #f8d7da;
        color: #dc3545 !important;
    }

    .navbar-dark .navbar-nav .nav-link.active {
        color: #fff;
        background-color: rgba(255, 255, 255, 0.1);
    }

    .dropdown-item {
        padding: 0.3rem 0.8rem;
        font-size: 0.85rem;
        /* Match nav-link font size */
    }

    /* Desktop-specific styles (for screens wider than 991px) */
    @media (min-width: 992px) {
        .nav-item.dropdown:hover .dropdown-menu {
            display: block;
            visibility: visible;
            opacity: 1;
            transform: translateY(0);
        }

        .dropdown-menu {
            position: absolute !important;
            top: 100% !important;
            opacity: 0;
            visibility: hidden;
            display: block;
            transform: translateY(4px);
            /* Reduced offset for smaller navbar */
        }

        /* Directional positioning for desktop */
        [dir="rtl"] .dropdown-menu {
            left: auto;
            right: -110%;
        }

        [dir="ltr"] .dropdown-menu {
            right: -60%;
            left: auto;
        }
    }

    /* Mobile-specific styles (for screens 991px and narrower) */
    @media (max-width: 991px) {
        .nav-item.dropdown .dropdown-menu {
            position: static !important;
            float: none !important;
            width: 100% !important;
            margin-top: 0;
            border: none;
            box-shadow: none;
            min-width: auto;
            max-width: 100%;
        }

        .dropdown-menu.show {
            position: static !important;
            transform: none !important;
            opacity: 1;
            visibility: visible;
        }

        .nav-item.dropdown .dropdown-toggle:active,
        .nav-item.dropdown .dropdown-toggle:focus {
            outline: none;
        }
    }
</style>

<script>
    // Simulate hover on mobile with touch events and handle click/tap
    document.addEventListener('DOMContentLoaded', function() {
        const dropdownToggle = document.querySelector('#navbarDropdownMenuLink');
        const dropdownMenu = document.querySelector('.dropdown-menu');

        if (dropdownToggle && dropdownMenu) {
            // Handle touchstart for mobile to simulate hover
            dropdownToggle.addEventListener('touchstart', function(e) {
                if (window.innerWidth <= 991) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropdownMenu.classList.toggle('show');
                }
            });

            // Handle click for mobile to ensure compatibility
            dropdownToggle.addEventListener('click', function(e) {
                if (window.innerWidth <= 991) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropdownMenu.classList.toggle('show');
                }
            });

            // Close dropdown when clicking/tapping outside on mobile
            document.addEventListener('touchstart', function(e) {
                if (window.innerWidth <= 991 && !dropdownToggle.contains(e.target) && !dropdownMenu.contains(e.target)) {
                    dropdownMenu.classList.remove('show');
                }
            });

            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 991 && !dropdownToggle.contains(e.target) && !dropdownMenu.contains(e.target)) {
                    dropdownMenu.classList.remove('show');
                }
            });
        }
    });
</script>