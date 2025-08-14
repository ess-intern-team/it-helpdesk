document.addEventListener("DOMContentLoaded", () => {
  const htmlElement = document.documentElement;
  const darkThemeSwitch = document.getElementById("darkThemeSwitch");
  const languageLinks = document.querySelectorAll(
    '.dropdown-item[href*="lang="]'
  );

  // --- Theme Toggling Logic ---
  function setTheme(theme) {
    if (theme === "dark") {
      htmlElement.classList.add("dark-theme");
      if (darkThemeSwitch) darkThemeSwitch.checked = true;
      localStorage.setItem("theme", "dark");
    } else {
      htmlElement.classList.remove("dark-theme");
      if (darkThemeSwitch) darkThemeSwitch.checked = false;
      localStorage.setItem("theme", "light");
    }
  }

  // Initialize theme on page load
  const savedTheme = localStorage.getItem("theme");
  if (savedTheme) {
    setTheme(savedTheme);
  } else if (
    window.matchMedia &&
    window.matchMedia("(prefers-color-scheme: dark)").matches
  ) {
    setTheme("dark");
  } else {
    setTheme("light");
  }

  // Listen for theme switch changes
  if (darkThemeSwitch) {
    darkThemeSwitch.addEventListener("change", () => {
      const newTheme = darkThemeSwitch.checked ? "dark" : "light";
      setTheme(newTheme);
    });
  }

  // --- Language Switch Logic ---
  if (languageLinks.length > 0) {
    languageLinks.forEach((link) => {
      link.addEventListener("click", (e) => {
        e.preventDefault();

        const currentUrl = new URL(window.location.href);
        // Extract language code from the link's href attribute
        const newLang = link
          .getAttribute("href")
          .split("lang=")[1]
          .split("&")[0];
        currentUrl.searchParams.set("lang", newLang);

        // Preserve other URL parameters
        const otherParams = new URLSearchParams(window.location.search);
        otherParams.forEach((value, key) => {
          if (key !== "lang") {
            currentUrl.searchParams.set(key, value);
          }
        });

        window.location.href = currentUrl.toString(); // Reload page with new language
      });
    });
  }

  // --- Nested Dropdown Logic for Language Selector ---
  document.querySelectorAll(".dropdown-submenu > a").forEach(function (el) {
    el.addEventListener("click", function (e) {
      e.stopPropagation();
      e.preventDefault();
      const nextUl = this.nextElementSibling;
      if (nextUl && nextUl.classList.contains("dropdown-menu")) {
        const parentUl = this.closest(".dropdown-menu");
        Array.from(
          parentUl.querySelectorAll(".dropdown-submenu .dropdown-menu.show")
        )
          .filter((menu) => menu !== nextUl)
          .forEach((menu) => menu.classList.remove("show"));
        nextUl.classList.toggle("show");
      }
    });
  });

  // Close all dropdowns when clicking outside
  document.addEventListener("click", function (e) {
    if (!e.target.closest(".dropdown")) {
      document.querySelectorAll(".dropdown-menu.show").forEach(function (menu) {
        menu.classList.remove("show");
      });
    }
  });

  // --- General Bootstrap & Other Scripts ---
  // Bootstrap form validation
  const forms = document.querySelectorAll(".needs-validation");
  forms.forEach((form) => {
    form.addEventListener(
      "submit",
      (event) => {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add("was-validated");
      },
      false
    );
  });

  // Automatically dismiss flash messages after 2 seconds
  const flashMessageContainer = document.getElementById(
    "flashMessageContainer"
  );
  if (flashMessageContainer) {
    const flashMessages = flashMessageContainer.querySelectorAll(".alert");
    flashMessages.forEach(function (message) {
      setTimeout(function () {
        const bsAlert = new bootstrap.Alert(message);
        bsAlert.close();
      }, 2000);
    });
  }
});
