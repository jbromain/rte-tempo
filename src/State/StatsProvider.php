<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Statistiques;
use App\Repository\JourTempoRepository;
use App\Service\DateService;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

/**
 * Fournisseur de données pour les statistiques de la période courante.
 */
class StatsProvider implements ProviderInterface
{

    private JourTempoRepository $jourTempoRepository;
    public function __construct(JourTempoRepository $jourTempoRepository, private DateService $dateService, private LoggerInterface $logger)
    {
        $this->jourTempoRepository = $jourTempoRepository;
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $today = date('Y-m-d');

        // On vérifie si apcu_entry existe
        if (function_exists('apcu_entry')) {
            // Cache 1h, mais expire au changement de date
            return apcu_entry($today, [$this, 'getStats'], 3600);
        } else {
            // Pas de cache, on calcule à chaque appel
            return $this->getStats($today);
        }
    }

    private function getStats($dateSQL): Statistiques
    {
        $this->logger->info("Calcul des statistiques pour $dateSQL");

        $stat = new Statistiques();
        $stat->periode = $this->dateService->getPeriodeOfDayAsString(new DateTimeImmutable($dateSQL));
        $stat->bissextile = $this->dateService->isPeriodeBissextile($stat->periode);

        // Nombres totaux de jours à placer sur la période
        $totalBleu = $stat->bissextile ? 301 : 300;
        $totalBlanc = 43;
        $totalRouge = 22;

        // Nombre de jours déjà placés sur la période, jusqu'à aujourd'hui inclus
        $stat->dernierJourInclus = date('Y-m-d');
        $stat->joursBleusConsommes = $this->jourTempoRepository->getNombreJoursBleusPlacesJusqua($stat->periode, $stat->dernierJourInclus);
        $stat->joursBlancsConsommes = $this->jourTempoRepository->getNombreJoursBlancsPlacesJusqua($stat->periode, $stat->dernierJourInclus);
        $stat->joursRougesConsommes = $this->jourTempoRepository->getNombreJoursRougesPlacesJusqua($stat->periode, $stat->dernierJourInclus);

        // Restants
        $stat->joursBleusRestants = $totalBleu - $stat->joursBleusConsommes;
        $stat->joursBlancsRestants = $totalBlanc - $stat->joursBlancsConsommes;
        $stat->joursRougesRestants = $totalRouge - $stat->joursRougesConsommes;

        return $stat;
    }
}
