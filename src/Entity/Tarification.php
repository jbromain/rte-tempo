<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\Metadata\ApiProperty;
use App\Repository\TarificationRepository;
use App\State\TarificationProvider;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Contient la grille tarifaire actuelle de l'option Tempo, hors abonnement.
 * Les tarifs sont TTC.
 */
#[ORM\Entity(repositoryClass: TarificationRepository::class)]
#[ApiResource(
    paginationEnabled: false,
    operations: [
        new Get(
            name: 'getTarifs',
            uriTemplate: 'tarifs',
            provider: TarificationProvider::class,
            openapi: new Operation(
                summary: "Retourne les tarifs actuels de l'offre Tempo (prix au Kwh). Le prix des abonnements varie selon la puissance soucrite, il n'est pas fourni par cette API.",
                description: "Cette méthode ne nécessite aucun paramètre et renvoie simplement les données tarifaires.\n\nCliquez sur 'Try it out' pour expérimenter et obtenir le code correspondant."
            )
        ),
    ]
)]
class Tarification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[ApiProperty(identifier: false, readable: false, writable: false)]
    private ?int $id = null;

    /**
     * Tarif TTC du kWh en heures creuses, jour bleu
     */
    #[ORM\Column]
    private ?float $bleuHC = null;

    /**
     * Tarif TTC du kWh en heures pleines, jour bleu
     */
    #[ORM\Column]
    private ?float $bleuHP = null;

    /**
     * Tarif TTC du kWh en heures creuses, jour blanc
     */
    #[ORM\Column]
    private ?float $blancHC = null;

    /**
     * Tarif TTC du kWh en heures pleines, jour blanc
     */
    #[ORM\Column]
    private ?float $blancHP = null;

    /**
     * Tarif TTC du kWh en heures creuses, jour rouge
     */
    #[ORM\Column]
    private ?float $rougeHC = null;

    /**
     * Tarif TTC du kWh en heures pleines, jour rouge
     */
    #[ORM\Column]
    private ?float $rougeHP = null;

    /**
     * Identifiant du tarif sur l'API gouvernementale (https://tabular-api.data.gouv.fr/api/resources/0c3d1d36-c412-4620-8566-e5cbb4fa2b5a/data/).
     * Information technique à usage interne.
     */
    #[ORM\Column]
    private ?int $dataGouvId = 0;

    /**
     * Indique si le tarif a été forcé coté API, c'est-à-dire s'il a été modifié manuellement.
     * Dans ce cas, les prochaines mises à jour automatiques ne seront pas effectuées, jusqu'à ce que le
     * dataGouvId change sur l'API gouvernementale. Ce cas particulier permettra de mettre à jour les tarifs
     * manuellement plus rapidement en cas de retard de l'API gouvernementale, et de repartir automatiquement
     * sur la base officielle dès sa mise à jour.
     */
    #[ORM\Column(options:[ 'default' => false])]
    private ?bool $tarifForce = false;

    /**
     * Date de début des tarifs actuels.
     */
    #[ORM\Column]
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'format' => 'date',
            'example' => '2025-08-01'
        ]
    )]
    private ?string $dateDebut = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBleuHC(): ?float
    {
        return $this->bleuHC;
    }

    public function setBleuHC(float $bleuHC): static
    {
        $this->bleuHC = $bleuHC;

        return $this;
    }

    public function getBleuHP(): ?float
    {
        return $this->bleuHP;
    }

    public function setBleuHP(float $bleuHP): static
    {
        $this->bleuHP = $bleuHP;

        return $this;
    }

    public function getBlancHC(): ?float
    {
        return $this->blancHC;
    }

    public function setBlancHC(float $blancHC): static
    {
        $this->blancHC = $blancHC;

        return $this;
    }

    public function getBlancHP(): ?float
    {
        return $this->blancHP;
    }

    public function setBlancHP(float $blancHP): static
    {
        $this->blancHP = $blancHP;

        return $this;
    }

    public function getRougeHC(): ?float
    {
        return $this->rougeHC;
    }

    public function setRougeHC(float $rougeHC): static
    {
        $this->rougeHC = $rougeHC;

        return $this;
    }

    public function getRougeHP(): ?float
    {
        return $this->rougeHP;
    }

    public function setRougeHP(float $rougeHP): static
    {
        $this->rougeHP = $rougeHP;

        return $this;
    }

    public function getDataGouvId(): ?int
    {
        return $this->dataGouvId;
    }

    public function setDataGouvId(int $dataGouvId): static
    {
        $this->dataGouvId = $dataGouvId;

        return $this;
    }

    public function isTarifForce(): ?bool
    {
        return $this->tarifForce;
    }

    public function setTarifForce(bool $tarifForce): static
    {
        $this->tarifForce = $tarifForce;

        return $this;
    }

    public function getDateDebut(): ?string
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?string $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }
}
