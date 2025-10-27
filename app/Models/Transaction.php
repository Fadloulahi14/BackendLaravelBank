<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'compte_id',
        'type',
        'montant',
        'devise',
        'description',
        'statut',
        'date_transaction',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_transaction' => 'datetime',
    ];

    /**
     * Relation avec le compte
     */
    public function compte(): BelongsTo
    {
        return $this->belongsTo(Compte::class);
    }

    /**
     * Scope pour les transactions validées
     */
    public function scopeValidees($query)
    {
        return $query->where('statut', 'validee');
    }

    /**
     * Scope pour les transactions en attente
     */
    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    /**
     * Scope pour les transactions annulées
     */
    public function scopeAnnulees($query)
    {
        return $query->where('statut', 'annulee');
    }

    /**
     * Scope pour filtrer par type
     */
    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Calculer l'impact sur le solde
     */
    public function getImpactSoldeAttribute(): float
    {
        return match($this->type) {
            'depot', 'virement' => $this->montant,
            'retrait', 'frais' => -$this->montant,
            default => 0
        };
    }
}
