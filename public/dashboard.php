<?php
session_start();
require __DIR__ .  '/../config/config.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
$user = $_SESSION['user'];
// detect whether this user already has a valid google_token so we can hide the link
$has_google = false;
$check_table = $conn->query("SHOW TABLES LIKE 'google_token'");
if ($check_table && $check_table->num_rows > 0) {
    $chk = $conn->prepare("SELECT access_token, refresh_token, expires_at FROM google_token WHERE user_id = ? LIMIT 1");
    $chk->bind_param('i', $user['id']); $chk->execute(); $res = $chk->get_result();
    if ($res && $row = $res->fetch_assoc()) {
        // consider valid only if access_token exists and not expired (30s slack)
        $valid = false;
        if (!empty($row['access_token']) && !empty($row['expires_at']) && strtotime($row['expires_at']) > time() + 30) $valid = true;
        // otherwise require the user to re-sync (show link) so they can re-authorize
        $has_google = $valid;
    } else {
        $has_google = false;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - DLReminder</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { primary: '#9333ea', primaryHover: '#7e22ce', secondary: '#f3e8ff', dark: '#1f2937' }, fontFamily: { sans: ['Inter'], heading: ['Poppins'] } } } }
    </script>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        /* small inline spinner used for buttons */
        .loader { display:inline-block; width:14px; height:14px; border:2px solid rgba(0,0,0,0.12); border-top-color:rgba(0,0,0,0.6); border-radius:9999px; vertical-align:middle; margin-right:6px; animation:spin 1s linear infinite; }
        @keyframes spin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }
        .btn-disabled { opacity:0.7; cursor:not-allowed; }
        /* chart containers */
        .chart-box { height: 240px; max-height: 240px; }
        #workloadChart, #weekdayPieChart { width:100% !important; height:100% !important; }
    </style>
</head>
<body class="font-sans text-gray-600 bg-gray-50 antialiased min-h-screen">

    <nav class="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-20">
                <div class="flex items-center gap-2" onclick="window.location.href='index.php'">
                    <div class="bg-primary text-white p-1.5 rounded-lg shadow-lg"><svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg></div>
                    <span class="font-heading font-bold text-xl text-dark hidden md:block">DL<span class="text-primary">Reminder</span></span>
                </div>
                <div class="flex items-center gap-4 relative">
                <?php if (!$has_google): ?>
                <a id="syncGoogleBtn" href="google_login.php" class="text-sm text-blue-600 hover:underline font-medium hidden sm:block">Sync Google Calendar</a>
                <?php endif; ?>
                <div class="h-8 w-px bg-gray-200 hidden md:block"></div>
                
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
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex flex-col justify-center gap-6 h-full">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-purple-50 text-primary rounded-xl"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg></div>
                    <div><div class="text-gray-500 text-sm font-medium">Tugas Aktif</div><div id="activeTaskCount" class="font-heading text-3xl font-bold text-gray-800">0</div></div>
                </div>
                <hr class="border-gray-100">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-red-50 text-red-500 rounded-xl"><svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>
                    <div><div class="text-gray-500 text-sm font-medium">Deadline < 3 Hari</div><div id="urgentTaskCount" class="font-heading text-3xl font-bold text-gray-800">0</div></div>
                </div>
            </div>
            <div class="lg:col-span-2 bg-white p-5 rounded-2xl shadow-sm border border-gray-100 flex flex-col h-full">
                <div class="flex justify-between items-center mb-3">
                     <h3 class="font-heading text-lg font-bold text-gray-800">Segera Dikumpulkan</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 flex-1 overflow-y-auto max-h-[180px] no-scrollbar pr-1" id="upcomingList"><p class="text-gray-400 text-sm text-center py-2 col-span-2">Memuat data...</p></div>
            </div>
        </div>

        <!-- Analitik: Beban Kerja (line) + Event per Hari (pie) -->
        <div class="w-full bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mt-4">
            <div class="flex justify-between items-start mb-4">
                <h3 class="font-heading text-xl font-bold text-gray-800">Analitik Beban Kerja</h3>
                <div class="flex items-center gap-2 text-sm">
                    <button id="btnYear2025" class="px-3 py-1 rounded-full bg-purple-50 text-purple-600">2025</button>
                    <button id="btnYear2024" class="px-3 py-1 rounded-full bg-white text-gray-500 border">2024</button>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 chart-box">
                    <canvas id="workloadChart"></canvas>
                </div>
                <div class="lg:col-span-1">
                    <h4 class="font-bold mb-2">Event per Hari (Kategori)</h4>
                    <div class="chart-box"><canvas id="weekdayPieChart"></canvas></div>
                    <p class="text-xs text-gray-500 mt-3">Kategori: Hari (Senin–Minggu). Hanya data yang dapat Anda lihat.</p>
                </div>
            </div>
        </div>

        <div class="w-full bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-heading text-xl font-bold text-gray-800">Kalender Akademik</h3>
                <button onclick="openCreateModal(new Date().toISOString().slice(0,10))" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-bold shadow hover:bg-primaryHover transition flex items-center gap-2"><span>+</span> Buat Event</button>
            </div>
            <div id="calendar" class="min-h-[650px]"></div>
        </div>
    </main>

    <div id="eventModal" class="fixed inset-0 hidden items-center justify-center bg-black/50 z-50 transition-opacity">
        <div class="bg-white rounded-xl p-6 w-full max-w-lg shadow-2xl">
            <h3 class="font-heading text-xl font-bold mb-4 text-primary">Buat Event Baru</h3>
            <form id="eventForm">
                <input type="hidden" name="action" value="create">
                <div class="space-y-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Judul Event</label><input name="title" class="w-full border px-4 py-2 rounded-lg" required /></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label><textarea name="description" class="w-full border px-4 py-2 rounded-lg"></textarea></div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Deadline (Tanggal)</label><input name="end" type="date" class="w-full border px-3 py-2 rounded-lg" required /></div>
                        <div class="flex items-center gap-2"><label class="flex items-center gap-2"><input type="checkbox" name="repeat_monthly"> <span class="text-sm text-gray-700">Ulangi setiap bulan (12x)</span></label></div>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                        <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Target Audiens</label>
                        <div id="visibilityOptions" class="text-sm text-gray-700"></div>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('eventModal').classList.remove('flex'); document.getElementById('eventModal').classList.add('hidden');" class="px-5 py-2.5 rounded-lg border hover:bg-gray-50">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-lg bg-primary text-white font-bold hover:bg-primaryHover">Simpan Event</button>
                </div>
            </form>
        </div>
    </div>

    <div id="editEventModal" class="fixed inset-0 hidden items-center justify-center bg-black/50 z-[60]">
        <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-2xl">
            <h3 class="font-heading text-lg font-bold mb-4 text-primary">Edit Event</h3>
            <form id="editEventForm">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editEventId">
                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-bold text-gray-500">Judul Event</label>
                        <input name="title" id="editEventTitle" class="w-full border px-3 py-2 rounded-lg" required>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500">Deskripsi</label>
                        <textarea name="description" id="editEventDesc" class="w-full border px-3 py-2 rounded-lg h-24"></textarea>
                    </div>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('editEventModal').classList.add('hidden'); document.getElementById('editEventModal').classList.remove('flex');" class="px-4 py-2 rounded border hover:bg-gray-50">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded bg-primary text-white font-bold hover:bg-primaryHover">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

        <div id="eventDetailModal" class="fixed inset-0 hidden items-center justify-center bg-black/50 z-50">
            <div class="bg-white rounded-xl p-6 w-full max-w-md shadow-2xl relative">
                <button onclick="document.getElementById('eventDetailModal').classList.remove('flex'); document.getElementById('eventDetailModal').classList.add('hidden');" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
                <h3 id="detailTitle" class="font-heading text-xl font-bold mb-1 text-gray-900"></h3>
                <div class="flex items-center gap-2 mb-4">
                    <span id="detailBadge" class="px-2 py-0.5 rounded text-xs font-bold bg-gray-100 text-gray-600 uppercase">Self</span>
                    <span id="detailCreator" class="text-xs text-gray-500 italic"></span>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 mb-4">
                    <p id="detailMeta" class="text-sm text-gray-600 font-medium mb-2 flex items-center gap-2"><span id="detailTime"></span></p>
                    <p id="detailDesc" class="text-gray-700 text-sm leading-relaxed whitespace-pre-wrap"></p>
                </div>
                <div id="creatorActions" class="hidden flex justify-end gap-2 pt-2 border-t border-gray-100">
                    <button id="btnEditEvent" class="px-3 py-1.5 rounded text-xs font-bold text-blue-600 border border-blue-200 hover:bg-blue-50">Edit</button>
                    <button id="btnDeleteEvent" class="px-3 py-1.5 rounded text-xs font-bold text-red-600 border border-red-200 hover:bg-red-50">Hapus</button>
                </div>
            </div>
        </div>

    <div id="profileModal" class="fixed inset-0 hidden items-center justify-center bg-black/50 z-[70]">
        <div class="bg-white rounded-xl p-6 w-full max-w-sm shadow-2xl relative">
            <h3 class="font-heading text-lg font-bold mb-4 text-primary">Profil Saya</h3>
            <form id="profileForm">
                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-bold text-gray-500">Nama Lengkap</label>
                        <input name="name" id="profName" class="w-full border px-3 py-2 rounded-lg" required>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500">Email (Read Only)</label>
                        <input name="email" id="profEmail" class="w-full border px-3 py-2 rounded-lg bg-gray-100 text-gray-500" readonly>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500">Ganti Password</label>
                        <input name="password" type="password" class="w-full border px-3 py-2 rounded-lg" placeholder="Kosongkan jika tidak diganti">
                    </div>
                    <div id="dosenClasses" class="hidden">
                        <label class="text-xs font-bold text-gray-500">Pilih Kelas (untuk Dosen)</label>
                        <div id="dosenClassesList" class="mt-2 space-y-2 max-h-40 overflow-auto border p-2 rounded"></div>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('profileModal').classList.add('hidden'); document.getElementById('profileModal').classList.remove('flex');" class="px-4 py-2 rounded border hover:bg-gray-50 text-sm">Tutup</button>
                    <button type="submit" class="px-4 py-2 rounded bg-primary text-white font-bold hover:bg-primaryHover text-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const CURRENT_USER = <?php echo json_encode($user); ?>;
        const GOOGLE_CONNECTED = <?php echo $has_google ? 'true' : 'false'; ?>;

        // Helper to set a button into loading state (disables and shows spinner)
        function setButtonLoading(btn, loading, label) {
            if (!btn) return;
            if (loading) {
                try { btn.dataset._orig = btn.innerHTML; } catch(e){}
                btn.disabled = true;
                btn.classList.add('btn-disabled');
                const txt = label || btn.textContent || '';
                btn.innerHTML = `<span class="loader"></span>${txt}`;
            } else {
                btn.disabled = false;
                btn.classList.remove('btn-disabled');
                if (btn.dataset && btn.dataset._orig) { btn.innerHTML = btn.dataset._orig; delete btn.dataset._orig; }
            }
        }

        function mapColorByVisibility(v) {
            switch (v) { case 'dosen': return '#9333ea'; case 'mahasiswa': return '#2563eb'; case 'all': return '#ef4444'; default: return '#10b981'; }
        }

        const calendarEl = document.getElementById('calendar');
        const upcomingList = document.getElementById('upcomingList');
        const activeTaskCount = document.getElementById('activeTaskCount');
        const urgentTaskCount = document.getElementById('urgentTaskCount');
        const eventForm = document.getElementById('eventForm');
        const visibilityOptions = document.getElementById('visibilityOptions');

        document.addEventListener('DOMContentLoaded', function() {
            let calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,listWeek' },
                events: [], height: 'auto', aspectRatio: 1.8,
                dateClick: function(info) { openCreateModal(info.dateStr); },
                eventClick: function(info) { showEventDetail(info.event); }
            });
            calendar.render();

            loadAndRender();
            // If user already connected Google, attempt an automatic sync of their own events
            if (GOOGLE_CONNECTED) {
                try {
                    const fd = new FormData(); fd.append('action','sync_me');
                    fetch('event_api.php', { method: 'POST', body: fd })
                        .then(r => r.json())
                        .then(d => console.log('auto sync_me response', d))
                        .catch(e => console.warn('auto sync failed', e));
                } catch(e) { console.warn('auto sync exception', e); }
            }

            async function fetchEvents() {
                try {
                    const res = await fetch('event_api.php');
                    const data = await res.json();
                    if (!data.status) return [];
                    return data.events.map(e => ({
                        id: e.id, title: e.title, start: e.start, end: e.end,
                        color: mapColorByVisibility(e.visibility),
                        extendedProps: { ...e }
                    }));
                } catch (err) { return []; }
            }

            async function loadAndRender() {
                const events = await fetchEvents();
                calendar.removeAllEvents();
                // update analytics charts
                updateWorkloadChart(events);
                updateWeekdayPie(events);
                upcomingList.innerHTML = '';
                let countActive = 0, countUrgent = 0;
                const now = new Date(); const threeDays = new Date(); threeDays.setDate(now.getDate()+3);

                events.sort((a,b)=>new Date(a.start)-new Date(b.start));
                events.forEach(ev => {
                    calendar.addEvent(ev);
                    const d = new Date(ev.start);
                    if(d >= now) { countActive++; if(d<=threeDays) countUrgent++; }
                });
                activeTaskCount.innerText = countActive; urgentTaskCount.innerText = countUrgent;

                const upcoming = events.filter(e=>new Date(e.start)>=now).slice(0,6);
                if(upcoming.length===0) upcomingList.innerHTML='<p class="text-center text-gray-400 text-sm py-2 col-span-2">Tidak ada tugas.</p>';
                else upcoming.forEach(ev => {
                    upcomingList.innerHTML += `<div class="p-3 rounded-xl bg-gray-50 border border-gray-100 cursor-pointer" onclick="alert('${ev.title}')"><div class="flex justify-between"><span class="font-bold text-sm text-gray-700">${ev.title}</span><span class="text-xs bg-white border px-1 rounded">${new Date(ev.start).toLocaleDateString('id-ID')}</span></div><div class="text-[10px] text-gray-400 mt-1 uppercase">${ev.extendedProps.visibility}</div></div>`;
                });
            }

            // Charts (Chart.js) - workload (monthly) and weekday pie
            let workloadChart = null;
            let weekdayPieChart = null;

            function updateWorkloadChart(events) {
                const months = new Array(12).fill(0);
                const thisYear = new Date().getFullYear();
                events.forEach(ev => {
                    try {
                        const d = new Date(ev.start);
                        if (d.getFullYear() === thisYear) months[d.getMonth()]++;
                    } catch(e){}
                });
                const labels = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
                const ctx = document.getElementById('workloadChart').getContext('2d');
                if (workloadChart) {
                        const y = window.scrollY;
                        workloadChart.data.labels = labels;
                        workloadChart.data.datasets[0].data = months;
                        workloadChart.update();
                        window.scrollTo(0, y);
                        return;
                }
                const gradient = ctx.createLinearGradient(0,0,0,200);
                gradient.addColorStop(0,'rgba(147,51,234,0.25)');
                gradient.addColorStop(1,'rgba(147,51,234,0.02)');
                const y = window.scrollY;
                workloadChart = new Chart(ctx, {
                    type: 'line',
                    data: { labels, datasets: [{ label: 'Tugas per bulan', data: months, fill: true, backgroundColor: gradient, borderColor: '#9333ea', tension: 0.35, pointRadius:4, pointBackgroundColor:'#fff', pointBorderColor:'#9333ea' }] },
                    options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } }, scales:{ y:{ beginAtZero:true, ticks:{ stepSize:5 } } } }
                });
                window.scrollTo(0, y);
            }

            function updateWeekdayPie(events) {
                const counts = [0,0,0,0,0,0,0]; // Sun..Sat
                events.forEach(ev => {
                    try { const d = new Date(ev.start); counts[d.getDay()]++; } catch(e){}
                });
                // convert to Monday..Sunday order
                const monFirst = [counts[1],counts[2],counts[3],counts[4],counts[5],counts[6],counts[0]];
                const labels = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
                const ctx = document.getElementById('weekdayPieChart').getContext('2d');
                if (weekdayPieChart) {
                    const y = window.scrollY;
                    weekdayPieChart.data.labels = labels;
                    weekdayPieChart.data.datasets[0].data = monFirst;
                    weekdayPieChart.update();
                    window.scrollTo(0, y);
                    return;
                }
                const y = window.scrollY;
                weekdayPieChart = new Chart(ctx, {
                    type: 'pie',
                    data: { labels, datasets: [{ data: monFirst, backgroundColor: ['#9333ea','#7c3aed','#4f46e5','#2563eb','#06b6d4','#10b981','#f59e0b'] }] },
                    options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom' } } }
                });
                window.scrollTo(0, y);
            }

            window.openCreateModal = function(dateStr) {
                eventForm.reset();
                const baseDate = dateStr ? dateStr : new Date().toISOString().slice(0,10);
                // deadline date input expects YYYY-MM-DD
                eventForm.end.value = baseDate;
                visibilityOptions.innerHTML = '';
                if (CURRENT_USER.role === 'dosen') {
                    visibilityOptions.innerHTML = `<div class="flex gap-4"><label class="flex items-center gap-2"><input type="checkbox" name="opt_dosen" checked> Dosen</label><label class="flex items-center gap-2"><input type="checkbox" name="opt_mahasiswa"> Mahasiswa</label></div>`;
                } else if (CURRENT_USER.role === 'admin') {
                    visibilityOptions.innerHTML = `<div class="flex gap-4"><label class="flex items-center gap-2"><input type="checkbox" name="chk_dosen"> Dosen</label><label class="flex items-center gap-2"><input type="checkbox" name="chk_mahasiswa"> Mahasiswa</label></div>`;
                } else {
                    visibilityOptions.innerHTML = '<div class="text-sm text-gray-500 italic p-2 bg-gray-50 rounded border">Pribadi</div>';
                }
                document.getElementById('eventModal').classList.remove('hidden');
                document.getElementById('eventModal').classList.add('flex');
            }

            eventForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                console.log('eventForm submit');
                const submitBtn = this.querySelector('button[type="submit"]');
                setButtonLoading(submitBtn, true, 'Menyimpan...');
                const fd = new FormData(this);
                if (CURRENT_USER.role === 'admin') {
                    if (this.querySelector('input[name="chk_dosen"]')?.checked) fd.append('chk_dosen','1');
                    if (this.querySelector('input[name="chk_mahasiswa"]')?.checked) fd.append('chk_mahasiswa','1');
                }
                try {
                    const res = await fetch('event_api.php', { method: 'POST', body: fd });
                    const text = await res.text();
                    let data = null;
                    try {
                        data = JSON.parse(text);
                    } catch (parseErr) {
                        console.error('Create: server returned non-JSON response:', text);
                        alert('Server returned invalid response. Lihat console (Network -> Response) untuk detail.');
                        return;
                    }
                    console.log('event_api create response', data);
                    if(data.status) {
                        document.getElementById('eventModal').classList.remove('flex');
                        document.getElementById('eventModal').classList.add('hidden');
                        loadAndRender();
                    } else {
                        alert('Gagal: ' + JSON.stringify(data));
                    }
                } catch(err) {
                    console.error('Create error', err);
                    alert('Terjadi kesalahan jaringan atau error JS. Lihat console.');
                } finally {
                    setButtonLoading(submitBtn, false);
                }
            });

            // EDIT & DELETE LOGIC
            window.showEventDetail = function(event) {
                const props = event.extendedProps;
                document.getElementById('detailTitle').textContent = event.title;
                document.getElementById('detailCreator').textContent = 'Oleh: ' + (props.creator_name || 'System');
                document.getElementById('detailTime').textContent = new Date(event.start).toLocaleString('id-ID');
                document.getElementById('detailDesc').textContent = props.description || "-";
                
                const badge = document.getElementById('detailBadge');
                badge.textContent = props.visibility;
                badge.style.backgroundColor = event.backgroundColor;
                badge.style.color = '#fff';

                const actionsDiv = document.getElementById('creatorActions');
                // Cek kepemilikan atau role admin -> admin dapat CRUD semua event
                if (CURRENT_USER.role === 'admin' || parseInt(props.created_by) === parseInt(CURRENT_USER.id)) {
                    actionsDiv.classList.remove('hidden');
                    document.getElementById('btnDeleteEvent').onclick = () => deleteEvent(event.id);
                    document.getElementById('btnEditEvent').onclick = () => {
                        document.getElementById('eventDetailModal').classList.add('hidden');
                        document.getElementById('eventDetailModal').classList.remove('flex');
                        openEditModal(event.id, event.title, props.description);
                    };
                } else {
                    actionsDiv.classList.add('hidden');
                }
                document.getElementById('eventDetailModal').classList.remove('hidden');
                document.getElementById('eventDetailModal').classList.add('flex');
            }

            function openEditModal(id, title, desc) {
                document.getElementById('editEventId').value = id;
                document.getElementById('editEventTitle').value = title;
                document.getElementById('editEventDesc').value = desc || '';
                document.getElementById('editEventModal').classList.remove('hidden');
                document.getElementById('editEventModal').classList.add('flex');
            }

            document.getElementById('editEventForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                console.log('editEventForm submit');
                const fd = new FormData(this);
                try {
                    const res = await fetch('event_api.php', { method: 'POST', body: fd });
                    const text = await res.text();
                    let data = null;
                    try {
                        data = JSON.parse(text);
                    } catch (parseErr) {
                        console.error('Update: server returned non-JSON response:', text);
                        alert('Server returned invalid response. Lihat console (Network -> Response) untuk detail.');
                        return;
                    }
                    console.log('event_api update response', data);
                    alert(data.message || JSON.stringify(data));
                    if (data.status) {
                        // close modal and refresh calendar
                        document.getElementById('editEventModal').classList.remove('flex');
                        document.getElementById('editEventModal').classList.add('hidden');
                        await loadAndRender();
                    }
                } catch(err) {
                    console.error('Update error', err);
                    alert('Terjadi kesalahan saat mengirim permintaan. Lihat console.');
                }
            });

            async function deleteEvent(id) {
                if(!confirm("Hapus event ini?")) return;
                const res = await fetch(`event_api.php?id=${id}`, { method: 'DELETE' });
                const data = await res.json();
                alert(data.message);
                if(data.status) location.reload();
            }
        });
                // --- PROFILE LOGIC ---
        function toggleProfileMenu() {
            const menu = document.getElementById('profileMenu');
            menu.classList.toggle('hidden');
        }

        // Tutup menu jika klik di luar
        document.addEventListener('click', function(e) {
            const menu = document.getElementById('profileMenu');
            const btn = document.querySelector('button[onclick="toggleProfileMenu()"]');
            if (!menu.contains(e.target) && !btn.contains(e.target)) {
                menu.classList.add('hidden');
            }
        });

        async function openProfileModal() {
            document.getElementById('profileMenu').classList.add('hidden'); // Tutup menu
            
            // Ambil data terbaru
            try {
                const res = await fetch('profile_api.php');
                const data = await res.json();
                document.getElementById('profName').value = data.name;
                document.getElementById('profEmail').value = data.email;
                    // if dosen, populate classes
                    if (data.is_dosen) {
                        const container = document.getElementById('dosenClasses');
                        const list = document.getElementById('dosenClassesList');
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
                
                const modal = document.getElementById('profileModal');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            } catch(e) { alert("Gagal memuat profil"); }
        }

        document.getElementById('profileForm').addEventListener('submit', async function(e) {
            e.preventDefault();
                    const fd = new FormData(this);
                    // if dosen classes present, append selected
                    const classChecks = document.querySelectorAll('#dosenClassesList input[name="class_cb"]:checked');
                    classChecks.forEach(cb => fd.append('classes[]', cb.value));
            try {
                const res = await fetch('profile_api.php', { method: 'POST', body: fd });
                const data = await res.json();
                alert(data.message);
                if(data.status) location.reload(); // Reload untuk update nama di navbar
            } catch(e) { alert("Error sistem"); }
        });
    </script>
</body>
</html>