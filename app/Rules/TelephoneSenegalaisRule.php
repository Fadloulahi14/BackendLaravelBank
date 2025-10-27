<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class TelephoneSenegalaisRule implements ValidationRule
{
   
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cleanNumber = preg_replace('/[\s\-]/', '', $value);

        if (strpos($cleanNumber, '+') === 0) {
            $digitsOnly = substr($cleanNumber, 1);
            if (!preg_match('/^\d{12}$/', $digitsOnly)) {
                $fail('Le numéro de téléphone international doit contenir exactement 12 chiffres après +.');
                return;
            }

            if (!preg_match('/^2217\d{8}$/', $digitsOnly)) {
                $fail('Le numéro doit être un numéro sénégalais valide (+2217XXXXXXXX).');
                return;
            }
        } else {
            if (!preg_match('/^7\d{8}$/', $cleanNumber)) {
                $fail('Le numéro local doit être au format 7XXXXXXXX (9 chiffres).');
                return;
            }
        }

        if (preg_match('/^(\d)\1{7,}$/', preg_replace('/[^\d]/', '', $cleanNumber))) {
            $fail('Le numéro de téléphone ne peut pas être constitué d\'une suite répétée.');
        }
    }
}
