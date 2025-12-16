<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center gap-x-4">
            <div class="flex-shrink-0">
                <img class="h-16 w-16 rounded-full object-cover border-2 border-gray-200" 
                     src="{{ $user->image_url ?? 'https://ui-avatars.com/api/?name='.urlencode($user->name) }}" 
                     alt="{{ $user->name }}">
            </div>
            
            <div class="flex-1">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    Halo, {{ $user->name }} 👋
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $user->position->name ?? 'Karyawan' }} | {{ $user->email }}
                </p>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="flex items-center p-3 bg-gray-50 rounded-lg border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                        <div class="p-2 bg-blue-100 rounded-full text-blue-600 mr-3">
                            <x-heroicon-o-clock class="w-5 h-5"/>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">Jadwal Shift</p>
                            @if($schedule)
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    {{ $schedule->shift->name }} 
                                    ({{ \Carbon\Carbon::parse($schedule->shift->start_time)->format('H:i') }} - 
                                     {{ \Carbon\Carbon::parse($schedule->shift->end_time)->format('H:i') }})
                                </p>
                            @else
                                <p class="text-red-500 text-sm">Belum ada jadwal</p>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center p-3 bg-gray-50 rounded-lg border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                        <div class="p-2 bg-green-100 rounded-full text-green-600 mr-3">
                            <x-heroicon-o-map-pin class="w-5 h-5"/>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 uppercase">Lokasi Penempatan</p>
                            @if($placement)
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    {{ $placement->office->name ?? '-' }}
                                    <span class="text-xs font-normal text-gray-500">({{ ucfirst($placement->is_backup) }})</span>
                                </p>
                            @elseif($schedule)
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    {{ $schedule->office->name ?? 'Kantor Pusat' }}
                                </p>
                            @else
                                <p class="text-gray-500 text-sm">-</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>