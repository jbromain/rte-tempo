<?php

namespace App\Controller;

use App\Repository\JourTempoRepository;
use App\State\TempsReelProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(JourTempoRepository $jourTempoRepository, TempsReelProvider $tempsReelProvider): Response
    {
        $today = $jourTempoRepository->findOneBy(['dateJour' => date('Y-m-d')]);
        $tomorrow = $jourTempoRepository->findOneBy(['dateJour' => date('Y-m-d', strtotime('+1 day'))]);
        $now = $tempsReelProvider->provideSimple();
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'today' => $today,
            'tomorrow' => $tomorrow,
            'now' => $now,
        ]);
    }
}
