import { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';

export default function TopUp({ wallet, auth }) {
    const [selectedAmount, setSelectedAmount] = useState(null);
    const [customAmount, setCustomAmount] = useState('');

    const { data, setData, post, processing, errors } = useForm({
        amount_cents: 0,
    });

    const predefinedAmounts = [1000, 2500, 5000, 10000]; // $10, $25, $50, $100

    const formatPrice = (cents) => {
        return `$${(cents / 100).toFixed(2)}`;
    };

    const handleAmountSelect = (cents) => {
        setSelectedAmount(cents);
        setCustomAmount('');
        setData('amount_cents', cents);
    };

    const handleCustomAmountChange = (e) => {
        const value = e.target.value;
        setCustomAmount(value);
        setSelectedAmount(null);

        // Convert dollars to cents
        const dollars = parseFloat(value);
        if (!isNaN(dollars) && dollars > 0) {
            setData('amount_cents', Math.round(dollars * 100));
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('wallet.process-top-up'));
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="Top Up Wallet" />

            <div className="py-12">
                <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <Link
                            href={route('wallet.index')}
                            className="text-blue-600 hover:text-blue-700 flex items-center"
                        >
                            <svg className="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                            </svg>
                            Back to Wallet
                        </Link>
                    </div>

                    <div className="bg-white rounded-lg shadow-md p-8">
                        <h2 className="text-2xl font-bold text-gray-900 mb-2">Top Up Wallet</h2>
                        <p className="text-gray-600 mb-6">
                            Current Balance: <span className="font-bold text-blue-600">{formatPrice(wallet.balance_cents)}</span>
                        </p>

                        <form onSubmit={handleSubmit}>
                            {/* Predefined Amounts */}
                            <div className="mb-6">
                                <label className="block text-sm font-medium text-gray-700 mb-3">
                                    Select Amount
                                </label>
                                <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                    {predefinedAmounts.map((cents) => (
                                        <button
                                            key={cents}
                                            type="button"
                                            onClick={() => handleAmountSelect(cents)}
                                            className={`py-4 px-6 rounded-lg border-2 font-semibold transition-all ${selectedAmount === cents
                                                    ? 'border-blue-600 bg-blue-50 text-blue-600'
                                                    : 'border-gray-200 hover:border-blue-300 text-gray-700'
                                                }`}
                                        >
                                            {formatPrice(cents)}
                                        </button>
                                    ))}
                                </div>
                            </div>

                            {/* Custom Amount */}
                            <div className="mb-6">
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Or Enter Custom Amount
                                </label>
                                <div className="relative">
                                    <span className="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500 text-lg">
                                        $
                                    </span>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="1"
                                        value={customAmount}
                                        onChange={handleCustomAmountChange}
                                        placeholder="0.00"
                                        className="w-full pl-8 pr-4 py-3 border-2 border-gray-200 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-lg"
                                    />
                                </div>
                                {errors.amount_cents && (
                                    <p className="mt-2 text-sm text-red-600">{errors.amount_cents}</p>
                                )}
                            </div>

                            {/* Summary */}
                            {data.amount_cents > 0 && (
                                <div className="bg-blue-50 rounded-lg p-6 mb-6">
                                    <div className="flex justify-between items-center mb-2">
                                        <span className="text-gray-700">Top-up Amount:</span>
                                        <span className="font-bold text-gray-900">{formatPrice(data.amount_cents)}</span>
                                    </div>
                                    <div className="flex justify-between items-center pt-2 border-t border-blue-200">
                                        <span className="text-gray-700">New Balance:</span>
                                        <span className="font-bold text-blue-600 text-xl">
                                            {formatPrice(wallet.balance_cents + data.amount_cents)}
                                        </span>
                                    </div>
                                </div>
                            )}

                            {/* Payment Method Info */}
                            <div className="bg-gray-50 rounded-lg p-4 mb-6">
                                <div className="flex items-start">
                                    <svg className="w-5 h-5 text-blue-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <div className="text-sm text-gray-600">
                                        <p className="font-medium text-gray-900 mb-1">Secure Payment via Stripe</p>
                                        <p>You will be redirected to Stripe's secure checkout page to complete your payment.</p>
                                    </div>
                                </div>
                            </div>

                            {/* Submit Button */}
                            <button
                                type="submit"
                                disabled={processing || data.amount_cents <= 0}
                                className="w-full bg-blue-600 text-white py-4 px-6 rounded-lg font-semibold hover:bg-blue-700 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed text-lg"
                            >
                                {processing ? 'Processing...' : `Proceed to Payment - ${formatPrice(data.amount_cents)}`}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
