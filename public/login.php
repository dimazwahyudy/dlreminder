<?php
session_start();
if (isset($_SESSION['success'])) {
    echo "<script>alert('{$_SESSION['success']}');</script>";
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    echo "<script>alert('{$_SESSION['error']}');</script>";
    unset($_SESSION['error']);
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk & Daftar - DLReminder</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#9333ea',
                        primaryHover: '#7e22ce',
                    },
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                        heading: ['Poppins', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="login-page">
    <div class="container" id="container">
        
        <div class="form-container sign-up-container">
            <form id="registerForm" action="auth.php" method="POST">
                <input type="hidden" name="action" value="register">
                <h1 class="font-heading font-bold text-2xl mb-2 text-primary">Buat Akun</h1>
                <p class="text-xs text-gray-400 mb-4">Gunakan email kampus untuk akses penuh</p>
                
                <div class="w-full space-y-2.5">
                    <input type="text" name="name" placeholder="Nama Lengkap" class="bg-gray-50 border-none px-4 py-2.5 w-full rounded-lg focus:ring-2 focus:ring-primary focus:outline-none transition text-xs font-medium" />
                    <input type="text" name="nim" placeholder="NIM" class="bg-gray-50 border-none px-4 py-2.5 w-full rounded-lg focus:ring-2 focus:ring-primary focus:outline-none transition text-xs font-medium" />
                    
                    <select id="classSelect" name="kelas" class="bg-gray-50 border-none px-4 py-2.5 w-full rounded-lg focus:ring-2 focus:ring-primary focus:outline-none transition text-xs font-medium text-gray-400 cursor-pointer" required>
                        <option value="" disabled selected>Pilih Kelas</option>
                    </select>
                    <input type="text" name="prodi" placeholder="Prodi" class="bg-gray-50 border-none px-4 py-2.5 w-full rounded-lg focus:ring-2 focus:ring-primary focus:outline-none transition text-xs font-medium" />
                    <input type="email" name="email" placeholder="Email Kampus" class="bg-gray-50 border-none px-4 py-2.5 w-full rounded-lg focus:ring-2 focus:ring-primary focus:outline-none transition text-xs font-medium" />
                    <input type="password" name="password" placeholder="Password" class="bg-gray-50 border-none px-4 py-2.5 w-full rounded-lg focus:ring-2 focus:ring-primary focus:outline-none transition text-xs font-medium" />
                </div>

                <button type="submit" class="mt-6 bg-primary text-white font-bold py-2.5 px-10 rounded-full hover:bg-primaryHover transition shadow-md shadow-purple-200 transform hover:scale-105 tracking-wide text-xs">
                    DAFTAR SEKARANG
                </button>
            </form>
        </div>

        <div class="form-container sign-in-container">
            <form id="loginForm" action="auth.php" method="POST">
                <input type="hidden" name="action" value="login">
                <h1 class="font-heading font-bold text-2xl mb-2 text-gray-800">Selamat Datang</h1>
                <p class="text-xs text-gray-400 mb-6">Silakan masuk ke akun akademik Anda</p>
                
                <div class="w-full space-y-3">
                    <input type="email" name="email" placeholder="Email" class="bg-gray-50 border-none px-4 py-3 w-full rounded-lg focus:ring-2 focus:ring-primary focus:outline-none transition text-xs font-medium" />
                    <input type="password" name="password" placeholder="Password" class="bg-gray-50 border-none px-4 py-3 w-full rounded-lg focus:ring-2 focus:ring-primary focus:outline-none transition text-xs font-medium" />
                </div>

                <a href="forgot_password.php" class="text-xs text-gray-500 mt-4 mb-6 hover:text-primary transition font-medium">Lupa Password?</a>
                
                <button type="submit" class="bg-primary text-white font-bold py-2.5 px-10 rounded-full hover:bg-primaryHover transition shadow-md shadow-purple-200 transform hover:scale-105 tracking-wide text-xs">
                    MASUK
                </button>
            </form>
        </div>

        <div class="overlay-container">
            <div class="overlay">
                
                <div class="overlay-panel overlay-left">
                    <h1 class="font-heading font-bold text-2xl mb-2">Sudah Punya Akun?</h1>
                    <p class="text-xs leading-relaxed max-w-xs opacity-90">
                        Masuk kembali untuk melihat jadwal dan deadline tugasmu.
                    </p>
                    <span class="btn-label">Login Disini</span>
                    <button class="ghost-circle" id="signIn">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                        </svg>
                    </button>
                </div>

                <div class="overlay-panel overlay-right">
                    <h1 class="font-heading font-bold text-2xl mb-2">Belum Punya Akun?</h1>
                    <p class="text-xs leading-relaxed max-w-xs opacity-90">
                        Daftar sekarang untuk mulai mengatur jadwal akademik lebih rapi.
                    </p>
                    <span class="btn-label">Daftar Disini</span>
                    <button class="ghost-circle" id="signUp">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const signUpButton = document.getElementById('signUp');
        const signInButton = document.getElementById('signIn');
        const container = document.getElementById('container');

        signUpButton.addEventListener('click', () => {
            container.classList.add("right-panel-active");
        });

        signInButton.addEventListener('click', () => {
            container.classList.remove("right-panel-active");
        });

        // Populate register class select from server-side `get_classes.php`.
        async function populateRegisterClasses() {
            const sel = document.getElementById('classSelect');
            if (!sel) return;
            // placeholder while loading
            sel.innerHTML = '<option value="" disabled selected>Memuat kelas...</option>';
            try {
                const res = await fetch('get_classes.php');
                if (!res.ok) {
                    sel.innerHTML = '<option value="" disabled selected>Pilih Kelas</option>';
                    return;
                }
                const data = await res.json();
                sel.innerHTML = '<option value="" disabled selected>Pilih Kelas</option>';
                if (Array.isArray(data)) {
                    data.forEach(c => {
                        const opt = document.createElement('option');
                        // keep value as kode_kelas to match existing register handling
                        opt.value = c.kode_kelas || c.id || '';
                        opt.textContent = (c.kode_kelas ? c.kode_kelas : c.id) + (c.nama_kelas ? (' - ' + c.nama_kelas) : '');
                        opt.className = 'text-gray-700';
                        sel.appendChild(opt);
                    });
                }
            } catch (err) {
                console.error('populateRegisterClasses error', err);
                sel.innerHTML = '<option value="" disabled selected>Pilih Kelas</option>';
            }
        }

        // Run once since script is at end of body
        populateRegisterClasses();
    </script>

</body>
</html>