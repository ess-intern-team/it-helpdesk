// assets/js/script.js
document.addEventListener('DOMContentLoaded', function() {
    const profileAvatar = document.querySelector('.profile-avatar-clickable');
    const profileDropdown = document.querySelector('.profile-dropdown'); // Parent of avatar and content
    const dropdownContent = document.querySelector('.dropdown-content');
    const toggleDarkModeBtn = document.getElementById('toggle-dark-mode');
    const languageSelect = document.getElementById('language-select');

    // Toggle dropdown visibility
    if (profileAvatar && profileDropdown && dropdownContent) {
        profileAvatar.addEventListener('click', function(event) {
            event.stopPropagation(); // Prevent click from bubbling up to window
            profileDropdown.classList.toggle('show'); // Toggle a class on the parent
        });

        // Close dropdown when clicking outside
        window.addEventListener('click', function(event) {
            if (!event.target.matches('.profile-avatar-clickable') && !event.target.closest('.profile-dropdown')) {
                if (profileDropdown.classList.contains('show')) {
                    profileDropdown.classList.remove('show');
                }
            }
        });
    }

    // Dark Mode Toggle
    if (toggleDarkModeBtn) {
        toggleDarkModeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            document.body.classList.toggle('dark-mode');
            // Save preference using a cookie for better persistence across pages/sessions
            if (document.body.classList.contains('dark-mode')) {
                document.cookie = "theme=dark; expires=Fri, 31 Dec 9999 23:59:59 GMT; path=/";
            } else {
                document.cookie = "theme=light; expires=Fri, 31 Dec 9999 23:59:59 GMT; path=/";
            }
        });
        // Initial check for theme from cookie (already done in PHP for initial page load class)
        // This JS ensures instant toggle on subsequent clicks and can be used as a fallback.
    }

    // Language Selector
    if (languageSelect) {
        languageSelect.addEventListener('change', function() {
            const selectedLang = this.value;
            // Redirect to update language via query parameter.
            // PHP handles setting the session and then redirecting to clean URL.
            window.location.href = `?lang=${selectedLang}`;
        });
    }

    // Smooth scroll to password section if hash exists
    if (window.location.hash === '#password-section') {
        const passwordSection = document.getElementById('password-section');
        if (passwordSection) {
            passwordSection.scrollIntoView({ behavior: 'smooth' });
        }
    }
});

// Dark Mode Toggle Functionality
function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    const isDarkMode = document.body.classList.contains('dark-mode');
    localStorage.setItem('darkMode', isDarkMode);
    
    // Update the dark mode toggle button icon
    const darkModeToggle = document.getElementById('darkModeToggle');
    if (darkModeToggle) {
        darkModeToggle.innerHTML = isDarkMode ? 
            '<i class="fas fa-sun"></i>' : 
            '<i class="fas fa-moon"></i>';
    }
}

// Initialize dark mode from localStorage
function initializeDarkMode() {
    const darkModeEnabled = localStorage.getItem('darkMode') === 'true';
    if (darkModeEnabled) {
        document.body.classList.add('dark-mode');
    }
    
    // Set the correct icon on page load
    const darkModeToggle = document.getElementById('darkModeToggle');
    if (darkModeToggle) {
        darkModeToggle.innerHTML = darkModeEnabled ? 
            '<i class="fas fa-sun"></i>' : 
            '<i class="fas fa-moon"></i>';
    }
}

// Call initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', initializeDarkMode);