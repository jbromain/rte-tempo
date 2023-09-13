<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\JourTempoRepository;
use DateTime;

/**
 * Fournisseur de données pour les jours tempo individuels Aujourd'hui et Demain.
 */
class SingleDayTempoProvider implements ProviderInterface
{

    private JourTempoRepository $jourTempoRepository;
    public function __construct(JourTempoRepository $jourTempoRepository)
    {
        $this->jourTempoRepository = $jourTempoRepository;
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {

        switch ($operation->getName()) {
            case 'getTomorrow':
                $dateJour = new DateTime('tomorrow');
                break;
            case 'getToday':
            default:
                // Cas général (aujourd'hui)
                $dateJour = new DateTime();
        }

        return $this->jourTempoRepository->findOneBy([
            'dateJour' => $dateJour->format('Y-m-d')
        ]);
    }
}
