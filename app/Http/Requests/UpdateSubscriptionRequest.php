<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateSubscriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'notification_type' => ['sometimes', Rule::in(['email', 'slack', 'teams', 'discord'])],
            'email' => ['required_if:notification_type,email', 'nullable', 'email', 'max:255'],
            'webhook_url' => [
                'required_if:notification_type,slack,teams,discord',
                'nullable',
                'url',
                'max:500'
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.required_if' => 'Email address is required for email notifications.',
            'webhook_url.required_if' => 'Webhook URL is required for webhook-based notifications.',
        ];
    }
}
