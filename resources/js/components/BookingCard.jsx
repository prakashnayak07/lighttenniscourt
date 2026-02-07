import { Link } from '@inertiajs/react';
import StatusBadge from './StatusBadge';

export default function BookingCard({ booking, showActions = true }) {
    const formatDate = (date) => {
        return new Date(date).toLocaleDateString('en-US', {
            weekday: 'short',
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    };

    const formatTime = (time) => {
        return time.substring(0, 5); // HH:MM
    };

    const formatPrice = (cents) => {
        return `$${(cents / 100).toFixed(2)}`;
    };

    const canCancel = booking.status === 'pending' || booking.status === 'confirmed';

    return (
        <div className="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow p-6">
            <div className="flex justify-between items-start mb-4">
                <div>
                    <h3 className="text-xl font-bold text-gray-900 mb-1">
                        {booking.resource.name}
                    </h3>
                    <p className="text-gray-600">
                        {formatDate(booking.date)}
                    </p>
                </div>
                <div className="flex flex-col gap-2 items-end">
                    <StatusBadge status={booking.status} type="booking" />
                    <StatusBadge status={booking.payment_status} type="payment" />
                </div>
            </div>

            <div className="space-y-2 mb-4">
                <div className="flex items-center text-gray-700">
                    <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{formatTime(booking.start_time)} - {formatTime(booking.end_time)}</span>
                </div>

                <div className="flex items-center text-gray-700">
                    <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span className="font-semibold">{formatPrice(booking.line_items.reduce((sum, item) => sum + item.total_cents, 0))}</span>
                </div>
            </div>

            {showActions && (
                <div className="flex gap-2 pt-4 border-t border-gray-200">
                    <Link
                        href={route('bookings.show', booking.id)}
                        className="flex-1 text-center bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition-colors"
                    >
                        View Details
                    </Link>
                    {canCancel && (
                        <Link
                            href={route('bookings.cancel', booking.id)}
                            method="post"
                            as="button"
                            className="flex-1 text-center bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700 transition-colors"
                            onBefore={() => confirm('Are you sure you want to cancel this booking?')}
                        >
                            Cancel
                        </Link>
                    )}
                </div>
            )}
        </div>
    );
}
