<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NciSenegalaisRule implements ValidationRule
{
  
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cleanNumber = preg_replace('/\D/', '', $value);

        if (strlen($cleanNumber) !== 13) {
            $fail('Le numéro de CNI doit contenir exactement 13 chiffres.');
            return;
        }

        if (!preg_match('/^1\d{12}$/', $cleanNumber)) {
            $fail('Le numéro de CNI doit commencer par 1 et contenir uniquement des chiffres.');
            return;
        }

        if (preg_match('/^(\d)\1{12}$/', $cleanNumber)) {
            $fail('Le numéro de CNI ne peut pas être constitué d\'une suite répétée.');
            return;
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int)$cleanNumber[$i];
            if ($i % 2 === 0) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
        }

        $checkDigit = (10 - ($sum % 10)) % 10;
        $expectedCheckDigit = (int)$cleanNumber[12];

        if ($checkDigit !== $expectedCheckDigit) {
            $fail('Le numéro de CNI n\'est pas valide (clé de contrôle incorrecte).');
        }
    }
}
