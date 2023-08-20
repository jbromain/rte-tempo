<?php

namespace App\Command;

use App\Entity\JourTempo;
use App\Repository\JourTempoRepository;
use \DateTime;
use DateTimeImmutable;
use \DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * URL de la page web officielle du site RTE sur laquelle le calendrier Tempo est visible.
 */
define("RTE_WEBPAGE_URL", "https://www.services-rte.com/fr/visualisez-les-donnees-publiees-par-rte/calendrier-des-offres-de-fourniture-de-type-tempo.html");

/**
 * URL de la ressource JSON utilisée par la page officielle pour obtenir les données.
 * Le paramètre season est alimenté avec la période, sous la forme "2022-2023".
 * 
 */
define("RTE_API_URL", "https://www.services-rte.com/cms/open_data/v1/tempo?season=");

/**
 * Nb: cette commande n'utiise pas pour l'instant l'API officielle car elle semble souffir de nombreux bugs.
 * Documentation de l'API officielle: https://data.rte-france.com/catalog/-/api/consumption/Tempo-Like-Supply-Contract/v1.1
 * 
 * @author JB Romain jbromain25@gmail.com
 */

#[AsCommand(
    name: 'app:update-from-rte',
    description: 'Appelée en tâche cron 2 fois par jour, met à jour les données à partir de l\'API RTE. Ne nécessite aucun argument.',
)]
class UpdateFromRteCommand extends Command
{

    private JourTempoRepository $jourTempoRepository;
    private EntityManagerInterface $em;
    public function __construct(JourTempoRepository $jourTempoRepository, EntityManagerInterface $em)
    {
        parent::__construct();
        $this->jourTempoRepository = $jourTempoRepository;
        $this->em = $em;
    }

    protected function configure(): void
    {
        $this
            // configure an argument
            ->addArgument('periode', InputArgument::OPTIONAL, "Période au format AAAA-AAAA, ou laisser vide pour charger la période courante (cas général).", "");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // On crée les données de J, J+1 et J+2 si elles n'existent pas encore
        // (On veut que le calendrier soit dispo dès 0h01 pour J+1, même si couleur indéfinie)
        $today = new DateTimeImmutable('today');
        $tomorrow = new DateTimeImmutable('tomorrow');
        $afterTomorrow = new DateTimeImmutable('today +2 days');

        foreach ([$today, $tomorrow, $afterTomorrow] as $day) {
            $dataDay = $this->jourTempoRepository->findOneBy([
                'dateJour' => $day->format('Y-m-d')
            ]);
            if (!$dataDay) {
                $periodeDay = $this->getPeriodeOfDay($day);
                $dataDay = new JourTempo();
                $dataDay
                    ->setDateJour($day)
                    ->setCodeJour(0)
                    ->setPeriode($periodeDay[0] . '-' . $periodeDay[1]);
                $this->em->persist($dataDay);
            }
        }
        $this->em->flush();

        // Détermination de la période à interroger (ce qui nous intéresse, c'est demain)
        $periode = $this->getPeriodeOfDay(new DateTimeImmutable('tomorrow'));
        $libPeriode = $periode[0] . '-' . $periode[1];

        // Sauf si la période a été fournie en argument de la commande (cas particulier pour récupérer les anciennes données)
        $periodeParam = trim($input->getArgument('periode'));
        if ($periodeParam != '') {
            echo "Période forcée: " . $periodeParam . "\n";
            $libPeriode = $periodeParam;
        }

        // Interrogation du serveur RTE
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, RTE_API_URL . $libPeriode);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_ACCEPT_ENCODING, ''); // Curl remplira tous les encodages qu'il supporte

        // On se fait passer pour Firefox... mais on n'abuse pas: 2 requêtes / jour
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Accept: application/json, text/plain, */*",
            "Accept-Language: fr,fr-FR;q=0.8,en-US;q=0.5,en;q=0.3",
            "Cache-Control: no-cache",
            "Connection: keep-alive",
            "DNT: 1",
            "Host: www.services-rte.com",
            "Sec-Fetch-Dest: empty",
            "Sec-Fetch-Mode: cors",
            "Sec-Fetch-Site: same-origin",
            "Sec-Fetch-User: ?1",
            "Upgrade-Insecure-Requests: 1",
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/116.0",
            "Referer: " . RTE_WEBPAGE_URL,
            "Pragma: no-cache",
        ));


        $result = curl_exec($curl);
        if ($result === false) {
            // Erreur
            echo "CURL error: " . curl_error($curl) . "\n";
            return Command::FAILURE;
        }
        curl_close($curl);

        $json = json_decode($result, true);
        if ($json === null) {
            echo "JSON DECODE ERROR\n";
            return Command::FAILURE;
        }

        // On reçoit un tableau associatif dont la clé est la date
        foreach ($json['values'] as $dateSQL => $colorName) {
            // On ignore les clés qui ne sont pas au bon format
            if (!preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $dateSQL)) {
                // On ignore 
                continue;
            }

            // Création ou mise à jour de la donnée
            try {
                $day = new DateTimeImmutable($dateSQL);
            } catch (Exception $e) {
                echo "Format de date invalide, ignoré: " . $dateSQL . "\n";
                continue;
            }

            $dataDay = $this->jourTempoRepository->findOneBy([
                'dateJour' => $day->format('Y-m-d')
            ]);
            if (!$dataDay) {
                // Première fois que RTE nous transmet une info pour cette date
                $periodeDay = $this->getPeriodeOfDay($day);
                $dataDay = new JourTempo();
                $dataDay
                    ->setDateJour($day)
                    ->setPeriode($periodeDay[0] . '-' . $periodeDay[1]);
                $this->em->persist($dataDay);
            }
            $dataDay->setCodeJour($this->getCodeFromColorName($colorName));
        }


        // On enregistre en base
        $this->em->flush();

        echo "Opération terminée sans erreur.\n";

        return Command::SUCCESS;
    }

    /**
     * Retourne le code couleur numérique (de 0 à 3) à partir du libellé de couleur.
     * 
     * @param string $colorName BLUE, WHITE, RED ou autre
     * @return int 1, 2, 3 ou 0 respectivement
     */
    private function getCodeFromColorName(string $colorName): int
    {
        switch (strtolower($colorName)) {
            case 'blue':
                return 1;
            case 'white':
                return 2;
            case 'red':
                return 3;
            default:
                return 0;
        }
    }

    /**
     * Retourne la période Tempo à laquelle partient le jour fourni.
     * Le résultat est un tableau de deux entiers, par exemple [2023, 2024].
     * Les périodes Tempo vont du 1er septembre au 31 août de l'année suivante.
     * 
     * @param DateTimeInterface $day Jour dont on souhaitre connaitre la période
     * @return array Tableau au format [2023, 2024]
     */
    private function getPeriodeOfDay(DateTimeInterface $day): array
    {
        if ($day->format('m-d') <= '08-31') {
            // Du 1er janvier au 31 août inclus: Saison = N-1 / N
            return [
                ((int) $day->format('Y')) - 1,
                (int) $day->format('Y')
            ];
        } else {
            // 1er septembre au 31 décembre inclus: Saison = N / N+1
            return [
                (int) $day->format('Y'),
                ((int) $day->format('Y')) + 1
            ];
        }
    }
}
