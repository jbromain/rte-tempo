<?php

namespace App\Command;

use App\Repository\JourTempoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:set-color',
    description: 'Force a color for a specific day',
)]
class SetColorCommand extends Command
{

    public function __construct(private JourTempoRepository $jourTempoRepository, private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('date', InputArgument::REQUIRED, 'Date au format MySQL')
            ->addArgument('color', InputArgument::REQUIRED, '0 inconnu, 1 bleu, 2 blanc, 3 rouge')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dateSql = $input->getArgument('date');
        $color = (int) $input->getArgument('color');

        $dataDay = $this->jourTempoRepository->findOneBy([
            'dateJour' => $dateSql
        ]);
        if (!$dataDay) {
            $io->error("Aucune donnée trouvée pour la date $dateSql, vérifiez le format de la date (SQL)");
            return Command::FAILURE;
        }
        if (! in_array($color, [0, 1, 2, 3])) {
            $io->error("Couleur invalide, utilisez 0, 1, 2 ou 3.");
            return Command::FAILURE;
        }

        $dataDay->setCodeJour($color);
        $this->em->flush();

        $io->success("Couleur mise à jour avec succès pour la date $dateSql.");
        return Command::SUCCESS;
    }
}
