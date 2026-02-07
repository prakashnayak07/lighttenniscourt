export default function StatusBadge({ status, type = 'booking' }) {
    const getStatusConfig = () => {
        if (type === 'booking') {
            const configs = {
                pending: { color: 'bg-yellow-100 text-yellow-800', label: 'Pending' },
                confirmed: { color: 'bg-green-100 text-green-800', label: 'Confirmed' },
                cancelled: { color: 'bg-red-100 text-red-800', label: 'Cancelled' },
                completed: { color: 'bg-blue-100 text-blue-800', label: 'Completed' },
                no_show: { color: 'bg-gray-100 text-gray-800', label: 'No Show' },
            };
            return configs[status] || { color: 'bg-gray-100 text-gray-800', label: status };
        }

        if (type === 'payment') {
            const configs = {
                pending: { color: 'bg-yellow-100 text-yellow-800', label: 'Pending' },
                paid: { color: 'bg-green-100 text-green-800', label: 'Paid' },
                refunded: { color: 'bg-purple-100 text-purple-800', label: 'Refunded' },
                refund_pending: { color: 'bg-orange-100 text-orange-800', label: 'Refund Pending' },
            };
            return configs[status] || { color: 'bg-gray-100 text-gray-800', label: status };
        }

        return { color: 'bg-gray-100 text-gray-800', label: status };
    };

    const config = getStatusConfig();

    return (
        <span className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${config.color}`}>
            {config.label}
        </span>
    );
}
