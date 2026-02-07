import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { index as bookingsIndex } from '@/routes/bookings';
import { dashboard } from '@/routes';
import { index as walletIndex } from '@/routes/wallet';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

type DashboardStats = {
    total_bookings: number;
    upcoming_count: number;
    wallet_balance_cents: number;
};

type MembershipSummary = {
    name: string | null;
    status: string;
    valid_from: string | null;
    valid_until: string | null;
};

type BookingSummary = {
    id: number;
    booking_id?: number | null;
    resource: string | null;
    date: string | null;
    start_time: string | null;
    end_time: string | null;
    status: string | null;
    payment_status: string | null;
    created_at?: string | null;
};

type ChartDay = { label: string; full: string; count: number };

type DashboardProps = {
    stats: DashboardStats;
    membership: MembershipSummary | null;
    upcomingBookings: BookingSummary[];
    recentBookings: BookingSummary[];
    bookingsChart: ChartDay[];
};

const formatCurrency = (amountCents: number) =>
    `$${(amountCents / 100).toFixed(2)}`;

const formatTimeRange = (booking: BookingSummary) => {
    if (booking.start_time && booking.end_time) {
        return `${booking.start_time} - ${booking.end_time}`;
    }

    return 'Time TBD';
};

const maxChartCount = (days: ChartDay[]) =>
    Math.max(1, ...days.map((d) => d.count));

export default function Dashboard({
    stats,
    membership,
    upcomingBookings,
    recentBookings,
    bookingsChart = [],
}: DashboardProps) {
    const chartMax = maxChartCount(bookingsChart);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="grid gap-4 md:grid-cols-3">
                    <div className="rounded-xl border border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-900">
                        <p className="text-sm text-neutral-500">
                            Total bookings
                        </p>
                        <p className="mt-2 text-2xl font-semibold text-neutral-900 dark:text-neutral-100">
                            {stats.total_bookings}
                        </p>
                        <Link
                            href={bookingsIndex()}
                            className="mt-3 inline-flex text-sm font-medium text-orange-600 hover:text-orange-500 dark:text-orange-400"
                        >
                            View bookings
                        </Link>
                    </div>
                    <div className="rounded-xl border border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-900">
                        <p className="text-sm text-neutral-500">Wallet balance</p>
                        <p className="mt-2 text-2xl font-semibold text-neutral-900 dark:text-neutral-100">
                            {formatCurrency(stats.wallet_balance_cents)}
                        </p>
                        <Link
                            href={walletIndex()}
                            className="mt-3 inline-flex text-sm font-medium text-orange-600 hover:text-orange-500 dark:text-orange-400"
                        >
                            Go to wallet
                        </Link>
                    </div>
                    <div className="rounded-xl border border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-900">
                        <p className="text-sm text-neutral-500">Membership</p>
                        <p className="mt-2 text-2xl font-semibold text-neutral-900 dark:text-neutral-100">
                            {membership?.name ?? 'No membership'}
                        </p>
                        <p className="mt-1 text-sm text-neutral-500">
                            {membership
                                ? `${membership.status} · ${membership.valid_from ?? '—'} to ${
                                      membership.valid_until ?? '—'
                                  }`
                                : 'Join a membership to unlock perks'}
                        </p>
                    </div>
                </div>

                {bookingsChart.length > 0 && (
                    <div className="rounded-xl border border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-900">
                        <h2 className="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                            Bookings this week
                        </h2>
                        <p className="mt-1 text-sm text-neutral-500">
                            Bookings created per day (last 7 days)
                        </p>
                        <div className="mt-6 flex h-32 items-end justify-between gap-2">
                            {bookingsChart.map((day) => (
                                <div
                                    key={day.label + day.full}
                                    className="flex flex-1 flex-col items-center gap-1"
                                >
                                    <div
                                        className="w-full min-w-6 max-w-16 rounded-t bg-primary/90 transition-all hover:bg-primary"
                                        style={{
                                            height: `${(day.count / chartMax) * 100}%`,
                                            minHeight: day.count > 0 ? 4 : 0,
                                        }}
                                        title={`${day.full}: ${day.count}`}
                                    />
                                    <span className="text-xs font-medium text-neutral-500">
                                        {day.label}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                <div className="grid gap-4 lg:grid-cols-2">
                    <div className="rounded-xl border border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-900">
                        <div className="flex items-center justify-between">
                            <h2 className="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                                Upcoming bookings
                            </h2>
                            <div className="flex items-center gap-2">
                                <span className="text-sm text-neutral-500">
                                    {stats.upcoming_count} upcoming
                                </span>
                                <Link
                                    href={bookingsIndex()}
                                    className="text-sm font-medium text-orange-600 hover:text-orange-500 dark:text-orange-400"
                                >
                                    View all
                                </Link>
                            </div>
                        </div>
                        <div className="mt-4 space-y-3">
                            {upcomingBookings.length === 0 && (
                                <p className="text-sm text-neutral-500">
                                    No upcoming bookings yet.
                                </p>
                            )}
                            {upcomingBookings.map((booking) => (
                                <div
                                    key={booking.id}
                                    className="flex items-center justify-between rounded-lg border border-neutral-200 px-3 py-2 text-sm dark:border-neutral-800"
                                >
                                    <div>
                                        <p className="font-medium text-neutral-900 dark:text-neutral-100">
                                            {booking.resource ?? 'Court'}
                                        </p>
                                        <p className="text-neutral-500">
                                            {booking.date ?? 'Date TBD'} ·{' '}
                                            {formatTimeRange(booking)}
                                        </p>
                                    </div>
                                    <div className="text-right">
                                        <p className="font-medium text-neutral-900 dark:text-neutral-100">
                                            {booking.status ?? 'pending'}
                                        </p>
                                        <p className="text-neutral-500">
                                            {booking.payment_status ?? 'pending'}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="rounded-xl border border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-900">
                        <div className="flex items-center justify-between">
                            <h2 className="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                                Recent bookings
                            </h2>
                            <p className="text-sm text-neutral-500">
                                Total: {stats.total_bookings}
                            </p>
                        </div>
                        <div className="mt-4 space-y-3">
                            {recentBookings.length === 0 && (
                                <p className="text-sm text-neutral-500">
                                    No bookings yet.
                                </p>
                            )}
                            {recentBookings.map((booking) => (
                                <div
                                    key={booking.id}
                                    className="flex items-center justify-between rounded-lg border border-neutral-200 px-3 py-2 text-sm dark:border-neutral-800"
                                >
                                    <div>
                                        <p className="font-medium text-neutral-900 dark:text-neutral-100">
                                            {booking.resource ?? 'Court'}
                                        </p>
                                        <p className="text-neutral-500">
                                            {booking.date ?? booking.created_at ?? 'Date TBD'} ·{' '}
                                            {formatTimeRange(booking)}
                                        </p>
                                    </div>
                                    <div className="text-right">
                                        <p className="font-medium text-neutral-900 dark:text-neutral-100">
                                            {booking.status ?? 'pending'}
                                        </p>
                                        <p className="text-neutral-500">
                                            {booking.payment_status ?? 'pending'}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
