<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('compte_id')->constrained('comptes')->onDelete('cascade');
            $table->enum('type', ['depot', 'retrait', 'virement', 'frais']);
            $table->decimal('montant', 15, 2);
            $table->string('devise', 3)->default('FCFA');
            $table->text('description')->nullable();
            $table->enum('statut', ['en_attente', 'validee', 'annulee'])->default('en_attente');
            $table->timestamp('date_transaction')->useCurrent();
            $table->timestamps();

            $table->index(['compte_id', 'type', 'statut']);
            $table->index(['date_transaction']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
