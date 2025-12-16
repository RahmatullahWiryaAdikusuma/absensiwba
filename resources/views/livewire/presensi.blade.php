<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8 flex justify-center" 
     x-data="attendanceSystem()" 
     x-init="initSystem()">
    
    <div class="max-w-md w-full space-y-6">

        <div class="text-center">
            <h2 class="mt-2 text-3xl font-extrabold text-gray-900">
                Presensi WBA
            </h2>
            <p class="mt-1 text-sm text-gray-600">Sistem Absensi Terintegrasi</p>
        </div>

        @if (session()->has('error'))
            <div class="rounded-md bg-red-50 p-4 border-l-4 border-red-400 mb-4">
                <div class="flex"><div class="ml-3"><h3 class="text-sm font-medium text-red-800">Gagal</h3><div class="mt-1 text-sm text-red-700">{{ session('error') }}</div></div></div>
            </div>
        @endif

        @if (session()->has('message'))
            <div class="rounded-md bg-green-50 p-4 border-l-4 border-green-400 mb-4">
                <div class="flex"><div class="ml-3"><h3 class="text-sm font-medium text-green-800">Sukses</h3><div class="mt-1 text-sm text-green-700">{{ session('message') }}</div></div></div>
            </div>
        @endif

        @if($userStatus['is_banned'])
            <div class="bg-red-100 border-t-4 border-red-500 rounded-b text-red-900 px-4 py-3 shadow-md">
                <div class="flex">
                    <div class="py-1"><svg class="fill-current h-6 w-6 text-red-500 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"/></svg></div>
                    <div><p class="font-bold">AKUN DINONAKTIFKAN</p><p class="text-sm">Hubungi Administrator.</p></div>
                </div>
            </div>
        @else
            <div class="bg-white shadow-xl rounded-2xl overflow-hidden">
                <div class="px-6 py-5 bg-slate-50 border-b border-gray-200">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg leading-6 font-bold text-gray-900 flex items-center">
                            Halo, {{ Auth::user()->name }}
                        </h3>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
                              :class="config.is_wfa ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-blue-100 text-blue-800'">
                            <span x-text="config.is_wfa ? 'MODE WFA' : 'MODE KANTOR'"></span>
                        </span>
                    </div>
                    
                    @if($schedule)
                        <div class="text-sm text-gray-600 space-y-3">
                            <p><strong>Lokasi:</strong> <span x-text="config.is_wfa ? 'Bebas (Dinas)' : '{{ $schedule->office->name ?? '-' }}'"></span></p>
                            <div>
                                <label for="shift_id" class="block text-xs font-bold text-gray-700 uppercase tracking-wide mb-1">Shift</label>
                                <select wire:model="shift_id" id="shift_id" class="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm sm:text-sm" {{ $attendance && !$attendance->end_time ? 'disabled' : '' }}>
                                    @foreach($shifts as $shift)
                                        <option value="{{ $shift->id }}">{{ $shift->name }} ({{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }})</option>
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
                                <span class="px-3 py-1 rounded-full text-xs font-bold shadow-sm flex items-center gap-1 transition-colors duration-300"
                                      :class="insideRadius ? 'bg-green-500 text-white' : 'bg-red-500 text-white'">
                                    <span x-text="insideRadius ? (config.is_wfa ? 'WFA (OK)' : 'Dalam Radius') : 'Luar Radius'"></span>
                                </span>

                                <span class="px-3 py-1 rounded-full text-xs font-bold shadow-sm border transition-colors duration-300"
                                      :class="gpsStatusClass"
                                      x-text="gpsStatusText">
                                    Menunggu GPS...
                                </span>
                            </div>
                        </div>

                        <div x-show="!config.is_wfa && !insideRadius && gpsStatusText !== 'Menunggu GPS...'" class="mb-4 text-center p-2 bg-orange-50 rounded-lg border border-orange-200">
                            <p class="text-xs text-orange-700">
                                Anda berada <strong x-text="Math.round(currentDistance)"></strong> meter dari kantor.<br>
                                Maksimal radius: <strong x-text="config.office_radius"></strong> meter.
                            </p>
                        </div>

                        <div x-show="insideRadius" x-transition.opacity.duration.500ms>
                            <div class="mb-4">
                                <div class="relative w-full h-64 bg-black rounded-xl overflow-hidden shadow-inner border border-gray-300" wire:ignore>
                                    <video x-ref="video" autoplay playsinline muted class="w-full h-full object-cover"></video>
                                    <div class="absolute bottom-2 left-0 right-0 text-center">
                                        <span class="text-xs text-white bg-black/50 px-2 py-1 rounded-full">Kamera Aktif</span>
                                    </div>
                                </div>
                                <canvas x-ref="canvas" class="hidden"></canvas>
                            </div>

                            <button @click="captureAndStore()" 
                                    :disabled="loading"
                                    class="w-full py-4 px-4 text-lg font-extrabold rounded-xl text-white bg-blue-600 hover:bg-blue-700 shadow-lg flex justify-center items-center gap-2 disabled:bg-gray-400">
                                <span x-show="!loading" class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    {{ $attendance ? 'AMBIL FOTO & PULANG' : 'AMBIL FOTO & MASUK' }}
                                </span>
                                <span x-show="loading">Memproses...</span>
                            </button>
                        </div>

                        <div x-show="!insideRadius" x-transition>
                            <button @click="forceRefreshLocation()" class="w-full py-3 px-4 border border-gray-300 font-bold rounded-xl text-gray-700 bg-white hover:bg-gray-50 shadow-sm flex justify-center items-center gap-2">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                Refresh Lokasi
                            </button>
                            <p class="text-center text-xs text-gray-500 mt-2">Masuk ke area kantor untuk mengaktifkan kamera.</p>
                        </div>

                    @endif
                </div>
            </div>
        @endif
    </div>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        function attendanceSystem() {
            return {
                map: null,
                marker: null,
                insideRadius: false,
                currentDistance: 0,
                loading: false,
                cameraActive: false, // Flag agar initCamera tidak dipanggil berulang kali
                config: @json($userStatus),

                // Status GPS
                gpsStatusText: 'Menunggu GPS...',
                gpsStatusClass: 'bg-blue-100 text-blue-800 border-blue-200',
                isFakeGPS: false,
                locationHistory: [],

                initSystem() {
                    // 1. Cek HTTPS
                    if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
                        alert('Peringatan: Wajib menggunakan HTTPS agar kamera berfungsi!');
                    }

                    // 2. Init Map
                    this.initMap();

                    // 3. Mulai Tracking Lokasi SAJA (Kamera nanti setelah lokasi valid)
                    this.startTracking();
                },

                initMap() {
                    const center = (this.config.office_lat && this.config.office_lng) 
                        ? [this.config.office_lat, this.config.office_lng] 
                        : [-6.200000, 106.816666];

                    this.map = L.map('map', { zoomControl: false }).setView(center, 15);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '' }).addTo(this.map);

                    // Gambar lingkaran jika WFO
                    if (!this.config.is_wfa && this.config.office_lat) {
                        L.circle(center, {
                            color: '#ef4444', fillColor: '#ef4444', fillOpacity: 0.2, radius: this.config.office_radius
                        }).addTo(this.map);
                    }
                },

                startTracking() {
                    if (navigator.geolocation) {
                        navigator.geolocation.watchPosition(
                            (position) => this.handlePosition(position),
                            (error) => {
                                console.error(error);
                                this.gpsStatusText = 'GPS Error: ' + error.message;
                                this.gpsStatusClass = 'bg-red-100 text-red-800 border-red-200';
                            },
                            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                        );
                    } else {
                        alert("Browser tidak mendukung Geolocation.");
                    }
                },

                handlePosition(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;

                    // Update UI Status (Un-stuck)
                    if(this.gpsStatusText === 'Menunggu GPS...') {
                        this.gpsStatusText = 'Lokasi Ditemukan';
                        this.gpsStatusClass = 'bg-green-100 text-green-800 border-green-200';
                    }

                    // Update Map Marker
                    if (this.marker) this.map.removeLayer(this.marker);
                    this.marker = L.marker([lat, lng]).addTo(this.map);
                    this.map.setView([lat, lng], 17);

                    // Cek Radius Logic
                    this.checkRadiusLogic(lat, lng);

                    // Cek Fake GPS
                    this.checkFakeGPS(lat, lng);

                    // Kirim ke Livewire (silent update untuk validasi server)
                    @this.set('latitude', lat, true);
                    @this.set('longitude', lng, true);
                },

                checkRadiusLogic(userLat, userLng) {
                    let isInside = false;

                    if (this.config.is_wfa) {
                        isInside = true;
                        this.currentDistance = 0;
                    } else if (this.config.office_lat) {
                        const dist = this.haversineDistance(
                            [userLat, userLng], 
                            [this.config.office_lat, this.config.office_lng]
                        );
                        this.currentDistance = dist;
                        isInside = dist <= this.config.office_radius;
                    }

                    this.insideRadius = isInside;

                    // LOGIKA UTAMA KAMERA: 
                    // Jika masuk radius DAN kamera belum nyala -> Nyalakan Kamera
                    if (isInside && !this.cameraActive) {
                        this.initCamera();
                    }
                },

                initCamera() {
                    this.cameraActive = true; // Tandai sedang mencoba menyalakan
                    
                    // Panggil getUserMedia
                    // Kita gunakan timeout kecil untuk memastikan x-show sudah merender elemen <video>
                    setTimeout(() => {
                        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
                                .then(stream => {
                                    if(this.$refs.video) {
                                        this.$refs.video.srcObject = stream;
                                    }
                                })
                                .catch(err => {
                                    console.error("Camera Error:", err);
                                    alert("Gagal akses kamera. Pastikan izin diberikan.");
                                    this.cameraActive = false; // Reset flag jika gagal
                                });
                        }
                    }, 300);
                },

                checkFakeGPS(lat, lng) {
                    const now = new Date().getTime();
                    this.locationHistory.push({ lat, lng, time: now });

                    if (this.locationHistory.length > 10) this.locationHistory.shift();

                    if (this.locationHistory.length >= 3) {
                        let consistent = true;
                        for (let i = 1; i < this.locationHistory.length; i++) {
                            const prev = this.locationHistory[i - 1];
                            const curr = this.locationHistory[i];
                            const dist = this.haversineDistance([prev.lat, prev.lng], [curr.lat, curr.lng]);
                            const timeDiff = (curr.time - prev.time) / 1000;

                            if (timeDiff > 0) {
                                const speed = dist / timeDiff;
                                if (speed > 100 && dist > 50) { // 360km/h
                                    consistent = false;
                                    break;
                                }
                            }
                        }
                        
                        if (!consistent) {
                            this.isFakeGPS = true;
                            this.gpsStatusText = 'Terdeteksi Fake GPS!';
                            this.gpsStatusClass = 'bg-red-600 text-white border-red-700 animate-pulse';
                        } else {
                            this.isFakeGPS = false;
                            this.gpsStatusText = 'GPS Terverifikasi';
                            this.gpsStatusClass = 'bg-green-100 text-green-800 border-green-200';
                        }
                    }
                },

                async captureAndStore() {
                    if (this.isFakeGPS) {
                        alert('Sistem mendeteksi penggunaan Fake GPS. Mohon gunakan GPS asli.');
                        return;
                    }

                    if (!this.$refs.video || !this.$refs.video.srcObject) {
                        alert("Kamera belum siap. Coba refresh halaman.");
                        this.initCamera(); 
                        return;
                    }

                    this.loading = true;

                    const context = this.$refs.canvas.getContext('2d');
                    this.$refs.canvas.width = this.$refs.video.videoWidth;
                    this.$refs.canvas.height = this.$refs.video.videoHeight;
                    context.drawImage(this.$refs.video, 0, 0, this.$refs.canvas.width, this.$refs.canvas.height);

                    this.$refs.canvas.toBlob((blob) => {
                        const file = new File([blob], "selfie.jpg", { type: "image/jpeg" });
                        
                        @this.upload('photo', file, (uploadedFilename) => {
                            @this.call('store').then(() => {
                                this.loading = false;
                            });
                        }, () => {
                            alert('Gagal upload foto.');
                            this.loading = false;
                        });
                    }, 'image/jpeg', 0.8);
                },

                forceRefreshLocation() {
                    location.reload();
                },

                haversineDistance(coords1, coords2) {
                    const toRad = x => x * Math.PI / 180;
                    const R = 6371e3; // metres
                    const dLat = toRad(coords2[0] - coords1[0]);
                    const dLon = toRad(coords2[1] - coords1[1]);
                    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                            Math.cos(toRad(coords1[0])) * Math.cos(toRad(coords2[0])) *
                            Math.sin(dLon/2) * Math.sin(dLon/2);
                    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                    return R * c;
                }
            }
        }
    </script>
</div>