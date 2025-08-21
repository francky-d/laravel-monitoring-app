<?php

namespace App\Http\Requests;

use App\Enums\IncidentSeverity;
use App\Enums\IncidentStatus;
use App\Models\Application;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreIncidentRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'application_id' => [
                'required',
                'string',
                'exists:applications,id',
                function ($attribute, $value, $fail) {
                    $application = Application::find($value);
                    if ($application && $application->user_id !== Auth::id()) {
                        $fail('You can only create incidents for your own applications.');
                    }
                }
            ],
            'status' => ['sometimes', Rule::enum(IncidentStatus::class)],
            'severity' => ['sometimes', Rule::enum(IncidentSeverity::class)],
            'started_at' => ['sometimes', 'date'],
        ];
    }

    /**
     * Get the validated data with defaults.
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);
        
        // Set defaults
        $validated['user_id'] = Auth::id();
        $validated['status'] = $validated['status'] ?? IncidentStatus::OPEN->value;
        $validated['severity'] = $validated['severity'] ?? IncidentSeverity::LOW->value;
        $validated['started_at'] = $validated['started_at'] ?? now();

        return $validated;
    }
}
