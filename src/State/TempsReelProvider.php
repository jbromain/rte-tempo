<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\JourTempo;
use App\Entity\Tarification;
use App\Entity\TempsReel;
use App\Repository\JourTempoRepository;
use App\Repository\TarificationRepository;
use App\Service\DateService;
use DateTime;
use Psr\Log\LoggerInterface;

/**
 * Fournisseur de données pour les informations tarifaires en temps réel.
 */
class TempsReelProvider implements ProviderInterface
{

    public function __construct(private JourTempoRepository $jourTempoRepository, private TarificationRepository $tarificationRepository, private DateService $dateService, private LoggerInterface $logger)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        return $this->provideSimple();
    }

    public function provideSimple(): TempsReel
    {
        // La donnée expire à chaque changement d'heure
        $cacheKey = date('Y-m-d H').':00:00';

        // On vérifie si apcu_entry existe
        if (function_exists('apcu_entry')) {
            // Cache max 1h
            return apcu_entry($cacheKey, [$this, 'getTempsReel'], 3610);
        } else {
            // Pas de cache, on calcule à chaque appel
            return $this->getTempsReel($cacheKey);
        }
    }

    private function getTempsReel($cacheKey): TempsReel
    {
        $this->logger->info("Calcul du tarif temps réel pour $cacheKey");
        return $this->getTempsReelForDateTime(new DateTime($cacheKey));
    }

    /**
     * Calcule le tarif temps réel pour une date/heure donnée.
     * Peut être utilisé pour calculer des tarifs futurs.
     * 
     * @param DateTime $dt Date/heure cible
     * @param ?JourTempo $jourTempo Si déjà récupéré (optimisation)
     * @param ?Tarification $tarif Si déjà récupéré (optimisation)
     */
    public function getTempsReelForDateTime(DateTime $dt, ?JourTempo $jourTempo = null, ?Tarification $tarif = null): TempsReel
    {
        $hour = (int) $dt->format('G');

        // Règle Tempo : avant 6h, on prend la veille
        $tempoDate = clone $dt;
        if($hour < 6) {
            $tempoDate->modify('-1 day');
        }

        // Récupérer le jour Tempo si non fourni
        if($jourTempo === null) {
            $jourTempo = $this->jourTempoRepository->findOneBy(['dateJour' => $tempoDate->format('Y-m-d')]);
        }

        // Récupérer le tarif si non fourni
        if($tarif === null) {
            $tarif = $this->tarificationRepository->findOneBy([]);
        }

        $isHP = ($hour >= 6) && ($hour < 22);

        $tr = new TempsReel();
        $tr->setCodeHoraire($isHP?1:0);
        $tr->setCodeCouleur($jourTempo?$jourTempo->getCodeJour():TARIF_INCONNU);

        if($tarif != null && $tr->getCodeCouleur() != TARIF_INCONNU) {
            // On a un tarif et une couleur connue
            switch($tr->getCodeCouleur()) {
                case TARIF_BLEU:
                    $tr->setTarifKwh($isHP?$tarif->getBleuHP():$tarif->getBleuHC());
                    break;
                case TARIF_BLANC:
                    $tr->setTarifKwh($isHP?$tarif->getBlancHP():$tarif->getBlancHC());
                    break;
                case TARIF_ROUGE:
                    $tr->setTarifKwh($isHP?$tarif->getRougeHP():$tarif->getRougeHC());
                    break;
            }
        }

        // Calcul du libellé tarifaire
        switch($tr->getCodeCouleur()) {
            case TARIF_BLEU:
                $tr->setLibTarif($isHP?"Bleu-HP":"Bleu-HC");
                break;
            case TARIF_BLANC:
                $tr->setLibTarif($isHP?"Blanc-HP":"Blanc-HC");
                break;
            case TARIF_ROUGE:
                $tr->setLibTarif($isHP?"Rouge-HP":"Rouge-HC");
                break;
            default:
                $tr->setLibTarif($isHP?"Inconnu-HP":"Inconnu-HC");
        }

        return $tr;
    }
}
