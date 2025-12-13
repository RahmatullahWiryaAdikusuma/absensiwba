<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8 flex justify-center">
    <div class="max-w-md w-full space-y-6">

        <div class="text-center">
            <h2 class="mt-2 text-3xl font-extrabold text-gray-900">
                Presensi WBA
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                Sistem Absensi Terintegrasi
            </p>
        </div>

        @if (session()->has('error'))
            <div class="rounded-md bg-red-50 p-4 border-l-4 border-red-400 animate-pulse">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Gagal</h3>
                        <div class="mt-1 text-sm text-red-700">
                            {{ session('error') }}
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="bg-white shadow-xl rounded-2xl overflow-hidden">
            
            <div class="px-6 py-5 bg-slate-50 border-b border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg leading-6 font-bold text-gray-900 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 mr-2 text-blue-600">
                            <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd" />
                        </svg>
                        Halo, {{ Auth::user()->name }}
                    </h3>
                    @if($schedule)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $schedule->is_wfa ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                            {{ $schedule->is_wfa ? 'WFA' : 'WFO' }}
                        </span>
                    @endif
                </div>
                
                @if($schedule)
                    <div class="text-sm text-gray-600 space-y-3">
                        <p><strong>Lokasi:</strong> {{ $schedule->officeLocation->name ?? '-' }}</p>
                        
                        <div>
                            <label for="shift_id" class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1">
                                Shift Saat Ini
                            </label>
                            <select wire:model="shift_id" id="shift_id" class="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" {{ $attendance && !$attendance->end_time ? 'disabled' : '' }}>
                                @foreach($shifts as $shift)
                                    <option value="{{ $shift->id }}">
                                        {{ $shift->name }} ({{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">*Ubah shift jika Anda sedang tukar jadwal.</p>
                        </div>
                    </div>
                @else
                    <div class="text-sm text-yellow-600 bg-yellow-50 p-2 rounded">
                        Jadwal belum tersedia. Hubungi Admin.
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-2 border-b border-gray-200 divide-x divide-gray-200 bg-white">
                <div class="p-4 text-center">
                    <p class="text-xs font-bold text-gray-400 uppercase mb-1">Masuk</p>
                    <p class="text-2xl font-extrabold text-gray-900">
                        {{ $attendance ? \Carbon\Carbon::parse($attendance->start_time)->format('H:i') : '--:--' }}
                    </p>
                </div>
                <div class="p-4 text-center">
                    <p class="text-xs font-bold text-gray-400 uppercase mb-1">Pulang</p>
                    <p class="text-2xl font-extrabold text-gray-900">
                        {{ $attendance && $attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time)->format('H:i') : '--:--' }}
                    </p>
                </div>
            </div>

            <div class="p-6 bg-white">
                @if($schedule)
                    <div class="relative rounded-xl overflow-hidden shadow-md border border-gray-200 mb-4">
                        <div id="map" style="height: 250px; width: 100%;" class="z-0" wire:ignore></div>
                        
                        <div class="absolute top-3 right-3 z-10">
                             @if($insideRadius)
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-500 text-white shadow-sm flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z"/></svg>
                                    Dalam Radius
                                </span>
                            @else
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-red-500 text-white shadow-sm flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"/></svg>
                                    Luar Radius
                                </span>
                            @endif
                        </div>
                    </div>

                    @if($insideRadius)
                        <div class="mb-4">
                            <div class="relative w-full h-64 bg-black rounded-xl overflow-hidden shadow-inner border border-gray-300">
                                <video id="camera-feed" autoplay playsinline muted class="w-full h-full object-cover"></video>
                                <canvas id="photo-canvas" class="hidden"></canvas>
                                
                                <div class="absolute bottom-2 left-0 right-0 text-center">
                                    <span class="text-xs text-white bg-black/50 px-2 py-1 rounded-full">Kamera Aktif</span>
                                </div>
                            </div>
                        </div>

                        <button onclick="captureAndStore()" id="btn-absen" class="w-full py-4 px-4 border border-transparent text-lg font-extrabold rounded-xl text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 shadow-lg transform transition hover:scale-[1.02] flex justify-center items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ $attendance ? 'AMBIL FOTO & PULANG' : 'AMBIL FOTO & MASUK' }}
                        </button>
                    @else
                        <button onclick="tagLocation()" class="w-full py-3 px-4 border border-gray-300 font-bold rounded-xl text-gray-700 bg-white hover:bg-gray-50 shadow-sm flex justify-center items-center gap-2">
                            <svg class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            Cek Lokasi Saya Lagi
                        </button>
                        <p class="text-center text-xs text-gray-500 mt-2">Anda harus berada di dalam area kantor untuk mengaktifkan kamera.</p>
                    @endif

                @else
                    <div class="text-center p-6 bg-gray-50 rounded-xl border border-dashed border-gray-300">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada jadwal</h3>
                    </div>
                @endif
            </div>
        </div>
        
        <p class="text-center text-xs text-gray-400">
            &copy; {{ date('Y') }} Sistem Presensi WBA.
        </p>
    </div>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        let map;
        let marker;
        let component;
        
        // Data dari Controller
        const officeLat = {{ $schedule->officeLocation->latitude ?? 0 }};
        const officeLng = {{ $schedule->officeLocation->longitude ?? 0 }};
        const officeRadius = {{ $schedule->officeLocation->radius ?? 50 }};
        const officeCenter = [officeLat, officeLng];

        document.addEventListener('livewire:initialized', function() {
            component = @this;

            // 1. Inisialisasi Peta
            if (officeLat !== 0 && officeLng !== 0) {
                map = L.map('map', { zoomControl: false }).setView(officeCenter, 16);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '' }).addTo(map);
                
                const isWfa = {{ json_encode($schedule->is_wfa ?? false) }};
                if (!isWfa) {
                    L.circle(officeCenter, {
                        color: '#ef4444', fillColor: '#ef4444', fillOpacity: 0.2, radius: officeRadius
                    }).addTo(map);
                }
            }

            // 2. Jalankan Cek Lokasi Pertama Kali
            tagLocation();
        });

        // Fungsi Cek Lokasi (GPS)
        function tagLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;

                    // Update Marker di Peta
                    if (map) {
                        if (marker) map.removeLayer(marker);
                        marker = L.marker([lat, lng]).addTo(map);
                        map.setView([lat, lng], 17);
                    }

                    // Kirim ke Livewire untuk cek radius
                    if (isWithinRadius(lat, lng)) {
                        component.set('insideRadius', true);
                        component.set('latitude', lat);
                        component.set('longitude', lng).then(() => {
                            startCamera();
                        });
                    } else {
                        component.set('insideRadius', false);
                    }

                }, function(error) {
                    alert('Gagal mengambil lokasi. Pastikan GPS aktif.');
                }, { enableHighAccuracy: true });
            }
        }

        // Fungsi Hitung Radius
        function isWithinRadius(userLat, userLng) {
            const isWfa = {{ json_encode($schedule->is_wfa ?? false) }};
            if (isWfa) return true;
            if (!map) return false;
            return map.distance([userLat, userLng], officeCenter) <= officeRadius;
        }

        // --- LOGIKA KAMERA --- //

        function startCamera() {
            setTimeout(() => {
                const video = document.getElementById('camera-feed');
                if (video) {
                    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
                        .then(stream => {
                            video.srcObject = stream;
                        })
                        .catch(err => {
                            console.error("Gagal akses kamera:", err);
                            // alert("Tidak bisa mengakses kamera. Izinkan akses kamera di browser.");
                        });
                }
            }, 500); 
        }

        // Fungsi Tombol Sakti: Capture -> Upload -> Submit
        async function captureAndStore() {
            const video = document.getElementById('camera-feed');
            const canvas = document.getElementById('photo-canvas');
            const btn = document.getElementById('btn-absen');

            if (!video || !canvas) return;

            // 1. Ubah tombol jadi Loading
            const originalText = btn.innerHTML;
            btn.innerHTML = `<svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Memproses...`;
            btn.disabled = true;

            // 2. Ambil Gambar dari Video
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

            // 3. Konversi ke File Blob
            canvas.toBlob(async (blob) => {
                const file = new File([blob], "selfie.jpg", { type: "image/jpeg" });

                // 4. Upload via Livewire JavaScript API
                @this.upload('photo', file, (uploadedFilename) => {
                    // Sukses Upload -> Panggil fungsi store() di PHP
                    component.store().then(() => {
                        // Absen Berhasil
                    });
                }, () => {
                    // Gagal Upload
                    alert('Gagal mengupload foto. Coba lagi.');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
            }, 'image/jpeg', 0.8); // Kualitas JPG 80%
        }
    </script>
</div>