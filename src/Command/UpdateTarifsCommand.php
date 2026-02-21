<?php

namespace App\Command;

use App\Entity\Tarification;
use App\Repository\TarificationRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Cette commande met à jour les prix au Kwh de l'option Tempo depuis l'API:
 * https://tabular-api.data.gouv.fr/api/resources/0c3d1d36-c412-4620-8566-e5cbb4fa2b5a/data/?page_size=1&P_SOUSCRITE__exact=6&__id__sort=desc
 * 
 * Cette API automatique récupère elle-même les données depuis la donnée ouverte CSV:
 * https://www.data.gouv.fr/datasets/historique-des-tarifs-reglementes-de-vente-delectricite-pour-les-consommateurs-residentiels/#/resources/0c3d1d36-c412-4620-8566-e5cbb4fa2b5a
 */
#[AsCommand(
    name: 'app:update-tarifs',
    description: 'Appelée en tâche cron une fois par jour, met à jour la grille tarifaire depuis Data.Gouv',
)]
class UpdateTarifsCommand extends Command
{

    /**
     * Gardée pour l'historique, format json, pas mis à jour régulièrement
     */
    private const GOUV_URL = "https://tabular-api.data.gouv.fr/api/resources/0c3d1d36-c412-4620-8566-e5cbb4fa2b5a/data/?page_size=1&P_SOUSCRITE__exact=6&__id__sort=desc";

    /**
     * Format CSV, utilisé depuis février 2026
     */
    private const RTE_URL = "https://www.cre.fr/fileadmin/Documents/Open_data/Marches_de_detail/Option_Tempo.csv";

    public function __construct(private TarificationRepository $tarificationRepository, private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void {}

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $ch = curl_init(self::RTE_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: */*',
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $io->error('Erreur lors de la récupération des tarifs: ' . curl_error($ch));
            $data = null;
        } else {
            $data = $this->getLastRowFromCSV($response);
        }
        curl_close($ch);

        if (! is_array($data) || count($data) != 17) {
            $io->error('Le fichier CSV a changé de format ou est vide. Aucune mise à jour effectuée.');
            return Command::FAILURE;
        }

        // On vérifie que la date de début est inférieure ou égale à aujourd'hui
        // (reçue au format JJ/MM/AAAA)
        $dateDebut = DateTimeImmutable::createFromFormat('d/m/Y', $data[0]);
        if ($dateDebut === false) {
            $io->error('Date de début du tarif au format inattendu: ' . $data[0] . '. Aucune mise à jour effectuée.');
            return Command::FAILURE;
        }
        if ($dateDebut > new DateTimeImmutable('today')) {
            $io->warning('Le tarif avec date de début ' . $dateDebut->format('Y-m-d') . ' n\'est pas encore applicable. Aucune mise à jour effectuée.');
            return Command::SUCCESS;
        }

        // On vérifie que la date de fin est vide ou dans le futur
        $dateFin = DateTimeImmutable::createFromFormat('d/m/Y', $data[1]);
        if ($dateFin !== false && $dateFin < new DateTimeImmutable('today')) {
            $io->warning('Le tarif avec date de fin ' . $dateFin->format('Y-m-d') . ' est expiré. Aucune mise à jour effectuée.');
            return Command::SUCCESS;
        }

        // On a un tarif officiel valable
        // Recherche du tarif local à mettre à jour
        $localTarif = $this->tarificationRepository->findOneBy([]);
        if (! $localTarif) {
            // Création nouveau tarif (normalement, uniquement la première fois que cette commande est appelée)
            $localTarif = new Tarification();
            $this->em->persist($localTarif);
        }

        if ($localTarif->isTarifForce()) {
            // Le tarif a été forcé localement, on ne tient plus compte des données officielles
            $io->warning('Le tarif a été modifié localement. Aucune mise à jour effectuée.');
            return Command::SUCCESS;
        }

        // Mise à jour du tarif et enregistrement
        $localTarif->setDataGouvId(0); // Plus dispo depuis qu'on a changé de source en février 2026
        $localTarif->setDateDebut($dateDebut->format('Y-m-d'));
        $localTarif->setBleuHC((float) str_replace(',', '.', $data[6]));
        $localTarif->setBleuHP((float) str_replace(',', '.', $data[8]));
        $localTarif->setBlancHC((float) str_replace(',', '.', $data[10]));
        $localTarif->setBlancHP((float) str_replace(',', '.', $data[12]));
        $localTarif->setRougeHC((float) str_replace(',', '.', $data[14]));
        $localTarif->setRougeHP((float) str_replace(',', '.', $data[16]));

        $this->em->flush();

        $io->success('Tarifs mis à jour.');

        return Command::SUCCESS;
    }

    /**
     * Retourne la dernière ligne d'une chaîne CSV, ou null si le CSV est vide ou invalide.
     * @param string $csvContent Le contenu CSV à analyser
     * @return array|null La dernière ligne du CSV sous forme de tableau, ou null
     */
    private function getLastRowFromCSV(string $csvContent): ?array
    {
        $lastRow = null;

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $csvContent);
        rewind($handle);

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $lastRow = $row;
        }

        fclose($handle);

        return $lastRow;
    }
}
