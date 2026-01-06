<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\OpenApi\Model\Operation;
use App\State\PrixHorairesProvider;

/**
 * Représente les prix kWh pour les 24 prochaines heures glissantes.
 * Compatible avec l'optimiseur de prix journalier Loxone.
 */
#[ApiResource(
    paginationEnabled: false,
    operations: [
        new Get(
            name: 'getPrixHoraires',
            uriTemplate: 'prixHoraires',
            provider: PrixHorairesProvider::class,
            openapi: new Operation(
                summary: "Retourne les prix kWh pour les 24 prochaines heures glissantes.",
                description: "Cette méthode retourne un objet contenant les prix du kWh en euros TTC pour les 24 prochaines heures (H0 = heure actuelle, H23 = dans 23 heures).\n\nLa logique Tempo officielle est appliquée : le jour Tempo commence à 6h et se termine à 6h le lendemain.\n\nSi la couleur d'une heure n'est pas encore connue (typiquement pour les heures du lendemain après-midi), la valeur sera null.\n\nCliquez sur 'Try it out' pour expérimenter et obtenir le code correspondant."
            )
        ),
    ]
)]
class PrixHoraires
{
    /**
     * Tableau associatif des tarifs par heure (H0 à H23).
     * H0 = heure actuelle, H23 = dans 23 heures.
     * Chaque entrée contient codeCouleur, codeHoraire, tarifKwh et libTarif.
     * Valeur = null si la couleur n'est pas connue.
     * @var array<string, array{codeCouleur: int, codeHoraire: int, tarifKwh: float, libTarif: string}|null>
     */
    #[ApiProperty(
        example: [
            "H0" => ["codeCouleur" => 3, "codeHoraire" => 1, "tarifKwh" => 0.6468, "libTarif" => "Rouge-HP"],
            "H1" => ["codeCouleur" => 3, "codeHoraire" => 1, "tarifKwh" => 0.6468, "libTarif" => "Rouge-HP"],
            "H2" => ["codeCouleur" => 3, "codeHoraire" => 0, "tarifKwh" => 0.146, "libTarif" => "Rouge-HC"],
            "H3" => null
        ]
    )]
    private array $prix = [];

    public function getPrix(): array
    {
        return $this->prix;
    }

    public function setPrix(array $prix): static
    {
        $this->prix = $prix;
        return $this;
    }

    public function setHourData(int $hour, ?array $data): static
    {
        $this->prix["H$hour"] = $data;
        return $this;
    }
}
