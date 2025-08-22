<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateNotificationSettingsRequest extends FormRequest
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
            'email_notifications' => ['sometimes', 'boolean'],
            'notification_email' => ['nullable', 'email', 'max:255'],
            'slack_webhook_url' => ['nullable', 'url', 'max:500'],
            'teams_webhook_url' => ['nullable', 'url', 'max:500'],
            'discord_webhook_url' => ['nullable', 'url', 'max:500'],
            'default_notification_channels' => ['sometimes', 'array'],
            'default_notification_channels.*' => ['string', 'in:email,slack,teams,discord'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'notification_email.email' => 'Please provide a valid email address for notifications.',
            'slack_webhook_url.url' => 'Please provide a valid Slack webhook URL.',
            'teams_webhook_url.url' => 'Please provide a valid Teams webhook URL.',
            'discord_webhook_url.url' => 'Please provide a valid Discord webhook URL.',
        ];
    }
}
