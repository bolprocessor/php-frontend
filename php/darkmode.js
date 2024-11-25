document.addEventListener("DOMContentLoaded", function () {
    const toggleButton = document.getElementById("darkModeToggle");
    const bodyElement = document.body;

    // Check if user has a saved preference in localStorage
    const userPreference = localStorage.getItem("lightMode");
    if (userPreference === "enabled") {
        bodyElement.classList.add("light-mode");
        }

    // Check if toggleButton exists before adding an event listener
    if (toggleButton) {
        toggleButton.addEventListener("click", function () {
            bodyElement.classList.toggle("light-mode");

            // Save preference in localStorage
            if (bodyElement.classList.contains("light-mode")) {
                localStorage.setItem("lightMode", "enabled");
                 }
            else localStorage.removeItem("lightMode");
            });
        }
    });