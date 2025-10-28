<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $metadonnees = [
            'derniereModification' => $this->derniere_modification,
            'version' => $this->version,
        ];

        // Ajouter les informations de blocage si elles existent
        if ($this->metadonnees) {
            if (isset($this->metadonnees['motifBlocage'])) {
                $metadonnees['motifBlocage'] = $this->metadonnees['motifBlocage'];
            }
            if (isset($this->metadonnees['dateDebutBlocage'])) {
                $metadonnees['dateDebutBlocage'] = $this->metadonnees['dateDebutBlocage'];
            }
            if (isset($this->metadonnees['dateFinBlocage'])) {
                $metadonnees['dateFinBlocage'] = $this->metadonnees['dateFinBlocage'];
            }
            if (isset($this->metadonnees['dureeBlocage'])) {
                $metadonnees['dureeBlocage'] = $this->metadonnees['dureeBlocage'];
            }
            if (isset($this->metadonnees['uniteBlocage'])) {
                $metadonnees['uniteBlocage'] = $this->metadonnees['uniteBlocage'];
            }
            if (isset($this->metadonnees['statutProgramme'])) {
                $metadonnees['statutProgramme'] = $this->metadonnees['statutProgramme'];
            }
            if (isset($this->metadonnees['dateDeblocageAutomatique'])) {
                $metadonnees['dateDeblocageAutomatique'] = $this->metadonnees['dateDeblocageAutomatique'];
            }
            if (isset($this->metadonnees['motifDeblocageAutomatique'])) {
                $metadonnees['motifDeblocageAutomatique'] = $this->metadonnees['motifDeblocageAutomatique'];
            }
        }

        return [
            'id' => $this->id,
            'numeroCompte' => $this->numero_compte,
            'titulaire' => $this->titulaire,
            'type' => $this->type,
            'solde' => $this->solde,
            'devise' => $this->devise,
            'dateCreation' => $this->date_creation,
            'statut' => $this->statut,
            'metadonnees' => $metadonnees,
        ];
    }
}
