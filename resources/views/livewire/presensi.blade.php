<div class="min-h-screen bg-gray-50 py-6 px-4 sm:px-6 lg:px-8 flex justify-center">
    <div class="max-w-md w-full space-y-6">

        <div class="text-center">
            <h2 class="mt-2 text-3xl font-extrabold text-gray-900">
                Presensi Harian
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                Silakan lakukan absensi sesuai lokasi dan jadwal.
            </p>
        </div>

        @if (session()->has('error'))
            <div class="rounded-md bg-red-50 p-4 border-l-4 border-red-400 animate-pulse">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
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
            
            <div class="px-6 py-5 bg-slate-50 border-b border-gray-200 sm:px-8">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg leading-6 font-bold text-gray-900 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 mr-2 text-blue-600">
                            <path fill-rule="evenodd" d="M7.5 6a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM3.751 20.105a8.25 8.25 0 0116.498 0 .75.75 0 01-.437.695A18.683 18.683 0 0112 22.5c-2.786 0-5.433-.608-7.812-1.7a.75.75 0 01-.437-.695z" clip-rule="evenodd" />
                        </svg>
                        Informasi Pegawai
                    </h3>
                    @if($schedule && $schedule->is_wfa)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-green-400" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                        WFA (Bebas)
                    </span>
                    @elseif($schedule)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-blue-400" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3" /></svg>
                        WFO (Kantor)
                    </span>
                    @endif
                </div>
                
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500 font-medium flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 mr-1.5 opacity-70">
                                <path d="M10 8a3 3 0 100-6 3 3 0 000 6zM3.465 14.493a1.25 1.25 0 00.41 1.652L6.955 18.33a1.25 1.25 0 001.652-.41l2.073-3.592a1.25 1.25 0 00-.41-1.652L8.197 11.024a1.25 1.25 0 00-1.652.41l-2.073 3.592z" />
                                <path d="M12.073 16.678a1.25 1.25 0 001.652.41l3.08-2.185a1.25 1.25 0 00.41-1.652l-2.073-3.592a1.25 1.25 0 00-1.652-.41l-3.08 2.185a1.25 1.25 0 00-.41 1.652l2.073 3.592z" />
                            </svg>
                            Nama
                        </span>
                        <span class="font-bold text-gray-900">{{ Auth::user()->name }}</span>
                    </div>

                    @if($schedule)
                    <div class="flex justify-between border-t border-gray-100 pt-2">
                        <span class="text-gray-500 font-medium flex items-center">
                             <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 mr-1.5 opacity-70">
                                <path fill-rule="evenodd" d="M4 2a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V4a2 2 0 00-2-2H4zm2 4a1 1 0 011-1h1a1 1 0 110 2H7a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2h-1zm-5 5a1 1 0 011-1h1a1 1 0 110 2H7a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2h-1zM6 14a1 1 0 011-1h1a1 1 0 110 2H7a1 1 0 01-1-1zm6-1a1 1 0 100 2h1a1 1 0 100-2h-1z" clip-rule="evenodd" />
                              </svg>
                            Kantor
                        </span>
                        <span class="text-gray-900 text-right">{{ $schedule->office->name ?? '-' }}</span>
                    </div>
                     <div class="flex justify-between border-t border-gray-100 pt-2">
                        <span class="text-gray-500 font-medium flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 mr-1.5 opacity-70">
                                <path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.493 1.698 5.988 3.355 7.72 .829.799 1.654 1.38 2.274 1.766a11.55 11.55 0 001.04.573l.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd" />
                            </svg>
                            Titik Lokasi
                        </span>
                        <span class="text-gray-900 font-semibold text-right">{{ $schedule->officeLocation->name ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between border-t border-gray-100 pt-2 items-center">
                        <span class="text-gray-500 font-medium flex items-center">
                             <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 mr-1.5 opacity-70">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" />
                            </svg>
                            Shift
                        </span>
                        <div class="text-right">
                            <span class="text-gray-900 font-bold">{{ $schedule->shift->name }}</span>
                            <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($schedule->shift->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->shift->end_time)->format('H:i') }}</div>
                        </div>
                    </div>
                    @else
                    <div class="rounded-md bg-yellow-50 p-4 mt-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">Jadwal Belum Tersedia</h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <p>Hubungi administrator untuk mendapatkan jadwal shift.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-2 border-b border-gray-200 divide-x divide-gray-200 bg-white">
                <div class="p-4 text-center">
                    <p class="text-xs font-bold tracking-wider text-gray-400 uppercase mb-1 flex justify-center items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 mr-1 text-green-500">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" />
                        </svg>
                        Masuk
                    </p>
                    <p class="text-2xl font-extrabold text-gray-900">
                        {{ $attendance ? \Carbon\Carbon::parse($attendance->start_time)->format('H:i') : '--:--' }}
                    </p>
                </div>
                <div class="p-4 text-center">
                    <p class="text-xs font-bold tracking-wider text-gray-400 uppercase mb-1 flex justify-center items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 mr-1 text-red-500">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" />
                        </svg>
                        Pulang
                    </p>
                    <p class="text-2xl font-extrabold text-gray-900">
                        {{ $attendance && $attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time)->format('H:i') : '--:--' }}
                    </p>
                </div>
            </div>

            <div class="p-6 px-4 sm:px-8 bg-white">
                 <h3 class="text-lg leading-6 font-bold text-gray-900 mb-4 flex items-center">
                     <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 mr-2 text-red-600">
                      <path fill-rule="evenodd" d="M11.54 22.351l.07.04.028.016a.76.76 0 00.723 0l.028-.015.071-.041a16.975 16.975 0 001.144-.742 19.58 19.58 0 002.683-2.282c1.944-1.99 3.963-4.98 3.963-8.827a8.25 8.25 0 00-16.5 0c0 3.846 2.02 6.837 3.963 8.827a19.58 19.58 0 002.682 2.282 16.975 16.975 0 001.145.742zM12 13.5a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                    </svg>
                    Area Presensi
                </h3>

                @if($schedule)
                    <div class="relative rounded-xl overflow-hidden shadow-md border border-gray-200 mb-6">
                        <div id="map" style="height: 320px; width: 100%;" class="z-0" wire:ignore></div>
                        
                        <div class="absolute top-4 right-4 z-10">
                             @if($insideRadius)
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wide bg-green-500 text-white shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 mr-1">
                                      <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                    </svg>
                                    Di Dalam Radius
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-wide bg-red-500 text-white shadow-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 mr-1">
                                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                                    </svg>
                                    Di Luar Radius
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="space-y-3">
                        <button type="button" onclick="tagLocation()" class="group relative w-full flex justify-center py-3 px-4 border border-transparent font-bold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 ease-in-out shadow-sm hover:shadow-md">
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-300 group-hover:text-indigo-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </span>
                            Perbarui Lokasi Saya
                        </button>

                        @if($insideRadius)
                            <button wire:click="store" wire:loading.attr="disabled" class="group relative w-full flex justify-center py-4 px-4 border border-transparent text-lg font-extrabold rounded-xl text-white bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-300 ease-in-out shadow-lg hover:shadow-xl hover:scale-[1.02]">
                                <span class="absolute left-0 inset-y-0 flex items-center pl-4">
                                   <svg wire:loading.remove xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                     <svg wire:loading class="animate-spin h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                                {{ $attendance ? 'ABSEN PULANG' : 'ABSEN MASUK' }}
                            </button>
                             <p class="text-center text-xs text-gray-500 mt-2">Pastikan lokasi Anda akurat sebelum menekan tombol.</p>
                        @else
                             <button disabled class="w-full flex justify-center py-3 px-4 border border-gray-300 rounded-xl text-gray-400 bg-gray-100 cursor-not-allowed font-semibold">
                                Anda di luar radius absen
                            </button>
                        @endif
                    </div>
                @else
                    <div class="text-center p-6 bg-gray-50 rounded-xl border-2 border-dashed border-gray-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0121 18.382V7.618a1 1 0 01-1.447-.894L15 7m0 13V7m0 0L9 4" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Peta Tidak Tersedia</h3>
                        <p class="mt-1 text-sm text-gray-500">Jadwal belum diatur oleh admin.</p>
                    </div>
                @endif
            </div>
        </div>
         <p class="text-center text-xs text-gray-400 mt-4">
            &copy; {{ date('Y') }} Sistem Presensi WBA.
        </p>
    </div>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        let map;
        let marker;
        let component;
        
        const officeLat = {{ $schedule->officeLocation->latitude ?? 0 }};
        const officeLng = {{ $schedule->officeLocation->longitude ?? 0 }};
        const officeRadius = {{ $schedule->officeLocation->radius ?? 50 }};
        const officeCenter = [officeLat, officeLng];

        document.addEventListener('livewire:initialized', function() {
            component = @this;

            if (officeLat === 0 && officeLng === 0) return;

            map = L.map('map', { zoomControl: false }).setView(officeCenter, 16); // Hide zoom control for cleaner look
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '' // Hide attribution for cleaner mobile look (optional)
            }).addTo(map);

            const isWfa = {{ json_encode($schedule->is_wfa ?? false) }};
            
            if (!isWfa) {
                L.circle(officeCenter, {
                    color: '#ef4444', // Tailwind red-500
                    fillColor: '#ef4444',
                    fillOpacity: 0.2,
                    radius: officeRadius,
                    weight: 2
                }).addTo(map).bindPopup("Area Absen: {{ $schedule->officeLocation->name ?? 'Kantor' }}");
            }
        });

        function tagLocation() {
            // Efek loading sederhana pada tombol
            const btn = document.querySelector('button[onclick="tagLocation()"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="flex items-center justify-center"><svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Mencari Lokasi...</span>';
            btn.disabled = true;

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;

                    if (marker) map.removeLayer(marker);
                    
                    if(map) {
                        marker = L.marker([lat, lng]).addTo(map).bindPopup("Posisi Anda Saat Ini").openPopup();
                        map.setView([lat, lng], 17);
                    }

                    if (isWithinRadius(lat, lng)) {
                        component.set('insideRadius', true);
                        component.set('latitude', lat);
                        component.set('longitude', lng);
                    } else {
                        // alert("Anda berada di luar jangkauan absensi!"); // Alert standar diganti UI status
                        component.set('insideRadius', false);
                    }
                    
                    // Kembalikan tombol
                    btn.innerHTML = originalText;
                    btn.disabled = false;

                }, function(error) {
                    alert('Gagal mengambil lokasi: Pastikan GPS aktif dan izin lokasi diberikan.');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                });
            } else {
                alert('Browser tidak mendukung Geolocation');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        function isWithinRadius(userLat, userLng) {
            const isWfa = {{ json_encode($schedule->is_wfa ?? false) }};
            if (isWfa) return true;
            if (!map) return false;
            
            const distance = map.distance([userLat, userLng], officeCenter);
            return distance <= officeRadius;
        }
    </script>
</div>