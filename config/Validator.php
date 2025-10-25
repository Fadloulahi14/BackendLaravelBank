<?php

namespace App\Config;

class Validator
{
    private static array $errors = [];
    private static $instance = null;
    private static array $rules;

    public function __construct()
    {
        self::$errors = [];
        self::$rules = [
            "required" => function ($key, $value, $message = "Champ obligatoire") {
                if (empty($value)) {
                    self::addError($key, $message);
                }
            },
            "minLength" => function ($key, $value, $minLength, $message = "Trop court") {
                if (strlen($value) < $minLength) {
                    self::addError($key, $message);
                }
            },
            "isEmail" => function ($key, $value, $message = "Adresse e-mail invalide") {
                
                if (!preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/', $value)) {
                    self::addError($key, $message);
                    return;
                }

                $localPart = strstr($value, '@', true);
                if (!preg_match('/^[a-zA-Z0-9._-]+$/', $localPart)) {
                    self::addError($key, "Le nom d'utilisateur de l'e-mail contient des caractères non autorisés");
                    return;
                }

              
                $domainPart = substr(strstr($value, '@'), 1);
                if (!preg_match('/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $domainPart)) {
                    self::addError($key, "Le domaine de l'e-mail n'est pas valide");
                    return;
                }

                // Vérification de l'absence d'espaces
                if (strpos($value, ' ') !== false) {
                    self::addError($key, "L'adresse e-mail ne doit pas contenir d'espaces");
                }
            },
            "isPassword" => function ($key, $value, $message = "Mot de passe invalide") {
                if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/', $value)) {
                    self::addError($key, $message);
                }
            },
            "isPhoneNumber" => function ($key, $value, $message = "Numéro de téléphone invalide") {
                // Nettoyer le numéro (supprimer espaces et tirets)
                $cleanNumber = preg_replace('/[\s\-]/', '', $value);

                // Vérifier le format international avec +
                if (strpos($cleanNumber, '+') === 0) {
                    // Format international : +XXXXXXXXXX
                    $digitsOnly = substr($cleanNumber, 1);
                    if (!preg_match('/^\d{8,15}$/', $digitsOnly)) {
                        self::addError($key, "Le numéro international doit contenir entre 8 et 15 chiffres après le +");
                        return;
                    }

                    // Vérifier que le code pays commence par un chiffre entre 1 et 9
                    $countryCode = substr($digitsOnly, 0, 1);
                    if (!preg_match('/^[1-9]$/', $countryCode)) {
                        self::addError($key, "Le code pays doit commencer par un chiffre entre 1 et 9");
                        return;
                    }
                } else {
                    // Format local : XXXXXXXXX
                    if (!preg_match('/^\d{8,15}$/', $cleanNumber)) {
                        self::addError($key, "Le numéro local doit contenir entre 8 et 15 chiffres");
                        return;
                    }
                }

                // Vérifier l'absence de suites répétées
                if (preg_match('/^(\d)\1{7,}$/', preg_replace('/[^\d]/', '', $cleanNumber))) {
                    self::addError($key, "Le numéro ne peut pas être constitué d'une suite répétée");
                }
            },
            "isCNI" => function ($key, $value, $message = "Numéro de CNI invalide") {
                // Nettoyer le numéro (supprimer espaces et caractères non numériques)
                $cleanNumber = preg_replace('/\D/', '', $value);

                // Vérifier la longueur (13 chiffres pour le Sénégal)
                if (strlen($cleanNumber) !== 13) {
                    self::addError($key, "Le numéro de CNI doit contenir exactement 13 chiffres");
                    return;
                }

                // Vérifier qu'il commence par 1 (format sénégalais)
                if (!preg_match('/^1\d{12}$/', $cleanNumber)) {
                    self::addError($key, "Le numéro de CNI doit commencer par 1 et contenir uniquement des chiffres");
                    return;
                }

                // Vérifier l'absence de suites répétées
                if (preg_match('/^(\d)\1{12}$/', $cleanNumber)) {
                    self::addError($key, "Le numéro de CNI ne peut pas être constitué d'une suite répétée");
                    return;
                }

                // Calcul de la clé de contrôle (algorithme de Luhn simplifié pour CNI)
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
                    self::addError($key, "Le numéro de CNI n'est pas valide (clé de contrôle incorrecte)");
                }
            },
        ];
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function validate(array $data, array $rules): bool
    {
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                if (is_string($rule)) {
                    $callback = self::$rules[$rule] ?? null;
                    if ($callback) {
                        $callback($field, $value);
                    }
                }
                elseif (is_array($rule)) {
                    $ruleName = $rule[0];
                    $params = array_slice($rule, 1);
                    $callback = self::$rules[$ruleName] ?? null;

                    if ($callback) {
                        $callback($field, $value, ...$params);
                    }
                }
            }
       }

       return empty(self::$errors);
   }

   public static function addError(string $field, string $message)
   {
       self::$errors[$field] = $message;
   }

   public static function getErrors()
   {
       return self::$errors;
   }

   public static function resetError(){
       self::$errors = [];
   }
}