/**
 * Applies the saved dark/light theme before first paint to avoid a flash; admin pages always stay light.
 */
try {
    if (
        !document.documentElement.dataset.admin &&
        localStorage.getItem("get-to-theme") === "dark"
    )
        document.documentElement.classList.add("dark");
} catch (_) {}
