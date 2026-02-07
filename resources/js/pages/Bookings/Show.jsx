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

                            {/* Access Code & QR Code */}
                            {booking.status === 'confirmed' && booking.access_code && (
                                <div className="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-lg p-6 mb-6 border-2 border-blue-200">
                                    <h3 className="text-xl font-bold text-gray-900 mb-4 text-center">Access Code</h3>

                                    {/* Access Code Text */}
                                    <div className="bg-white rounded-lg p-4 mb-4 text-center border border-blue-300">
                                        <p className="text-sm text-gray-600 mb-2">Your Access Code</p>
                                        <p className="text-3xl font-bold text-blue-600 tracking-wider font-mono">
                                            {booking.access_code}
                                        </p>
                                    </div>

                                    {/* QR Code Display */}
                                    <div className="flex justify-center mb-4">
                                        <div className="bg-white p-4 rounded-lg shadow-md">
                                            {booking.qr_code ? (
                                                <div
                                                    className="w-48 h-48"
                                                    dangerouslySetInnerHTML={{ __html: booking.qr_code }}
                                                />
                                            ) : (
                                                <div className="w-48 h-48 bg-gray-100 flex items-center justify-center rounded">
                                                    <svg className="w-32 h-32 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1} d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                                    </svg>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    <div className="text-center">
                                        <p className="text-sm text-gray-700 font-medium mb-1">
                                            📱 Show this code at the court entrance
                                        </p>
                                        <p className="text-xs text-gray-500">
                                            Valid from 30 minutes before your booking time
                                        </p>
                                    </div>
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
