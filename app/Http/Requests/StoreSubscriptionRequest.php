<?php

namespace App\Http\Requests;

use App\Models\Application;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreSubscriptionRequest extends FormRequest
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
            'subscribable_type' => ['required', 'string', Rule::in([Application::class, \App\Models\ApplicationGroup::class])],
            'subscribable_id' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $type = request()->input('subscribable_type');
                    if (! $type) {
                        return;
                    }

                    if (! class_exists($type)) {
                        $fail('Invalid subscribable type.');

                        return;
                    }

                    $model = $type::find($value);
                    if (! $model) {
                        $fail('The selected subscribable resource does not exist.');

                        return;
                    }

                    if ($model->user_id !== Auth::id()) {
                        $fail('You can only create subscriptions for your own resources.');

                        return;
                    }

                    // Check for existing subscription
                    $existingSubscription = \App\Models\Subscription::where([
                        'user_id' => Auth::id(),
                        'subscribable_type' => $type,
                        'subscribable_id' => $value,
                    ])->exists();

                    if ($existingSubscription) {
                        $fail('You already have a subscription for this resource.');
                    }
                },
            ],
            'notification_channels' => ['required', 'array', 'min:1'],
            'notification_channels.*' => [Rule::in(['email', 'slack', 'teams', 'discord'])],
            'webhook_url' => ['nullable', 'url'],
        ];
    }

    /**
     * Get the validated data with defaults.
     */
    public function validated($key = null, $default = null): array
    {
        return parent::validated($key, $default);
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'notification_channels.required' => 'At least one notification channel is required.',
            'notification_channels.min' => 'At least one notification channel is required.',
        ];
    }
}
