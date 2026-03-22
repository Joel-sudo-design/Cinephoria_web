<?php

namespace App\Controller;

use App\Repository\CinemaRepository;
use App\Repository\FilmRepository;
use App\Repository\GenreRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FilmsController extends AbstractController
{
    #[Route('/films', name: 'app_films')]
    public function index(CinemaRepository $cinemaRepository, GenreRepository $genreRepository, FilmRepository $filmRepository): Response
    {   $films = $filmRepository->findAll();
        $cinemas = $cinemaRepository->findAll();
        $genres = $genreRepository->findAll();
        return $this->render('films/index.html.twig', [
            'controller_name' => 'FilmsController',
            'films' => $films,
            'cinemas' => $cinemas,
            'genres' => $genres
        ]);
    }
    #[Route('/films/loading', name: 'app_films_loading')]
    public function film(FilmRepository $filmRepository): Response
    {
        // Récupérer tous les films
        $films = $filmRepository->findAll();
        $AllFilmsArray = [];

        foreach ($films as $film) {
            // Convertir le film en tableau
            $filmArray = $film->toArray();

            // Ajouter l'image si disponible
            if ($film->getImageName() !== null) {
                $filmArray['image'] = $this->getParameter('films_images_directory') . '/image_film/' . $film->getImageName();
                $filmArray['image2'] = $this->getParameter('films_images_directory') . '/image_film/' . $film->getImageName();
            } else {
                $filmArray['image'] = $this->getParameter('films_images_directory') . '/image_film/' .'default-image.jpg';
                $filmArray['image2'] = $this->getParameter('films_images_directory') . '/image_film/' .'default-image2.jpg';
            }

            // Ajouter le genre si disponible
            if ($film->getGenre() !== null) {
                $filmArray['genre'] = $film->getGenre()->getName();
            } else {
                $filmArray['genre'] = 'Aucun';
            }

            // Ajouter le film au tableau final
            $AllFilmsArray[] = $filmArray;
        }

        return new JsonResponse($AllFilmsArray);
    }
    #[Route('/api/films', name: 'app_films_api')]
    public function filmApi(FilmRepository $filmRepository): Response
    {
        // Récupérer tous les films
        $films = $filmRepository->findAll();
        $AllFilmsArray = [];

        foreach ($films as $film) {
            // Convertir le film en tableau
            $filmArray = $film->toArray();

            // Ajouter l'image si disponible
            if ($film->getImageName() !== null) {
                $filmArray['image'] = $this->getParameter('films_images_directory') . '/image_film/' . $film->getImageName();
                $filmArray['image2'] = $this->getParameter('films_images_directory') . '/image_film/' . $film->getImageName();
            } else {
                $filmArray['image'] = $this->getParameter('films_images_directory') . '/image_film/' .'default-image.jpg';
                $filmArray['image2'] = $this->getParameter('films_images_directory') . '/image_film/' .'default-image2.jpg';
            }

            // Ajouter le genre si disponible
            if ($film->getGenre() !== null) {
                $filmArray['genre'] = $film->getGenre()->getName();
            } else {
                $filmArray['genre'] = 'Aucun';
            }

            // Ajouter le film au tableau final
            $AllFilmsArray[] = $filmArray;
        }

        return new JsonResponse($AllFilmsArray);
    }
    #[Route('/films/filter', name: 'app_films_loading_filter')]
    public function filterFilms(FilmRepository $filmRepository, CinemaRepository $cinemaRepository, GenreRepository $genreRepository, Request $request): Response
    {
        // Décoder les données envoyées dans la requête
        $data = json_decode($request->getContent(), true);

        // Initialisation du tableau des films
        $AllFilmsArray = [];

        // Traitement des filtres
        $cinemaId = $data['cinema'] ?? null;
        $genreId = $data['genre'] ?? null;
        $date = $data['date'] ?? null;

        // Effectuer la recherche basée sur les filtres
        $films = $filmRepository->findByFilters($cinemaId, $genreId, $date);

        // Traitement des films obtenus
        foreach ($films as $film) {
            $filmArray = $film->toArray();

            // Ajouter l'image principale et secondaire
            $imageName = $film->getImageName();
            $imagePath = $this->getParameter('films_images_directory') . '/image_film/';
            $filmArray['image'] = $imageName ? $imagePath . $imageName : $imagePath . 'default-image.jpg';
            $filmArray['image2'] = $imageName ? $imagePath . $imageName : $imagePath . 'default-image2.jpg';

            // Ajouter le genre
            $filmArray['genre'] = $film->getGenre() ? $film->getGenre()->getName() : 'Aucun';

            $AllFilmsArray[] = $filmArray;
        }

        // Retourner les films sous forme de JSON
        return new JsonResponse($AllFilmsArray);
    }
    #[Route('/films/seances', name: 'app_films_loading_seances')]
    public function seancesFilm(FilmRepository $filmRepository, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $filmId = $data['filmId'] ?? null;
        $cinemaId = $data['cinemaId'] ?? null;
        $structuredSeances = [];

        if ($filmId !== '') {
            $seances = $filmRepository->findOneBy(['id' => $filmId])->getSeance();

            foreach ($seances as $seance) {
                $seanceArray = $seance->toArray($cinemaId);

                // Regrouper les séances par date
                $date = $seanceArray['date'];
                if (!isset($structuredSeances[$date])) {
                    $structuredSeances[$date] = [
                        'date' => $date,
                        'seances' => []
                    ];
                }
                $structuredSeances[$date]['seances'][] = [
                    'id' => $seanceArray['id'],
                    'heureDebut' => $seanceArray['heure_debut_seance'],
                    'heureFin' => $seanceArray['heure_fin_seance'],
                    'format' => $seanceArray['qualite'],
                    'salle' => 'Salle ' . $seanceArray['salle'],
                    'tarif' => $seanceArray['price'],
                    'cinemaId' => $seanceArray['cinema_id']
                ];
            }
        }

        // Convertir l'objet associatif en un tableau indexé pour respecter la structure demandée
        $response = array_values($structuredSeances);

        return new JsonResponse($response);
    }
    #[Route('/utilisateur/films', name: 'app_films_user')]
    public function indexUser(CinemaRepository $cinemaRepository, GenreRepository $genreRepository, FilmRepository $filmRepository): Response
    {
        $films = $filmRepository->findAll();
        $cinemas = $cinemaRepository->findAll();
        $genres = $genreRepository->findAll();
        return $this->render('films/user.html.twig', [
            'films' => $films,
            'controller_name' => 'FilmsUserController',
            'cinemas' => $cinemas,
            'genres' => $genres
        ]);
    }
    #[Route('/employe/films', name: 'app_films_employe')]
    public function indexEmploye(CinemaRepository $cinemaRepository, GenreRepository $genreRepository, FilmRepository $filmRepository): Response
    {   $films = $filmRepository->findAll();
        $cinemas = $cinemaRepository->findAll();
        $genres = $genreRepository->findAll();
        return $this->render('films/employe.html.twig', [
            'controller_name' => 'FilmsEmployeController',
            'films' => $films,
            'cinemas' => $cinemas,
            'genres' => $genres
        ]);
    }
    #[Route('/administrateur/films', name: 'app_films_admin')]
    public function indexAdmin(CinemaRepository $cinemaRepository, GenreRepository $genreRepository, FilmRepository $filmRepository): Response
    {   $films = $filmRepository->findAll();
        $cinemas = $cinemaRepository->findAll();
        $genres = $genreRepository->findAll();
        return $this->render('films/admin.html.twig', [
            'controller_name' => 'FilmsAdminController',
            'films' => $films,
            'cinemas' => $cinemas,
            'genres' => $genres
        ]);
    }
}
