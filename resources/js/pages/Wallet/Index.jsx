import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/layouts/authenticated-layout';

export default function Index({ wallet, transactions, auth }) {
    const [filter, setFilter] = useState('all');

    const formatPrice = (cents) => {
        return `$${(cents / 100).toFixed(2)}`;
    };

    const formatDate = (date) => {
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const filteredTransactions = filter === 'all'
        ? transactions
        : transactions.filter(t => t.type === filter);

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title="My Wallet" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <h2 className="text-3xl font-bold text-gray-900 mb-8">My Wallet</h2>

                    {/* Wallet Balance Card */}
                    <div className="bg-gradient-to-r from-blue-600 to-blue-700 rounded-lg shadow-lg p-8 mb-8 text-white">
                        <div className="flex justify-between items-start">
                            <div>
                                <p className="text-blue-100 mb-2">Current Balance</p>
                                <h3 className="text-5xl font-bold mb-4">
                                    {formatPrice(wallet.balance_cents)}
                                </h3>
                                <p className="text-sm text-blue-100">
                                    Last updated: {formatDate(wallet.updated_at)}
                                </p>
                            </div>
                            <Link
                                href={route('wallet.top-up')}
                                className="bg-white text-blue-600 px-6 py-3 rounded-lg font-semibold hover:bg-blue-50 transition-colors"
                            >
                                Top Up Wallet
                            </Link>
                        </div>
                    </div>

                    {/* Transaction History */}
                    <div className="bg-white rounded-lg shadow-md p-6">
                        <div className="flex justify-between items-center mb-6">
                            <h3 className="text-xl font-bold text-gray-900">Transaction History</h3>

                            {/* Filter */}
                            <select
                                value={filter}
                                onChange={(e) => setFilter(e.target.value)}
                                className="px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="all">All Transactions</option>
                                <option value="credit">Credits</option>
                                <option value="debit">Debits</option>
                                <option value="refund">Refunds</option>
                            </select>
                        </div>

                        {filteredTransactions.length > 0 ? (
                            <div className="space-y-4">
                                {filteredTransactions.map((transaction) => (
                                    <div
                                        key={transaction.id}
                                        className="flex justify-between items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
                                    >
                                        <div className="flex items-center">
                                            <div className={`w-10 h-10 rounded-full flex items-center justify-center mr-4 ${transaction.type === 'credit' ? 'bg-green-100' :
                                                    transaction.type === 'debit' ? 'bg-red-100' :
                                                        'bg-blue-100'
                                                }`}>
                                                {transaction.type === 'credit' && (
                                                    <svg className="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                                                    </svg>
                                                )}
                                                {transaction.type === 'debit' && (
                                                    <svg className="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 12H4" />
                                                    </svg>
                                                )}
                                                {transaction.type === 'refund' && (
                                                    <svg className="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                                    </svg>
                                                )}
                                            </div>
                                            <div>
                                                <p className="font-medium text-gray-900">{transaction.description}</p>
                                                <p className="text-sm text-gray-500">{formatDate(transaction.created_at)}</p>
                                                {transaction.payment_method && (
                                                    <p className="text-xs text-gray-400 capitalize">via {transaction.payment_method}</p>
                                                )}
                                            </div>
                                        </div>
                                        <div className="text-right">
                                            <p className={`text-lg font-bold ${transaction.type === 'credit' || transaction.type === 'refund'
                                                    ? 'text-green-600'
                                                    : 'text-red-600'
                                                }`}>
                                                {transaction.type === 'credit' || transaction.type === 'refund' ? '+' : '-'}
                                                {formatPrice(Math.abs(transaction.amount_cents))}
                                            </p>
                                            <p className="text-sm text-gray-500">
                                                Balance: {formatPrice(transaction.balance_after_cents)}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="text-center py-12">
                                <svg className="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <h3 className="text-lg font-medium text-gray-900 mb-2">No transactions found</h3>
                                <p className="text-gray-500">Your transaction history will appear here</p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
