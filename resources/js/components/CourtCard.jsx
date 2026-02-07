export default function CourtCard({ court, selected = false, onSelect }) {
    return (
        <div
            onClick={() => onSelect(court)}
            className={`bg-white rounded-lg shadow-md hover:shadow-xl transition-all cursor-pointer p-6 ${selected ? 'ring-2 ring-blue-600' : ''
                }`}
        >
            <div className="flex justify-between items-start mb-3">
                <h3 className="text-xl font-bold text-gray-900">{court.name}</h3>
                <span className={`px-3 py-1 rounded-full text-sm font-medium ${court.status === 'enabled' ? 'bg-green-100 text-green-800' :
                        court.status === 'maintenance' ? 'bg-yellow-100 text-yellow-800' :
                            'bg-red-100 text-red-800'
                    }`}>
                    {court.status === 'enabled' ? 'Available' : court.status}
                </span>
            </div>

            <div className="space-y-2 mb-4">
                <div className="flex items-center text-gray-600">
                    <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    <span className="capitalize">{court.type}</span>
                </div>

                <div className="flex items-center text-gray-600">
                    <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{court.daily_start_time.substring(0, 5)} - {court.daily_end_time.substring(0, 5)}</span>
                </div>

                <div className="flex items-center text-gray-600">
                    <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span>Capacity: {court.capacity} players</span>
                </div>
            </div>

            {selected && (
                <div className="pt-3 border-t border-gray-200">
                    <p className="text-blue-600 font-medium flex items-center">
                        <svg className="w-5 h-5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                        </svg>
                        Selected
                    </p>
                </div>
            )}
        </div>
    );
}
