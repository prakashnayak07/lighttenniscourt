import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import BookingCard from '@/components/BookingCard';

export default function Index({ bookings, auth }) {
    const [activeTab, setActiveTab] = useState('upcoming');

    const filterBookings = (status) => {
        const now = new Date();
        return bookings.filter((booking) => {
            const bookingDate = new Date(booking.date);

            if (status === 'upcoming') {
                return (booking.status === 'pending' || booking.status === 'confirmed') && bookingDate >= now;
            } else if (status === 'past') {
                return booking.status === 'completed' || bookingDate < now;
            } else if (status === 'cancelled') {
                return booking.status === 'cancelled';
            }
            return false;
        });
    };

    const upcomingBookings = filterBookings('upcoming');
    const pastBookings = filterBookings('past');
    const cancelledBookings = filterBookings('cancelled');

    const tabs = [
        { key: 'upcoming', label: 'Upcoming', count: upcomingBookings.length },
        { key: 'past', label: 'Past', count: pastBookings.length },
        { key: 'cancelled', label: 'Cancelled', count: cancelledBookings.length },
    ];

    const getActiveBookings = () => {
        if (activeTab === 'upcoming') return upcomingBookings;
        if (activeTab === 'past') return pastBookings;
        return cancelledBookings;
    };

    const activeBookings = getActiveBookings();

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="My Bookings" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="flex justify-between items-center mb-8">
                        <h2 className="text-3xl font-bold text-gray-900">My Bookings</h2>
                        <Link
                            href={route('bookings.create')}
                            className="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors flex items-center"
                        >
                            <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                            </svg>
                            New Booking
                        </Link>
                    </div>

                    {/* Tabs */}
                    <div className="mb-6">
                        <div className="border-b border-gray-200">
                            <nav className="-mb-px flex space-x-8">
                                {tabs.map((tab) => (
                                    <button
                                        key={tab.key}
                                        onClick={() => setActiveTab(tab.key)}
                                        className={`py-4 px-1 border-b-2 font-medium text-sm transition-colors ${activeTab === tab.key
                                                ? 'border-blue-600 text-blue-600'
                                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                            }`}
                                    >
                                        {tab.label}
                                        <span className={`ml-2 py-0.5 px-2.5 rounded-full text-xs ${activeTab === tab.key
                                                ? 'bg-blue-100 text-blue-600'
                                                : 'bg-gray-100 text-gray-600'
                                            }`}>
                                            {tab.count}
                                        </span>
                                    </button>
                                ))}
                            </nav>
                        </div>
                    </div>

                    {/* Bookings List */}
                    {activeBookings.length > 0 ? (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {activeBookings.map((booking) => (
                                <BookingCard key={booking.id} booking={booking} />
                            ))}
                        </div>
                    ) : (
                        <div className="text-center py-12">
                            <svg className="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <h3 className="text-lg font-medium text-gray-900 mb-2">No {activeTab} bookings</h3>
                            <p className="text-gray-500 mb-6">
                                {activeTab === 'upcoming' && "You don't have any upcoming bookings."}
                                {activeTab === 'past' && "You don't have any past bookings."}
                                {activeTab === 'cancelled' && "You don't have any cancelled bookings."}
                            </p>
                            {activeTab === 'upcoming' && (
                                <Link
                                    href={route('bookings.create')}
                                    className="inline-flex items-center bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors"
                                >
                                    <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                                    </svg>
                                    Book a Court
                                </Link>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
