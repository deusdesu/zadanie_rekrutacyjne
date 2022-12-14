<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\UserCode;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home',methods: 'GET')]
    public function index(ManagerRegistry $doctrine): Response
    {
        //pobiera dane z  bazy
        $codes = $doctrine->getRepository(UserCode::class)->findBy([], ['date' => 'DESC']);
        return $this->render('home/index.html.twig', [
            'codes' => $codes,
        ]);
    }
}
