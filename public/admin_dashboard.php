<?php
session_start();
require __DIR__ .  '/../config/config.php';

// Cek Login & Role Admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}
$user = $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script> tailwind.config = { theme: { extend: { colors: { primary: '#9333ea', primaryHover: '#7e22ce' }, fontFamily: { sans: ['Inter'], heading: ['Poppins'] } } } } </script>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .loader { display:inline-block; width:14px; height:14px; border:2px solid rgba(0,0,0,0.12); border-top-color:rgba(0,0,0,0.6); border-radius:9999px; vertical-align:middle; margin-right:6px; animation:spin 1s linear infinite; }
        @keyframes spin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }
        .btn-disabled { opacity:0.7; cursor:not-allowed; }
    </style>
</head>
<body class="bg-gray-50 font-sans text-gray-600">

    <nav class="bg-white border-b sticky top-0 z-40 px-8 h-20 flex justify-between items-center shadow-sm">
        <div class="flex items-center gap-2" onclick="window.location.href='index.php'">
            <div class="bg-primary text-white p-1.5 rounded-lg"><svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg></div>
            <span class="font-heading font-bold text-xl text-gray-800">DLR<span class="text-primary">Admin</span></span>
        </div>
        <div class="flex items-center gap-4">
    <button onclick="openListModal()" class="text-sm font-medium text-gray-600 hover:text-primary transition flex items-center gap-1">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
        Data Pengguna
    </button>
    <div class="h-6 w-px bg-gray-200"></div>
    
    <div class="relative">
        <button onclick="toggleProfileMenu()" class="flex items-center gap-3 focus:outline-none hover:bg-gray-50 p-2 rounded-xl transition">
            <div class="text-right hidden sm:block">
                <div class="font-heading font-bold text-sm text-gray-800" id="navUserName"><?php echo htmlspecialchars($user['name']); ?></div>
                <div class="text-xs text-primary font-bold uppercase"><?php echo htmlspecialchars($user['role']); ?></div>
            </div>
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-purple-100 to-purple-300 flex items-center justify-center text-primary font-bold border border-purple-200 shadow-sm">
                <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
            </div>
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </button>

        <div id="profileMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden z-50 transform origin-top-right transition-all">
            <div class="px-4 py-3 border-b border-gray-50 bg-gray-50">
                <p class="text-xs text-gray-500">Login sebagai</p>
                <p class="text-sm font-bold text-gray-800 truncate"><?php echo htmlspecialchars($user['name']); ?></p>
            </div>
            <a href="#" onclick="openProfileModal()" class="block px-4 py-2 text-sm text-gray-700 hover:bg-purple-50 hover:text-primary transition">Edit Profil</a>
            <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition">Logout</a>
        </div>
    </div>
    </div>
    </nav>

    <main class="max-w-7xl mx-auto px-8 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-bold font-heading text-gray-800">Dashboard Manajemen</h1>
            <div class="flex gap-3">
                <button onclick="document.getElementById('classModal').classList.remove('hidden'); document.getElementById('classModal').classList.add('flex');" class="bg-white text-purple-600 border border-purple-200 px-4 py-2 rounded-lg text-sm font-bold shadow-sm hover:bg-purple-50">+ Kelas</button>
                <button onclick="openCreateModal()" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-bold shadow hover:bg-primaryHover">+ Event Baru</button>
            </div>
        </div>

        <!-- Semester Analytics (same as user dashboard) -->
        <div class="w-full bg-white p-4 rounded-2xl shadow-sm border border-gray-100 mb-6">
            <div class="flex justify-between items-center mb-3">
                <h3 class="font-heading text-lg font-bold">Analitik: Beban Kerja Semester</h3>
                <div class="flex items-center gap-2">
                    <select id="adminAnalyticsYear" class="border px-2 py-1 rounded text-sm"></select>
                    <select id="adminAnalyticsSem" class="border px-2 py-1 rounded text-sm"><option value="1">Semester 1 (Jan–Jun)</option><option value="2">Semester 2 (Jul–Dec)</option></select>
                    <button id="adminDownloadCsv" class="text-sm bg-gray-100 px-3 py-1 rounded border">Unduh CSV</button>
                </div>
            </div>
            <div id="adminAnalyticsCards" class="grid grid-cols-2 md:grid-cols-4 gap-3"></div>
            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <canvas id="adminBarChart" class="w-full h-44"></canvas>
                </div>
                <div>
                    <canvas id="adminPieChart" class="w-full h-44"></canvas>
                </div>
            </div>
            <div id="adminAnalyticsNotes" class="mt-3 text-sm text-gray-600"></div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <div id="calendar"></div>
            </div>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 max-h-[70vh] overflow-auto">
                <h3 class="font-heading text-lg font-bold mb-4">Tugas Admin</h3>
                <div id="adminTaskList" class="space-y-3 text-sm text-gray-500">Memuat...</div>
            </div>
        </div>
    </main>

    <div id="listModal" class="fixed inset-0 hidden items-center justify-center bg-black/50 z-50 p-4">
        <div class="bg-white rounded-2xl w-full max-w-5xl h-[85vh] flex flex-col shadow-2xl">
            <div class="p-6 border-b flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-xl">Data Pengguna</h3>
                <button onclick="document.getElementById('listModal').classList.remove('flex');document.getElementById('listModal').classList.add('hidden');">X</button>
            </div>
            <div class="flex border-b px-6 justify-between items-center">
                <div>
                    <button onclick="loadUsers('mahasiswa')" id="tabMhs" class="py-3 px-6 text-sm font-bold text-primary border-b-2 border-primary">Mahasiswa</button>
                    <button onclick="loadUsers('dosen')" id="tabDosen" class="py-3 px-6 text-sm text-gray-500">Dosen</button>
                </div>
                <div>
                    <button id="btnAddUser" onclick="openUserForm('create')" class="py-2 px-3 text-sm bg-primary text-white rounded">Tambah</button>
                </div>
            </div>
            <div class="flex-1 overflow-y-auto p-6">
                <table class="w-full text-left text-sm text-gray-600"><tbody id="userTableBody"></tbody></table>
            </div>
        </div>
    </div>
    
    <div id="formUserModal" class="fixed inset-0 hidden items-center justify-center bg-black/60 z-[60]">
        <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-2xl">
            <h3 id="formTitle" class="font-bold text-lg mb-4 text-primary">User</h3>
            <form id="userForm">
                <input type="hidden" name="action" id="formAction">
                <input type="hidden" name="role" id="formRole">
                <input type="hidden" name="id" id="userId">
                <div class="space-y-3"><div>
                    <label class="text-xs font-bold text-gray-500">Nama</label>
                    <input name="name" id="inpName" class="w-full border px-3 py-2 rounded-lg" required>
                </div>
                <div>
                    <label class="text-xs font-bold text-gray-500">Email</label>
                    <input name="email" id="inpEmail" class="w-full border px-3 py-2 rounded-lg" required>
                </div>
                <div>
                    <label class="text-xs font-bold text-gray-500">Password</label>
                    <input name="password" id="inpPassword" type="password" class="w-full border px-3 py-2 rounded-lg">
                </div>
                    <div id="fieldMhs" class="grid grid-cols-2 gap-3">
                        <div>
                        <label class="text-xs font-bold text-gray-500">NIM</label>
                        <input name="nim" id="inpNim" class="w-full border px-3 py-2 rounded-lg">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-500">Kelas</label>
                            <select name="kelas_id" id="inpKelas" class="w-full border px-3 py-2 rounded-lg bg-white"></select>
                        </div>
                        <div class="col-span-2">
                            <label class="text-xs font-bold text-gray-500">Prodi</label>
                            <input name="prodi" id="inpProdi" class="w-full border px-3 py-2 rounded-lg">
                        </div>
                    </div>
                    <div id="fieldDosen" class="hidden">
                        <label class="text-xs font-bold text-gray-500">NIDN</label>
                        <input name="no_induk" id="inpNoInduk" class="w-full border px-3 py-2 rounded-lg">
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('formUserModal').classList.add('hidden');document.getElementById('formUserModal').classList.remove('flex');" class="px-4 py-2 rounded border">Batal</button><button type="submit" class="px-4 py-2 rounded bg-primary text-white font-bold">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div id="eventModal" class="fixed inset-0 hidden items-center justify-center bg-black/50 z-50">
        <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow-2xl">
            <h3 class="font-bold text-lg mb-4 text-primary">Buat Event Global</h3>
            <form id="eventForm">
                <input type="hidden" name="action" value="create">
                <div class="space-y-3">
                    <input name="title" placeholder="Judul Event" class="w-full border px-4 py-2 rounded-lg" required>
                    <textarea name="description" placeholder="Deskripsi" class="w-full border px-4 py-2 rounded-lg"></textarea>
                    <div class="grid grid-cols-2 gap-3"><input name="start" type="datetime-local" class="border px-3 py-2 rounded-lg" required><input name="end" type="datetime-local" class="border px-3 py-2 rounded-lg" required></div>
                    <div class="bg-gray-50 p-3 rounded-lg border"><p class="text-xs font-bold text-gray-500 mb-2 uppercase">Kirim Kepada:</p><div class="flex gap-4"><label class="flex items-center gap-2 text-sm"><input type="checkbox" name="chk_dosen" class="rounded"> Dosen</label><label class="flex items-center gap-2 text-sm"><input type="checkbox" name="chk_mahasiswa" class="rounded"> Mahasiswa</label></div></div>
                </div>
                <div class="mt-4 flex justify-end gap-2"><button type="button" onclick="document.getElementById('eventModal').classList.remove('flex');document.getElementById('eventModal').classList.add('hidden');" class="px-4 py-2 rounded border">Batal</button><button type="submit" class="px-4 py-2 rounded bg-primary text-white font-bold">Simpan</button></div>
            </form>
        </div>
    </div>

    <div id="editEventModal" class="fixed inset-0 hidden items-center justify-center bg-black/50 z-[60]">
        <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-2xl">
            <h3 class="font-bold text-lg mb-4 text-primary">Edit Event</h3>
            <form id="editEventForm">
                <input type="hidden" name="action" value="update"><input type="hidden" name="id" id="editEventId">
                <div class="space-y-3">
                    <div><label class="text-xs font-bold text-gray-500">Judul</label><input name="title" id="editEventTitle" class="w-full border px-3 py-2 rounded-lg" required></div>
                    <div><label class="text-xs font-bold text-gray-500">Deskripsi</label><textarea name="description" id="editEventDesc" class="w-full border px-3 py-2 rounded-lg h-24"></textarea></div>
                </div>
                <div class="mt-4 flex justify-end gap-2"><button type="button" onclick="document.getElementById('editEventModal').classList.remove('flex');document.getElementById('editEventModal').classList.add('hidden');" class="px-4 py-2 rounded border">Batal</button><button type="submit" class="px-4 py-2 rounded bg-primary text-white font-bold">Simpan</button></div>
            </form>
        </div>
    </div>

    <div id="eventDetailModal" class="fixed inset-0 hidden items-center justify-center bg-black/50 z-50">
        <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-2xl relative">
            <button onclick="document.getElementById('eventDetailModal').classList.remove('flex');document.getElementById('eventDetailModal').classList.add('hidden');" class="absolute top-4 right-4 text-gray-400">X</button>
            <h3 id="detailTitle" class="font-bold text-xl mb-1"></h3>
            <div class="flex items-center gap-2 mb-4"><span id="detailBadge" class="px-2 py-0.5 rounded text-xs font-bold text-white"></span><span id="detailCreator" class="text-xs text-gray-500"></span></div>
            <div class="bg-gray-50 p-4 rounded-lg border mb-4"><p id="detailTime" class="text-sm font-bold mb-2"></p><p id="detailDesc" class="text-sm"></p></div>
            <div id="creatorActions" class="hidden flex justify-end gap-2 pt-2 border-t">
                <button id="btnEditEvent" class="px-3 py-1.5 rounded text-xs font-bold text-blue-600 border border-blue-200 hover:bg-blue-50">Edit</button>
                <button id="btnDeleteEvent" class="px-3 py-1.5 rounded text-xs font-bold text-red-600 border border-red-200 hover:bg-red-50">Hapus</button>
            </div>
        </div>
    </div>

    <div id="classModal" class="fixed inset-0 hidden items-center justify-center bg-black/50 z-50 p-4">
        <div class="bg-white rounded-2xl w-full max-w-2xl h-[75vh] flex flex-col shadow-2xl">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="font-bold text-lg">Kelas</h3>
                <button onclick="document.getElementById('classModal').classList.remove('flex');document.getElementById('classModal').classList.add('hidden');">X</button>
            </div>
            <div class="p-4 flex gap-6 flex-1 overflow-auto">
                <div class="w-1/2">
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="font-bold">Daftar Kelas</h4>
                        <button id="btnAddClass" class="px-3 py-1 rounded bg-primary text-white">Tambah Kelas</button>
                    </div>
                    <div id="classListContainer" class="space-y-2 overflow-auto"></div>
                </div>
                <div class="w-1/2">
                    <div id="classFormWrapper" class="hidden">
                        <h4 id="classFormTitle" class="font-bold mb-2">Tambah Kelas</h4>
                        <form id="classForm">
                            <input type="hidden" name="id" />
                            <input name="kode_kelas" placeholder="Kode" class="w-full border px-4 py-2 rounded-lg mb-2" required>
                            <input name="nama_kelas" placeholder="Nama" class="w-full border px-4 py-2 rounded-lg" required>
                            <div class="mt-4"><button type="button" id="btnCancelClass" class="px-4 py-2 rounded border mr-2">Batal</button><button type="submit" id="btnSaveClass" class="px-4 py-2 rounded bg-purple-600 text-white font-bold">Simpan</button></div>
                        </form>
                    </div>
                    <div id="classEmptyNote" class="text-sm text-gray-500">Pilih "Tambah Kelas" untuk membuat kelas baru.</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const CURRENT_USER = <?php echo json_encode($user); ?>;
        let currentRoleTab = 'mahasiswa';
        let CLASSES = null;

        function setButtonLoading(btn, loading, label) {
            if (!btn) return;
            try { if (loading && !btn.dataset._orig) btn.dataset._orig = btn.innerHTML; } catch(e){}
            if (loading) {
                btn.disabled = true; btn.classList.add('btn-disabled');
                const txt = label || btn.textContent || '';
                btn.innerHTML = `<span class="loader"></span>${txt}`;
            } else {
                btn.disabled = false; btn.classList.remove('btn-disabled');
                if (btn.dataset && btn.dataset._orig) { btn.innerHTML = btn.dataset._orig; delete btn.dataset._orig; }
            }
        }

        function ensureClassesLoaded() {
            return new Promise((resolve, reject) => {
                if (Array.isArray(CLASSES) && CLASSES.length>0) return resolve(CLASSES);
                fetch('get_classes.php').then(r=>r.json()).then(data=>{
                    CLASSES = data || [];
                    const sel = document.getElementById('inpKelas');
                    if (sel) {
                        // always provide a placeholder option so the select shows something
                        sel.innerHTML = `<option value="">-- Pilih Kelas --</option>`;
                        CLASSES.forEach(c=> sel.innerHTML += `<option value="${c.id}">${c.kode_kelas} - ${c.nama_kelas}</option>`);
                    }
                    resolve(CLASSES);
                }).catch(err=>{ CLASSES = []; resolve(CLASSES); });
            });
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            var calendar = new FullCalendar.Calendar(document.getElementById('calendar'), {
                initialView: 'dayGridMonth', headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listWeek' },
                events: [], height: 'auto',
                dateClick: function(info) { openCreateModal(info.dateStr); },
                eventClick: function(info) { showEventDetail(info.event); }
            });
            calendar.render();

            fetch('event_api.php').then(r=>r.json()).then(data=>{
                if(data.status) {
                    // FIX: Menambahkan pemetaan 'id: e.id' agar ID terbaca oleh script delete/edit
                    const events = data.events.map(e=>({
                        id: e.id, // <--- INI YANG SEBELUMNYA KURANG
                        title: e.title, 
                        start: e.start, 
                        end: e.end, 
                        color: e.visibility==='all'?'#ef4444':'#9333ea', 
                        extendedProps:{...e}
                    }));
                    calendar.addEventSource(events);
                    
                    const listEl = document.getElementById('adminTaskList');
                    const myTasks = events.filter(e=>e.extendedProps.created_by==CURRENT_USER.id);
                    listEl.innerHTML = myTasks.length===0 ? '<p class="text-gray-400 italic">Belum ada tugas.</p>' : '';
                    myTasks.forEach(ev=>{ listEl.innerHTML += `<div class="p-2 bg-gray-50 border rounded mb-2 text-xs font-bold cursor-pointer hover:bg-gray-100" onclick="alert('${ev.title}')">${ev.title}</div>`; });
                }
            });

            // Edit & Delete Logic
            window.showEventDetail = function(event) {
                const props = event.extendedProps;
                document.getElementById('detailTitle').textContent = event.title;
                document.getElementById('detailCreator').textContent = 'Oleh: ' + (props.creator_name||'System');
                document.getElementById('detailTime').textContent = new Date(event.start).toLocaleString('id-ID');
                document.getElementById('detailDesc').textContent = props.description||"-";
                document.getElementById('detailBadge').textContent = props.visibility;
                document.getElementById('detailBadge').style.backgroundColor = event.backgroundColor;

                const actionsDiv = document.getElementById('creatorActions');
                if (parseInt(props.created_by) === parseInt(CURRENT_USER.id)) {
                    actionsDiv.classList.remove('hidden');
                    // Kirim event.id (yang sekarang sudah ada) ke fungsi delete
                    document.getElementById('btnDeleteEvent').onclick = () => deleteEvent(event.id);
                    document.getElementById('btnEditEvent').onclick = () => {
                        document.getElementById('eventDetailModal').classList.remove('flex');
                        document.getElementById('eventDetailModal').classList.add('hidden');
                        openEditModal(event.id, event.title, props.description);
                    };
                } else { actionsDiv.classList.add('hidden'); }
                
                document.getElementById('eventDetailModal').classList.remove('hidden');
                document.getElementById('eventDetailModal').classList.add('flex');
            }

            function openEditModal(id, title, desc) {
                document.getElementById('editEventId').value = id;
                document.getElementById('editEventTitle').value = title;
                document.getElementById('editEventDesc').value = desc||'';
                document.getElementById('editEventModal').classList.remove('hidden');
                document.getElementById('editEventModal').classList.add('flex');
            }

            async function deleteEvent(id) {
                if(!confirm("Yakin hapus?")) return;
                const btn = document.getElementById('btnDeleteEvent');
                try {
                    setButtonLoading(btn, true, 'Menghapus...');
                    const res = await fetch(`event_api.php?id=${id}`, { method: 'DELETE' });
                    const data = await res.json();
                    alert(data.message);
                    if(data.status) location.reload();
                } catch (err) {
                    console.error('deleteEvent error', err); alert('Error saat menghapus event');
                } finally {
                    setButtonLoading(btn, false);
                }
            }

            document.getElementById('editEventForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                const submitBtn = this.querySelector('button[type="submit"]');
                try {
                    setButtonLoading(submitBtn, true, 'Menyimpan...');
                    const res = await fetch('event_api.php', { method: 'POST', body: new FormData(this) });
                    const data = await res.json();
                    alert(data.message);
                    if(data.status) location.reload();
                } catch (err) {
                    console.error('editEventForm submit error', err); alert('Error saat menyimpan perubahan');
                } finally {
                    setButtonLoading(submitBtn, false);
                }
            });

            // Standard Create Logic
            window.openCreateModal = function(dateStr='') {
                document.getElementById('eventForm').reset();
                if(dateStr) {
                    document.querySelector('input[name="start"]').value = dateStr+'T09:00';
                    document.querySelector('input[name="end"]').value = dateStr+'T10:00';
                }
                document.getElementById('eventModal').classList.remove('hidden');
                document.getElementById('eventModal').classList.add('flex');
            }
            document.getElementById('eventForm').addEventListener('submit', async function(e){
                e.preventDefault();
                const submitBtn = this.querySelector('button[type="submit"]');
                try {
                    setButtonLoading(submitBtn, true, 'Menyimpan...');
                    const fd = new FormData(this);
                    if(this.querySelector('input[name="chk_dosen"]')?.checked) fd.append('chk_dosen','1');
                    if(this.querySelector('input[name="chk_mahasiswa"]')?.checked) fd.append('chk_mahasiswa','1');
                    const res = await fetch('event_api.php', { method: 'POST', body: fd });
                    const data = await res.json();
                    alert(data.message);
                    if(data.status) location.reload();
                } catch (err) {
                    console.error('eventForm submit error', err); alert('Error saat menyimpan event');
                } finally {
                    setButtonLoading(submitBtn, false);
                }
            });
            
            // List Users Logic
            window.openListModal = function() {
                document.getElementById('listModal').classList.remove('hidden');
                document.getElementById('listModal').classList.add('flex');
                loadUsers('mahasiswa');
            }
            window.loadUsers = async function(type) {
                currentRoleTab = type;
                const tabM = document.getElementById('tabMhs'); const tabD = document.getElementById('tabDosen');
                if(type=='mahasiswa'){ tabM.className="py-3 px-6 text-sm font-bold text-primary border-b-2 border-primary"; tabD.className="py-3 px-6 text-sm text-gray-500 hover:text-primary"; }
                else { tabD.className="py-3 px-6 text-sm font-bold text-primary border-b-2 border-primary"; tabM.className="py-3 px-6 text-sm text-gray-500 hover:text-primary"; }

                const tbody = document.getElementById('userTableBody');
                tbody.innerHTML = '<tr><td class="p-2">Loading...</td></tr>';
                const res = await fetch(`user_api.php?type=${type}`);
                const data = await res.json();
                tbody.innerHTML = '';
                if(data.length===0) tbody.innerHTML = '<tr><td colspan="5" class="p-4 text-center">Data kosong.</td></tr>';
                
                data.forEach(u => {
                    // include kode_kelas too in case backend doesn't send kelas_id
                    const rowData = { id: u.id, name: u.name, email: u.email, nim: u.nim || '', no_induk: u.no_induk || '', prodi: u.prodi || '', kelas_id: u.kelas_id || '', kode_kelas: u.kode_kelas || '' };
                    let btn = `<button onclick='openUserForm("edit", ${JSON.stringify(rowData)})' class="text-blue-500 mr-2">Edit</button> <button onclick="deleteUser(${u.id}, this)" class="text-red-500">Hapus</button>`;
                    tbody.innerHTML += `<tr class="border-b"><td class="p-3">${u.name}</td><td class="p-3 text-gray-500">${u.email}</td><td class="p-3">${u.nim||u.no_induk}</td><td class="p-3">${u.kode_kelas||'-'}</td><td class="p-3 text-right">${btn}</td></tr>`;
                });
            }

            // User Form Logic
            window.openUserForm = async function(action, data=null) {
                await ensureClassesLoaded();
                const form = document.getElementById('userForm'); form.reset();
                document.getElementById('formAction').value = action;
                document.getElementById('formRole').value = currentRoleTab;
                document.getElementById('formTitle').innerText = action==='create' ? 'Tambah User' : 'Edit User';
                
                if(currentRoleTab==='mahasiswa'){ document.getElementById('fieldMhs').classList.remove('hidden'); document.getElementById('fieldMhs').classList.add('grid'); document.getElementById('fieldDosen').classList.add('hidden'); }
                else { document.getElementById('fieldMhs').classList.add('hidden'); document.getElementById('fieldMhs').classList.remove('grid'); document.getElementById('fieldDosen').classList.remove('hidden'); }

                if(action==='edit' && data){
                    document.getElementById('userId').value = data.id;
                    document.getElementById('inpName').value = data.name;
                    document.getElementById('inpEmail').value = data.email;
                    if(data.nim) {
                        document.getElementById('inpNim').value = data.nim;
                        document.getElementById('inpProdi').value = data.prodi;
                        // prefer kelas_id; if missing, map from kode_kelas using CLASSES cache
                        if (data.kelas_id) {
                            document.getElementById('inpKelas').value = data.kelas_id;
                        } else if (data.kode_kelas && Array.isArray(CLASSES)) {
                            const found = CLASSES.find(c => c.kode_kelas === data.kode_kelas);
                            if (found) document.getElementById('inpKelas').value = found.id;
                        }
                    }
                    if(data.no_induk) document.getElementById('inpNoInduk').value = data.no_induk;
                }
                document.getElementById('formUserModal').classList.remove('hidden'); document.getElementById('formUserModal').classList.add('flex');
            }

            window.deleteUser = async function(id, btnEl) {
                if(!confirm("Yakin hapus user ini?")) return;
                try {
                    if (btnEl) setButtonLoading(btnEl, true, 'Menghapus...');
                    const res = await fetch(`user_api.php?id=${id}`, { method:'DELETE' });
                    const data = await res.json();
                    alert(data.message);
                    loadUsers(currentRoleTab);
                } catch (err) {
                    console.error('deleteUser error', err); alert('Error saat menghapus user');
                } finally {
                    if (btnEl) setButtonLoading(btnEl, false);
                }
            }

            document.getElementById('userForm').addEventListener('submit', async function(e){
                e.preventDefault();
                const submitBtn = this.querySelector('button[type="submit"]');
                try {
                    setButtonLoading(submitBtn, true, 'Menyimpan...');
                    const res = await fetch('user_api.php', { method:'POST', body:new FormData(this) });
                    const data = await res.json();
                    alert(data.message);
                    document.getElementById('formUserModal').classList.add('hidden'); document.getElementById('formUserModal').classList.remove('flex');
                    loadUsers(currentRoleTab);
                } catch (err) {
                    console.error('userForm submit error', err); alert('Error saat menyimpan user');
                } finally {
                    setButtonLoading(submitBtn, false);
                }
            });
            
            // Classes: list, create/edit form, delete
            window.loadClassList = async function() {
                const container = document.getElementById('classListContainer');
                container.innerHTML = 'Memuat...';
                try {
                    const res = await fetch('get_classes.php');
                    const data = await res.json();
                    CLASSES = data || [];
                    // also populate the select for user form so it's available if user form opens
                    const sel = document.getElementById('inpKelas');
                    if (sel) {
                        sel.innerHTML = `<option value="">-- Pilih Kelas --</option>`;
                        CLASSES.forEach(c=> sel.innerHTML += `<option value="${c.id}">${c.kode_kelas} - ${c.nama_kelas}</option>`);
                    }
                    if (CLASSES.length === 0) {
                        container.innerHTML = '<p class="text-sm text-gray-500">Belum ada kelas.</p>';
                        return;
                    }
                    container.innerHTML = '';
                    CLASSES.forEach(c => {
                        const el = document.createElement('div');
                        el.className = 'p-3 border rounded flex justify-between items-center';
                        el.innerHTML = `<div><div class="font-bold">${c.kode_kelas}</div><div class="text-sm text-gray-500">${c.nama_kelas}</div></div><div class="flex gap-2"><button class="px-2 py-1 text-sm bg-white border rounded text-blue-600" onclick='openClassForm("edit", ${JSON.stringify(c)})'>Edit</button><button class="px-2 py-1 text-sm bg-white border rounded text-red-600" onclick='deleteClass(${c.id}, this)'>Hapus</button></div>`;
                        container.appendChild(el);
                    });
                } catch (err) {
                    console.error('loadClassList error', err);
                    container.innerHTML = '<p class="text-sm text-red-500">Gagal memuat kelas.</p>';
                }
            }

            window.openClassForm = function(mode='create', data=null) {
                const wrapper = document.getElementById('classFormWrapper');
                const note = document.getElementById('classEmptyNote');
                wrapper.classList.remove('hidden'); note.classList.add('hidden');
                const frm = document.getElementById('classForm'); frm.reset();
                document.getElementById('classFormTitle').textContent = mode==='edit' ? 'Edit Kelas' : 'Tambah Kelas';
                if (mode === 'edit' && data) {
                    frm.elements['id'].value = data.id;
                    frm.elements['kode_kelas'].value = data.kode_kelas;
                    frm.elements['nama_kelas'].value = data.nama_kelas;
                } else {
                    frm.elements['id'].value = '';
                }
            }

            window.deleteClass = async function(id, btnEl) {
                if(!confirm('Yakin hapus kelas ini?')) return;
                try {
                    if (btnEl) setButtonLoading(btnEl, true, 'Menghapus...');
                    const res = await fetch(`class_api.php?id=${id}`, { method:'DELETE' });
                    const data = await res.json();
                    alert(data.message);
                    await loadClassList();
                } catch (err) {
                    console.error('deleteClass error', err); alert('Gagal menghapus kelas');
                } finally { if (btnEl) setButtonLoading(btnEl, false); }
            }

            document.getElementById('btnAddClass').addEventListener('click', function(){ openClassForm('create'); });
            document.getElementById('btnCancelClass').addEventListener('click', function(){ document.getElementById('classFormWrapper').classList.add('hidden'); document.getElementById('classEmptyNote').classList.remove('hidden'); });

            document.getElementById('classForm').addEventListener('submit', async function(e){
                e.preventDefault();
                const submitBtn = document.getElementById('btnSaveClass');
                try {
                    setButtonLoading(submitBtn, true, 'Menyimpan...');
                    const res = await fetch('class_api.php', { method: 'POST', body: new FormData(this) });
                    const data = await res.json();
                    alert(data.message);
                    await loadClassList();
                    // keep modal open, but hide form
                    document.getElementById('classFormWrapper').classList.add('hidden'); document.getElementById('classEmptyNote').classList.remove('hidden');
                } catch (err) {
                    console.error('classForm submit error', err); alert('Gagal menyimpan kelas');
                } finally { setButtonLoading(submitBtn, false); }
            });

            // Preload classes for selects and list
            loadClassList();

            // --- Admin: semester analytics UI logic (reuse lightweight logic) ---
            const adminAnalyticsYear = document.getElementById('adminAnalyticsYear');
            const adminAnalyticsSem = document.getElementById('adminAnalyticsSem');
            const adminAnalyticsCards = document.getElementById('adminAnalyticsCards');
            const adminAnalyticsNotes = document.getElementById('adminAnalyticsNotes');
            const adminDownloadCsv = document.getElementById('adminDownloadCsv');

            function adminPopulateYearSelect() {
                const cur = new Date().getFullYear();
                for (let y = cur; y >= cur-5; y--) {
                    const opt = document.createElement('option'); opt.value = y; opt.textContent = y; adminAnalyticsYear.appendChild(opt);
                }
                adminAnalyticsYear.value = new Date().getFullYear();
                adminAnalyticsSem.value = (new Date().getMonth() < 6) ? '1' : '2';
            }

            async function adminFetchSemesterAnalytics(year, sem) {
                try {
                    const res = await fetch(`analytics_api.php?range=semester&year=${year}&sem=${sem}`);
                    const data = await res.json();
                    if (!data.status) throw new Error(data.message || 'Error');
                    return data;
                } catch (e) { console.error('adminFetchSemesterAnalytics', e); return null; }
            }

            function adminRenderAnalytics(data) {
                if (!data) return;
                adminAnalyticsCards.innerHTML = '';
                const cards = [
                    {k:'total_events',t:'Total Event',fmt:v=>v},
                    {k:'weeks',t:'Jumlah Minggu',fmt:v=>v},
                    {k:'avg_per_week',t:'Rata-rata / Minggu',fmt:v=>v},
                    {k:'avg_duration_hours',t:'Rata-rata Durasi (jam)',fmt:v=>v}
                ];
                cards.forEach(c=>{
                    const v = data[c.k] ?? 0;
                    const el = document.createElement('div'); el.className='p-3 bg-gray-50 rounded-lg border border-gray-100';
                    el.innerHTML = `<div class="text-xs text-gray-500">${c.t}</div><div class="font-bold text-lg">${c.fmt(v)}</div>`;
                    adminAnalyticsCards.appendChild(el);
                });
                adminAnalyticsNotes.innerHTML = `<div>Periode: <strong>${data.period.start}</strong> — <strong>${data.period.end}</strong></div><div class="mt-2">Event puncak: Bulan <strong>${data.busiest_month || '-'}</strong> (${data.busiest_month_count} events), Hari paling sibuk: <strong>${data.busiest_weekday || '-'}</strong> (${data.busiest_weekday_count})</div>`;
                // render charts (bar: month_series, pie: weekday_series)
                try {
                    const barCtx = document.getElementById('adminBarChart').getContext('2d');
                    if (window._adminBarChart) window._adminBarChart.destroy();
                    window._adminBarChart = new Chart(barCtx, {
                        type: 'bar', data: { labels: data.month_series.labels, datasets: [{ label: 'Events', data: data.month_series.data, backgroundColor: 'rgba(147,51,234,0.7)' }] },
                        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
                    });
                } catch (e) { console.warn('bar chart error', e); }

                try {
                    const pieCtx = document.getElementById('adminPieChart').getContext('2d');
                    if (window._adminPieChart) window._adminPieChart.destroy();
                    window._adminPieChart = new Chart(pieCtx, {
                        type: 'pie', data: { labels: data.weekday_series.labels, datasets: [{ data: data.weekday_series.data, backgroundColor: ['#9333ea','#7c3aed','#4f46e5','#2563eb','#06b6d4','#10b981','#f59e0b'] }] },
                        options: { responsive: true, maintainAspectRatio: false }
                    });
                } catch (e) { console.warn('pie chart error', e); }
            }

            adminDownloadCsv.addEventListener('click', () => {
                const y = adminAnalyticsYear.value; const s = adminAnalyticsSem.value;
                window.location = `analytics_api.php?range=semester&year=${y}&sem=${s}&export=csv`;
            });

            adminPopulateYearSelect();
            (async ()=>{ const d = await adminFetchSemesterAnalytics(adminAnalyticsYear.value, adminAnalyticsSem.value); adminRenderAnalytics(d); })();
            adminAnalyticsYear.addEventListener('change', async ()=>{ const d = await adminFetchSemesterAnalytics(adminAnalyticsYear.value, adminAnalyticsSem.value); adminRenderAnalytics(d); });
            adminAnalyticsSem.addEventListener('change', async ()=>{ const d = await adminFetchSemesterAnalytics(adminAnalyticsYear.value, adminAnalyticsSem.value); adminRenderAnalytics(d); });
        });

        // --- PROFILE LOGIC (copied from user dashboard) ---
        function toggleProfileMenu() {
            const menu = document.getElementById('profileMenu');
            if (!menu) return;
            menu.classList.toggle('hidden');
        }

        // Tutup menu jika klik di luar
        document.addEventListener('click', function(e) {
            const menu = document.getElementById('profileMenu');
            const btn = document.querySelector('button[onclick="toggleProfileMenu()"]');
            if (!menu || !btn) return;
            if (!menu.contains(e.target) && !btn.contains(e.target)) {
                menu.classList.add('hidden');
            }
        });

        async function openProfileModal() {
            const pm = document.getElementById('profileMenu'); if (pm) pm.classList.add('hidden'); // Tutup menu
            try {
                const res = await fetch('profile_api.php');
                const data = await res.json();
                // lazy: populate minimal fields if modal exists
                if (document.getElementById('profName')) document.getElementById('profName').value = data.name || '';
                if (document.getElementById('profEmail')) document.getElementById('profEmail').value = data.email || '';

                // if there is a profile modal that expects dosen classes, populate similarly (best-effort)
                if (data.is_dosen) {
                    const container = document.getElementById('dosenClasses');
                    const list = document.getElementById('dosenClassesList');
                    if (container && list) {
                        container.classList.remove('hidden');
                        list.innerHTML = '';
                        (data.classes || []).forEach(c => {
                            const id = c.id;
                            const checked = (data.selected_classes || []).includes(id) ? 'checked' : '';
                            const el = document.createElement('div');
                            el.innerHTML = `<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="class_cb" value="${id}" ${checked}> <span>${c.nama_kelas} (${c.kode_kelas})</span></label>`;
                            list.appendChild(el);
                        });
                    }
                }

                const modal = document.getElementById('profileModal');
                if (modal) {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                }
            } catch(e) { console.warn('openProfileModal error', e); alert('Gagal memuat profil'); }
        }
    </script>
</body>
</html>