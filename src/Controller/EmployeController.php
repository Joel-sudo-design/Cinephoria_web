<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Cinema;
use App\Entity\Film;
use App\Entity\Genre;
use App\Entity\Salle;
use App\Entity\Seance;
use App\Repository\FilmRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
#[Route('/employe/administration')]
class EmployeController extends AbstractController
{
    #[Route('', name: 'app_employe')]
    public function index(): Response
    {
        return $this->render('employe/index.html.twig', [
            'controller_name' => 'EmployeController',
        ]);
    }
    #[Route('/film', name: 'app_employe_film')]
    public function Film(EntityManagerInterface $entityManager, FilmRepository $filmRepository): Response
    {
        // Récupérer tous les films
        $AllFilms = $filmRepository->findAll();
        $AllFilmsArray = [];

        // Parcourir les films
        foreach ($AllFilms as $film) {
            // Convertir le film en tableau
            $filmArray = $film->toArrayEmploye();

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

            // Récupérer et ajouter les cinémas
            $cinemasArray = [];
            foreach ($film->getCinema() as $cinema) {
                $cinemasArray[] = $cinema->getName();
            }
            if (!empty($cinemasArray)) {
                $filmArray['cinema'] = $cinemasArray;
            } else {
                $filmArray['cinema'] = 'Aucun';
            }

            // Ajouter les dates de début et de fin si disponibles
            if ($film->getDateDebut() !== null) {
                $filmArray['date_debut'] = $film->getDateDebut()->format('d/m/Y');
            }
            if ($film->getDateFin() !== null) {
                $filmArray['date_fin'] = $film->getDateFin()->format('d/m/Y');
            }

            // Récupérer les séances sur 1 jour
            $date_debut = $film->getDateDebut();
            $film_id = $film->getId();
            $seances = $entityManager->getRepository(Seance::class)->findByFilmIdByDate($film_id, $date_debut);

            // Ajouter les séances pour 1 jour
            $seancesArray = [];
            foreach ($seances as $Seance) {
                $seancesArray[] = $Seance->toArraySeance();
            }
            $filmArray['seances'] = $seancesArray ?: [];

            // Récupérer les séances totales
            $seancesTotal = $film->getSeance();
            $reservations = [];
            foreach ($seancesTotal as $seance) {
                $reservations[] = $seance->toArrayReservation();
            }

            // Regrouper les réservations par date
            $reservationsByDate = [];

            foreach ($reservations as $reservation) {
                $date = $reservation['date'];

                // Initialiser le cumul pour cette date si elle n'existe pas encore
                if (!isset($reservationsByDate[$date])) {
                    $reservationsByDate[$date] = 0;
                }

                // Ajouter le nombre de réservations
                $reservationsByDate[$date] += $reservation['reservation'];
            }
            $filmArray['reservations'] = $reservationsByDate;

            // Ajouter les avis
            $avis = $film->getAvis();
            if (!empty($avis)) {
                $avisArray = [];
                foreach ($avis as $avi) {
                    $avisArray[] = $avi->toArray();
                }
                $filmArray['avis'] = $avisArray;
            }

            // Ajouter le film au tableau final
            $AllFilmsArray[] = $filmArray;
        }

        // Récupérer les salles
        $salles = $entityManager->getRepository(Salle::class)->findAll();
        $sallesArray = [];
        foreach ($salles as $salle) {
            $sallesArray[] = $salle->toArray();
        }

        // Retourner deux JSON distincts
        return new JsonResponse([
            'films' => $AllFilmsArray,
            'salles' => $sallesArray
        ]);
    }
    #[Route('/film/create', name: 'app_employe_creation_film')]
    public function CreateFilm(EntityManagerInterface $entityManager): Response
    {
        $film = new Film();
        $entityManager->persist($film);
        $entityManager->flush();
        return new JsonResponse(['status' => 'film created']);
    }
    #[Route('/film/delete', name: 'app_employe_delete_film')]
    public function FilmDelete(Request $request,EntityManagerInterface $entityManager): Response
    {
        $data = json_decode($request->getContent(), true);
        $Id = $data['id'];
        $film = $entityManager->getRepository(Film::class)->find($Id);
        $seances = $film->getSeance();
        if ($seances != null) {
            foreach ($seances as $seance) {
                $reservation = $seance->getReservation();
                foreach ($reservation as $res) {
                    $entityManager->remove($res);
                }
                $entityManager->remove($seance);
            }
        }
        $entityManager->remove($film);
        $entityManager->flush();
        return new JsonResponse(['status' => 'film deleted']);
    }
    #[Route('/film/validate', name: 'app_employe_validate_film')]
    public function ValidateFilm(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Récupérer les données du formulaire
        $data = $request->request->all();

        // Initialiser un tableau pour collecter les erreurs des séances
        $seanceErrors = [];

        // Vérifier si l'ID du film est fourni
        $id = $data['id'] ?? null;
        if (!$id) {
            return $this->json(['error' => 'L\'ID du film est requis.'], 400);
        }

        // Récupérer le film
        $film = $entityManager->getRepository(Film::class)->find($id);
        if (!$film) {
            return $this->json(['error' => 'Film non trouvé.'], 404);
        }

        // Gérer l'upload de l'image si elle est fournie
        $image = $request->files->get('image');
        if ($image) {
            $film->setImageFile($image);
        }

        // Mettre à jour le nom du film
        $name = trim($data['nom'] ?? '');
        if ($name && $name !== 'Titre du film') {
            $film->setName($name);
        }

        // Mettre à jour le genre
        $stringGenre = trim($data['genre'] ?? '');
        if ($stringGenre !== '') {
            $genre = $entityManager->getRepository(Genre::class)->findOneBy(['name' => $stringGenre]);
            if (!$genre) {
                return $this->json(['error' => "Genre '{$stringGenre}' non trouvé."], 404);
            }
            $film->setGenre($genre);
        }

        // Mettre à jour l'âge minimum
        $age = $data['age'] ?? null;
        if (is_numeric($age)) {
            $film->setAgeMinimum((int)$age);
        }

        // Mettre à jour le label
        $label = trim($data['label'] ?? '');
        if ($label !== '') {
            $film->setLabel($label);
        }

        // Mettre à jour la description
        $description = trim($data['description'] ?? '');
        if ($description && $description !== 'Description du film') {
            $film->setDescription($description);
        }

        // Mettre à jour les dates de début et de fin
        $stringDateDebut = trim($data['date_debut'] ?? '');
        $stringDateFin = trim($data['date_fin'] ?? '');
        $dateDebut = $stringDateDebut ? \DateTime::createFromFormat('Y-m-d', $stringDateDebut) : null;
        $dateFin = $stringDateFin ? \DateTime::createFromFormat('Y-m-d', $stringDateFin) : null;

        if ($dateDebut) {
            $film->setDateDebut($dateDebut);
        }
        if ($dateFin) {
            $film->setDateFin($dateFin);
        }

        // Mettre à jour les cinémas
        $arrayCinema = $data['cinema'] ?? [];
        if (!is_array($arrayCinema)) {
            return $this->json(['error' => 'Les cinémas doivent être une liste.'], 400);
        }

        // Vérifier que des cinémas sont sélectionnés
        if (empty($arrayCinema)) {
            return $this->json(['error' => 'Aucun cinéma sélectionné.'], 400);
        }

        // Précharger les cinémas pour éviter les requêtes répétées
        $cinemas = $entityManager->getRepository(Cinema::class)->findBy(['name' => $arrayCinema]);
        $cinemaMap = [];
        foreach ($cinemas as $cinema) {
            $cinemaMap[$cinema->getName()] = $cinema;
        }

        foreach ($arrayCinema as $cinemaName) {
            if (isset($cinemaMap[$cinemaName])) {
                $film->addCinema($cinemaMap[$cinemaName]);
            } else {
                // Ajouter une erreur pour les cinémas non trouvés
                $seanceErrors[] = "Cinéma '{$cinemaName}' non trouvé.";
            }
        }

        // Mettre à jour les séances
        $formats = ['3DX', '4DX', 'IMAX', 'Dolby'];
        $nombreSalles = 4;

        // Précharger toutes les salles nécessaires
        $salles = $entityManager->getRepository(Salle::class)->findBy(['qualite' => $formats]);
        $salleMap = [];
        foreach ($salles as $salle) {
            $salleMap[$salle->getQualite()] = $salle;
        }

        // Commencer une transaction pour assurer l'intégrité des données
        $entityManager->beginTransaction();

        try {
            foreach ($formats as $format) {
                for ($i = 1; $i <= $nombreSalles; $i++) {
                    // Construire les clés dynamiques
                    $heureDebutKey = "heure_debut_{$format}_{$i}";
                    $heureFinKey = "heure_fin_{$format}_{$i}";
                    $priceKey = "price_{$format}_{$i}";

                    // Vérifier si toutes les données nécessaires sont présentes
                    if (isset($data[$heureDebutKey], $data[$heureFinKey], $data[$priceKey])) {
                        $HeureDebut = trim($data[$heureDebutKey]);
                        $HeureFin = trim($data[$heureFinKey]);
                        $price = trim($data[$priceKey]);

                        // Vérifier que les heures et le prix ne sont pas vides
                        if ($HeureDebut === '' || $HeureFin === '' || $price === '') {
                            // Ignorer cette séance et passer à la suivante
                            continue;
                        }

                        // Vérifier si la salle existe
                        if (!isset($salleMap[$format])) {
                            $seanceErrors[] = "Salle non trouvée pour la qualité : {$format}.";
                            continue;
                        }

                        $salle = $salleMap[$format];

                        // Convertir les heures en objets DateTime
                        $heureDebutObj = \DateTime::createFromFormat('H:i', $HeureDebut);
                        $heureFinObj = \DateTime::createFromFormat('H:i', $HeureFin);

                        if (!$heureDebutObj || !$heureFinObj) {
                            $seanceErrors[] = "Format d'heure invalide pour le format {$format}, salle {$i}. Veuillez utiliser le format HH:MM.";
                            continue;
                        }

                        // Validation supplémentaire : Heure de fin doit être après l'heure de début
                        if ($heureFinObj <= $heureDebutObj) {
                            $seanceErrors[] = "L'heure de fin doit être après l'heure de début pour le format {$format}, salle {$i}.";
                            continue;
                        }

                        // Créer la séance
                        try {
                            $this->CreateSeance($heureDebutObj, $heureFinObj, $price, $dateDebut, $dateFin, $salle, $film, $entityManager, $cinemas);
                        } catch (\Exception $e) {
                            $seanceErrors[] = "Erreur lors de la création de la séance pour le cinéma {$cinema->getName()}, format {$format}, salle {$i} : " . $e->getMessage();
                        }
                    }
                }
            }

            // Si des erreurs sont collectées, les renvoyer et annuler la transaction
            if (!empty($seanceErrors)) {
                throw new \Exception(implode(' ', $seanceErrors));
            }

            // Enregistrer toutes les modifications en une seule fois
            $entityManager->flush();
            $entityManager->commit();

        } catch (\Exception $e) {
            $entityManager->rollback();
            return $this->json(['error' => $e->getMessage()], 400);
        }

        // Enregistrer les modifications du film et des cinémas
        try {
            $entityManager->persist($film);
            $entityManager->flush();
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la mise à jour du film.'], 500);
        }

        return new JsonResponse(['status' => 'Film et séances mis à jour avec succès.'], 200);
    }
    public function CreateSeance(\DateTime $heureDebut, \DateTime $heureFin, string $price, \DateTime $dateDebut, \DateTime $dateFin, ?Salle $salle, ?Film $film, EntityManagerInterface $entityManager, Array $arrayCinema): void
    {
        $dateSeance = clone $dateDebut;
        while ($dateSeance <= $dateFin) {
            $seance = new Seance();
            $seance->setDate(clone $dateSeance);
            $seance->setHeureDebut(clone $heureDebut);
            $seance->setHeureFin(clone $heureFin);
            $seance->setPrice($price);
            $seance->setSalle($salle);
            $seance->setFilm($film);
            foreach ($arrayCinema as $cinemas) {
                $seance->addCinema($cinemas);
            }
            $entityManager->persist($seance);
            $dateSeance->modify('+1 day');
        }
    }
    #[Route('/film/reset', name: 'app_employe_reset_film')]
    public function ResetFilm(Request $request,EntityManagerInterface $entityManager): Response
    {
        $data = json_decode($request->getContent(), true);
        $id = $data['id'];
        $film = $entityManager->getRepository(Film::class)->find($id);
        $seances = $film->getSeance();
        $cinemas = $film->getCinema();
        foreach ($seances as $seance) {
            $reservation = $seance->getReservation();
            foreach ($reservation as $res) {
                $entityManager->remove($res);
            }
            $entityManager->remove($seance);
        }
        foreach ($cinemas as $cinema) {
            $film->removeCinema($cinema);
        }
        $film->setDateDebut(null);
        $film->setDateFin(null);
        $entityManager->persist($film);
        $entityManager->flush();
        return new JsonResponse(['status' => 'champs reset']);
    }
    #[Route('/avis', name: 'app_employe_avis')]
    public function avis(FilmRepository $filmRepository): Response
    {
        $films = $filmRepository->findAll();
        return $this->render('employe/avis.html.twig', [
            'films' => $films,
        ]);
    }
    #[Route('/avis/validate', name: 'app_employe_validation_avis')]
    public function validationAvis(Request $request,EntityManagerInterface $entityManager): Response
    {
        $data = json_decode($request->getContent(), true);
        $id = $data['id'];
        if ($id != null) {
            $avis = $entityManager->getRepository(Avis::class)->find($id);
            $avis->setValidate(true);
            $entityManager->persist($avis);
            $entityManager->flush();
        }
        return new JsonResponse(['status' => 'avis validé']);
    }
    #[Route('/avis/delete', name: 'app_employe_delete_avis')]
    public function deleteAvis(Request $request,EntityManagerInterface $entityManager): Response
    {
        $data = json_decode($request->getContent(), true);
        $id = $data['id'];
        if ($id != null) {
            $avis = $entityManager->getRepository(Avis::class)->find($id);
            $entityManager->remove($avis);
            $entityManager->flush();
        }
        return new JsonResponse(['status' => 'avis supprimé']);
    }
}
