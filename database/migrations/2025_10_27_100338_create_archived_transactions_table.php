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
        Schema::create('archived_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('original_id');
            $table->uuid('compte_id');
            $table->enum('type', ['depot', 'retrait', 'virement']);
            $table->decimal('montant', 15, 2);
            $table->string('devise', 3)->default('FCFA');
            $table->text('description')->nullable();
            $table->string('statut', 20)->default('en_attente');
            $table->timestamp('date_transaction');
            $table->timestamp('archived_at');
            $table->timestamps();

            $table->index(['compte_id', 'date_transaction']);
            $table->index('archived_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archived_transactions');
    }
};
