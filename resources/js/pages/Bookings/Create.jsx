import { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import CourtCard from '@/components/CourtCard';
import AvailabilityCalendar from '@/components/AvailabilityCalendar';
import PriceBreakdown from '@/components/PriceBreakdown';

export default function Create({ courts, auth }) {
    const [step, setStep] = useState(1);
    const [selectedCourt, setSelectedCourt] = useState(null);
    const [selectedSlot, setSelectedSlot] = useState(null);
    const [pricePreview, setPricePreview] = useState(null);

    const { data, setData, post, processing, errors } = useForm({
        resource_id: '',
        date: '',
        start_time: '',
        end_time: '',
        coupon_code: '',
        payment_method: 'wallet',
    });

    const handleCourtSelect = (court) => {
        setSelectedCourt(court);
        setData('resource_id', court.id);
        setStep(2);
    };

    const handleSlotSelect = async (slot) => {
        setSelectedSlot(slot);
        setData({
            ...data,
            date: slot.date,
            start_time: slot.start_time,
            end_time: slot.end_time,
        });

        // Fetch price preview
        try {
            const response = await fetch(route('api.booking-price', {
                resource_id: selectedCourt.id,
                date: slot.date,
                start_time: slot.start_time,
                end_time: slot.end_time,
            }));
            const priceData = await response.json();
            setPricePreview(priceData);
        } catch (error) {
            console.error('Error fetching price:', error);
        }

        setStep(3);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('bookings.store'));
    };

    const handleBack = () => {
        if (step > 1) {
            setStep(step - 1);
        }
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Book a Court" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Step Indicator */}
                    <div className="mb-8">
                        <div className="flex items-center justify-center">
                            {[1, 2, 3].map((s) => (
                                <div key={s} className="flex items-center">
                                    <div className={`flex items-center justify-center w-10 h-10 rounded-full ${step >= s ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600'
                                        }`}>
                                        {s}
                                    </div>
                                    {s < 3 && (
                                        <div className={`w-24 h-1 ${step > s ? 'bg-blue-600' : 'bg-gray-200'}`} />
                                    )}
                                </div>
                            ))}
                        </div>
                        <div className="flex justify-center mt-2 text-sm text-gray-600">
                            <span className="mx-8">Select Court</span>
                            <span className="mx-8">Choose Time</span>
                            <span className="mx-8">Review & Pay</span>
                        </div>
                    </div>

                    {/* Step 1: Court Selection */}
                    {step === 1 && (
                        <div>
                            <h2 className="text-2xl font-bold text-gray-900 mb-6">Select a Court</h2>
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                {courts.map((court) => (
                                    <CourtCard
                                        key={court.id}
                                        court={court}
                                        selected={selectedCourt?.id === court.id}
                                        onSelect={handleCourtSelect}
                                    />
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Step 2: Date & Time Selection */}
                    {step === 2 && selectedCourt && (
                        <div>
                            <div className="mb-6">
                                <button
                                    onClick={handleBack}
                                    className="text-blue-600 hover:text-blue-700 flex items-center"
                                >
                                    <svg className="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                                    </svg>
                                    Back to Court Selection
                                </button>
                            </div>
                            <h2 className="text-2xl font-bold text-gray-900 mb-2">{selectedCourt.name}</h2>
                            <p className="text-gray-600 mb-6">Select your preferred date and time</p>
                            <AvailabilityCalendar
                                courtId={selectedCourt.id}
                                onSlotSelect={handleSlotSelect}
                            />
                        </div>
                    )}

                    {/* Step 3: Review & Payment */}
                    {step === 3 && selectedCourt && selectedSlot && (
                        <div>
                            <div className="mb-6">
                                <button
                                    onClick={handleBack}
                                    className="text-blue-600 hover:text-blue-700 flex items-center"
                                >
                                    <svg className="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                                    </svg>
                                    Back to Time Selection
                                </button>
                            </div>

                            <h2 className="text-2xl font-bold text-gray-900 mb-6">Review & Payment</h2>

                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                {/* Booking Details */}
                                <div className="bg-white rounded-lg shadow-md p-6">
                                    <h3 className="text-lg font-bold text-gray-900 mb-4">Booking Details</h3>
                                    <div className="space-y-3">
                                        <div>
                                            <p className="text-sm text-gray-500">Court</p>
                                            <p className="font-medium text-gray-900">{selectedCourt.name}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-gray-500">Date</p>
                                            <p className="font-medium text-gray-900">
                                                {new Date(selectedSlot.date).toLocaleDateString('en-US', {
                                                    weekday: 'long',
                                                    year: 'numeric',
                                                    month: 'long',
                                                    day: 'numeric',
                                                })}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-gray-500">Time</p>
                                            <p className="font-medium text-gray-900">
                                                {selectedSlot.start_time.substring(0, 5)} - {selectedSlot.end_time.substring(0, 5)}
                                            </p>
                                        </div>
                                    </div>

                                    {/* Coupon Code */}
                                    <div className="mt-6">
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Coupon Code (Optional)
                                        </label>
                                        <input
                                            type="text"
                                            value={data.coupon_code}
                                            onChange={(e) => setData('coupon_code', e.target.value)}
                                            className="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="Enter coupon code"
                                        />
                                        {errors.coupon_code && (
                                            <p className="mt-1 text-sm text-red-600">{errors.coupon_code}</p>
                                        )}
                                    </div>

                                    {/* Payment Method */}
                                    <div className="mt-6">
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Payment Method
                                        </label>
                                        <div className="space-y-2">
                                            <label className="flex items-center p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-300">
                                                <input
                                                    type="radio"
                                                    name="payment_method"
                                                    value="wallet"
                                                    checked={data.payment_method === 'wallet'}
                                                    onChange={(e) => setData('payment_method', e.target.value)}
                                                    className="mr-3"
                                                />
                                                <span className="font-medium">Wallet</span>
                                            </label>
                                            <label className="flex items-center p-3 border-2 border-gray-200 rounded-lg cursor-pointer hover:border-blue-300">
                                                <input
                                                    type="radio"
                                                    name="payment_method"
                                                    value="stripe"
                                                    checked={data.payment_method === 'stripe'}
                                                    onChange={(e) => setData('payment_method', e.target.value)}
                                                    className="mr-3"
                                                />
                                                <span className="font-medium">Credit/Debit Card</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                {/* Price Breakdown */}
                                <div>
                                    {pricePreview && (
                                        <PriceBreakdown lineItems={pricePreview.line_items} />
                                    )}

                                    <button
                                        onClick={handleSubmit}
                                        disabled={processing}
                                        className="w-full mt-6 bg-blue-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-blue-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed"
                                    >
                                        {processing ? 'Processing...' : 'Confirm Booking'}
                                    </button>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
