# Abah Laut - Website Restoran Seafood

Website manajemen restoran berbasis **PHP Native + MySQL** siap dijalankan di XAMPP.

## Cara Instalasi (XAMPP)

1. Download file ZIP proyek ini.
2. Ekstrak folder ke `C:\xampp\htdocs\abah-laut`  
   (hasil ekstraksi harus berisi `index.php`, folder `config/`, `database/`, dll. di dalam `abah-laut`).
3. Buka **XAMPP Control Panel** → Start **Apache** dan **MySQL**.
4. Buka browser: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
5. Buat database baru bernama: `db_alan`
6. Pilih database tersebut → tab **Import** → pilih file `database/db_alan.sql` → Go.
7. Buka: [http://localhost/abah-laut/](http://localhost/abah-laut/)
8. (Opsional) Buka [http://localhost/abah-laut/install.php](http://localhost/abah-laut/install.php) untuk cek koneksi.
9. **Hapus atau rename `install.php`** setelah instalasi selesai.

Tidak memerlukan Composer, Node.js, npm, Laravel, atau build process.

## Akun Demo

| Role        | Nomor WhatsApp | Password       |
|-------------|----------------|----------------|
| Admin       | 081234567890   | admin123       |
| Petugas     | 081298765432   | petugas123     |
| Petugas 2   | 081223344455   | petugas123     |
| Pengunjung  | 081112223333   | pengunjung123  |

Password disimpan dalam bentuk hash bcrypt di database.

## Fitur yang Sudah Berfungsi

- Login / Logout (autentikasi WhatsApp + password)
- Registrasi pengunjung
- Session & pembatasan akses berdasarkan role
- Dashboard Admin (statistik real dari DB, activity log, kinerja petugas)
- Manajemen Pengguna/Petugas lengkap (tambah, edit, hapus, toggle status) oleh Admin
- Dashboard Petugas
- Dashboard Pengunjung
- CRUD Menu (tambah, edit, nonaktifkan) + upload gambar nyata (JPG/PNG/WEBP/GIF)
- CRUD Kategori (admin dan petugas), termasuk validasi kategori yang masih dipakai menu
- CRUD Pilihan Saus oleh admin, dengan harga tambahan dan status aktif/nonaktif
- Pengaturan saus yang tersedia untuk setiap menu secara terpisah dari kategori
- Perhitungan harga saus di keranjang dan checkout, dengan snapshot nama/harga saus di riwayat pesanan
- Tampilan menu publik & filter kategori
- Keranjang belanja (session)
- Checkout & penyimpanan pesanan + order items (transaksi DB)
- Riwayat pesanan pengunjung
- Reservasi meja (buat + status)
- Update status pesanan & reservasi oleh petugas
- Activity log untuk aksi penting
- Validasi backend, CSRF token, password hashing, prepared statements
- Proteksi role di setiap halaman

## Fitur yang Sengaja Belum Diselesaikan (Segera Hadir)

- Pencetakan struk pesanan
- Pencetakan bukti reservasi
- Ekspor / unduh laporan
- Pembayaran online
- Notifikasi WhatsApp otomatis

Tombol fitur di atas tetap tampil dengan label **Segera Hadir / Belum Tersedia** dan dinonaktifkan.

## Struktur Folder

```
abah-laut/
├── index.php, login.php, register.php, logout.php, dashboard.php
├── install.php, .htaccess, README.md
├── config/database.php
├── includes/ (auth, functions, header, navbar, sidebar, footer)
├── admin/ (dashboard, users, staff-performance, activity-log, sauces)
├── petugas/ (dashboard, menus, menu-create, menu-edit, categories, orders, reservations)
├── pengunjung/ (dashboard, menus, cart, checkout, reservations, order-history, profile)
├── assets/ (css, js, images)
├── database/db_alan.sql (database baru)
├── uploads/menus/
└── storage/
```

## Konfigurasi Database

Edit `config/database.php` jika pengaturan MySQL berbeda dari default XAMPP:

- Host: `localhost`
- Database: `db_alan`
- User: `root`
- Password: (kosong)

## Catatan

- Semua path menggunakan konstanta `BASE_URL` (`/abah-laut`) sehingga berjalan dari subfolder htdocs.
- Gambar menu menggunakan placeholder berwarna. Saat upload baru, gambar tersimpan di `uploads/menus/`.
- Data statistik dashboard dihitung langsung dari database (bukan angka palsu permanen).
- Jumlah pesanan yang ditangani petugas adalah indikator aktivitas, bukan otomatis ukuran kualitas kerja.

## Keamanan

- PDO + prepared statements
- Password hashing (bcrypt)
- CSRF token pada form pengubah data
- `htmlspecialchars` pada output
- Validasi role server-side
- Validasi MIME & ukuran upload gambar
- Nama file upload unik
- Harga di-checkout diambil ulang dari database (bukan dari input browser)

---
Abah Laut © 2026



## Database dan pilihan saus

Semua fitur menggunakan satu database baru bernama `db_alan`. File `database/db_alan.sql` membuat tabel aplikasi, tabel `sauces`, serta relasi `menu_sauces`. Database lama tidak dihapus otomatis.

- Kelola kategori: admin dan petugas melalui menu **Kategori**.
- Kelola saus dan harga tambahan: admin melalui menu **Pilihan Saus**.
- Tentukan menu yang menawarkan saus melalui bagian **Atur saus per menu**.
- Menu yang tidak diberi pilihan saus tetap bisa dipesan tanpa saus tambahan.
- Detail pesanan menyimpan nama dan harga saus pada saat transaksi dibuat.

## Upgrade UI & laporan
- Tampilan diperbarui dengan identitas warna ocean-teal, aksen gold, kartu, formulir, tabel, dan layout responsif.
- Halaman operasional (admin/petugas) mendapatkan kontrol **Cetak tabel** dan **Ekspor CSV** otomatis pada tabel yang tersedia. Ekspor mengambil isi tabel yang sedang dirender, sehingga hasilnya mengikuti hasil pencarian/filter yang sedang tampil.
- CSS print menyembunyikan navigasi dan kontrol aplikasi, menggunakan layout laporan A4, dan mengulang header tabel saat pergantian halaman jika didukung browser.
- Fitur cetak menggunakan dialog cetak browser; pilih “Save as PDF” untuk menyimpan sebagai PDF.
- Verifikasi lingkungan: lakukan pengujian integrasi dengan database MySQL dan akun tiap role di server lokal/hosting sebelum dipakai produksi. Fitur yang memerlukan koneksi database tidak dapat dinyatakan lulus hanya dari pemeriksaan file statis.
