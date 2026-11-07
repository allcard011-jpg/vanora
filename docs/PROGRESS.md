# Catatan Progres dan Keputusan Teknis: Vanora

## Keputusan

- Laravel 13, PHP 8.5.0, PostgreSQL, Blade, Bootstrap 5
- Session driver: database (hosting Render memiliki filesystem sementara)
- Stok berkurang saat pesanan dibuat; kembali jika dibatalkan
- Ongkir flat Rp10.000, hanya Banjarbaru; disimpan di tabel settings
- Pembayaran: tunai dan QRIS manual
- Kategori: Pria, Wanita, Unisex (satu produk satu kategori)
- Foto: aset statis atau URL (belum ada upload)
- Zona waktu: Asia/Makassar (WITA)
- Satu akun admin
- Format nomor pesanan: VNR-YYYYMMDD-0001
- Produk unggulan ditandai manual (is_featured)

## Progres

- [x] Fase 1: Analisis kebutuhan dan perencanaan
- [ ] Fase 2: Lingkungan dan instalasi Laravel
- [ ] Fase 3: Database, ERD, Use Case, Activity Diagram
- [ ] Fase 4: UI/UX
- [ ] Fase 5: Database, autentikasi, dashboard admin
- [ ] Fase 6: Katalog, detail, keranjang
- [ ] Fase 7: Checkout, pesanan, status
- [ ] Fase 8: Laporan dan pengujian
- [ ] Fase 9: Keamanan dan responsivitas
- [ ] Fase 10: Deployment
- [ ] Fase 11: Dokumentasi
