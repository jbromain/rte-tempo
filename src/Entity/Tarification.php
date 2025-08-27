<?php

namespace App\Entity;

use App\Repository\TarificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Contient la grille tarifaire actuelle de l'option Tempo, hors abonnement.
 * Les tarifs sont TTC.
 */
#[ORM\Entity(repositoryClass: TarificationRepository::class)]
class Tarification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Tarif du kWh en heures creuses, jour bleu
     */
    #[ORM\Column]
    private ?float $bleuHC = null;

    /**
     * Tarif du kWh en heures pleines, jour bleu
     */
    #[ORM\Column]
    private ?float $bleuHP = null;

    /**
     * Tarif du kWh en heures creuses, jour blanc
     */
    #[ORM\Column]
    private ?float $blancHC = null;

    /**
     * Tarif du kWh en heures pleines, jour blanc
     */
    #[ORM\Column]
    private ?float $blancHP = null;

    /**
     * Tarif du kWh en heures creuses, jour rouge
     */
    #[ORM\Column]
    private ?float $rougeHC = null;

    /**
     * Tarif du kWh en heures pleines, jour rouge
     */
    #[ORM\Column]
    private ?float $rougeHP = null;

    /**
     * Identifiant du tarif sur l'API gouvernementale (https://tabular-api.data.gouv.fr/api/resources/0c3d1d36-c412-4620-8566-e5cbb4fa2b5a/data/).
     * Information technique à usage interne.
     */
    #[ORM\Column]
    private ?int $dataGouvId = null;

    /**
     * Indique si le tarif a été forcé coté API, c'est-à-dire s'il a été modifié manuellement.
     * Dans ce cas, les prochaines mises à jour automatiques ne seront pas effectuées, jusqu'à ce que le
     * dataGouvId change sur l'API gouvernementale. Ce cas particulier permettra de mettre à jour les tarifs
     * manuellement plus rapidement en cas de retard de l'API gouvernementale, et de repartir automatiquement
     * sur la base officielle dès sa mise à jour.
     */
    #[ORM\Column(options:[ 'default' => false])]
    private ?bool $tarifForce = null;

    /**
     * Date de début des tarifs actuels.
     */
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $dateDebut = null;

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

    public function getDateDebut(): ?\DateTimeImmutable
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeImmutable $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }
}
