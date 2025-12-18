<div class="min-h-screen bg-slate-50 flex flex-col items-center justify-center py-6 px-4 sm:px-6 lg:px-8 font-sans"
     x-data="attendanceApp()"
     x-init="initSystem()">

    <div class="w-full max-w-md bg-white rounded-3xl shadow-xl overflow-hidden border border-slate-100 relative">
        
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-blue-500 to-indigo-600"></div>

        <div class="px-6 pt-8 pb-4">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-sm text-slate-500 font-medium mb-1">{{ now()->format('l, d F Y') }}</p>
                    <h2 class="text-2xl font-bold text-slate-800">Halo, {{ Str::limit(Auth::user()->name, 15) }}! 👋</h2>
                </div>
                <div class="flex flex-col items-end gap-2">
                     <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold tracking-wide uppercase shadow-sm border"
                          :class="config.is_wfa ? 'bg-purple-50 text-purple-700 border-purple-100' : 'bg-blue-50 text-blue-700 border-blue-100'">
                        <span class="w-2 h-2 rounded-full mr-2" :class="config.is_wfa ? 'bg-purple-500' : 'bg-blue-500'"></span>
                        <span x-text="config.is_wfa ? 'WFA / DINAS' : 'WFO / KANTOR'"></span>
                    </span>
                    
                    @if($attendance)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[9px] font-bold tracking-wide uppercase shadow-sm border"
                             :class="!@json($attendance->end_time) ? 'bg-amber-50 text-amber-700 border-amber-100' : 'bg-green-50 text-green-700 border-green-100'">
                            <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="!@json($attendance->end_time) ? 'bg-amber-500 animate-pulse' : 'bg-green-500'"></span>
                            <span x-text="!@json($attendance->end_time) ? 'MASUK' : 'SELESAI'"></span>
                        </span>
                    @endif
                </div>
            </div>

            @if (session()->has('error'))
                <div class="mt-4 p-4 rounded-xl bg-red-50 text-red-700 border border-red-100 flex items-center gap-3 animate-fade-in-down">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="text-sm font-medium">{{ session('error') }}</span>
                </div>
            @endif
            
            @if (session()->has('message'))
                <div class="mt-4 p-4 rounded-xl bg-green-50 text-green-700 border border-green-100 flex items-center gap-3 animate-fade-in-down">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span class="text-sm font-medium">{{ session('message') }}</span>
                </div>
            @endif
        </div>

        @if($geoConfig['is_banned'])
            <div class="px-6 pb-8 text-center">
                <div class="bg-red-100 w-20 h-20 mx-auto rounded-full flex items-center justify-center mb-4">
                    <svg class="w-10 h-10 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900">Akun Dibekukan</h3>
                <p class="text-gray-500 mt-2 text-sm">Terdeteksi aktivitas mencurigakan. Silakan hubungi HRD/Admin.</p>
            </div>
        @else
            
            <div class="px-6 pb-6 space-y-5">
                
                @if($schedule)
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Jadwal Hari Ini</span>
                            <span class="text-xs font-bold text-slate-700 bg-white px-2 py-1 rounded border border-slate-200 shadow-sm">
                                {{ $schedule->office->name ?? 'Lokasi Bebas' }}
                            </span>
                        </div>
                        
                        <select wire:model="shift_id" 
                                class="w-full bg-white border-0 text-slate-700 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 block p-3 shadow-sm ring-1 ring-slate-200" 
                                {{ $attendance && !$attendance->end_time ? 'disabled' : '' }}>
                            @foreach($shifts as $shift)
                                <option value="{{ $shift->id }}">
                                    {{ $shift->name }} ({{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if($attendance)
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 p-4 rounded-2xl border border-blue-200">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-semibold text-blue-600 uppercase tracking-wider">Status Absensi</span>
                                <span class="text-xs font-bold text-white bg-blue-600 px-2 py-1 rounded-full">
                                    {{ $attendance->getWorkDateRange() }}
                                </span>
                            </div>
                            <div class="grid grid-cols-3 gap-3">
                                <div class="bg-white rounded-lg p-3 text-center shadow-sm border border-blue-100">
                                    <span class="text-[10px] text-slate-500 uppercase font-semibold tracking-wider">Masuk</span>
                                    <div class="text-lg font-bold text-slate-800 mt-1">
                                        {{ \Carbon\Carbon::parse($attendance->start_time)->format('H:i') }}
                                    </div>
                                    <div class="text-[9px] text-slate-400">
                                        {{ \Carbon\Carbon::parse($attendance->start_time)->format('d M') }}
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-center">
                                    <div class="text-center">
                                        @if($attendance->end_time)
                                            <svg class="w-6 h-6 text-green-500 mx-auto" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg>
                                        @else
                                            <div class="w-6 h-6 rounded-full border-2 border-blue-500 border-t-transparent animate-spin mx-auto"></div>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="bg-white rounded-lg p-3 text-center shadow-sm border {{ $attendance->end_time ? 'border-green-100' : 'border-amber-100' }}">
                                    <span class="text-[10px] {{ $attendance->end_time ? 'text-slate-500' : 'text-amber-600' }} uppercase font-semibold tracking-wider">Pulang</span>
                                    <div class="text-lg font-bold {{ $attendance->end_time ? 'text-slate-800' : 'text-amber-600' }} mt-1">
                                        {{ $attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time)->format('H:i') : '-' }}
                                    </div>
                                    <div class="text-[9px] {{ $attendance->end_time ? 'text-slate-400' : 'text-amber-400' }}">
                                        {{ $attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time)->format('d M') : 'Aktif' }}
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-3 bg-white rounded-lg p-3 text-center border border-blue-100">
                                <span class="text-[10px] text-slate-500 uppercase font-semibold tracking-wider">Total Durasi Kerja</span>
                                <div class="text-2xl font-bold text-blue-600 mt-1">
                                    {{ $attendance->workDuration() }}
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="relative group">
                        <div class="absolute top-3 left-3 z-[400] flex gap-2">
                             <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold shadow-md bg-white/90 backdrop-blur border border-white/50 transition-colors duration-300"
                                  :class="gpsReady ? 'text-green-600' : 'text-amber-500 animate-pulse'"
                                  x-text="gpsText"></span>
                        </div>
                        
                        <div class="absolute top-3 right-3 z-[400]">
                            <template x-if="!config.is_wfa">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold shadow-md text-white transition-all duration-300 flex items-center gap-1"
                                      :class="insideRadius ? 'bg-emerald-500' : 'bg-rose-500'">
                                    <span x-show="insideRadius">✓</span>
                                    <span x-show="!insideRadius">✕</span>
                                    <span x-text="insideRadius ? 'DALAM RADIUS' : 'LUAR RADIUS'"></span>
                                </span>
                            </template>
                        </div>

                        <div id="map" wire:ignore class="h-48 w-full rounded-2xl shadow-sm border border-slate-200 z-0"></div>
                        
                        <div x-show="!config.is_wfa && gpsReady" 
                             class="absolute -bottom-3 left-1/2 transform -translate-x-1/2 z-[400]">
                            <div class="bg-white/95 backdrop-blur px-4 py-1.5 rounded-full shadow-md border border-slate-100 text-[11px] font-medium text-slate-600 whitespace-nowrap">
                                Jarak: <span class="font-bold text-slate-800" x-text="distance"></span>m 
                                <span class="text-slate-400 mx-1">|</span> 
                                Max: <span x-text="config.radius_meter"></span>m
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 relative" wire:ignore>
                        <div class="aspect-[4/3] bg-slate-900 rounded-2xl overflow-hidden shadow-inner relative ring-4 ring-slate-100">
                            <video x-ref="video" autoplay playsinline muted class="w-full h-full object-cover"></video>
                            <canvas x-ref="canvas" class="hidden"></canvas>
                            
                            <div class="absolute inset-0 pointer-events-none flex flex-col justify-between p-4">
                                <div class="flex justify-center">
                                    <span x-show="cameraStarted" class="bg-black/30 backdrop-blur text-white text-[10px] px-2 py-0.5 rounded-md">Live Preview</span>
                                </div>
                                <div class="flex justify-between items-end opacity-50">
                                    <div class="w-8 h-8 border-l-2 border-b-2 border-white rounded-bl-lg"></div>
                                    <div class="w-8 h-8 border-r-2 border-b-2 border-white rounded-br-lg"></div>
                                </div>
                            </div>
                            <div class="absolute top-4 left-4 right-4 flex justify-between items-start opacity-50 pointer-events-none">
                                <div class="w-8 h-8 border-l-2 border-t-2 border-white rounded-tl-lg"></div>
                                <div class="w-8 h-8 border-r-2 border-t-2 border-white rounded-tr-lg"></div>
                            </div>
                        </div>
                        
                        <div x-show="!cameraStarted" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-100 rounded-2xl z-10 text-slate-400">
                            <svg class="w-12 h-12 mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            <span class="text-xs">Menunggu Sinyal GPS...</span>
                        </div>
                    </div>

                    <div class="pt-2 pb-2">
                        <button type="button"
                                @click="capture()"
                                :disabled="!canSubmit || loading"
                                class="w-full relative group overflow-hidden rounded-2xl p-4 transition-all duration-300 transform active:scale-[0.98] shadow-lg hover:shadow-xl disabled:opacity-70 disabled:cursor-not-allowed disabled:shadow-none"
                                :class="(!canSubmit || loading) ? 'bg-slate-200 text-slate-400' : 'bg-gradient-to-br from-blue-600 to-indigo-700 text-white'">
                            
                            <div class="relative flex items-center justify-center gap-3">
                                <svg x-show="loading" class="animate-spin h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                
                                <div x-show="!loading" style="display: flex;" class="flex flex-col items-center">
                                    <span class="text-lg font-bold tracking-wide text-white" x-text="
                                        !gpsReady ? 'MENUNGGU GPS...' :
                                        isFakeGPS ? 'LOKASI PALSU!' :
                                        (gpsReady && !isFakeGPS && !insideRadius && !config.is_wfa) ? 'DILUAR JANGKAUAN' :
                                        @json($attendance ? 'ABSEN PULANG' : 'ABSEN HADIR')
                                    "></span>
                                    <span class="text-[10px] opacity-90 font-normal mt-0.5 text-white" x-text="canSubmit ? 'Tap untuk mengambil foto' : 'Tombol terkunci otomatis'"></span>
                                </div>
                            </div>
                        </button>
                    </div>

                    <button @click="location.reload()" class="mx-auto block text-slate-400 text-xs hover:text-slate-600 transition-colors flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Refresh Lokasi
                    </button>

                @else
                    <div class="text-center py-10">
                        <div class="bg-gray-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900">Tidak Ada Jadwal</h3>
                        <p class="text-gray-500 text-sm mt-1">Anda tidak memiliki jadwal kerja hari ini.</p>
                    </div>
                @endif
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
                
                // State Variables
                gpsReady: false,
                insideRadius: false,
                isFakeGPS: false,
                distance: 0,
                loading: false,
                gpsText: 'Mencari Satelit...',
                
                // Logic Variables
                cameraStarted: false,
                myLat: 0,
                myLng: 0,
                history: [],

                initSystem() {
                    if(this.config.is_banned) return;
                    this.initMap();
                    this.watchLocation();
                },

                startCamera() {
                    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
                            .then(stream => {
                                this.$refs.video.srcObject = stream;
                            })
                            .catch(err => {
                                console.error(err);
                                alert("Tidak dapat mengakses kamera. Pastikan izin diberikan.");
                            });
                    }
                },

                initMap() {
                    // Default view Jakarta if no config
                    const center = (this.config.office_lat) ? [this.config.office_lat, this.config.office_lng] : [-6.2088, 106.8456];
                    
                    this.map = L.map('map', { 
                        zoomControl: false,
                        attributionControl: false,
                        dragging: false, // Lock map interaction for simplicity
                        touchZoom: false,
                        scrollWheelZoom: false
                    }).setView(center, 16);
                    
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(this.map);

                    // Marker Kantor & Radius (Hanya jika WFO)
                    if (!this.config.is_wfa && this.config.office_lat) {
                        // Icon Kantor
                        const officeIcon = L.icon({
                            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/markers-default/blue-2x.png',
                            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                            iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
                        });
                        
                        L.marker(center, {icon: officeIcon}).addTo(this.map);

                        // Lingkaran Radius
                        this.circle = L.circle(center, {
                            color: '#3b82f6', // Tailwind blue-500
                            fillColor: '#3b82f6',
                            fillOpacity: 0.1,
                            weight: 1,
                            radius: this.config.radius_meter
                        }).addTo(this.map);
                    }
                },

                watchLocation() {
                    if (!navigator.geolocation) {
                        this.gpsText = 'Browser Error';
                        return;
                    }
                    navigator.geolocation.watchPosition(
                        (pos) => {
                            this.myLat = pos.coords.latitude;
                            this.myLng = pos.coords.longitude;
                            this.gpsReady = true;
                            this.gpsText = 'GPS Terkunci Akurat (' + Math.round(pos.coords.accuracy) + 'm)';

                            this.updateMap();
                            this.checkLogic();
                        },
                        (err) => {
                            this.gpsText = 'Gagal: ' + err.message;
                        },
                        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                    );
                },

                updateMap() {
                    if(!this.map) return;
                    
                    // Icon User (Red)
                    const userIcon = L.icon({
                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/markers-default/red-2x.png',
                        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                        iconSize: [25, 41], iconAnchor: [12, 41], popupAnchor: [1, -34], shadowSize: [41, 41]
                    });

                    if (this.marker) this.map.removeLayer(this.marker);
                    this.marker = L.marker([this.myLat, this.myLng], {icon: userIcon}).addTo(this.map);
                    
                    // Center map to user
                    this.map.panTo([this.myLat, this.myLng]); 
                },

                checkLogic() {
                    // 1. Cek Radius
                    if (this.config.is_wfa) {
                        this.insideRadius = true;
                        this.distance = 0;
                    } else {
                        const dist = this.getDistanceFromLatLonInKm(this.myLat, this.myLng, this.config.office_lat, this.config.office_lng) * 1000;
                        this.distance = Math.round(dist);
                        this.insideRadius = this.distance <= this.config.radius_meter;
                    }

                    // 2. Cek Fake GPS (Simple Speed Logic)
                    const now = Date.now();
                    this.history.push({ lat: this.myLat, lng: this.myLng, time: now });
                    if(this.history.length > 5) this.history.shift();
                    
                    if(this.history.length > 1) {
                        const last = this.history[this.history.length - 1];
                        const prev = this.history[this.history.length - 2];
                        const distMove = this.getDistanceFromLatLonInKm(prev.lat, prev.lng, last.lat, last.lng) * 1000;
                        const timeDiff = (last.time - prev.time) / 1000; // seconds
                        
                        if(timeDiff > 0) {
                            const speed = distMove / timeDiff; // m/s
                            // Threshold: 100 m/s (~360km/h) implies teleportation/spoofing
                            this.isFakeGPS = (speed > 100 && distMove > 100); 
                        }
                    }

                    // 3. Auto Start Camera
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
                        alert("Kamera sedang memuat, silakan tunggu..."); return;
                    }

                    this.loading = true;
                    // Ambil resolusi asli video
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    // Draw image
                    canvas.getContext('2d').drawImage(video, 0, 0);

                    // Convert to blob and upload
                    canvas.toBlob((blob) => {
                        if(!blob) {
                            alert("Gagal mengambil gambar."); 
                            this.loading = false;
                            return;
                        }
                        const file = new File([blob], "foto_absen.jpg", { type: "image/jpeg" });
                        
                        // Upload ke Livewire Component
                        @this.upload('photo', file, (uploadedFilename) => {
                            // Setelah upload sukses, panggil fungsi store
                            @this.store(this.myLat, this.myLng).then(() => {
                                // Loading dimatikan oleh re-render Livewire biasanya, 
                                // tapi kita set false untuk jaga-jaga
                                this.loading = false; 
                            });
                        }, () => {
                            alert("Gagal mengupload foto. Koneksi internet mungkin tidak stabil.");
                            this.loading = false;
                        });
                    }, 'image/jpeg', 0.8); // 0.8 quality jpeg
                },

                getDistanceFromLatLonInKm(lat1, lon1, lat2, lon2) {
                    var R = 6371; 
                    var dLat = this.deg2rad(lat2-lat1);  
                    var dLon = this.deg2rad(lon2-lon1); 
                    var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
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