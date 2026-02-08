<x-filament-panels::page>
    @assets
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" crossorigin="anonymous"></script>
    @endassets

    {{-- Data for charts (updated on morph so we can redraw) --}}
    <div id="reports-chart-data" class="hidden">
        <script type="application/json" id="reports-booking-stats">{{ json_encode($bookingStats ?? []) }}</script>
        <script type="application/json" id="reports-utilization">{{ json_encode($utilizationStats ?? []) }}</script>
    </div>

    <div class="space-y-6">
        {{-- Filters: full width --}}
        <x-filament::section>
            <x-slot name="heading">Filters</x-slot>
            <form wire:submit="loadReports">
                {{ $this->form }}
                <div class="mt-4 flex flex-wrap gap-2">
                    <x-filament::button type="submit">Generate Reports</x-filament::button>
                    <x-filament::button wire:click="exportBookingsCsv" color="success">Export by organization (CSV)</x-filament::button>
                </div>
            </form>
        </x-filament::section>

        {{-- Grid: stat cards + charts --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {{-- Booking stats cards --}}
            <div class="lg:col-span-12">
                <x-filament::section>
                    <x-slot name="heading">Booking statistics</x-slot>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total</p>
                            <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ $bookingStats['total_bookings'] ?? 0 }}</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Confirmed</p>
                            <p class="mt-1 text-2xl font-semibold text-emerald-600 dark:text-emerald-400">{{ $bookingStats['confirmed_bookings'] ?? 0 }}</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending</p>
                            <p class="mt-1 text-2xl font-semibold text-amber-600 dark:text-amber-400">{{ $bookingStats['pending_bookings'] ?? 0 }}</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Cancelled</p>
                            <p class="mt-1 text-2xl font-semibold text-red-600 dark:text-red-400">{{ $bookingStats['cancelled_bookings'] ?? 0 }}</p>
                        </div>
                    </div>
                </x-filament::section>
            </div>

            {{-- Chart: Booking status (bar) --}}
            <div class="lg:col-span-6">
                <x-filament::section>
                    <x-slot name="heading">Bookings by status</x-slot>
                    <div class="h-64">
                        <canvas id="chart-booking-status" role="img" aria-label="Bookings by status"></canvas>
                    </div>
                </x-filament::section>
            </div>

            {{-- Chart: Bookings by court (bar) --}}
            <div class="lg:col-span-6">
                <x-filament::section>
                    <x-slot name="heading">Bookings by court</x-slot>
                    <div class="h-64">
                        <canvas id="chart-bookings-by-court" role="img" aria-label="Bookings by court"></canvas>
                    </div>
                </x-filament::section>
            </div>

            {{-- Revenue cards --}}
            <div class="lg:col-span-12">
                <x-filament::section>
                    <x-slot name="heading">Revenue</x-slot>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total revenue</p>
                            <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ $revenueStats['total_revenue'] ?? '$0.00' }}</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Paid bookings</p>
                            <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ $revenueStats['paid_bookings'] ?? 0 }}</p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Average booking value</p>
                            <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">${{ number_format(($revenueStats['average_booking_value_cents'] ?? 0) / 100, 2) }}</p>
                        </div>
                    </div>
                </x-filament::section>
            </div>

            {{-- Chart: Court utilization (bar) --}}
            <div class="lg:col-span-12">
                <x-filament::section>
                    <x-slot name="heading">Court utilization</x-slot>
                    <div class="h-64">
                        <canvas id="chart-utilization" role="img" aria-label="Court utilization rate"></canvas>
                    </div>
                </x-filament::section>
            </div>

            {{-- Utilization table --}}
            <div class="lg:col-span-12">
                <x-filament::section>
                    <x-slot name="heading">Court utilization (table)</x-slot>
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Court</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Reservations</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Hours booked</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">Utilization</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                                @forelse($utilizationStats as $stat)
                                    <tr>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ $stat['court_name'] }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $stat['total_reservations'] }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $stat['total_hours_booked'] }}</td>
                                        <td class="whitespace-nowrap px-4 py-3">
                                            @php $rate = (float) $stat['utilization_rate']; @endphp
                                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $rate > 70 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400' }}">
                                                {{ $stat['utilization_rate'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No data for the selected date range.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>
            </div>
        </div>
    </div>

    @script
    <script>
        (function () {
            function getData() {
                const bookingEl = document.getElementById('reports-booking-stats');
                const utilEl = document.getElementById('reports-utilization');
                return {
                    booking: bookingEl ? (function () { try { return JSON.parse(bookingEl.textContent); } catch (e) { return {}; } })() : {},
                    utilization: utilEl ? (function () { try { return JSON.parse(utilEl.textContent); } catch (e) { return []; } })() : []
                };
            }

            let chartInstances = [];

            function destroyCharts() {
                chartInstances.forEach(c => { if (c) c.destroy(); });
                chartInstances = [];
            }

            function drawCharts() {
                destroyCharts();
                const { booking: bookingStats, utilization: utilizationStats } = getData();

                if (typeof Chart === 'undefined') return;

                const statusCtx = document.getElementById('chart-booking-status');
                if (statusCtx) {
                    chartInstances.push(new Chart(statusCtx, {
                        type: 'bar',
                        data: {
                            labels: ['Total', 'Confirmed', 'Pending', 'Cancelled'],
                            datasets: [{
                                label: 'Bookings',
                                data: [
                                    bookingStats.total_bookings ?? 0,
                                    bookingStats.confirmed_bookings ?? 0,
                                    bookingStats.pending_bookings ?? 0,
                                    bookingStats.cancelled_bookings ?? 0
                                ],
                                backgroundColor: ['rgba(59, 130, 246, 0.8)', 'rgba(16, 185, 129, 0.8)', 'rgba(245, 158, 11, 0.8)', 'rgba(239, 68, 68, 0.8)'],
                                borderColor: ['rgb(59, 130, 246)', 'rgb(16, 185, 129)', 'rgb(245, 158, 11)', 'rgb(239, 68, 68)'],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                        }
                    }));
                }

                const courtCtx = document.getElementById('chart-bookings-by-court');
                if (courtCtx) {
                    const byCourt = bookingStats.bookings_by_court ?? [];
                    chartInstances.push(new Chart(courtCtx, {
                        type: 'bar',
                        data: {
                            labels: byCourt.map(c => c.court_name),
                            datasets: [{
                                label: 'Bookings',
                                data: byCourt.map(c => c.count),
                                backgroundColor: 'rgba(99, 102, 241, 0.8)',
                                borderColor: 'rgb(99, 102, 241)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            indexAxis: 'y',
                            plugins: { legend: { display: false } },
                            scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }
                        }
                    }));
                }

                const utilCtx = document.getElementById('chart-utilization');
                if (utilCtx) {
                    const rates = utilizationStats.map(s => (s.utilization_rate_raw != null ? s.utilization_rate_raw : parseFloat(String(s.utilization_rate || '0').replace('%', ''))));
                    chartInstances.push(new Chart(utilCtx, {
                        type: 'bar',
                        data: {
                            labels: utilizationStats.map(s => s.court_name),
                            datasets: [{
                                label: 'Utilization %',
                                data: rates,
                                backgroundColor: 'rgba(16, 185, 129, 0.8)',
                                borderColor: 'rgb(16, 185, 129)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: { y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } } }
                        }
                    }));
                }
            }

            drawCharts();

            $wire.interceptMessage(({ onSuccess }) => {
                onSuccess(({ onMorph }) => {
                    onMorph(async () => {
                        await Promise.resolve();
                        drawCharts();
                    });
                });
            });
        })();
    </script>
    @endscript
</x-filament-panels::page>
