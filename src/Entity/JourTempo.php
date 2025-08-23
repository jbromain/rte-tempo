<?php

namespace App\Entity;

use ApiPlatform\Metadata\Get;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\JourTempoRepository;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use App\State\SingleDayTempoProvider;

define("TARIF_INCONNU", 0);
define("TARIF_BLEU", 1);
define("TARIF_BLANC", 2);
define("TARIF_ROUGE", 3);

/**
 * Représente une journée du calendrier tarifaire Tempo.
 * 
 * @author JB Romain jbromain25@gmail.com
 */

#[ORM\Entity(repositoryClass: JourTempoRepository::class)]
#[ApiResource(
    order: ['dateJour' => 'ASC'],
    paginationEnabled: false,
    operations: [
        new Get(
            name: 'getToday',
            uriTemplate: 'jourTempo/today',
            provider: SingleDayTempoProvider::class,
            openapi: new Operation(
                summary: "Retourne les informations Tempo pour aujourd'hui.",
                description: "Cette méthode ne nécessite aucun paramètre et renvoie simplement les données pour aujourd'hui.\n\nCliquez sur 'Try it out' pour expérimenter et obtenir le code correspondant."
            )
        ),
        new Get(
            name: 'getTomorrow',
            uriTemplate: 'jourTempo/tomorrow',
            provider: SingleDayTempoProvider::class,
            openapi: new Operation(
                summary: "Retourne les informations Tempo pour demain.",
                description: "Cette méthode ne nécessite aucun paramètre et renvoie simplement les données pour demain.\n\nNotez que les donnée du lendemain sont susceptibles de ne pas être encore disponible (code jour à 0); elles peuvent également ne pas être définitives (changement possible jusqu'à 12h environ).\n\nCliquez sur 'Try it out' pour expérimenter et obtenir le code correspondant."
            )
        ),
        new Get(
            uriTemplate: '/jourTempo/{dateJour}',
            openapi: new Operation(
                summary: "Retourne les informations Tempo d'une date donnée.",
                description: "Spécifiez simplement la date souhaitée au format AAAA-MM-JJ.\n\nNotez que les donnée du lendemain sont susceptibles de ne pas être encore disponible (code jour à 0); elles peuvent également ne pas être définitives (changement possible jusqu'à 12h environ).\n\nCliquez sur 'Try it out' pour expérimenter et obtenir le code correspondant."
            )
        ),
        new GetCollection(
            uriTemplate: '/joursTempo',
            openapi: new Operation(
                summary: "Retourne les informations d'un ou plusieurs jours selon les critères de filtrages fournis.",
                description: "Cette méthode plus complexe permet de récupérer les informations de plusieurs jours en une seule requête.\n\nSpécifiez les critères de recherche, par exemple une date ou un ensemble de dates, afin d'obtenir les informations correspondantes.\n\nLes données sont retournées dans l'ordre chronologique.\n\nCliquez sur 'Try it out' pour expérimenter et obtenir le code correspondant."
            )
        )
    ]
)]
#[ApiFilter(SearchFilter::class, properties: ['dateJour' => 'exact', 'codeJour' => 'exact', 'periode' => 'exact'])]
class JourTempo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[ApiProperty(identifier: false, readable: false, writable: false)]
    private ?int $id = null;

    #[ORM\Column]
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'format' => 'date',
            'example' => '2024-11-08'
        ],

        identifier: true
    )]
    /**
     * Date du jour concerné par l'information. La date est au format SQL (AAAA-MM-JJ).
     */
    private string $dateJour;
    // NB: string car impossible de faire marcher une date, bug Doctrine probable, erreur:
    // "The class 'DateTimeImmutable' was not found in the chain configured namespaces App\Entity"

    #[ORM\Column(type: Types::SMALLINT)]
    /**
     * Code couleur du tarif Tempo applicable. Il s'agit de la couleur tarifaire applicable à partir de 6h du matin du jour indiqué, jusqu'au lendemain à 6h. Valeurs possibles:
     * - 0: tarif inconnu (pas encore communiqué par RTE)
     * - 1: tarif bleu
     * - 2: tarif blanc
     * - 3: tarif rouge
     */
    private ?int $codeJour = null;

    #[ORM\Column]
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'example' => '2025-2026'
        ]
    )]
    /**
     * La période est l'année Tempo à laquelle ce jour appartient. Les périodes vont du 1er septembre au 31 août. La période est retournée au format AAAA-AAAA, par exemple '2023-2024'.
     */
    private string $periode;

    
    /*#[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'example' => 'Bleu'
        ]
    )]
    private string $libCouleur = "salut";*/


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateJour(): string
    {
        return $this->dateJour;
    }

    public function setDateJour(\DateTimeInterface $dateJour): static
    {
        $this->dateJour = $dateJour->format('Y-m-d');

        return $this;
    }

    public function getCodeJour(): ?int
    {
        return $this->codeJour;
    }

    public function setCodeJour(int $codeJour): static
    {
        $this->codeJour = $codeJour;

        return $this;
    }

    public function getPeriode(): ?string
    {
        return $this->periode;
    }

    public function setPeriode(string $periode): static
    {
        $this->periode = $periode;

        return $this;
    }

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'example' => 'Bleu'
        ]
    )]
    /**
     * Libellé de la couleur en toutes lettres, parmi Inconnu, Bleu, Blanc, Rouge.
     */
    public function getLibCouleur(): string
    {
       switch($this->codeJour) {
           case 1:
               return 'Bleu';
           case 2:
               return 'Blanc';
           case 3:
               return 'Rouge';
           default:
               return 'Inconnu';
       }
    }
}
