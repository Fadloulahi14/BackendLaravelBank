<?php

namespace App\Config;

use App\Config\Validator;

class ValidationService
{
    private static $instance = null;
    private Validator $validator;

    public function __construct()
    {
        $this->validator = Validator::getInstance();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Valide les données de connexion
     */
    public function validateLogin(array $data): array
    {
        $rules = [
            'login' => ['required', ['minLength', 3, 'Le login doit contenir au moins 3 caractères']],
            'password' => ['required', ['minLength', 8, 'Le mot de passe doit contenir au moins 8 caractères']]
        ];

        $isValid = $this->validator->validate($data, $rules);

        return [
            'isValid' => $isValid,
            'errors' => Validator::getErrors()
        ];
    }

    /**
     * Valide les données d'inscription
     */
    public function validateRegister(array $data): array
    {
        $rules = [
            'login' => ['required', ['minLength', 3, 'Le login doit contenir au moins 3 caractères']],
            'password' => ['required', 'isPassword'],
            'nom' => ['required', ['minLength', 2, 'Le nom doit contenir au moins 2 caractères']],
            'nci' => ['required', 'isCNI'],
            'email' => ['required', 'isEmail'],
            'telephone' => ['required', 'isPhoneNumber'],
            'adresse' => ['required', ['minLength', 5, 'L\'adresse doit contenir au moins 5 caractères']]
        ];

        $isValid = $this->validator->validate($data, $rules);

        return [
            'isValid' => $isValid,
            'errors' => Validator::getErrors()
        ];
    }

    /**
     * Valide un numéro de téléphone
     */
    public function validatePhoneNumber(string $phone): array
    {
        $rules = [
            'phone' => ['isPhoneNumber']
        ];

        $isValid = $this->validator->validate(['phone' => $phone], $rules);

        return [
            'isValid' => $isValid,
            'errors' => Validator::getErrors()
        ];
    }

    /**
     * Valide une adresse e-mail
     */
    public function validateEmail(string $email): array
    {
        $rules = [
            'email' => ['isEmail']
        ];

        $isValid = $this->validator->validate(['email' => $email], $rules);

        return [
            'isValid' => $isValid,
            'errors' => Validator::getErrors()
        ];
    }

    /**
     * Valide un numéro de CNI
     */
    public function validateCNI(string $cni): array
    {
        $rules = [
            'cni' => ['isCNI']
        ];

        $isValid = $this->validator->validate(['cni' => $cni], $rules);

        return [
            'isValid' => $isValid,
            'errors' => Validator::getErrors()
        ];
    }

    /**
     * Valide un mot de passe
     */
    public function validatePassword(string $password): array
    {
        $rules = [
            'password' => ['isPassword']
        ];

        $isValid = $this->validator->validate(['password' => $password], $rules);

        return [
            'isValid' => $isValid,
            'errors' => Validator::getErrors()
        ];
    }

    /**
     * Réinitialise les erreurs
     */
    public function resetErrors(): void
    {
        Validator::resetError();
    }

    /**
     * Récupère les erreurs actuelles
     */
    public function getErrors(): array
    {
        return Validator::getErrors();
    }
}