// Theme toggle handling
document.querySelector("[data-theme-toggle]")?.addEventListener("click", () => {
    const dark = document.documentElement.classList.toggle("dark");
    try {
        localStorage.setItem("get-to-theme", dark ? "dark" : "light");
    } catch (_) {}
});

// Modal helpers
document.querySelectorAll("[data-open-modal]").forEach((button) => {
    button.addEventListener("click", () => {
        document.getElementById(button.dataset.openModal)?.showModal();
    });
});

document.querySelectorAll("[data-close-modal]").forEach((button) => {
    button.addEventListener("click", () => {
        button.closest("dialog")?.close();
    });
});

// Confirmation dialogs
document.querySelectorAll("[data-confirm]").forEach((form) => {
    form.addEventListener("submit", (event) => {
        if (!window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
});

// Booking form interactivity & ticket stepper
const booking = document.querySelector("[data-booking-form]");
if (booking) {
    const inputs = [...booking.querySelectorAll("[data-price]")];
    const quotaAlert = booking.querySelector("[data-quota-alert]");
    const submitBtn = booking.querySelector("[data-booking-submit]");

    const syncStepperButtons = (input) => {
        const parent = input.closest(".stepper");
        if (!parent) return;
        const minusBtn = parent.querySelector('[data-step="-1"]');
        const plusBtn = parent.querySelector('[data-step="1"]');
        const current = Number(input.value) || 0;
        const max = Number(input.max) || 0;

        if (minusBtn) minusBtn.disabled = input.disabled || current <= 0;
        if (plusBtn) plusBtn.disabled = input.disabled || current >= max;
    };

    const update = () => {
        let total = 0;
        let count = 0;

        inputs.forEach((input) => {
            const max = Number(input.max) || 0;
            const value = Math.max(
                0,
                Math.min(max, Math.floor(Number(input.value) || 0)),
            );
            input.value = value;
            count += value;
            total += value * Number(input.dataset.price);
            syncStepperButtons(input);
        });

        const totalEl = booking.querySelector("[data-ticket-total]");
        const countEl = booking.querySelector("[data-ticket-count]");

        if (totalEl) {
            totalEl.textContent =
                "Rp" + new Intl.NumberFormat("id-ID").format(total);
        }
        if (countEl) {
            countEl.textContent = count + " tiket";
        }

        if (quotaAlert) {
            if (count > 20) {
                quotaAlert.style.display = "block";
                quotaAlert.textContent = `Total pemesanan (${count} tiket) melebihi batas maksimal 20 tiket per pesanan.`;
            } else {
                quotaAlert.style.display = "none";
            }
        }

        if (submitBtn) {
            submitBtn.disabled = count === 0 || count > 20;
        }
    };

    booking.querySelectorAll("[data-step]").forEach((button) => {
        button.addEventListener("click", () => {
            const input = button.parentElement.querySelector("input");
            if (!input || input.disabled) return;
            const step = Number(button.dataset.step) || 0;
            const max = Number(input.max) || 0;
            const current = Number(input.value) || 0;
            const next = current + step;

            if (next >= 0 && next <= max) {
                input.value = next;
                update();
            }
        });
    });

    inputs.forEach((input) => {
        input.addEventListener("input", update);
        syncStepperButtons(input);
    });

    if (inputs.length) {
        update();
    }
}

// Smooth scroll for anchor links with click animation
document.addEventListener("click", (event) => {
    const button = event.target.closest('a[href^="#"]');
    if (!button) return;

    const targetId = button.getAttribute("href");
    if (!targetId || targetId === "#") return;

    const targetElement = document.querySelector(targetId);
    if (!targetElement) return;

    event.preventDefault();

    button.classList.add("clicked");
    setTimeout(() => {
        button.classList.remove("clicked");
    }, 600);

    targetElement.scrollIntoView({
        behavior: "smooth",
        block: "start",
    });

    if (history.pushState) {
        history.pushState(null, "", targetId);
    }
});

// Dynamic header scroll: Awal full transparan mengikuti background vintage hero,
// semakin di-scroll bertransisi menjadi normal (solid / tidak transparan lagi)
const initHeaderScroll = () => {
    const header = document.querySelector(".site-header.header-home");
    const hero = document.querySelector(".vintage-hero");
    if (!header || !hero) return;

    const onScroll = () => {
        const scrollY = window.scrollY || window.pageYOffset;
        // Jarak scroll transisi (0px = full transparan, 180px = solid penuh)
        const fadeDistance = 180;
        const ratio = Math.min(1, Math.max(0, scrollY / fadeDistance));

        header.style.setProperty("--scroll-ratio", ratio);

        if (ratio <= 0.02) {
            // Awal di posisi atas: 100% full transparan
            header.classList.remove("is-scrolling", "is-solid");
        } else if (ratio >= 0.98) {
            // Melewati scroll / area event: Solid normal
            header.classList.remove("is-scrolling");
            header.classList.add("is-solid");
        } else {
            // Selama scroll bertransisi gradual
            header.classList.remove("is-solid");
            header.classList.add("is-scrolling");
        }
    };

    window.addEventListener("scroll", onScroll, { passive: true });
    onScroll();
};

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initHeaderScroll);
} else {
    initHeaderScroll();
}
