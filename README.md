# DLReminder - Final Project

DLReminder adalah aplikasi web pengingat deadline akademik yang terintegrasi dengan Google Calendar dan dilengkapi sistem notifikasi email otomatis serta analisis workload.

## Fitur Utama

- Manajemen Event & Tugas: CRUD tugas akademik dengan kategori visual (Dosen/Mahasiswa).
- Google Calendar Sync: Sinkronisasi jadwal otomatis ke kalender pribadi pengguna.
- Notifikasi Otomatis: Mengirim email pengingat (H-2) sebelum deadline tugas berakhir.
- Analitik Beban Kerja: Grafik visual (Workload Analysis) untuk memantau kepadatan tugas per bulan.
- Sistem Keamanan: Login multi-role dan reset password menggunakan OTP.

## 1. Cara Run Lokal

Berikut adalah langkah-langkah untuk menjalankan aplikasi di komputer lokal (Windows/XAMPP):

1. Pastikan XAMPP (modul Apache & MySQL) dan Composer sudah terinstall.
2. Letakkan folder proyek dlreminder ke dalam direktori htdocs (biasanya di C:\xampp\htdocs\).
3. Buka terminal (CMD/PowerShell) di dalam folder proyek tersebut.
4. Jalankan perintah instalasi dependensi:

   composer install

5. Nyalakan Apache dan MySQL di XAMPP Control Panel.
6. Akses aplikasi melalui browser:
   http://localhost/dlreminder

## 2. Cara Import Database

1. Buka phpMyAdmin di browser (http://localhost/phpmyadmin).
2. Buat database baru dengan nama: finalproject
3. Pilih database tersebut, lalu masuk ke tab Import.
4. Pilih file FinalProject.sql yang ada di folder proyek, lalu klik Import.

Catatan: Jika ingin mereset akun admin, Anda dapat menjalankan script setup_admin.php melalui browser.

## 3. Setting .env

Konfigurasi sistem disimpan dalam file environment. Buat file baru bernama .env di folder utama proyek, lalu salin konfigurasi berikut:

# DATABASE
DB_HOST=localhost
DB_DATABASE=finalproject
DB_USERNAME=root
DB_PASSWORD=

# GOOGLE API (Fitur Sync)
GOOGLE_CLIENT_ID=isi_client_id_anda
GOOGLE_CLIENT_SECRET=isi_client_secret_anda
GOOGLE_REDIRECT_URI=http://localhost/dlreminder/google_callback.php

# EMAIL (Fitur Notifikasi & OTP)
SMTP_HOST=smtp.gmail.com
SMTP_EMAIL=email_anda@gmail.com
SMTP_PASSWORD=app_password_anda

(Pastikan SMTP_PASSWORD menggunakan App Password 16 digit dari Google Account).

## 4. Daftar Akun Uji
##Dosen
- dlreminderfp@gmail.com
- 

## Author

- Dimaz Wahyudy
- Septian Eka Putra
Universitas Buana Perjuangan Karawang - 2025