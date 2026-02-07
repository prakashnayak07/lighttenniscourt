<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Policy handles authorization
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'resource_id' => ['required', 'exists:resources,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'visibility' => ['sometimes', 'in:public,private'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'resource_id.required' => 'Please select a court.',
            'resource_id.exists' => 'The selected court does not exist.',
            'date.required' => 'Please select a date.',
            'date.after_or_equal' => 'Booking date must be today or in the future.',
            'start_time.required' => 'Please select a start time.',
            'start_time.date_format' => 'Start time must be in HH:MM format.',
            'end_time.required' => 'Please select an end time.',
            'end_time.date_format' => 'End time must be in HH:MM format.',
            'end_time.after' => 'End time must be after start time.',
            'visibility.in' => 'Visibility must be either public or private.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
        ];
    }
}
