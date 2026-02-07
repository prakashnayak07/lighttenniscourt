<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Date Range Filter --}}
        <x-filament::section>
            <x-slot name="heading">
                Select Date Range
            </x-slot>

            <form wire:submit="loadReports">
                {{ $this->form }}

                <div class="mt-4">
                    <x-filament::button type="submit">
                        Generate Reports
                    </x-filament::button>

                    <x-filament::button wire:click="exportBookingsCsv" color="success" class="ml-2">
                        Export Bookings CSV
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        {{-- Booking Statistics --}}
        <x-filament::section>
            <x-slot name="heading">
                Booking Statistics
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-sm text-gray-600">Total Bookings</div>
                    <div class="text-2xl font-bold text-blue-600">{{ $bookingStats['total_bookings'] ?? 0 }}</div>
                </div>

                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-sm text-gray-600">Confirmed</div>
                    <div class="text-2xl font-bold text-green-600">{{ $bookingStats['confirmed_bookings'] ?? 0 }}</div>
                </div>

                <div class="bg-yellow-50 p-4 rounded-lg">
                    <div class="text-sm text-gray-600">Pending</div>
                    <div class="text-2xl font-bold text-yellow-600">{{ $bookingStats['pending_bookings'] ?? 0 }}</div>
                </div>

                <div class="bg-red-50 p-4 rounded-lg">
                    <div class="text-sm text-gray-600">Cancelled</div>
                    <div class="text-2xl font-bold text-red-600">{{ $bookingStats['cancelled_bookings'] ?? 0 }}</div>
                </div>
            </div>
        </x-filament::section>

        {{-- Revenue Statistics --}}
        <x-filament::section>
            <x-slot name="heading">
                Revenue Statistics
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-purple-50 p-4 rounded-lg">
                    <div class="text-sm text-gray-600">Total Revenue</div>
                    <div class="text-2xl font-bold text-purple-600">{{ $revenueStats['total_revenue'] ?? '$0.00' }}</div>
                </div>

                <div class="bg-indigo-50 p-4 rounded-lg">
                    <div class="text-sm text-gray-600">Paid Bookings</div>
                    <div class="text-2xl font-bold text-indigo-600">{{ $revenueStats['paid_bookings'] ?? 0 }}</div>
                </div>

                <div class="bg-pink-50 p-4 rounded-lg">
                    <div class="text-sm text-gray-600">Average Booking Value</div>
                    <div class="text-2xl font-bold text-pink-600">
                        ${{ number_format(($revenueStats['average_booking_value_cents'] ?? 0) / 100, 2) }}
                    </div>
                </div>
            </div>
        </x-filament::section>

        {{-- Court Utilization --}}
        <x-filament::section>
            <x-slot name="heading">
                Court Utilization
            </x-slot>

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Court</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reservations</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hours Booked</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Utilization Rate</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($utilizationStats as $stat)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $stat['court_name'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $stat['total_reservations'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $stat['total_hours_booked'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        {{ floatval($stat['utilization_rate']) > 70 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ $stat['utilization_rate'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No data available for the selected date range
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
