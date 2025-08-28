<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\OpenApi\Model\Operation;
use App\State\TempsReelProvider;

/**
 * Représente les données tarifaires applicable actuellement, au moment de la requête. Contrairement à l'objet JourTempo, qui indique le tarif applicable un jour donné de 6h à 6h le lendemain, l'objet TempsReel tient compte de l'heure d'appel (s'il est moins de 6h du matin, le tarif de la veille est renvoyé).
 */
#[ApiResource(
    paginationEnabled: false,
    operations: [
        new Get(
            name: 'now',
            uriTemplate: 'now',
            provider: TempsReelProvider::class,
            openapi: new Operation(
                summary: "Retourne le tarif applicable actuellement.",
                description: "Cette méthode ne nécessite aucun paramètre et renvoie simplement les données tarifaires actuelles.\n\nCette méthode tient compte de l'heure d'appel (s'il est moins de 6h du matin, le tarif de la veille est renvoyé).\n\nCliquez sur 'Try it out' pour expérimenter et obtenir le code correspondant."
            )
        ),
    ]
)]
class TempsReel
{
    /**
     * Code couleur du tarif Tempo applicable. Il s'agit de la couleur tarifaire applicable actuellement. Valeurs possibles:
     * - 0: tarif inconnu (ne devrait pas arriver sauf en cas d'erreur de remontée de l'information officielle)
     * - 1: tarif bleu
     * - 2: tarif blanc
     * - 3: tarif rouge
     */
    private int $codeCouleur;

    /**
     * Code indiquant si on est actuellement en heures pleines ou en heures creuses. Valeurs possibles:
     * - 0: heures creuses
     * - 1: heures pleines
     */
    private int $codeHoraire;

    /**
     * Tarif actuel du Kwh en euros TTC. 0 en cas d'erreur d'obtention du tarif.
     */
    private float $tarifKwh = 0;

    /**
     * Libellé du tarif applicable, pour affichage. Le libellé est une de ces chaines:
     * - "Bleu-HC"
     * - "Bleu-HP"
     * - "Blanc-HC"
     * - "Blanc-HP"
     * - "Rouge-HC"
     * - "Rouge-HP"
     * - "Inconnu-HP" ou "Inconnu-HC" en cas d'erreur de remontée des données officielles
     */
    private string $libTarif;

    public function getCodeCouleur(): ?int
    {
        return $this->codeCouleur;
    }

    public function setCodeCouleur(int $codeCouleur): static
    {
        $this->codeCouleur = $codeCouleur;

        return $this;
    }

    public function getCodeHoraire(): ?int
    {
        return $this->codeHoraire;
    }

    public function setCodeHoraire(int $codeHoraire): static
    {
        $this->codeHoraire = $codeHoraire;

        return $this;
    }

    public function getTarifKwh(): ?float
    {
        return $this->tarifKwh;
    }

    public function setTarifKwh(float $tarifKwh): static
    {
        $this->tarifKwh = $tarifKwh;

        return $this;
    }

    public function getLibTarif(): ?string
    {
        return $this->libTarif;
    }

    public function setLibTarif(string $libTarif): static
    {
        $this->libTarif = $libTarif;

        return $this;
    }
}
