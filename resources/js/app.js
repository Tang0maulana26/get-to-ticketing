/**
 * Frontend behaviour (no framework): theme toggle, modals, fetch-based forms, live catalog filter, confirmations, ticket stepper and header scroll effects.
 * Everything degrades to normal form posts when JavaScript is unavailable.
 */
// Theme toggle handling
document.querySelector("[data-theme-toggle]")?.addEventListener("click", () => {
    const dark = document.documentElement.classList.toggle("dark");
    try {
        localStorage.setItem("get-to-theme", dark ? "dark" : "light");
    } catch (_) {}
});

// Toggle password visibility
document.addEventListener("click", (event) => {
    const toggle = event.target.closest("[data-toggle-password]");
    if (!toggle) return;
    event.preventDefault();
    event.stopPropagation();
    const wrapper = toggle.closest(".password-field-wrapper");
    if (!wrapper) return;
    const input = wrapper.querySelector("input");
    if (!input) return;

    const isPassword = input.getAttribute("type") === "password";
    input.setAttribute("type", isPassword ? "text" : "password");
    toggle.setAttribute("aria-label", isPassword ? "Sembunyikan kata sandi" : "Tampilkan kata sandi");

    const eyeShow = toggle.querySelector(".eye-show");
    const eyeHide = toggle.querySelector(".eye-hide");
    if (eyeShow && eyeHide) {
        eyeShow.style.display = isPassword ? "none" : "";
        eyeHide.style.display = isPassword ? "" : "none";
    }
});

// Modal helpers (delegated so they keep working after partial page refreshes)
document.addEventListener("click", (event) => {
    const opener = event.target.closest("[data-open-modal]");
    if (opener) {
        document.getElementById(opener.dataset.openModal)?.showModal();
        return;
    }
    if (event.target.closest("[data-close-modal]")) {
        event.target.closest("dialog")?.close();
    }
});

// Fetch API helpers -----------------------------------------------------
const csrfToken = (form) =>
    form?.querySelector('input[name="_token"]')?.value ??
    document.querySelector('meta[name="csrf-token"]')?.content ??
    "";

const requestJson = async (
    url,
    { method = "GET", body, form, signal } = {},
) => {
    const response = await fetch(url, {
        method,
        signal,
        credentials: "same-origin",
        headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
            ...(body ? { "Content-Type": "application/json" } : {}),
            ...(method !== "GET" ? { "X-CSRF-TOKEN": csrfToken(form) } : {}),
        },
        body: body ? JSON.stringify(body) : undefined,
    });
    let data = {};
    try {
        data = await response.json();
    } catch (_) {}
    return { ok: response.ok, status: response.status, data };
};

const showNotice = (message, type = "success") => {
    const main = document.getElementById("main");
    if (!main) return;
    main.querySelector("[data-live-notice]")?.remove();
    const wrap = document.createElement("div");
    wrap.className = "shell";
    wrap.dataset.liveNotice = "";
    const box = document.createElement("div");
    box.className = `notice ${type}`;
    box.setAttribute("role", type === "error" ? "alert" : "status");
    box.textContent = message;
    wrap.append(box);
    main.prepend(wrap);
    if (type === "success") setTimeout(() => wrap.remove(), 5000);
};

const clearFieldErrors = (form) => {
    form.querySelectorAll(".field-error").forEach((el) => el.remove());
    form.querySelectorAll("[aria-invalid]").forEach((el) =>
        el.removeAttribute("aria-invalid"),
    );
};

const showFieldErrors = (form, errors) => {
    let general = [];
    Object.entries(errors).forEach(([name, messages]) => {
        const input = form.querySelector(`[name="${CSS.escape(name)}"]`);
        if (!input) {
            general = general.concat(messages);
            return;
        }
        input.setAttribute("aria-invalid", "true");
        const hint = document.createElement("small");
        hint.className = "field-error";
        hint.textContent = messages[0];
        (input.closest("label") ?? input.parentElement).append(hint);
    });
    return general;
};

// Forms marked data-fetch-form submit as JSON; they still work without JS.
document.addEventListener("submit", async (event) => {
    const form = event.target.closest("form[data-fetch-form]");
    if (!form) return;
    event.preventDefault();

    if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) return;
    if (form.dataset.busy) return;

    const button = form.querySelector('button:not([type="button"])');
    const label = button?.textContent;
    form.dataset.busy = "1";
    if (button) {
        button.disabled = true;
        button.textContent = "Memproses…";
    }
    clearFieldErrors(form);

    try {
        const body = Object.fromEntries(new FormData(form));
        const { ok, status, data } = await requestJson(form.action, {
            method: "POST",
            body,
            form,
        });

        if (ok) {
            if (form.dataset.redirectFrom && data[form.dataset.redirectFrom]) {
                window.location.assign(data[form.dataset.redirectFrom]);
                return; // keep the button disabled while navigating
            }
            form.closest("dialog")?.close();
            await refreshTarget();
            showNotice(data.message ?? "Berhasil disimpan.");
        } else if (status === 422 && data.errors) {
            const general = showFieldErrors(form, data.errors);
            if (general.length) showNotice(general.join(" "), "error");
        } else if (status === 419) {
            showNotice(
                "Sesi habis. Muat ulang halaman lalu coba lagi.",
                "error",
            );
        } else {
            showNotice(
                data.message || "Terjadi kesalahan. Coba lagi.",
                "error",
            );
        }
    } catch (_) {
        showNotice("Koneksi bermasalah. Coba lagi.", "error");
    } finally {
        delete form.dataset.busy;
        if (button && button.isConnected) {
            button.disabled = false;
            button.textContent = label;
        }
    }
});

// Re-fetch the current page and swap only the section that changed.
const refreshTarget = async () => {
    const current = document.querySelector("[data-refresh-target]");
    if (!current) return;
    const response = await fetch(window.location.href, {
        credentials: "same-origin",
        headers: { "X-Requested-With": "XMLHttpRequest" },
    });
    const doc = new DOMParser().parseFromString(
        await response.text(),
        "text/html",
    );
    const fresh = doc.getElementById(current.id);
    if (fresh) current.replaceWith(fresh);
};

// Live catalog filter: debounce, cancel stale requests, keep URL shareable.
const filterForm = document.querySelector("form[data-live-filter]");
const results = document.querySelector("[data-catalog-results]");
if (filterForm && results) {
    const counter = document.querySelector("[data-catalog-count]");
    let timer;
    let controller;

    const load = async (url, push = true) => {
        controller?.abort();
        controller = new AbortController();
        results.setAttribute("aria-busy", "true");
        results.style.opacity = "0.5";
        try {
            const { ok, data } = await requestJson(url, {
                signal: controller.signal,
            });
            if (!ok || typeof data.html !== "string")
                throw new Error("bad response");
            results.innerHTML = data.html;
            if (counter) {
                const tpl = counter.dataset.countTemplate || ":count events available";
                counter.textContent = tpl.replace(":count", data.total);
            }
            if (push) history.pushState(null, "", url);
        } catch (error) {
            if (error.name === "AbortError") return;
            window.location.assign(url); // graceful fallback to full page
            return;
        }
        results.removeAttribute("aria-busy");
        results.style.opacity = "";
    };

    const currentUrl = () => {
        const params = new URLSearchParams(new FormData(filterForm));
        [...params].forEach(
            ([key, value]) => value === "" && params.delete(key),
        );
        const query = params.toString();
        return (
            filterForm.getAttribute("action").split("#")[0] +
            (query ? `?${query}` : "")
        );
    };

    filterForm.addEventListener("submit", (event) => {
        event.preventDefault();
        clearTimeout(timer);
        load(currentUrl());
    });
    filterForm.addEventListener("input", (event) => {
        if (event.target.type !== "search") return;
        clearTimeout(timer);
        timer = setTimeout(() => load(currentUrl()), 350);
    });
    filterForm.addEventListener("change", () => {
        clearTimeout(timer);
        load(currentUrl());
    });

    results.addEventListener("click", (event) => {
        const link = event.target.closest(".pagination a[href]");
        if (!link) return;
        event.preventDefault();
        load(link.href);
    });

    window.addEventListener("popstate", () => {
        const params = new URLSearchParams(window.location.search);
        [...filterForm.elements].forEach((el) => {
            if (el.name) el.value = params.get(el.name) ?? "";
        });
        load(window.location.pathname + window.location.search, false);
    });
}

// Confirmation dialogs for classic (non-fetch) forms
document.addEventListener("submit", (event) => {
    const form = event.target.closest(
        "form[data-confirm]:not([data-fetch-form])",
    );
    if (form && !window.confirm(form.dataset.confirm)) event.preventDefault();
});

// Booking form interactivity & ticket stepper with promo discount calculation
const booking = document.querySelector("[data-booking-form]");
if (booking) {
    let bookingI18n = {};
    try {
        bookingI18n = JSON.parse(booking.dataset.i18n || "{}");
    } catch (_) {}

    const tBooking = (key, fallback, replacements = {}) => {
        let text = bookingI18n[key] || fallback || key;
        for (const [k, v] of Object.entries(replacements)) {
            text = text.replace(new RegExp(k, "g"), String(v));
        }
        return text;
    };

    const inputs = [...booking.querySelectorAll("[data-price]")];
    const quotaAlert = booking.querySelector("[data-quota-alert]");
    const submitBtn = booking.querySelector("[data-booking-submit]");

    const discountInput = booking.querySelector("#discount-code-input");
    const applyDiscountBtn = booking.querySelector("#apply-discount-btn");
    const discountClearBtn = booking.querySelector("#discount-clear-btn");
    const discountFeedback = booking.querySelector("#discount-feedback");
    const breakdownEl = booking.querySelector("#booking-breakdown");
    const breakdownSubtotal = booking.querySelector("#breakdown-subtotal");
    const breakdownDiscountRow = booking.querySelector("#breakdown-discount-row");
    const breakdownDiscountLabel = booking.querySelector("#breakdown-discount-label");
    const breakdownDiscountAmount = booking.querySelector("#breakdown-discount-amount");

    let currentSubtotal = 0;
    let appliedDiscount = null;

    const formatRupiah = (num) => "Rp" + new Intl.NumberFormat("id-ID").format(num);

    const setDiscountFeedback = (type, message) => {
        if (!discountFeedback) return;
        if (!message) {
            discountFeedback.style.display = "none";
            discountFeedback.replaceChildren();
            return;
        }

        discountFeedback.className = `discount-feedback is-${type}`;
        discountFeedback.style.display = "flex";
        discountFeedback.replaceChildren();

        const svgNs = "http://www.w3.org/2000/svg";
        const svg = document.createElementNS(svgNs, "svg");
        svg.setAttribute("width", "15");
        svg.setAttribute("height", "15");
        svg.setAttribute("viewBox", "0 0 24 24");
        svg.setAttribute("fill", "none");
        svg.setAttribute("stroke", "currentColor");
        svg.setAttribute("stroke-width", "2.5");
        svg.setAttribute("stroke-linecap", "round");
        svg.setAttribute("stroke-linejoin", "round");
        svg.setAttribute("aria-hidden", "true");

        if (type === "success") {
            const polyline = document.createElementNS(svgNs, "polyline");
            polyline.setAttribute("points", "20 6 9 17 4 12");
            svg.appendChild(polyline);
        } else {
            const circle = document.createElementNS(svgNs, "circle");
            circle.setAttribute("cx", "12");
            circle.setAttribute("cy", "12");
            circle.setAttribute("r", "10");
            const line1 = document.createElementNS(svgNs, "line");
            line1.setAttribute("x1", "12");
            line1.setAttribute("y1", "8");
            line1.setAttribute("x2", "12");
            line1.setAttribute("y2", "12");
            const line2 = document.createElementNS(svgNs, "line");
            line2.setAttribute("x1", "12");
            line2.setAttribute("y1", "16");
            line2.setAttribute("x2", "12.01");
            line2.setAttribute("y2", "16");
            svg.append(circle, line1, line2);
        }

        const span = document.createElement("span");
        span.textContent = message;

        discountFeedback.append(svg, span);
    };

    const syncDiscountClearBtn = () => {
        if (!discountClearBtn || !discountInput) return;
        discountClearBtn.style.display = discountInput.value.trim() ? "inline-flex" : "none";
    };

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

        currentSubtotal = total;
        let finalTotal = total;

        if (appliedDiscount && total > 0) {
            let discountAmount = 0;
            if (appliedDiscount.type === "percent") {
                discountAmount = Math.round((total * appliedDiscount.value) / 100);
            } else {
                discountAmount = appliedDiscount.value;
            }
            discountAmount = Math.min(discountAmount, total);
            finalTotal = Math.max(0, total - discountAmount);

            if (breakdownEl) breakdownEl.style.display = "block";
            if (breakdownSubtotal) breakdownSubtotal.textContent = formatRupiah(total);
            if (breakdownDiscountRow) {
                breakdownDiscountRow.style.display = "flex";
                if (breakdownDiscountLabel) {
                    breakdownDiscountLabel.textContent = tBooking("discount_applied", `Potongan (${appliedDiscount.code}):`, { ":code": appliedDiscount.code });
                }
                if (breakdownDiscountAmount) breakdownDiscountAmount.textContent = "-" + formatRupiah(discountAmount);
            }
        } else {
            if (breakdownEl) breakdownEl.style.display = "none";
            if (breakdownDiscountRow) breakdownDiscountRow.style.display = "none";
        }

        const totalEl = booking.querySelector("[data-ticket-total]");
        const countEl = booking.querySelector("[data-ticket-count]");

        if (totalEl) {
            totalEl.textContent = formatRupiah(finalTotal);
        }
        if (countEl) {
            countEl.textContent = count === 1
                ? tBooking("ticket_singular", "1 tiket")
                : tBooking("tickets_count", `${count} tiket`, { ":count": count });
        }

        if (quotaAlert) {
            if (count > 20) {
                quotaAlert.style.display = "block";
                quotaAlert.textContent = tBooking("quota_exceeded", `Total pemesanan (${count} tiket) melebihi batas maksimal 20 tiket per pesanan.`, { ":count": count });
            } else {
                quotaAlert.style.display = "none";
            }
        }

        if (submitBtn) {
            submitBtn.disabled = count === 0 || count > 20;
        }
    };

    const checkDiscount = async () => {
        if (!discountInput) return;
        const code = discountInput.value.trim().toUpperCase();
        if (!code) {
            appliedDiscount = null;
            applyDiscountBtn?.classList.remove("is-applied");
            setDiscountFeedback(null, "");
            syncDiscountClearBtn();
            update();
            return;
        }

        if (currentSubtotal <= 0) {
            setDiscountFeedback("error", tBooking("select_ticket_first", "Pilih tiket terlebih dahulu untuk menerapkan diskon."));
            return;
        }

        if (applyDiscountBtn) {
            applyDiscountBtn.disabled = true;
            applyDiscountBtn.classList.add("is-loading");
        }

        const eventId = Number(booking.querySelector('[name="event_id"]')?.value) || undefined;

        const { ok, data } = await requestJson("/discounts/check", {
            method: "POST",
            body: { code, subtotal: currentSubtotal, event_id: eventId },
            form: booking,
        });

        if (applyDiscountBtn) {
            applyDiscountBtn.disabled = false;
            applyDiscountBtn.classList.remove("is-loading");
        }

        if (ok && data.ok) {
            appliedDiscount = data;
            applyDiscountBtn?.classList.add("is-applied");
            setDiscountFeedback("success", data.message);
        } else {
            appliedDiscount = null;
            applyDiscountBtn?.classList.remove("is-applied");
            setDiscountFeedback("error", data?.message || tBooking("invalid_discount", "Kode diskon tidak valid."));
        }
        syncDiscountClearBtn();
        update();
    };

    applyDiscountBtn?.addEventListener("click", checkDiscount);
    discountInput?.addEventListener("keydown", (e) => {
        if (e.key === "Enter") {
            e.preventDefault();
            checkDiscount();
        }
    });

    discountInput?.addEventListener("input", () => {
        applyDiscountBtn?.classList.remove("is-applied");
        syncDiscountClearBtn();
    });

    discountClearBtn?.addEventListener("click", () => {
        if (!discountInput) return;
        discountInput.value = "";
        syncDiscountClearBtn();
        appliedDiscount = null;
        applyDiscountBtn?.classList.remove("is-applied");
        setDiscountFeedback(null, "");
        discountInput.focus();
        update();
    });

    syncDiscountClearBtn();

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
