<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\OpenApi\Model\Operation;
use App\State\StatsProvider;

/**
 * Données statistiques sur la période Tempo en cours (01/09 -> 31/08). 
 * 
 * @author JB Romain jbromain25@gmail.com
 */
#[ApiResource(
    paginationEnabled: false,
    operations: [
        new Get(
            name: 'getStats',
            uriTemplate: 'stats',
            provider: StatsProvider::class,
            openapi: new Operation(
                summary: "Retourne les informations statistiques sur la période Tempo en cours (jours consommés et restants pour chaque couleur).",
                description: "Cette méthode ne nécessite aucun paramètre et renvoie simplement les données statistiques.\n\nLa date courante est incluse dans les statistiques de jours consommés dès 0h00. En revanche, le lendemain n'est jamais inclus, même lorsque sa couleur est déjà connue.\n\nCliquez sur 'Try it out' pour expérimenter et obtenir le code correspondant."
            )
        ),
    ]
)]
class Statistiques {
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'example' => '2025-2026'
        ]
    )]
    /**
     * La période est l'année Tempo concernée par ces statistiques. Les périodes vont du 1er septembre au 31 août. La période est retournée au format AAAA-AAAA, par exemple '2023-2024'.
     */
    public string $periode;

    #[ApiProperty(
        openapiContext: [
            'type' => 'bool',
            'example' => false
        ]
    )]
    /**
     * Indique si la période est bissextile. Les périodes bissextiles ont un jour bleu supplémentaire.
     */
    public bool $bissextile;

    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'example' => '2025-08-23'
        ]
    )]
    /**
     * Dernière date incluse dans les statistiques des jours consommés. Il s'agit de la date courante, au format AAAA-MM-JJ.
     */
    public string $dernierJourInclus;

    /**
     * Nombre total de jours Bleus consommés dans la période, depuis le 1er septembre et jusqu'au dernier jour inclus.
     */
    public int $joursBleusConsommes;

    /**
     * Nombre total de jours Blancs consommés dans la période, depuis le 1er septembre et jusqu'au dernier jour inclus.
     */
    public int $joursBlancsConsommes;

    /**
     * Nombre total de jours Rouges consommés dans la période, depuis le 1er septembre et jusqu'au dernier jour inclus.
     */
    public int $joursRougesConsommes;

    /**
     * Nombre total de jours Bleus restants dans la période, à partir du lendemain et jusqu'au 31/08.
     */
    public int $joursBleusRestants;

    /**
     * Nombre total de jours Blancs restants dans la période, à partir du lendemain et jusqu'au 31/08.
     */
    public int $joursBlancsRestants;

    /**
     * Nombre total de jours Rouges restants dans la période, à partir du lendemain et jusqu'au 31/08.
     */
    public int $joursRougesRestants;

}