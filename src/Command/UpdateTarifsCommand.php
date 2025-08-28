<?php

namespace App\Command;

use App\Entity\Tarification;
use App\Repository\TarificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
    
    private const GOUV_URL = "https://tabular-api.data.gouv.fr/api/resources/0c3d1d36-c412-4620-8566-e5cbb4fa2b5a/data/?page_size=1&P_SOUSCRITE__exact=6&__id__sort=desc";

    public function __construct(private TarificationRepository $tarificationRepository, private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $ch = curl_init(self::GOUV_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $io->error('Erreur lors de la récupération des tarifs: ' . curl_error($ch));
            $data = null;
        } else {
            $data = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $io->error('Erreur de décodage JSON: ' . json_last_error_msg());
                $data = null;
            }
        }
        curl_close($ch);

        if(! $data){
            return Command::FAILURE;
        }

        if(! isset($data['data'], $data['data'][0])){
            $io->error('Aucune donnée tarifaire disponible.');
            return Command::FAILURE;
        }

        $tarifGouv = $data['data'][0];
        $idTarif = (int) $tarifGouv['__id'];
        if($tarifGouv['DATE_FIN'] !== null && $tarifGouv['DATE_FIN'] < date('Y-m-d')){
            $io->error('Le tarif avec l\'ID ' . $idTarif . ' est expiré.');
            return Command::FAILURE;
        }

        // On a un tarif officiel valable
        // Recherche du tarif local à mettre à jour
        $localTarif = $this->tarificationRepository->findOneBy([]);
        if(! $localTarif){
            // Création nouveau tarif (normalement, uniquement la première fois que cette commande est appelée)
            $localTarif = new Tarification();
            $this->em->persist($localTarif);
        }

        if($localTarif->isTarifForce() && $localTarif->getDataGouvId() == $idTarif){
            // Ce tarif gouvernemental a été modifié localement, on ne tient plus compte de ses données
            $io->warning('Le tarif avec l\'ID ' . $idTarif . ' a été modifié localement. Aucune mise à jour effectuée.');
            return Command::SUCCESS;
        }

        // Attention: la date debut dans les données source est au format AAAA-JJ-MM (inversion mois et année)
        // Bug signalé, mais en attendant on inverse nous-même
        // TODO traitement à retirer dès correction de la donnée source
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $tarifGouv['DATE_DEBUT'], $matches)) {
            // $matches[1]=année, [2]=jour, [3]=mois
            $tarifGouv['DATE_DEBUT'] = sprintf('%s-%s-%s', $matches[1], $matches[3], $matches[2]);
        }

        // Mise à jour du tarif et enregistrement
        $localTarif->setDataGouvId($idTarif);
        $localTarif->setDateDebut($tarifGouv['DATE_DEBUT']);
        $localTarif->setBleuHC((float) $tarifGouv['PART_VARIABLE_HCBleu_TTC']);
        $localTarif->setBleuHP((float) $tarifGouv['PART_VARIABLE_HPBleu_TTC']);
        $localTarif->setBlancHC((float) $tarifGouv['PART_VARIABLE_HCBlanc_TTC']);
        $localTarif->setBlancHP((float) $tarifGouv['PART_VARIABLE_HPBlanc_TTC']);
        $localTarif->setRougeHC((float) $tarifGouv['PART_VARIABLE_HCRouge_TTC']);
        $localTarif->setRougeHP((float) $tarifGouv['PART_VARIABLE_HPRouge_TTC']);

        $this->em->flush();

        $io->success('Tarifs mis à jour.');

        return Command::SUCCESS;
    }
}
