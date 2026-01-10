<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Repository\JourTempoRepository;
use App\Repository\TarificationRepository;
use DateTime;
use Psr\Log\LoggerInterface;

/**
 * Fournisseur de données pour les prix horaires sur 24h glissantes.
 */
class PrixHorairesProvider implements ProviderInterface
{
    public function __construct(
        private TempsReelProvider $tempsReelProvider,
        private JourTempoRepository $jourTempoRepository,
        private TarificationRepository $tarificationRepository,
        private LoggerInterface $logger
    ) {}

    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): object|array|null {
        // La donnée expire à chaque changement d'heure
        $cacheKey = 'prixHoraires-' . date('Y-m-d H') . ':00:00';

        // On vérifie si apcu_entry existe
        if (function_exists('apcu_entry')) {
            // Cache max 1h
            return apcu_entry($cacheKey, [$this, 'getPrixHoraires'], 3610);
        } else {
            // Pas de cache, on calcule à chaque appel
            return $this->getPrixHoraires($cacheKey);
        }
    }

    /**
     * @return TempsReel[]
     */
    private function getPrixHoraires(string $cacheKey): array
    {
        $this->logger->info("Calcul des prix horaires pour $cacheKey");

        $prixHoraires = [];
        $tarif = $this->tarificationRepository->findOneBy([]);

        // FIXME bug possible si appel pile à un changement d'heure
        // (il faudrait prendre l'heure de la clé de cache)
        $now = new DateTime();

        // Cache des jours Tempo pour éviter les requêtes multiples
        $joursTempoCache = [];

        for ($n = 0; $n < 24; $n++) {
            // Calculer la date/heure cible
            $targetDateTime = clone $now;
            $targetDateTime->modify("+$n hour");
            $targetHour = (int) $targetDateTime->format('G');

            // Calculer le jour Tempo (règle des 6h)
            $tempoDate = clone $targetDateTime;
            if ($targetHour < 6) {
                $tempoDate->modify('-1 day');
            }
            $tempoDateStr = $tempoDate->format('Y-m-d');

            // Récupérer le jour Tempo (avec cache local)
            if (!isset($joursTempoCache[$tempoDateStr])) {
                $joursTempoCache[$tempoDateStr] = $this->jourTempoRepository->findOneBy(['dateJour' => $tempoDateStr]);
            }
            $jourTempo = $joursTempoCache[$tempoDateStr];

            // Ici JourTempo n'est pas censé être null (ils sont initialisés à J-2)
            if ($jourTempo === null) {
                $this->logger->error("JourTempo manquant pour la date " . $tempoDateStr);
                break; // Tant pis s'il n'y a pas 24 entrées, mais on veut pas de trous
            }

            // Utiliser TempsReelProvider pour calculer le tarif
            $tr = $this->tempsReelProvider->getTempsReelForDateTime($targetDateTime, $jourTempo, $tarif);
            $tr->setApplicableIn($n);
            $prixHoraires[$n] = $tr;
        }

        return $prixHoraires;
    }
}
