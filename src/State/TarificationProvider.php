<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Tarification;
use App\Repository\TarificationRepository;
use Psr\Log\LoggerInterface;

/**
 * Fournisseur de données pour les tarifs actuels.
 */
class TarificationProvider implements ProviderInterface
{

    public function __construct(private TarificationRepository $tarificationRepository, private LoggerInterface $logger)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $today = date('Y-m-d');
        $cacheKey = 'tarif-'.$today;

        // On vérifie si apcu_entry existe
        if (function_exists('apcu_entry')) {
            // Cache 1h, mais expire au changement de date
            return apcu_entry($cacheKey, [$this, 'getTarification'], 3600);
        } else {
            // Pas de cache, on calcule à chaque appel
            return $this->getTarification($cacheKey);
        }
    }

    /**
     * @param string $cacheKey tarif-YYYY-MM-DD
     */
    private function getTarification($cacheKey): Tarification
    {
        $dateSQL = substr($cacheKey, 6);
        $this->logger->info("Lecture BD pour tarification pour $dateSQL");

        // C'est un singleton
        return $this->tarificationRepository->findOneBy([]);
    }
}
