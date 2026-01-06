<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\PrixHoraires;
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
    ) {
    }

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

    private function getPrixHoraires(string $cacheKey): PrixHoraires
    {
        $this->logger->info("Calcul des prix horaires pour $cacheKey");

        $prixHoraires = new PrixHoraires();
        $tarif = $this->tarificationRepository->findOneBy([]);
        $now = new DateTime();
        $currentHour = (int) $now->format('G');

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

            // Si pas de jour Tempo ou couleur inconnue → null
            if ($jourTempo === null || $jourTempo->getCodeJour() === TARIF_INCONNU) {
                $prixHoraires->setHourData($n, null);
                continue;
            }

            // Utiliser TempsReelProvider pour calculer le tarif
            $tr = $this->tempsReelProvider->getTempsReelForDateTime($targetDateTime, $jourTempo, $tarif);

            $prixHoraires->setHourData($n, [
                'codeCouleur' => $tr->getCodeCouleur(),
                'codeHoraire' => $tr->getCodeHoraire(),
                'tarifKwh' => $tr->getTarifKwh(),
                'libTarif' => $tr->getLibTarif(),
            ]);
        }

        return $prixHoraires;
    }
}
