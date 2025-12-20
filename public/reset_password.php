<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body{font-family:'Poppins',sans-serif;background:#f3e8ff}</style>
</head>
<body class="flex justify-center items-center h-screen px-4">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md text-center">
        <h2 class="text-2xl font-bold text-purple-700 mb-2">Buat Password Baru</h2>
        <p class="text-sm text-gray-500 mb-6">Silakan masukkan password baru untuk akun Anda.</p>
        
        <form id="resetForm" class="space-y-4">
            <div class="relative">
                <input type="password" id="new_pass" placeholder="Password Baru" class="w-full bg-gray-50 px-4 py-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-purple-500 outline-none" required>
            </div>
            <div class="relative">
                <input type="password" id="confirm_pass" placeholder="Konfirmasi Password" class="w-full bg-gray-50 px-4 py-3 rounded-lg border border-gray-200 focus:ring-2 focus:ring-purple-500 outline-none" required>
            </div>
            
            <button type="submit" id="btnSimpan" class="w-full bg-purple-600 text-white font-bold py-3 rounded-full hover:bg-purple-700 transition shadow-md">
                SIMPAN PASSWORD
            </button>
        </form>
    </div>

    <script>
        // 1. Ambil Email & Token dari URL
        const urlParams = new URLSearchParams(window.location.search);
        const email = urlParams.get('email');
        const token = urlParams.get('token'); // Token ini adalah OTP yang tadi dimasukkan

        // Jika akses langsung tanpa link, tendang ke login
        if(!email || !token) { 
            alert("Sesi tidak valid/kadaluarsa."); 
            window.location.href='login.php'; 
        }

        // 2. Proses Simpan Password
        document.getElementById('resetForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const p1 = document.getElementById('new_pass').value;
            const p2 = document.getElementById('confirm_pass').value;
            const btn = document.getElementById('btnSimpan');

            // Validasi sederhana
            if(p1.length < 6) return alert("Password minimal 6 karakter!");
            if(p1 !== p2) return alert("Konfirmasi password tidak cocok!");

            btn.innerText = "Menyimpan...";
            btn.disabled = true;

            const fd = new FormData();
            fd.append('action', 'reset_password');
            fd.append('email', email);
            fd.append('otp', token); // Kirim token sebagai otp untuk verifikasi akhir
            fd.append('new_password', p1);

            try {
                const res = await fetch('password_api.php', { method: 'POST', body: fd });
                const text = await res.text();
                console.log('Raw response:', text);

                let data;
                try {
                    data = JSON.parse(text);
                } catch (err) {
                    alert('Respons server tidak valid. Cek konsol untuk detail.');
                    console.error('Invalid JSON response:', text);
                    btn.innerText = "SIMPAN PASSWORD";
                    btn.disabled = false;
                    return;
                }

                if (data.status) {
                    alert("Berhasil! " + data.message);
                    window.location.href = 'login.php';
                } else {
                    alert("Gagal: " + data.message);
                    btn.innerText = "SIMPAN PASSWORD";
                    btn.disabled = false;
                }
            } catch (err) {
                console.error('Fetch error:', err);
                alert("Terjadi kesalahan koneksi. Cek konsol untuk detail.");
                btn.innerText = "SIMPAN PASSWORD";
                btn.disabled = false;
            }
        });
    </script>
</body>
</html>