<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between w-full">
                <h3 class="text-lg font-semibold">Maintenance Calendar</h3>
                
                <div class="flex items-center gap-2">
                    <button 
                        wire:click="previousMonth" 
                        class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    
                    <span class="text-lg font-medium min-w-[150px] text-center">
                        {{ $this->getMonthName() }}
                    </span>
                    
                    <button 
                        wire:click="nextMonth" 
                        class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>
        </x-slot>

        <div class="overflow-x-auto">
            {{-- Calendar Header --}}
            <div class="grid grid-cols-7 gap-px bg-gray-200 dark:bg-gray-700 rounded-t-lg overflow-hidden">
                @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
                    <div class="bg-gray-50 dark:bg-gray-800 p-2 text-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                        {{ $day }}
                    </div>
                @endforeach
            </div>

            {{-- Calendar Grid --}}
            <div class="grid grid-cols-7 gap-px bg-gray-200 dark:bg-gray-700 rounded-b-lg overflow-hidden">
                @foreach($this->getCalendarData() as $week)
                    @foreach($week as $day)
                        <div class="bg-white dark:bg-gray-900 min-h-[100px] p-2 
                            {{ !$day['isCurrentMonth'] ? 'opacity-50' : '' }}
                            {{ $day['isToday'] ? 'ring-2 ring-blue-500' : '' }}">
                            
                            {{-- Date Number --}}
                            <div class="text-sm font-semibold mb-1 
                                {{ $day['isToday'] ? 'text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300' }}">
                                {{ $day['date']->format('j') }}
                            </div>

                            {{-- Maintenance Items --}}
                            <div class="space-y-1">
                                @foreach($day['schedules'] as $schedule)
                                    <div class="text-xs p-1 rounded truncate
                                        @if($schedule['status'] === 'scheduled')
                                            bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                        @elseif($schedule['status'] === 'in_progress')
                                            bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                        @elseif($schedule['status'] === 'completed')
                                            bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                        @else
                                            bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                        @endif">
                                        <div class="font-medium">{{ $schedule['time'] }}</div>
                                        <div class="truncate">{{ $schedule['court'] }}</div>
                                        <div class="capitalize text-[10px]">{{ str_replace('_', ' ', $schedule['type']) }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>

        {{-- Legend --}}
        <div class="mt-4 flex flex-wrap gap-4 text-sm">
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded bg-blue-100 dark:bg-blue-900"></div>
                <span class="text-gray-700 dark:text-gray-300">Scheduled</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded bg-yellow-100 dark:bg-yellow-900"></div>
                <span class="text-gray-700 dark:text-gray-300">In Progress</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded bg-green-100 dark:bg-green-900"></div>
                <span class="text-gray-700 dark:text-gray-300">Completed</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded bg-gray-100 dark:bg-gray-700"></div>
                <span class="text-gray-700 dark:text-gray-300">Cancelled</span>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
