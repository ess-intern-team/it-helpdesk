<?php
// Remember: config.php must be included *before* this header.php on every page.
// The html tag's lang, dir, and class are now set directly in each PHP file.
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavDropdown">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if (isLoggedIn()) : ?>
                    <?php if (isSeniorOfficer() || isAdmin()) : ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php"><?php echo t('dashboard'); ?></a>
                        </li>
                    <?php endif; ?>
                    <?php if (isSubmitter()) : // Show 'User Dashboard' for submitters
                    ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'user_dashboard.php') ? 'active' : ''; ?>" href="user_dashboard.php"><?php echo t('user_dashboard'); ?></a>
                        </li>
                    <?php endif; ?>
                    <?php if (isAdmin()) : ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'manage_users.php') ? 'active' : ''; ?>" href="manage_users.php"><?php echo t('manage_users'); ?></a>
                        </li>
                    <?php endif; ?>
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
                                <h6 class="dropdown-header"><?php echo t('logged_in_as'); ?> <?php echo htmlspecialchars($_SESSION['name']); ?></h6>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle me-2"></i> <?php echo t('profile'); ?></a></li>
                            <li><a class="dropdown-item" href="change_password.php"><i class="fas fa-key me-2"></i> <?php echo t('change_password'); ?></a></li>

                            <li>
                                <div class="dropdown-item d-flex align-items-center">
                                    <i class="fas fa-moon me-2"></i> <?php echo t('dark_theme'); ?>
                                    <div class="form-check form-switch ms-auto">
                                        <input class="form-check-input" type="checkbox" id="darkThemeSwitch" <?php echo (($_SESSION['theme'] ?? 'light') == 'dark' ? 'checked' : ''); ?>>
                                    </div>
                                </div>
                            </li>

                            <li class="dropdown-submenu dropstart">
                                <a class="dropdown-item dropdown-toggle" href="#" id="navbarLanguageDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-globe me-2"></i> <?php echo t('change_language'); ?>
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="navbarLanguageDropdown">
                                    <?php foreach ($available_languages as $code => $label) : ?>
                                        <li><a class="dropdown-item <?php echo (($_SESSION['lang'] ?? 'en') == $code ? 'active' : ''); ?>" href="?lang=<?php echo $code; ?><?php echo isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING']) ? '&' . $_SERVER['QUERY_STRING'] : ''; ?>"><?php echo htmlspecialchars($label); ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </li>

                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> <?php echo t('logout'); ?></a></li>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Theme toggle logic (now just changes URL param, which config.php handles)
        const darkThemeSwitch = document.getElementById('darkThemeSwitch');
        if (darkThemeSwitch) {
            darkThemeSwitch.addEventListener('change', function() {
                const currentUrl = new URL(window.location.href);
                const params = new URLSearchParams(currentUrl.search);

                // Remove existing theme param to avoid duplicates
                params.delete('theme');

                if (this.checked) {
                    params.set('theme', 'dark');
                } else {
                    params.set('theme', 'light');
                }
                currentUrl.search = params.toString();
                window.location.href = currentUrl.toString(); // Reload page with new theme
            });
        }

        // Bootstrap nested dropdown behavior for 'Change Language'
        document.querySelectorAll('.dropdown-submenu > a').forEach(function(el) {
            el.addEventListener('click', function(e) {
                e.stopPropagation(); // Prevent main dropdown from closing
                e.preventDefault(); // Prevent default link behavior
                const nextUl = this.nextElementSibling;
                if (nextUl && nextUl.classList.contains('dropdown-menu')) {
                    // Close any other open submenus at the same level
                    Array.from(this.closest('.dropdown-menu').querySelectorAll('.dropdown-submenu .dropdown-menu.show'))
                        .filter(menu => menu !== nextUl)
                        .forEach(menu => menu.classList.remove('show'));
                    nextUl.classList.toggle('show'); // Toggle visibility of the clicked submenu
                }
            });
        });

        // Close all dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown')) { // If click is outside any dropdown
                document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
                    menu.classList.remove('show');
                });
            }
        });
    });
</script>

<style>
    .avatar-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }

    .dropdown-submenu {
        position: relative;
    }

    .dropdown-submenu .dropdown-menu {
        top: 0;
        left: -100%;
        margin-top: -1px;
    }

    [dir="rtl"] .dropdown-submenu .dropdown-menu {
        left: auto;
        right: -100%;
    }

    .navbar-dark .navbar-nav .nav-link.active {
        color: #fff;
        background-color: rgba(255, 255, 255, 0.1);
    }
</style>