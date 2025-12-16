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
            <div class="rounded-md bg-red-50 p-4 border-l-4 border-red-400">
                <div class="flex">
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">Gagal</h3>
                        <div class="mt-1 text-sm text-red-700">{{ session('error') }}</div>
                    </div>
                </div>
            </div>
        @endif

        @if (session()->has('message'))
            <div class="rounded-md bg-green-50 p-4 border-l-4 border-green-400">
                <div class="flex">
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800">Sukses</h3>
                        <div class="mt-1 text-sm text-green-700">{{ session('message') }}</div>
                    </div>
                </div>
            </div>
        @endif

        @if($user->is_banned)
            <div class="bg-red-100 border-t-4 border-red-500 rounded-b text-red-900 px-4 py-3 shadow-md" role="alert">
                <div class="flex">
                    <div class="py-1"><svg class="fill-current h-6 w-6 text-red-500 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"/></svg></div>
                    <div>
                        <p class="font-bold">Akun Non-Aktif</p>
                        <p class="text-sm">Anda tidak dapat melakukan absensi lagi. Mohon hubungi HRD.</p>
                    </div>
                </div>
            </div>
        @else
            <div class="bg-white shadow-xl rounded-2xl overflow-hidden">
                <div class="px-6 py-5 bg-slate-50 border-b border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg leading-6 font-bold text-gray-900 flex items-center">
                            Halo, {{ Auth::user()->name }}
                        </h3>
                        
                        @if($user->is_wfa)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 border border-purple-200 animate-pulse">
                                DINAS LUAR (WFA)
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                WFO (KANTOR)
                            </span>
                        @endif
                    </div>
                    
                    @if($schedule)
                        <div class="text-sm text-gray-600 space-y-3">
                            @if(!$user->is_wfa)
                                <p><strong>Lokasi Wajib:</strong> {{ $schedule->officeLocation->name ?? '-' }}</p>
                            @else
                                <p><strong>Lokasi:</strong> Bebas (Dinas Luar)</p>
                            @endif
                            
                            <div>
                                <label for="shift_id" class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1">Shift</label>
                                <select wire:model="shift_id" id="shift_id" class="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm sm:text-sm" {{ $attendance && !$attendance->end_time ? 'disabled' : '' }}>
                                    @foreach($shifts as $shift)
                                        <option value="{{ $shift->id }}">
                                            {{ $shift->name }} ({{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @else
                        <div class="text-sm text-yellow-600 bg-yellow-50 p-2 rounded">Jadwal belum tersedia.</div>
                    @endif
                </div>

                <div class="p-6 bg-white">
                    @if($schedule)
                        <div class="relative rounded-xl overflow-hidden shadow-md border border-gray-200 mb-4">
                            <div id="map" style="height: 250px; width: 100%;" class="z-0" wire:ignore></div>
                            
                            <div class="absolute top-3 right-3 z-10 flex flex-col gap-2 items-end">
                                @if($insideRadius)
                                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-green-500 text-white shadow-sm flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z"/></svg>
                                        {{ $user->is_wfa ? 'Lokasi OK (WFA)' : 'Dalam Radius' }}
                                    </span>
                                @else
                                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-red-500 text-white shadow-sm flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"/></svg>
                                        Luar Radius
                                    </span>
                                @endif

                                <span id="consistency-badge" class="px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800 shadow-sm border border-blue-200">
                                    Menunggu GPS...
                                </span>
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

                            <button onclick="captureAndStore()" id="btn-absen" class="w-full py-4 px-4 text-lg font-extrabold rounded-xl text-white bg-blue-600 hover:bg-blue-700 shadow-lg flex justify-center items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                {{ $attendance ? 'AMBIL FOTO & PULANG' : 'AMBIL FOTO & MASUK' }}
                            </button>
                        @else
                            <button onclick="tagLocation()" class="w-full py-3 px-4 border border-gray-300 font-bold rounded-xl text-gray-700 bg-white hover:bg-gray-50 shadow-sm flex justify-center items-center gap-2">
                                <svg class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                Refresh Lokasi
                            </button>
                            <p class="text-center text-xs text-gray-500 mt-2">Masuk ke area kantor untuk mengaktifkan tombol.</p>
                        @endif
                    @endif
                </div>
            </div>
        @endif
        
        <p class="text-center text-xs text-gray-400">&copy; {{ date('Y') }} Sistem Presensi WBA.</p>
    </div>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        let map, marker, component;
        let locationHistory = []; 
        let isFakeGPSDetected = false; 

        // Data dari PHP
        const officeLat = {{ $schedule->officeLocation->latitude ?? 0 }};
        const officeLng = {{ $schedule->officeLocation->longitude ?? 0 }};
        const officeRadius = {{ $schedule->officeLocation->radius ?? 50 }};
        
        // PENTING: Ambil status WFA langsung dari USER yang dikirim dari Backend
        const isWfa = {{ ($user->is_wfa ?? false) ? 'true' : 'false' }};
        
        const officeCenter = [officeLat, officeLng];

        document.addEventListener('livewire:initialized', function() {
            component = @this;
            
            // Init Map
            let initialCenter = (officeLat !== 0 && officeLng !== 0) ? officeCenter : [-6.200000, 106.816666]; 
            map = L.map('map', { zoomControl: false }).setView(initialCenter, 15);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '' }).addTo(map);
            
            // GAMBAR LINGKARAN MERAH HANYA JIKA BUKAN WFA
            if (!isWfa && officeLat !== 0) {
                L.circle(officeCenter, {
                    color: '#ef4444', fillColor: '#ef4444', fillOpacity: 0.2, radius: officeRadius
                }).addTo(map);
            }

            startTracking();
        });

        function startTracking() {
            if (navigator.geolocation) {
                navigator.geolocation.watchPosition(
                    (position) => {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;

                        if (map) {
                            if (marker) map.removeLayer(marker);
                            marker = L.marker([lat, lng]).addTo(map);
                            map.setView([lat, lng], 17);
                        }

                        // Cek Radius
                        let statusRadius = isWithinRadius(lat, lng);
                        
                        if(statusRadius !== component.get('insideRadius')) {
                             component.set('insideRadius', statusRadius);
                             component.set('latitude', lat);
                             component.set('longitude', lng).then(() => {
                                 if(statusRadius) startCamera();
                             });
                        } else {
                            component.set('latitude', lat, true); 
                            component.set('longitude', lng, true);
                        }

                        checkLocationConsistency(lat, lng);
                    },
                    (error) => console.log('GPS Error'),
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );
            }
        }

        // --- FUNGSI UTAMA CEK RADIUS ---
        function isWithinRadius(userLat, userLng) {
            // JIKA WFA = TRUE, KITA SELALU RETURN TRUE (BYPASS LOKASI)
            if (isWfa) return true;

            // JIKA WFO, HITUNG JARAK NORMAL
            if (!map || officeLat === 0) return false;
            return map.distance([userLat, userLng], officeCenter) <= officeRadius;
        }

        // --- Logic Anti Fake GPS ---
        function checkLocationConsistency(lat, lng) {
            const timestamp = new Date().getTime();
            locationHistory.push({ lat: lat, lng: lng, time: timestamp });
            if (locationHistory.length > 10) locationHistory.shift();

            if (locationHistory.length >= 3) {
                let isConsistent = true;
                for (let i = 1; i < locationHistory.length; i++) {
                    const prev = locationHistory[i - 1];
                    const curr = locationHistory[i];
                    const distance = haversineDistance([prev.lat, prev.lng], [curr.lat, curr.lng]);
                    const timeDiff = (curr.time - prev.time) / 1000;

                    if (timeDiff > 0) {
                        const speed = distance / timeDiff;
                        if (speed > 100 && distance > 50) { 
                            isConsistent = false; break;
                        }
                    }
                }
                const badge = document.getElementById('consistency-badge');
                if(badge) {
                    if (isConsistent) {
                        isFakeGPSDetected = false;
                        badge.innerText = 'GPS Wajar';
                        badge.className = 'px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800 shadow-sm border border-green-200';
                    } else {
                        isFakeGPSDetected = true;
                        badge.innerText = 'Terdeteksi Fake GPS!';
                        badge.className = 'px-3 py-1 rounded-full text-xs font-bold bg-red-600 text-white shadow-sm border border-red-700 animate-pulse';
                    }
                }
            } else {
                const badge = document.getElementById('consistency-badge');
                if(badge) badge.innerText = 'Kalibrasi GPS... (' + locationHistory.length + '/3)';
            }
        }

        function haversineDistance(coords1, coords2) {
            function toRad(x) { return x * Math.PI / 180; }
            var R = 6371; 
            var dLat = toRad(coords2[0] - coords1[0]);
            var dLon = toRad(coords2[1] - coords1[1]);
            var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) + Math.cos(toRad(coords1[0])) * Math.cos(toRad(coords2[0])) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
            var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c * 1000;
        }

        function startCamera() {
            const video = document.getElementById('camera-feed');
            if (video && !video.srcObject) {
                navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
                    .then(stream => { video.srcObject = stream; })
                    .catch(err => console.error("Camera Error:", err));
            }
        }

        async function captureAndStore() {
            if (isFakeGPSDetected) {
                alert('Sistem mendeteksi penggunaan Fake GPS. Mohon matikan aplikasi tambahan.');
                return;
            }
            const video = document.getElementById('camera-feed');
            const canvas = document.getElementById('photo-canvas');
            const btn = document.getElementById('btn-absen');

            if (!video || !canvas) { alert('Kamera belum siap.'); return; }

            const originalText = btn.innerHTML;
            btn.innerHTML = 'Memproses...';
            btn.disabled = true;

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

            canvas.toBlob(async (blob) => {
                const file = new File([blob], "selfie.jpg", { type: "image/jpeg" });
                @this.upload('photo', file, (uploadedFilename) => {
                    component.store();
                }, () => {
                    alert('Gagal upload foto.');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
            }, 'image/jpeg', 0.8);
        }
        
        function tagLocation() { startTracking(); }
    </script>
</div>