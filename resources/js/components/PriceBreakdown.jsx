export default function PriceBreakdown({ lineItems }) {
    const formatPrice = (cents) => {
        return `$${(cents / 100).toFixed(2)}`;
    };

    const total = lineItems.reduce((sum, item) => sum + item.total_cents, 0);

    return (
        <div className="bg-gray-50 rounded-lg p-6">
            <h3 className="text-lg font-bold text-gray-900 mb-4">Price Breakdown</h3>

            <div className="space-y-3">
                {lineItems.map((item, index) => (
                    <div key={index} className="flex justify-between items-center">
                        <div>
                            <p className="text-gray-900">{item.description}</p>
                            {item.quantity > 1 && (
                                <p className="text-sm text-gray-500">
                                    {formatPrice(item.unit_price_cents)} × {item.quantity}
                                </p>
                            )}
                        </div>
                        <span className={`font-medium ${item.total_cents < 0 ? 'text-green-600' : 'text-gray-900'}`}>
                            {item.total_cents < 0 ? '-' : ''}{formatPrice(Math.abs(item.total_cents))}
                        </span>
                    </div>
                ))}

                <div className="pt-3 border-t-2 border-gray-300">
                    <div className="flex justify-between items-center">
                        <span className="text-lg font-bold text-gray-900">Total</span>
                        <span className="text-2xl font-bold text-blue-600">{formatPrice(total)}</span>
                    </div>
                </div>
            </div>
        </div>
    );
}
