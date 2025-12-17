<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8 flex justify-center"
     x-data="attendanceApp()"
     x-init="initSystem()">

    <div class="max-w-md w-full space-y-6">

        <div class="text-center">
            <h2 class="mt-2 text-3xl font-extrabold text-gray-900">Presensi WBA</h2>
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

        @if($geoConfig['is_banned'])
             <div class="bg-red-600 text-white p-6 rounded-xl shadow-lg text-center">
                <svg class="h-16 w-16 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <h2 class="text-xl font-bold">AKUN DIBEKUKAN</h2>
                <p class="mt-2">Hubungi Administrator.</p>
            </div>
        @else
            <div class="bg-white shadow-xl rounded-2xl overflow-hidden">
                <div class="px-6 py-5 bg-slate-50 border-b border-gray-200">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-lg leading-6 font-bold text-gray-900">Halo, {{ Auth::user()->name }}</h3>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
                              :class="config.is_wfa ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-blue-100 text-blue-800'">
                            <span x-text="config.is_wfa ? 'MODE WFA' : 'MODE KANTOR'"></span>
                        </span>
                    </div>
                    @if($schedule)
                    <div class="text-sm text-gray-600">
                        <p>Lokasi: <span x-text="config.is_wfa ? 'Bebas (Dinas)' : '{{ $schedule->office->name ?? '-' }}'"></span></p>
                        <div class="mt-2">
                            <label class="block text-xs font-bold uppercase text-gray-500">Shift</label>
                            <select wire:model="shift_id" class="block w-full mt-1 py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm sm:text-sm" {{ $attendance && !$attendance->end_time ? 'disabled' : '' }}>
                                @foreach($shifts as $shift)
                                    <option value="{{ $shift->id }}">{{ $shift->name }} ({{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @endif
                </div>

                <div class="p-6 bg-white space-y-4">
                    @if($schedule)
                    
                    <div class="relative rounded-xl overflow-hidden shadow-md border border-gray-200 h-64">
                        <div id="map" style="height: 100%; width: 100%;" wire:ignore></div>
                        <div class="absolute top-2 right-2 z-[9999] flex flex-col gap-1 items-end">
                            <span class="px-2 py-1 rounded text-[10px] font-bold shadow bg-white/90"
                                  :class="gpsReady ? 'text-green-600' : 'text-red-600'"
                                  x-text="gpsText"></span>
                            
                            <template x-if="!config.is_wfa">
                                <span class="px-2 py-1 rounded text-[10px] font-bold shadow text-white"
                                      :class="insideRadius ? 'bg-green-500' : 'bg-red-500'"
                                      x-text="insideRadius ? 'DALAM RADIUS' : 'LUAR RADIUS'"></span>
                            </template>
                        </div>
                    </div>

                    <div x-show="!config.is_wfa && gpsReady" class="text-center bg-gray-100 p-2 rounded text-xs text-gray-600">
                        Jarak ke Kantor: <strong x-text="distance + ' meter'"></strong> (Maks: <span x-text="config.radius_meter"></span>m)
                    </div>

                    <div class="relative bg-black rounded-xl overflow-hidden shadow-inner h-64" wire:ignore>
                        <video x-ref="video" autoplay playsinline muted class="w-full h-full object-cover"></video>
                        <canvas x-ref="canvas" class="hidden"></canvas>
                        <div class="absolute bottom-2 left-0 right-0 text-center">
                            <span class="text-[10px] text-white bg-black/50 px-2 py-1 rounded-full">Preview Kamera</span>
                        </div>
                    </div>

                    <button type="button"
                            @click="capture()"
                            :disabled="!canSubmit || loading"
                            class="w-full py-4 px-4 text-lg font-extrabold rounded-xl text-white shadow-lg flex justify-center items-center gap-2 transition-all"
                            :class="(!canSubmit || loading) ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'">
                        
                        <span x-show="loading" class="animate-pulse">Memproses Absen...</span>
                        
                        <span x-show="!loading" class="flex items-center gap-2">
                            <template x-if="!gpsReady"><span>TUNGGU GPS...</span></template>
                            <template x-if="gpsReady && isFakeGPS"><span>FAKE GPS TERDETEKSI</span></template>
                            <template x-if="gpsReady && !isFakeGPS && !insideRadius && !config.is_wfa"><span>DILUAR LOKASI</span></template>
                            <template x-if="gpsReady && !isFakeGPS && (insideRadius || config.is_wfa)">
                                <span class="flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    {{ $attendance ? 'FOTO & PULANG' : 'FOTO & MASUK' }}
                                </span>
                            </template>
                        </span>
                    </button>
                    
                    <button @click="location.reload()" class="w-full text-xs text-blue-500 underline mt-2">
                        Refresh Halaman jika Macet
                    </button>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        function attendanceApp() {
            return {
                config: @json($geoConfig),
                map: null,
                marker: null,
                circle: null,
                
                // State
                gpsReady: false,
                insideRadius: false,
                isFakeGPS: false,
                distance: 0,
                loading: false,
                gpsText: 'Mencari GPS...',
                
                // Vars
                cameraStarted: false,
                myLat: 0,
                myLng: 0,
                history: [],

                initSystem() {
                    console.log('=== DEBUG CONFIG ===');
                    console.log('Config received:', this.config);
                    console.log('radius_meter:', this.config.radius_meter);
                    console.log('office_lat:', this.config.office_lat);
                    console.log('office_lng:', this.config.office_lng);
                    console.log('is_wfa:', this.config.is_wfa);
                    console.log('is_banned:', this.config.is_banned);
                    console.log('===================');
                    
                    if(this.config.is_banned) return;

                    // 1. Jalankan Maps
                    this.initMap();

                    // 2. Jalankan GPS Tracking (Kamera akan otomatis menyala setelah GPS valid)
                    this.watchLocation();
                },

                startCamera() {
                    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
                            .then(stream => {
                                this.$refs.video.srcObject = stream;
                            })
                            .catch(err => {
                                alert("Gagal akses kamera: " + err.message + ". Pastikan di HTTPS/Localhost.");
                            });
                    }
                },

                initMap() {
                    const center = (this.config.office_lat) ? [this.config.office_lat, this.config.office_lng] : [-6.2088, 106.8456];
                    
                    this.map = L.map('map', { zoomControl: false }).setView(center, 15);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '' }).addTo(this.map);

                    // Gambar Radius Kantor jika WFO
                    if (!this.config.is_wfa && this.config.office_lat) {
                        this.circle = L.circle(center, {
                            color: 'red', fillColor: '#f03', fillOpacity: 0.2, radius: this.config.radius_meter
                        }).addTo(this.map);
                    }
                },

                watchLocation() {
                    if (!navigator.geolocation) {
                        alert("Browser tidak support GPS.");
                        return;
                    }
                    navigator.geolocation.watchPosition(
                        (pos) => {
                            this.myLat = pos.coords.latitude;
                            this.myLng = pos.coords.longitude;
                            this.gpsReady = true;
                            this.gpsText = 'GPS Aktif';

                            this.updateMap();
                            this.checkLogic();
                        },
                        (err) => {
                            this.gpsText = 'GPS Error: ' + err.message;
                        },
                        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                    );
                },

                updateMap() {
                    if(!this.map) return;
                    
                    if (this.marker) this.map.removeLayer(this.marker);
                    this.marker = L.marker([this.myLat, this.myLng]).addTo(this.map);
                    this.map.setView([this.myLat, this.myLng]); 
                },

                checkLogic() {
                    // 1. Cek Radius
                    if (this.config.is_wfa) {
                        this.insideRadius = true;
                        this.distance = 0;
                    } else {
                        const dist = this.getDistanceFromLatLonInKm(this.myLat, this.myLng, this.config.office_lat, this.config.office_lng) * 1000; // ke meter
                        this.distance = Math.round(dist);
                        this.insideRadius = this.distance <= this.config.radius_meter;
                    }

                    // 2. Cek Fake GPS (Speed Check)
                    const now = Date.now();
                    this.history.push({ lat: this.myLat, lng: this.myLng, time: now });
                    if(this.history.length > 5) this.history.shift();
                    
                    if(this.history.length > 1) {
                        const last = this.history[this.history.length - 1];
                        const prev = this.history[this.history.length - 2];
                        const distMove = this.getDistanceFromLatLonInKm(prev.lat, prev.lng, last.lat, last.lng) * 1000;
                        const timeDiff = (last.time - prev.time) / 1000; // detik
                        
                        if(timeDiff > 0) {
                            const speed = distMove / timeDiff; // meter/detik
                            // 100 m/s = 360 km/jam -> Mustahil
                            if(speed > 100 && distMove > 100) {
                                this.isFakeGPS = true;
                            } else {
                                this.isFakeGPS = false;
                            }
                        }
                    }

                    // 3. Auto-start kamera setelah kondisi terpenuhi
                    if (!this.cameraStarted && this.gpsReady && !this.isFakeGPS && (this.insideRadius || this.config.is_wfa)) {
                        this.startCamera();
                        this.cameraStarted = true;
                    }
                },

                get canSubmit() {
                    return this.gpsReady && !this.isFakeGPS && (this.insideRadius || this.config.is_wfa);
                },

                capture() {
                    if (!this.canSubmit) return;
                    
                    const video = this.$refs.video;
                    const canvas = this.$refs.canvas;
                    
                    if (!video.srcObject) {
                        alert("Kamera belum siap."); return;
                    }

                    this.loading = true;
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    canvas.getContext('2d').drawImage(video, 0, 0);

                    canvas.toBlob((blob) => {
                        const file = new File([blob], "foto_absen.jpg", { type: "image/jpeg" });
                        
                        // Upload ke Livewire
                        @this.upload('photo', file, (uploadedFilename) => {
                            // Panggil store dengan parameter koordinat
                            @this.store(this.myLat, this.myLng).then(() => {
                                this.loading = false;
                            });
                        }, () => {
                            alert("Gagal upload foto.");
                            this.loading = false;
                        });
                    }, 'image/jpeg', 0.8);
                },

                // Rumus Jarak Haversine
                getDistanceFromLatLonInKm(lat1, lon1, lat2, lon2) {
                    var R = 6371; 
                    var dLat = this.deg2rad(lat2-lat1);  
                    var dLon = this.deg2rad(lon2-lon1); 
                    var a = 
                        Math.sin(dLat/2) * Math.sin(dLat/2) +
                        Math.cos(this.deg2rad(lat1)) * Math.cos(this.deg2rad(lat2)) * Math.sin(dLon/2) * Math.sin(dLon/2); 
                    var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)); 
                    var d = R * c; 
                    return d;
                },
                deg2rad(deg) { return deg * (Math.PI/180) }
            }
        }
    </script>
</div>  