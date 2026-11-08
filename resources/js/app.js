import "@fontsource/inter/400.css";
import "@fontsource/inter/500.css";
import "@fontsource/inter/600.css";
import "@fontsource/cormorant-garamond/600.css";
import "@fontsource/cormorant-garamond/700.css";
import "bootstrap";

// Dialog konfirmasi untuk form berlabel data-confirm (hapus, dll.).
document.addEventListener("submit", (event) => {
    const message = event.target.dataset ? event.target.dataset.confirm : null;
    if (message && !window.confirm(message)) {
        event.preventDefault();
        event.stopImmediatePropagation();
    }
});

// Cegah klik ganda: tombol submit dinonaktifkan setelah form dikirim.
document.addEventListener("submit", (event) => {
    event.target.querySelectorAll('button[type="submit"]').forEach((button) => {
        button.disabled = true;
    });
});

// Aktifkan lagi jika pengguna menekan tombol Kembali di browser.
window.addEventListener("pageshow", () => {
    document.querySelectorAll('button[type="submit"]').forEach((button) => {
        button.disabled = false;
    });
});

// Tombol − / + pada input jumlah.
document.addEventListener("click", (event) => {
    const button = event.target.closest("[data-qty-step]");
    if (!button) return;

    const input = button
        .closest("[data-qty]")
        .querySelector('input[type="number"]');
    const min = parseInt(input.min || "1", 10);
    const max = parseInt(input.max || "20", 10);
    const next =
        (parseInt(input.value, 10) || min) +
        parseInt(button.dataset.qtyStep, 10);

    input.value = Math.min(max, Math.max(min, next));
    input.dispatchEvent(new Event("change", { bubbles: true }));
});

// Pilih ukuran: batas jumlah mengikuti stok ukuran yang dipilih.
function syncQuantityLimit(radio) {
    const input = document.querySelector('#buyForm input[name="quantity"]');
    if (!input || !radio) return;

    const max = Math.max(
        1,
        Math.min(parseInt(radio.dataset.stock, 10) || 1, 20),
    );
    input.max = max;
    if (parseInt(input.value, 10) > max) input.value = max;
}

document.addEventListener("change", (event) => {
    if (event.target.matches('input[name="variant_id"][data-stock]')) {
        syncQuantityLimit(event.target);
    }
    if (event.target.matches("[data-autosubmit]")) {
        event.target.form.requestSubmit();
    }
});

document.addEventListener("DOMContentLoaded", () => {
    syncQuantityLimit(
        document.querySelector('input[name="variant_id"][data-stock]:checked'),
    );
});

// Checkout: tampilkan kolom alamat dan ongkir hanya untuk pesanan antar (tampilan saja; server tetap menghitung ulang).
const checkoutForm = document.querySelector("[data-checkout]");

if (checkoutForm) {
    const rupiah = (n) => "Rp" + Number(n).toLocaleString("id-ID");
    const subtotal = parseInt(checkoutForm.dataset.subtotal, 10) || 0;
    const fee = parseInt(checkoutForm.dataset.fee, 10) || 0;
    const deliveryFields = checkoutForm.querySelector("[data-delivery-fields]");
    const feeRow = checkoutForm.querySelector("[data-fee-row]");
    const totalEl = checkoutForm.querySelector("[data-total]");

    const update = () => {
        const selected = checkoutForm.querySelector(
            'input[name="fulfillment_type"]:checked',
        );
        const isDelivery = selected && selected.value === "delivery";

        deliveryFields.hidden = !isDelivery;
        feeRow.hidden = !isDelivery;
        totalEl.textContent = rupiah(subtotal + (isDelivery ? fee : 0));
    };

    checkoutForm.addEventListener("change", update);
    update();
}
