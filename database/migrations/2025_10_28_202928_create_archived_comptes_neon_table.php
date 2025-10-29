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
        Schema::create('archived_comptes_neon', function (Blueprint $table) {
            $table->id();
            $table->uuid('original_id');
            $table->string('numero_compte');
            $table->uuid('user_id');
            $table->enum('type', ['epargne', 'cheque']);
            $table->decimal('solde', 15, 2);
            $table->string('devise', 10)->default('FCFA');
            $table->enum('statut', ['actif', 'bloque', 'ferme']);
            $table->json('metadonnees')->nullable();
            $table->timestamp('date_archivage');
            $table->text('motif_archivage');
            $table->timestamps();

            $table->index(['original_id', 'numero_compte']);
            $table->index('date_archivage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archived_comptes_neon');
    }
};
