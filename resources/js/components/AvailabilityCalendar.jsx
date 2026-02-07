import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';

export default function AvailabilityCalendar({ courtId, selectedDate, onSlotSelect }) {
    const [date, setDate] = useState(selectedDate || new Date().toISOString().split('T')[0]);
    const [slots, setSlots] = useState([]);
    const [loading, setLoading] = useState(false);
    const [selectedSlot, setSelectedSlot] = useState(null);

    useEffect(() => {
        if (courtId && date) {
            fetchAvailability();
        }
    }, [courtId, date]);

    const fetchAvailability = async () => {
        setLoading(true);
        try {
            const response = await fetch(route('api.available-slots', { resource_id: courtId, date }));
            const data = await response.json();
            setSlots(data.slots || []);
        } catch (error) {
            console.error('Error fetching availability:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleSlotClick = (slot) => {
        if (slot.available) {
            setSelectedSlot(slot);
            onSlotSelect(slot);
        }
    };

    const formatTime = (time) => {
        return time.substring(0, 5); // HH:MM
    };

    const getNextDays = (count = 7) => {
        const days = [];
        const today = new Date();
        for (let i = 0; i < count; i++) {
            const day = new Date(today);
            day.setDate(today.getDate() + i);
            days.push(day);
        }
        return days;
    };

    const days = getNextDays();

    return (
        <div className="bg-white rounded-lg shadow-md p-6">
            <h3 className="text-xl font-bold text-gray-900 mb-4">Select Date & Time</h3>

            {/* Date Selector */}
            <div className="mb-6">
                <div className="flex gap-2 overflow-x-auto pb-2">
                    {days.map((day) => {
                        const dayStr = day.toISOString().split('T')[0];
                        const isSelected = dayStr === date;
                        const isToday = dayStr === new Date().toISOString().split('T')[0];

                        return (
                            <button
                                key={dayStr}
                                onClick={() => setDate(dayStr)}
                                className={`flex-shrink-0 px-4 py-3 rounded-lg border-2 transition-all ${isSelected
                                        ? 'border-blue-600 bg-blue-50'
                                        : 'border-gray-200 hover:border-blue-300'
                                    }`}
                            >
                                <div className="text-center">
                                    <div className="text-xs text-gray-500 mb-1">
                                        {day.toLocaleDateString('en-US', { weekday: 'short' })}
                                    </div>
                                    <div className={`text-lg font-bold ${isSelected ? 'text-blue-600' : 'text-gray-900'}`}>
                                        {day.getDate()}
                                    </div>
                                    {isToday && (
                                        <div className="text-xs text-blue-600 mt-1">Today</div>
                                    )}
                                </div>
                            </button>
                        );
                    })}
                </div>
            </div>

            {/* Time Slots */}
            <div>
                <h4 className="text-sm font-semibold text-gray-700 mb-3">Available Time Slots</h4>
                {loading ? (
                    <div className="flex justify-center py-8">
                        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    </div>
                ) : slots.length > 0 ? (
                    <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                        {slots.map((slot, index) => {
                            const isSelected = selectedSlot?.start_time === slot.start_time;
                            return (
                                <button
                                    key={index}
                                    onClick={() => handleSlotClick(slot)}
                                    disabled={!slot.available}
                                    className={`py-3 px-4 rounded-lg border-2 font-medium transition-all ${isSelected
                                            ? 'border-blue-600 bg-blue-600 text-white'
                                            : slot.available
                                                ? 'border-green-200 bg-green-50 text-green-700 hover:border-green-400'
                                                : 'border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed'
                                        }`}
                                >
                                    {formatTime(slot.start_time)}
                                </button>
                            );
                        })}
                    </div>
                ) : (
                    <div className="text-center py-8 text-gray-500">
                        <svg className="w-12 h-12 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p>No available slots for this date</p>
                    </div>
                )}
            </div>
        </div>
    );
}
