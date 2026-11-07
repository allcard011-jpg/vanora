import "@fontsource/inter/400.css";
import "@fontsource/inter/500.css";
import "@fontsource/inter/600.css";
import "@fontsource/cormorant-garamond/600.css";
import "@fontsource/cormorant-garamond/700.css";
import "bootstrap";

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
