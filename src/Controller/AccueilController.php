<?php

namespace App\Controller;

use App\Repository\FilmRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AccueilController extends AbstractController
{
    #[Route('/accueil', name: 'app_accueil')]
    public function index(EntityManagerInterface $entityManager, FilmRepository $filmRepository): Response
    {
        if ($this->getUser()) {
            $roles = $this->getUser()->getRoles();

            if (in_array('ROLE_ADMIN', $roles)) {
                return $this->redirectToRoute('app_accueil_admin');
            } elseif (in_array('ROLE_EMPLOYE', $roles)) {
                return $this->redirectToRoute('app_accueil_employe');
            } elseif (in_array('ROLE_USER', $roles)) {
                return $this->redirectToRoute('app_accueil_user');
            }
        }
        $filmsArray = $this->getFilmsArray($filmRepository);

        return $this->render('accueil/index.html.twig', [
            'controller_name' => 'AccueilController',
            'films' => $filmsArray,
        ]);
    }
    #[Route('/utilisateur/accueil', name: 'app_accueil_user')]
    public function indexUser(EntityManagerInterface $entityManager, FilmRepository $filmRepository): Response
    {
        $filmsArray = $this->getFilmsArray($filmRepository);

        return $this->render('accueil/user.html.twig', [
            'controller_name' => 'AccueilUserController',
            'films' => $filmsArray,
        ]);
    }
    #[Route('/employe/accueil', name: 'app_accueil_employe')]
    public function indexEmploye(EntityManagerInterface $entityManager, FilmRepository $filmRepository): Response
    {
        $filmsArray = $this->getFilmsArray($filmRepository);

        return $this->render('accueil/employe.html.twig', [
            'controller_name' => 'AccueilEmployeController',
            'films' => $filmsArray,
        ]);
    }
    #[Route('/administrateur/accueil', name: 'app_accueil_admin')]
    public function indexAdmin(EntityManagerInterface $entityManager, FilmRepository $filmRepository): Response
    {
        $filmsArray = $this->getFilmsArray($filmRepository);

        return $this->render('accueil/admin.html.twig', [
            'controller_name' => 'AccueilAdminController',
            'films' => $filmsArray,
        ]);
    }
    // Fonction pour calculer le dernier mercredi
    private function getLastWednesday(): \DateTime
    {
        $currentDate = new \DateTime();

        // Trouver le dernier mercredi (le jour 3 de la semaine)
        while ($currentDate->format('N') != 3) {
            $currentDate->modify('-1 day');
        }

        return $currentDate;
    }
    // Méthode privée pour récupérer les films sous forme de tableau
    private function getFilmsArray(FilmRepository $filmRepository): array
    {
        $lastWednesday = $this->getLastWednesday();
        $films = $filmRepository->findFilmsWithLastWednesdayBetweenDates($lastWednesday);

        $filmsArray = [];
        foreach ($films as $film) {
            $filmsArray[] = [
                'image' => $this->getParameter('films_images_directory') . '/image_film/' . $film->getImageName(),
                'name' => $film->getName(),
            ];
        }

        return $filmsArray;
    }

}
