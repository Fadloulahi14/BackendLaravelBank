<?php

namespace App\Http\Requests;

use App\Rules\NciSenegalaisRule;
use App\Rules\TelephoneSenegalaisRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCompteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'type' => 'required|in:epargne,cheque',
            'soldeInitial' => 'required|numeric|min:0',
            'devise' => 'required|string|max:3',
            'client' => 'required|array',
            'client.titulaire' => 'required|string|min:2|max:255',
        ];

        // Règles conditionnelles selon que le client existe ou non
        if (empty($this->input('client.id'))) {
            // Nouveau client
            $rules = array_merge($rules, [
                'client.nci' => ['required', 'string', 'unique:clients,nci'],
                'client.email' => 'required|email|unique:clients,email|max:255',
                'client.telephone' => ['required', new TelephoneSenegalaisRule(), 'unique:clients,telephone'],
                'client.adresse' => 'required|string|min:5|max:500',
            ]);
        } else {
            // Client existant - vérifier qu'il existe
            $rules = array_merge($rules, [
                'client.id' => 'required|exists:users,id',
            ]);
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Le type de compte est obligatoire.',
            'type.in' => 'Le type de compte doit être soit "epargne" soit "cheque".',
            'soldeInitial.required' => 'Le solde initial est obligatoire.',
            'soldeInitial.numeric' => 'Le solde initial doit être un nombre.',
            'soldeInitial.min' => 'Le solde initial doit être supérieur ou égal à 0.',
            'devise.required' => 'La devise est obligatoire.',
            'devise.string' => 'La devise doit être une chaîne de caractères.',
            'devise.max' => 'La devise ne peut pas dépasser 3 caractères.',
            'client.required' => 'Les informations du client sont obligatoires.',
            'client.array' => 'Les informations du client doivent être un tableau.',
            'client.titulaire.required' => 'Le nom du titulaire est obligatoire.',
            'client.titulaire.string' => 'Le nom du titulaire doit être une chaîne de caractères.',
            'client.titulaire.min' => 'Le nom du titulaire doit contenir au moins 2 caractères.',
            'client.titulaire.max' => 'Le nom du titulaire ne peut pas dépasser 255 caractères.',
            'client.id.exists' => 'Le client spécifié n\'existe pas.',
            'client.nci.required' => 'Le numéro de CNI est obligatoire.',
            'client.nci.unique' => 'Ce numéro de CNI est déjà utilisé.',
            'client.email.required' => 'L\'adresse e-mail est obligatoire.',
            'client.email.email' => 'L\'adresse e-mail n\'est pas valide.',
            'client.email.unique' => 'Cette adresse e-mail est déjà utilisée.',
            'client.email.max' => 'L\'adresse e-mail ne peut pas dépasser 255 caractères.',
            'client.telephone.required' => 'Le numéro de téléphone est obligatoire.',
            'client.telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'client.adresse.required' => 'L\'adresse est obligatoire.',
            'client.adresse.string' => 'L\'adresse doit être une chaîne de caractères.',
            'client.adresse.min' => 'L\'adresse doit contenir au moins 5 caractères.',
            'client.adresse.max' => 'L\'adresse ne peut pas dépasser 500 caractères.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'type' => 'type de compte',
            'soldeInitial' => 'solde initial',
            'devise' => 'devise',
            'client.titulaire' => 'nom du titulaire',
            'client.nci' => 'numéro de CNI',
            'client.email' => 'adresse e-mail',
            'client.telephone' => 'numéro de téléphone',
            'client.adresse' => 'adresse',
        ];
    }
}
