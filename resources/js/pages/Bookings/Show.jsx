import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import StatusBadge from '@/components/StatusBadge';
import PriceBreakdown from '@/components/PriceBreakdown';

export default function Show({ booking, auth }) {
    const formatDate = (date) => {
        return new Date(date).toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
        });
    };

    const formatTime = (time) => {
        return time.substring(0, 5); // HH:MM
    };

    const canCancel = booking.status === 'pending' || booking.status === 'confirmed';

    const handleCancel = () => {
        if (confirm('Are you sure you want to cancel this booking? You will receive a refund.')) {
            router.post(route('bookings.cancel', booking.id));
        }
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title={`Booking #${booking.id}`} />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <Link
                            href={route('bookings.index')}
                            className="text-blue-600 hover:text-blue-700 flex items-center"
                        >
                            <svg className="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                            </svg>
                            Back to Bookings
                        </Link>
                    </div>

                    <div className="bg-white rounded-lg shadow-md overflow-hidden">
                        {/* Header */}
                        <div className="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-8 text-white">
                            <div className="flex justify-between items-start">
                                <div>
                                    <h1 className="text-3xl font-bold mb-2">Booking #{booking.id}</h1>
                                    <p className="text-blue-100">
                                        {formatDate(booking.date)} at {formatTime(booking.start_time)}
                                    </p>
                                </div>
                                <div className="flex flex-col gap-2">
                                    <StatusBadge status={booking.status} type="booking" />
                                    <StatusBadge status={booking.payment_status} type="payment" />
                                </div>
                            </div>
                        </div>

                        {/* Content */}
                        <div className="p-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                {/* Court Details */}
                                <div>
                                    <h3 className="text-lg font-bold text-gray-900 mb-4">Court Details</h3>
                                    <div className="space-y-3">
                                        <div>
                                            <p className="text-sm text-gray-500">Court Name</p>
                                            <p className="font-medium text-gray-900">{booking.resource.name}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-gray-500">Court Type</p>
                                            <p className="font-medium text-gray-900 capitalize">{booking.resource.type}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-gray-500">Capacity</p>
                                            <p className="font-medium text-gray-900">{booking.resource.capacity} players</p>
                                        </div>
                                    </div>
                                </div>

                                {/* Booking Details */}
                                <div>
                                    <h3 className="text-lg font-bold text-gray-900 mb-4">Booking Information</h3>
                                    <div className="space-y-3">
                                        <div>
                                            <p className="text-sm text-gray-500">Date</p>
                                            <p className="font-medium text-gray-900">{formatDate(booking.date)}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-gray-500">Time</p>
                                            <p className="font-medium text-gray-900">
                                                {formatTime(booking.start_time)} - {formatTime(booking.end_time)}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-gray-500">Payment Method</p>
                                            <p className="font-medium text-gray-900 capitalize">{booking.payment_method}</p>
                                        </div>
                                        {booking.checked_in_at && (
                                            <div>
                                                <p className="text-sm text-gray-500">Checked In</p>
                                                <p className="font-medium text-green-600">
                                                    {new Date(booking.checked_in_at).toLocaleString()}
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Price Breakdown */}
                            <div className="mb-6">
                                <PriceBreakdown lineItems={booking.line_items} />
                            </div>

                            {/* QR Code Placeholder */}
                            {booking.status === 'confirmed' && (
                                <div className="bg-gray-50 rounded-lg p-6 mb-6 text-center">
                                    <h3 className="text-lg font-bold text-gray-900 mb-2">Access Code</h3>
                                    <div className="bg-white inline-block p-4 rounded-lg">
                                        <div className="w-48 h-48 bg-gray-200 flex items-center justify-center">
                                            <p className="text-gray-500">QR Code</p>
                                        </div>
                                    </div>
                                    <p className="text-sm text-gray-600 mt-2">
                                        Show this code at the court entrance
                                    </p>
                                </div>
                            )}

                            {/* Actions */}
                            <div className="flex gap-4">
                                {canCancel && (
                                    <button
                                        onClick={handleCancel}
                                        className="flex-1 bg-red-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-red-700 transition-colors"
                                    >
                                        Cancel Booking
                                    </button>
                                )}
                                <Link
                                    href={route('bookings.index')}
                                    className="flex-1 bg-gray-200 text-gray-700 py-3 px-6 rounded-lg font-semibold hover:bg-gray-300 transition-colors text-center"
                                >
                                    Back to Bookings
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
