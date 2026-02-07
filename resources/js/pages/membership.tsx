import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { index as membershipIndex } from '@/routes/membership';
import type { BreadcrumbItem } from '@/types';

type MembershipSummary = {
    name: string | null;
    status: string;
    valid_from: string | null;
    valid_until: string | null;
};

type MembershipType = {
    id: number;
    name: string;
    price_cents: number;
    billing_cycle: string;
    booking_window_days: number;
    max_active_bookings: number | null;
    court_fee_discount_percent: number;
};

type MembershipPageProps = {
    membership: MembershipSummary | null;
    membershipTypes: MembershipType[];
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Membership',
        href: membershipIndex().url,
    },
];

const formatCurrency = (amountCents: number) =>
    `$${(amountCents / 100).toFixed(2)}`;

const formatCycle = (cycle: string) => {
    if (cycle === 'one_time') {
        return 'One-time';
    }

    return cycle.charAt(0).toUpperCase() + cycle.slice(1);
};

export default function Membership({
    membership,
    membershipTypes,
}: MembershipPageProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Membership" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="rounded-xl border border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-900">
                    <h2 className="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                        Current membership
                    </h2>
                    {membership ? (
                        <div className="mt-3 space-y-2 text-sm text-neutral-600 dark:text-neutral-300">
                            <p>
                                <span className="font-medium text-neutral-900 dark:text-neutral-100">
                                    {membership.name}
                                </span>
                            </p>
                            <p>Status: {membership.status}</p>
                            <p>
                                Valid:{' '}
                                {membership.valid_from ?? '—'} to{' '}
                                {membership.valid_until ?? '—'}
                            </p>
                        </div>
                    ) : (
                        <p className="mt-3 text-sm text-neutral-500">
                            You do not have an active membership yet.
                        </p>
                    )}
                </div>

                <div className="rounded-xl border border-sidebar-border/70 bg-white p-4 shadow-sm dark:border-sidebar-border dark:bg-neutral-900">
                    <div className="flex items-center justify-between">
                        <h2 className="text-lg font-semibold text-neutral-900 dark:text-neutral-100">
                            Membership plans
                        </h2>
                        <p className="text-sm text-neutral-500">
                            {membershipTypes.length} available
                        </p>
                    </div>
                    <div className="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {membershipTypes.length === 0 && (
                            <p className="text-sm text-neutral-500">
                                No public membership plans available.
                            </p>
                        )}
                        {membershipTypes.map((type) => (
                            <div
                                key={type.id}
                                className="rounded-lg border border-neutral-200 p-4 dark:border-neutral-800"
                            >
                                <h3 className="text-base font-semibold text-neutral-900 dark:text-neutral-100">
                                    {type.name}
                                </h3>
                                <p className="mt-2 text-2xl font-semibold text-neutral-900 dark:text-neutral-100">
                                    {formatCurrency(type.price_cents)}
                                    <span className="ml-2 text-sm font-normal text-neutral-500">
                                        / {formatCycle(type.billing_cycle)}
                                    </span>
                                </p>
                                <div className="mt-3 space-y-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    <p>
                                        Booking window:{' '}
                                        {type.booking_window_days} days
                                    </p>
                                    <p>
                                        Max active bookings:{' '}
                                        {type.max_active_bookings ?? 'Unlimited'}
                                    </p>
                                    <p>
                                        Court discount:{' '}
                                        {type.court_fee_discount_percent}%
                                    </p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
