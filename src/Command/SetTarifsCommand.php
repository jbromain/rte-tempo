<?php

namespace App\Command;

use App\Repository\TarificationRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Cette commande permet de saisir manuellement les 6 prix au Kwh de l'option Tempo.
 * Le tarif est alors marqué comme forcé, ce qui bloque les mises à jour automatiques
 * effectuées par la commande app:update-tarifs.
 */
#[AsCommand(
    name: 'app:set-tarifs',
    description: 'Saisie manuelle de la grille tarifaire, les mises à jour automatiques seront ensuite ignorées',
)]
class SetTarifsCommand extends Command
{

    /**
     * Libellés des tarifs à saisir, dans l'ordre, associés aux accesseurs de l'entité Tarification
     */
    private const TARIFS = [
        ['label' => 'Bleu heures creuses',  'getter' => 'getBleuHC',  'setter' => 'setBleuHC'],
        ['label' => 'Bleu heures pleines',  'getter' => 'getBleuHP',  'setter' => 'setBleuHP'],
        ['label' => 'Blanc heures creuses', 'getter' => 'getBlancHC', 'setter' => 'setBlancHC'],
        ['label' => 'Blanc heures pleines', 'getter' => 'getBlancHP', 'setter' => 'setBlancHP'],
        ['label' => 'Rouge heures creuses', 'getter' => 'getRougeHC', 'setter' => 'setRougeHC'],
        ['label' => 'Rouge heures pleines', 'getter' => 'getRougeHP', 'setter' => 'setRougeHP'],
    ];

    public function __construct(private TarificationRepository $tarificationRepository, private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void {}

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $localTarif = $this->tarificationRepository->findOneBy([]);
        if (! $localTarif) {
            $io->error('Aucune grille tarifaire en base. Lancez d\'abord la commande app:update-tarifs.');
            return Command::FAILURE;
        }

        $io->title('Saisie manuelle des tarifs TTC au kWh');
        $io->text('Laissez vide pour conserver la valeur actuelle.');

        $questionDate = new Question('Date de début des tarifs (format AAAA-MM-JJ)', $localTarif->getDateDebut());
        $questionDate->setValidator(function ($reponse) {
            $reponse = trim((string) $reponse);
            $date = DateTimeImmutable::createFromFormat('Y-m-d', $reponse);
            if ($date === false || $date->format('Y-m-d') !== $reponse) {
                throw new \RuntimeException('Merci de saisir une date valide au format AAAA-MM-JJ (par exemple 2025-08-01).');
            }

            return $date->format('Y-m-d');
        });

        $dateDebut = $io->askQuestion($questionDate);

        $valeurs = [];
        foreach (self::TARIFS as $tarif) {
            $valeurActuelle = $localTarif->{$tarif['getter']}();

            $question = new Question($tarif['label'], $valeurActuelle);
            $question->setValidator(function ($reponse) {
                $reponse = str_replace(',', '.', trim((string) $reponse));
                if (! is_numeric($reponse) || (float) $reponse <= 0) {
                    throw new \RuntimeException('Merci de saisir un prix au kWh valide (par exemple 0.1552).');
                }

                return (float) $reponse;
            });

            $valeurs[] = $io->askQuestion($question);
        }

        // Récapitulatif avant enregistrement
        $recap = [['Date de début', $localTarif->getDateDebut(), $dateDebut]];
        foreach (self::TARIFS as $index => $tarif) {
            $recap[] = [$tarif['label'], $localTarif->{$tarif['getter']}(), $valeurs[$index]];
        }
        $io->table(['Donnée', 'Valeur actuelle', 'Nouvelle valeur'], $recap);

        if (! $io->confirm('Confirmez-vous l\'enregistrement de ces tarifs ?')) {
            $io->warning('Aucune mise à jour effectuée.');
            return Command::SUCCESS;
        }

        $localTarif->setDateDebut($dateDebut);
        foreach (self::TARIFS as $index => $tarif) {
            $localTarif->{$tarif['setter']}($valeurs[$index]);
        }
        // Les tarifs ayant été saisis manuellement, on ignore désormais les données officielles
        $localTarif->setTarifForce(true);

        $this->em->flush();

        $io->success('Tarifs mis à jour et marqués comme forcés.');

        return Command::SUCCESS;
    }
}
