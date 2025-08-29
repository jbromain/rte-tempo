<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
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

        $dt = new DateTime($cacheKey);
        if((int) $dt->format('G') < 6) {
            // Avant 6h, on prend la veille
            $dt->modify('-1 day');
        }
        $dateTarif = $dt->format('Y-m-d');
        $jourTempo = $this->jourTempoRepository->findOneBy(['dateJour' => $dateTarif]);
        $tarif = $this->tarificationRepository->findOneBy([]);
        $isHP = ((int) $dt->format('G') >= 6) && ((int) $dt->format('G') < 22);

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
