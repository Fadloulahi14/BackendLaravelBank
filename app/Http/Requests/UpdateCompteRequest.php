<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompteRequest extends FormRequest
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
        $rules = [];

        // Vérifier qu'au moins un champ est fourni
        if (empty(array_filter($this->all()))) {
            $rules['at_least_one_field'] = 'required';
        }

        // Règles pour les champs du compte
        if ($this->has('titulaire')) {
            $rules['titulaire'] = 'string|max:255';
        }

        if ($this->has('type')) {
            $rules['type'] = 'in:epargne,cheque';
        }

        if ($this->has('solde')) {
            $rules['solde'] = 'numeric|min:0';
        }

        if ($this->has('devise')) {
            $rules['devise'] = 'string|size:3';
        }

        if ($this->has('statut')) {
            $rules['statut'] = 'in:actif,bloque,ferme';
        }

        // Règles pour les informations client
        if ($this->has('informationsClient')) {
            $clientRules = [];

            if ($this->has('informationsClient.telephone')) {
                $clientRules['informationsClient.telephone'] = [
                    'nullable',
                    'string',
                    'unique:clients,telephone,' . $this->route('compte')->user->client->id,
                    new \App\Rules\TelephoneSenegalaisRule()
                ];
            }

            if ($this->has('informationsClient.email')) {
                $clientRules['informationsClient.email'] = [
                    'nullable',
                    'email',
                    'unique:clients,email,' . $this->route('compte')->user->client->id
                ];
            }

            if ($this->has('informationsClient.password')) {
                $clientRules['informationsClient.password'] = 'nullable|string|min:8';
            }

            if ($this->has('informationsClient.nci')) {
                $clientRules['informationsClient.nci'] = [
                    'nullable',
                    'string',
                    'unique:clients,nci,' . $this->route('compte')->user->client->id,
                    new \App\Rules\NciSenegalaisRule()
                ];
            }

            $rules = array_merge($rules, $clientRules);
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'at_least_one_field.required' => 'Au moins un champ doit être fourni pour la mise à jour.',
            'informationsClient.telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'informationsClient.email.unique' => 'Cette adresse e-mail est déjà utilisée.',
            'informationsClient.email.email' => 'L\'adresse e-mail doit être valide.',
            'informationsClient.password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'informationsClient.nci.unique' => 'Ce numéro de CNI est déjà utilisé.',
        ];
    }
}
