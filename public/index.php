<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DLReminder - Pengingat Deadline Akademik</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#9333ea',
                        primaryHover: '#7e22ce',
                        secondary: '#f3e8ff',
                        dark: '#1f2937',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        heading: ['Poppins', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="font-sans text-gray-600 antialiased bg-white">

    <nav id="navbar" class="fixed w-full z-50 transition-all duration-300 glass-nav">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20 items-center">
                <div class="flex items-center gap-2">
                    <div id="logoContainer" class="bg-white/20 text-white p-2 rounded-xl border border-white/30 transition-colors duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <span id="logoText" class="font-heading font-bold text-2xl text-white tracking-tight transition-colors duration-300">DL<span id="logoSuffix" class="text-purple-200">Reminder</span></span>
                </div>
                
                <div class="hidden md:flex space-x-8 items-center">
                    <a href="#fitur" class="nav-link font-heading font-medium text-purple-100 hover:text-white transition-colors duration-300">Fitur</a>
                    <a href="#tentang" class="nav-link font-heading font-medium text-purple-100 hover:text-white transition-colors duration-300">Tentang</a>
                </div>

                <div class="flex items-center gap-4">
                    <a href="login.php" id="loginBtn" class="font-heading font-semibold text-white hover:text-purple-200 transition-colors duration-300">Masuk</a>
                    <a href="login.php" id="registerBtn" class="font-heading bg-white text-primary border border-transparent hover:bg-gray-100 px-6 py-2.5 rounded-full font-bold transition-all shadow-lg transform hover:-translate-y-0.5">
                        Daftar
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <header class="pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden relative bg-gradient-to-b from-purple-800 via-primary to-white">
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[500px] bg-purple-500 rounded-full blur-3xl opacity-30 -z-10 animate-pulse"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center z-10 relative">
            <span class="inline-flex items-center gap-2 py-1.5 px-4 rounded-full bg-white/10 border border-white/20 text-purple-100 text-sm font-heading font-semibold mb-8 backdrop-blur-md">
                <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                Web Pengingat Deadline Akademik
            </span>
            
            <h1 class="font-heading text-5xl md:text-7xl font-extrabold text-white mb-6 leading-tight tracking-tight drop-shadow-sm">
                Kelola Deadline, <br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-200 to-pink-200">
                    Kuasai Semestermu.
                </span>
            </h1>
            
            <p class="text-lg md:text-xl text-purple-100 mb-10 max-w-2xl mx-auto leading-relaxed font-light">
                DLReminder membantu mahasiswa mengatur jadwal tugas, kuis, dan ujian dengan notifikasi otomatis yang terintegrasi.
            </p>
            
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="login.php" class="font-heading bg-white text-primary hover:bg-gray-50 px-8 py-4 rounded-xl font-bold text-lg shadow-xl shadow-purple-900/20 transition transform hover:-translate-y-1">
                    Mulai Sekarang Gratis
                </a>
                <a href="#fitur" class="font-heading bg-transparent border border-purple-200 text-white hover:bg-white/10 px-8 py-4 rounded-xl font-bold text-lg transition flex items-center justify-center gap-2 group">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                    </svg>
                    Pelajari Fitur
                </a>
            </div>
            
            <div class="mt-20 relative mx-auto w-full max-w-5xl animate-float">
                <div class="absolute inset-0 bg-gradient-to-t from-white via-transparent to-transparent z-10 h-32 bottom-0"></div>
                <div class="bg-white border border-gray-200 rounded-2xl shadow-2xl shadow-purple-200 p-2 overflow-hidden ring-1 ring-gray-100">
                    <div class="bg-gray-50 rounded-xl overflow-hidden border border-gray-100">
                         <div class="flex items-center gap-2 px-4 py-3 border-b border-gray-200 bg-white">
                            <div class="flex gap-1.5">
                                <div class="w-3 h-3 rounded-full bg-red-400"></div>
                                <div class="w-3 h-3 rounded-full bg-yellow-400"></div>
                                <div class="w-3 h-3 rounded-full bg-green-400"></div>
                            </div>
                            <div class="ml-4 bg-gray-100 rounded-md px-3 py-1 text-xs text-gray-400 font-mono flex-1 text-center">localhost:8000/dl-reminder</div>
                        </div>
                        <div class="p-6 grid grid-cols-1 md:grid-cols-12 gap-6 text-left">
                            <div class="hidden md:block col-span-2 space-y-3">
                                <div class="h-8 bg-purple-100 rounded-lg w-full"></div>
                                <div class="h-8 bg-white border border-gray-100 rounded-lg w-3/4"></div>
                            </div>
                            <div class="col-span-12 md:col-span-10 grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm col-span-2">
                                    <div class="h-4 bg-gray-100 rounded w-1/3 mb-4"></div>
                                    <div class="h-32 bg-purple-50 rounded-lg flex items-center justify-center text-purple-300">
                                        <div class="text-center">
                                            <div class="text-2xl font-heading font-bold mb-1">Workload Chart</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                                    <div class="h-4 bg-gray-100 rounded w-1/2 mb-4"></div>
                                    <div class="space-y-2">
                                        <div class="h-10 bg-red-50 border-l-4 border-red-400 rounded p-2 text-xs text-red-400 font-heading">Tugas PHP</div>
                                        <div class="h-10 bg-yellow-50 border-l-4 border-yellow-400 rounded p-2 text-xs text-yellow-600 font-heading">Kuis SQL</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <section id="fitur" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="text-primary font-bold tracking-widest uppercase text-xs mb-2 block">Fitur Unggulan</span>
                <h2 class="font-heading text-3xl md:text-4xl font-bold text-dark">Solusi Cerdas Mahasiswa</h2>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-purple-50/50 p-8 rounded-2xl border border-purple-100 hover:border-primary transition duration-300 group">
                    <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center text-primary mb-6 shadow-sm group-hover:bg-primary group-hover:text-white transition">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <h3 class="font-heading text-xl font-bold mb-3 text-gray-800">Sinkronisasi Jadwal</h3>
                    <p class="text-gray-600 text-sm">Terhubung langsung dengan Google Calendar untuk mengimpor jadwal kuliah otomatis.</p>
                </div>
                <div class="bg-purple-50/50 p-8 rounded-2xl border border-purple-100 hover:border-primary transition duration-300 group">
                    <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center text-primary mb-6 shadow-sm group-hover:bg-primary group-hover:text-white transition">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    </div>
                    <h3 class="font-heading text-xl font-bold mb-3 text-gray-800">Pengingat Pintar</h3>
                    <p class="text-gray-600 text-sm">Dapatkan notifikasi deadline via Email sebelum waktu habis.</p>
                </div>
                <div class="bg-purple-50/50 p-8 rounded-2xl border border-purple-100 hover:border-primary transition duration-300 group">
                     <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center text-primary mb-6 shadow-sm group-hover:bg-primary group-hover:text-white transition">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    </div>
                    <h3 class="font-heading text-xl font-bold mb-3 text-gray-800">Analitik Beban Kerja</h3>
                    <p class="text-gray-600 text-sm">Pantau statistik tugas dan ujianmu per semester dalam bentuk grafik yang mudah dipahami.</p>
                </div>
            </div>
        </div>
    </section>

    <footer id="tentang" class="bg-white border-t border-gray-100 pt-16 pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8 mb-12">
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="bg-primary text-white p-1.5 rounded-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <span class="font-heading font-bold text-xl text-dark">DL<span class="text-primary">Reminder</span></span>
                    </div>
                    <p class="text-gray-500 text-sm leading-relaxed max-w-sm mb-4">
                        DLReminder adalah aplikasi web manajemen akademik yang dirancang untuk membantu mahasiswa mengatur jadwal kuliah dan deadline tugas secara efisien.
                    </p>
                    <p class="text-gray-400 text-xs">
                        Dibangun dengan PHP Native, MySQL, dan Tailwind CSS.
                    </p>
                </div>
                
                <div>
                    <h5 class="font-heading font-bold text-dark mb-4">Navigasi</h5>
                    <ul class="space-y-3 text-sm text-gray-500">
                        <li><a href="#" class="hover:text-primary transition">Beranda</a></li>
                        <li><a href="#fitur" class="hover:text-primary transition">Fitur Utama</a></li>
                        <li><a href="login.php" class="hover:text-primary transition">Masuk / Daftar</a></li>
                    </ul>
                </div>
                
                <div>
                    <h5 class="font-heading font-bold text-dark mb-4">Hubungi Kami</h5>
                    <ul class="space-y-3 text-sm text-gray-500">
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            ini pasangin email
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                            aaaa
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-100 pt-8 text-center flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-gray-400 text-sm">&copy; 2025 DLReminder. Hak Cipta Dilindungi.</p>
            </div>
        </div>
    </footer>

    <script>
        const navbar = document.getElementById('navbar');
        const logoContainer = document.getElementById('logoContainer');
        const logoText = document.getElementById('logoText');
        const logoSuffix = document.getElementById('logoSuffix');
        const navLinks = document.querySelectorAll('.nav-link');
        const loginBtn = document.getElementById('loginBtn');
        const registerBtn = document.getElementById('registerBtn');

        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled-nav');
                navbar.classList.remove('glass-nav');

                logoContainer.classList.remove('bg-white/20', 'text-white', 'border-white/30');
                logoContainer.classList.add('bg-primary', 'text-white', 'border-transparent');

                logoText.classList.remove('text-white');
                logoText.classList.add('text-gray-800');
                
                logoSuffix.classList.remove('text-purple-200');
                logoSuffix.classList.add('text-primary');

                navLinks.forEach(link => {
                    link.classList.remove('text-purple-100', 'hover:text-white');
                    link.classList.add('text-gray-600', 'hover:text-primary');
                });

                loginBtn.classList.remove('text-white', 'hover:text-purple-200');
                loginBtn.classList.add('text-gray-600', 'hover:text-primary');

                registerBtn.classList.remove('bg-white', 'text-primary');
                registerBtn.classList.add('bg-primary', 'text-white');

            } else {
                navbar.classList.remove('scrolled-nav');
                navbar.classList.add('glass-nav');

                logoContainer.classList.add('bg-white/20', 'text-white', 'border-white/30');
                logoContainer.classList.remove('bg-primary', 'text-white', 'border-transparent');

                logoText.classList.add('text-white');
                logoText.classList.remove('text-gray-800');
                
                logoSuffix.classList.add('text-purple-200');
                logoSuffix.classList.remove('text-primary');

                navLinks.forEach(link => {
                    link.classList.add('text-purple-100', 'hover:text-white');
                    link.classList.remove('text-gray-600', 'hover:text-primary');
                });

                loginBtn.classList.add('text-white', 'hover:text-purple-200');
                loginBtn.classList.remove('text-gray-600', 'hover:text-primary');

                registerBtn.classList.add('bg-white', 'text-primary');
                registerBtn.classList.remove('bg-primary', 'text-white');
            }
        });
    </script>
</body>
</html>