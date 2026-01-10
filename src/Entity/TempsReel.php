<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation;
use App\State\PrixHorairesProvider;
use App\State\TempsReelProvider;

/**
 * Représente les données tarifaires applicables à un instant donné. 
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
        new GetCollection(
            name: '24h',
            uriTemplate: '24h',
            provider: PrixHorairesProvider::class,
            openapi: new Operation(
                summary: "Retourne les données de couleurs et tarifs sur 24 heures glissantes.",
                description: "Cette méthode retourne un tableau de 24 objets TempsReel. La première donnée correspond à l'heure actuelle (identique à /api/now).\n\nLa logique Tempo est appliquée: le jour Tempo commence à 6h et se termine à 6h le lendemain.\n\nSi la couleur d'une heure n'est pas encore connue (typiquement pour les heures du lendemain après-midi), la valeur contiendra des données par défaut conformément à la description de l'objet TempsReel.\n\nCliquez sur 'Try it out' pour expérimenter et obtenir le code correspondant."
            )
        ),
    ]
)]
class TempsReel
{
    /**
     * Indique à quoi correspond cette donnée horaire. Valeurs possibles:
     * - 0: donnée pour l'heure en cours (de l'appel)
     * - 1: donnée pour l'heure suivante
     * - 2: donnée pour l'heure d'après
     * - etc jusqu'à 23 (donnée pour l'heure 23 heures après l'appel)
     */
    private int $applicableIn = 0;

    /**
     * Code couleur du tarif Tempo applicable. Il s'agit de la couleur tarifaire applicable. Valeurs possibles:
     * - 0: tarif inconnu (ne devrait pas arriver dans le cadre d'un appel sur /now sauf en cas d'erreur de remontée de l'information officielle)
     * - 1: tarif bleu
     * - 2: tarif blanc
     * - 3: tarif rouge
     */
    private int $codeCouleur;

    /**
     * Code indiquant si le tarif est en heures pleines ou en heures creuses. Valeurs possibles:
     * - 0: heures creuses
     * - 1: heures pleines
     */
    private int $codeHoraire;

    /**
     * Tarif du Kwh en euros TTC. 0 en cas d'erreur d'obtention du tarif.
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
     * - "Inconnu-HP" ou "Inconnu-HC" si l'information n'est pas disponible.
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

    public function getApplicableIn(): ?int
    {
        return $this->applicableIn;
    }
    
    public function setApplicableIn(int $applicableIn): static
    {
        $this->applicableIn = $applicableIn;

        return $this;
    }
}
