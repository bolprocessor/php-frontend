document.addEventListener("DOMContentLoaded", function () {
    const toggleButton = document.getElementById("darkModeToggle");
    const bodyElement = document.body;

    // Check if user has a saved preference in localStorage
    const userPreference = localStorage.getItem("darkMode");
    if (userPreference === "enabled") {
        bodyElement.classList.add("dark-mode");
        }

    // Toggle dark mode when button is clicked
    toggleButton.addEventListener("click", function () {
        bodyElement.classList.toggle("dark-mode");
        // Save preference in localStorage
        if (bodyElement.classList.contains("dark-mode")) {
            localStorage.setItem("darkMode", "enabled");
            }
        else {
            localStorage.removeItem("darkMode");
            }
        });
    });