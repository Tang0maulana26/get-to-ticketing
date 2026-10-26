document.querySelector('[data-theme-toggle]')?.addEventListener('click', () => {
    const dark = document.documentElement.classList.toggle('dark');
    try { localStorage.setItem('get-to-theme', dark ? 'dark' : 'light'); } catch (_) {}
});
document.querySelectorAll('[data-open-modal]').forEach(button => button.addEventListener('click', () => {
    document.getElementById(button.dataset.openModal)?.showModal();
}));
document.querySelectorAll('[data-close-modal]').forEach(button => button.addEventListener('click', () => button.closest('dialog').close()));
document.querySelectorAll('[data-confirm]').forEach(form => form.addEventListener('submit', event => {
    if (!window.confirm(form.dataset.confirm)) event.preventDefault();
}));
const booking = document.querySelector('[data-booking-form]');
if (booking) {
    const inputs = [...booking.querySelectorAll('[data-price]')];
    const update = () => {
        let total = 0, count = 0;
        inputs.forEach(input => {
            const value = Math.max(0, Math.min(Number(input.max), Math.floor(Number(input.value) || 0)));
            input.value = value; count += value; total += value * Number(input.dataset.price);
        });
        booking.querySelector('[data-ticket-total]').textContent = 'Rp' + new Intl.NumberFormat('id-ID').format(total);
        booking.querySelector('[data-ticket-count]').textContent = count + ' tiket';
        const submit = booking.querySelector('[data-booking-submit]');
        if (submit) submit.disabled = count === 0 || count > 20;
    };
    booking.querySelectorAll('[data-step]').forEach(button => button.addEventListener('click', () => {
        const input = button.parentElement.querySelector('input');
        if (input.disabled) return;
        input.value = Number(input.value) + Number(button.dataset.step); update();
    }));
    inputs.forEach(input => input.addEventListener('input', update));
    if (inputs.length) update();
}
