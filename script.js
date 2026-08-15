const button = document.getElementById("darkModeBtn");
const html = document.documentElement;

button.addEventListener("click", () => {

    if (html.classList.contains("dark")) {

        // Dark → Light
        html.classList.remove("dark");
        html.classList.add("light");

        button.textContent = "🌙 Dark Mode";

    } else {

        // Light → Dark
        html.classList.remove("light");
        html.classList.add("dark");

        button.textContent = "☀️ Light Mode";
    }

});