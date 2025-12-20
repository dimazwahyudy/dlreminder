<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f3e8ff; }
        .hidden { display: none; }
        .fade-in { animation: fadeIn 0.5s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="flex justify-center items-center h-screen px-4">

    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md text-center transition-all duration-300">
        <h2 class="text-2xl font-bold text-purple-700 mb-2">Reset Password</h2>
        <p class="text-sm text-gray-500 mb-6" id="instructionText">Masukkan email untuk menerima kode verifikasi.</p>
        
        <form id="forgotForm" class="space-y-4">
            
            <div id="stepEmail">
                <div class="relative">
                    <input type="email" id="email" placeholder="Email Kampus" class="w-full bg-gray-50 px-4 py-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-purple-500 outline-none transition" required>
                    <div id="emailCheck" class="hidden absolute right-3 top-3 text-green-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    </div>
                </div>
                <button type="submit" id="btnKirim" class="w-full mt-4 bg-purple-600 text-white font-bold py-3 rounded-full hover:bg-purple-700 transition shadow-md">
                    KIRIM KODE
                </button>
            </div>

            <div id="stepOtp" class="hidden fade-in space-y-4 pt-2 border-t border-gray-100 mt-4">
                <p class="text-xs text-green-600 font-medium bg-green-50 py-2 rounded-lg">
                    Kode terkirim! Cek email Anda.
                </p>
                
                <input type="text" id="otp" placeholder="Kode 6 Digit" maxlength="6" class="w-full bg-gray-50 px-4 py-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-purple-500 outline-none text-center text-xl tracking-widest font-bold font-mono">
                
                <button type="submit" id="btnVerifikasi" class="w-full bg-purple-600 text-white font-bold py-3 rounded-full hover:bg-purple-700 transition shadow-md">
                    VERIFIKASI KODE
                </button>
                
                <button type="button" id="btnUlang" class="text-xs text-gray-400 hover:text-purple-600 underline">
                    Kirim Ulang Kode
                </button>
            </div>

        </form>
        
        <a href="login.php" class="block mt-6 text-sm text-gray-400 hover:text-purple-600 font-medium">Kembali ke Login</a>
    </div>

    <script>
        // JavaScript akan ditambahkan kembali setelah diperbaiki sepenuhnya
        const btnKirim = document.getElementById('btnKirim');
        const btnVerifikasi = document.getElementById('btnVerifikasi');
        const stepEmail = document.getElementById('stepEmail');
        const stepOtp = document.getElementById('stepOtp');
        const emailInput = document.getElementById('email');
        const emailCheck = document.getElementById('emailCheck');
        const btnUlang = document.getElementById('btnUlang');

        // Helper function to reset button state
        function resetButton(button, text) {
            button.innerText = text;
            button.disabled = false;
            button.classList.remove('opacity-70', 'cursor-not-allowed');
        }

        // KIRIM KODE OTP
        async function sendOtp() {
            const email = emailInput.value.trim();
            if (!email) {
                alert("Harap isi email!");
                return;
            }

            // Loading state
            btnKirim.innerText = "Mengirim...";
            btnKirim.disabled = true;
            btnKirim.classList.add('opacity-70', 'cursor-not-allowed');

            const fd = new FormData();
            fd.append('action', 'request_otp');
            fd.append('email', email);

            try {
                const res = await fetch('password_api.php', { method: 'POST', body: fd });
                const text = await res.text();

                try {
                    const data = JSON.parse(text);
                    if (data.status) {
                        emailInput.readOnly = true;
                        emailInput.classList.add('bg-gray-100', 'text-gray-500');
                        btnKirim.classList.add('hidden');
                        emailCheck.classList.remove('hidden');
                        stepOtp.classList.remove('hidden');
                        document.getElementById('otp').focus();
                    } else {
                        alert(data.message);
                        resetButton(btnKirim, "KIRIM KODE");
                    }
                } catch (err) {
                    console.error("Invalid JSON response:", text);
                    alert("Respons server tidak valid. Cek konsol untuk detail.");
                    resetButton(btnKirim, "KIRIM KODE");
                }
            } catch (err) {
                console.error("Error parsing response or server issue:", err);
                alert("Terjadi kesalahan pada server. Cek konsol untuk detail.");
                resetButton(btnKirim, "KIRIM KODE");
            }
        }

        // VERIFIKASI KODE
        async function verifyOtp() {
            const email = emailInput.value;
            const otp = document.getElementById('otp').value;

            if (otp.length < 6) {
                alert("Kode harus 6 digit!");
                return;
            }

            btnVerifikasi.innerText = "Memverifikasi...";
            btnVerifikasi.disabled = true;

            const fd = new FormData();
            fd.append('action', 'verify_otp');
            fd.append('email', email);
            fd.append('otp', otp);

            try {
                const res = await fetch('password_api.php', { method: 'POST', body: fd });
                const text = await res.text();
                console.log("Raw response:", text);

                try {
                    const data = JSON.parse(text);
                    if (data.status) {
                        window.location.href = `reset_password.php?email=${encodeURIComponent(email)}&token=${otp}`;
                    } else {
                        alert(data.message);
                        resetButton(btnVerifikasi, "VERIFIKASI KODE");
                    }
                } catch (err) {
                    console.error("Invalid JSON response:", text);
                    alert("Respons server tidak valid. Cek konsol untuk detail.");
                    resetButton(btnVerifikasi, "VERIFIKASI KODE");
                }
            } catch (err) {
                console.error("Error parsing response or server issue:", err);
                alert("Terjadi kesalahan pada server. Cek konsol untuk detail.");
                resetButton(btnVerifikasi, "VERIFIKASI KODE");
            }
        }

        // Event listener untuk form
        btnKirim.addEventListener('click', (e) => {
            e.preventDefault();
            sendOtp();
        });

        document.getElementById('forgotForm').addEventListener('submit', (e) => {
            e.preventDefault();
            verifyOtp();
        });

        // Fitur Kirim Ulang
        btnUlang.addEventListener('click', () => {
            stepOtp.classList.add('hidden');
            btnKirim.classList.remove('hidden');
            emailInput.readOnly = false;
            emailInput.classList.remove('bg-gray-100', 'text-gray-500');
            emailCheck.classList.add('hidden');
            resetButton(btnKirim, "KIRIM KODE");
            sendOtp();
        });
    </script>
</body>
</html>