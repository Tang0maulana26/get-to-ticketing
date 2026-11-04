/**
 * Applies the saved dark/light theme before first paint to avoid a flash.
 */
try {
    if (localStorage.getItem("get-to-theme") === "dark") {
        document.documentElement.classList.add("dark");
    }
} catch (_) {}
