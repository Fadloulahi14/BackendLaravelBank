<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'compte_id' => \App\Models\Compte::factory(),
            'type' => $this->faker->randomElement(['depot', 'retrait', 'virement', 'frais']),
            'montant' => $this->faker->randomFloat(2, 1000, 100000),
            'devise' => 'FCFA',
            'description' => $this->faker->sentence(6), // Réduire la longueur
            'statut' => $this->faker->randomElement(['en_attente', 'validee', 'annulee']),
            'date_transaction' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
