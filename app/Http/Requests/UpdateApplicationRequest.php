<?php

namespace App\Http\Requests;

use App\Models\ApplicationGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateApplicationRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'url' => ['sometimes', 'url', 'max:255'],
            'url_to_watch' => ['nullable', 'url', 'max:255'],
            'expected_http_code' => ['sometimes', 'integer', 'min:100', 'max:599'],
            'application_group_id' => [
                'nullable',
                'string',
                'exists:application_groups,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $group = ApplicationGroup::find($value);
                        if ($group && $group->user_id !== Auth::id()) {
                            $fail('You can only add applications to your own groups.');
                        }
                    }
                }
            ],
        ];
    }
}
