<?php

namespace App\Rules;

use App\Enums\IncidentStatus;
use App\Models\Incident;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidStatusTransition implements ValidationRule
{
    public function __construct(
        private readonly ?Incident $incident = null
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // If there's no existing incident, any valid status is allowed
        if (!$this->incident) {
            if (!in_array($value, IncidentStatus::values())) {
                $fail('The :attribute must be a valid incident status.');
            }
            return;
        }

        // Check if the transition is valid
        $currentStatus = $this->incident->status;
        $newStatus = IncidentStatus::tryFrom($value);

        if (!$newStatus) {
            $fail('The :attribute must be a valid incident status.');
            return;
        }

        if (!$currentStatus->canTransitionTo($newStatus)) {
            $allowedStatuses = implode(', ', array_map(
                fn(IncidentStatus $status) => $status->value,
                $currentStatus->getAllowedTransitions()
            ));
            
            $fail("Cannot transition from {$currentStatus->value} to {$newStatus->value}. Allowed transitions: {$allowedStatuses}");
        }
    }
}
