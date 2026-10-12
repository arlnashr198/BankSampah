CARA MEMINDAHKAN PROJECT BANK SAMPAH ARLIS

1. Ekstrak folder banksampah_arlis ke:
   C:\xampp\htdocs\

2. Jalankan Apache dan MySQL di XAMPP.

3. Buka phpMyAdmin.

4. Buat database bernama:
   banksampah_arlis

5. Import:
   Database\banksampah_arlis.sql

   Jika database yang dipakai SUDAH ADA dari versi sebelumnya,
   jangan import ulang SQL full. Jalankan hanya:
   Database\migrasi_pending.sql

6. Buka:
   http://localhost/banksampah_arlis/

ROLE:
- Admin
- Siswa
- Guru
- Pengepul

Catatan:
- Guru memiliki saldo dan dapat mengajukan penarikan saldo.
- Pengepul memiliki saldo dan dapat mengajukan penarikan saldo.
- Admin tidak menggunakan saldo.
- Setoran siswa/guru harus menunggu ACC admin sebelum saldo dan stok berubah.
- Pembelian pengepul harus menunggu ACC admin sebelum saldo pengepul dan stok berubah.
