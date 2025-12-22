<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

class CommandesController extends AbstractController
{
    #[Route('/utilisateur/mon_espace/commandes', name: 'app_commandes_user')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $reservations = $user->getReservations();
        $reservationArray = [];

        foreach ($reservations as $reservation) {
            $seance = [];
            $cinemaId = $reservation->getCinema()->getId();
            $cinema = $reservation->toArray();
            $seance['seance'] = $reservation->getSeance()->toArray($cinemaId);

            // Récupérer les heures de début et de fin
            $heureDebut =  $reservation->getSeance()->getHeureDebut();
            $heureFin = $reservation->getSeance()->getHeureFin();

            if ($heureDebut && $heureFin) {
                $diff = $heureDebut->diff($heureFin);

                if ($diff->i === 0) {
                    // Si les minutes sont 0, afficher uniquement les heures
                    $duree = sprintf('%dh', $diff->h);
                } else {
                    // Sinon, afficher heures et minutes
                    $duree = sprintf('%dh %dm', $diff->h, $diff->i);
                }
            } else {
                $duree = null; // En cas de données incorrectes
            }

            $seance['seance']['duree'] = $duree;
            $seance['seance']['cinema'] = $cinema['cinema'];
            $seance['reservation_id'] = $reservation->getId();
            $seance['seance']['sieges_reserves'] = $reservation->getSiege();
            $seance['seance']['qrCode'] = $reservation->getQrCode();
            $seance['film'] = $reservation->getSeance()->getFilm()->toArray();
            $imageName = $reservation->getSeance()->getFilm()->getImageName();
            $seance['film']['image'] = $imageName
                ? $this->getParameter('films_images_directory') . '/image_film/' . $imageName
                : null;
            $genre= $reservation->getSeance()?->getFilm()?->getGenre();
            if ($genre) {
                $genreName = $genre->getName();
            } else {
                $genreName = 'Aucun';
            }
            $seance['film']['genre'] = $genreName;
            $Allavis = $reservation->getSeance()->getFilm()->getAvis()->toArray();
            $seance['seance']['avis'] = [];
            $seance['seance']['notation'] = [];
            foreach ($Allavis as $avis) {
                if ($avis->getUser()->getId() == $user->getId()) {
                    $seance['seance']['avis'] = $avis->getDescription();
                    $seance['seance']['notation'] = $avis->getNotation();
                    break;
                }
            }
            $reservationArray[] = $seance;
        }

        return $this->render('commandes/index.html.twig', [
            'controller_name' => 'CommandesUserController',
            'reservations' => $reservationArray,
        ]);

    }
    #[Route('/employe/mon_espace/commandes', name: 'app_commandes_employe')]
    public function indexAdmin(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $reservations = $user->getReservations();
        $reservationArray = [];

        foreach ($reservations as $reservation) {
            $seance = [];
            $cinemaId = $reservation->getCinema()->getId();
            $cinema = $reservation->toArray();
            $seance['seance'] = $reservation->getSeance()->toArray($cinemaId);

            // Récupérer les heures de début et de fin
            $heureDebut =  $reservation->getSeance()->getHeureDebut();
            $heureFin = $reservation->getSeance()->getHeureFin();

            if ($heureDebut && $heureFin) {
                $diff = $heureDebut->diff($heureFin);

                if ($diff->i === 0) {
                    // Si les minutes sont 0, afficher uniquement les heures
                    $duree = sprintf('%dh', $diff->h);
                } else {
                    // Sinon, afficher heures et minutes
                    $duree = sprintf('%dh %dm', $diff->h, $diff->i);
                }
            } else {
                $duree = null; // En cas de données incorrectes
            }

            $seance['seance']['duree'] = $duree;
            $seance['seance']['cinema'] = $cinema['cinema'];
            $seance['reservation_id'] = $reservation->getId();
            $seance['seance']['sieges_reserves'] = $reservation->getSiege();
            $seance['seance']['qrCode'] = $reservation->getQrCode();
            $seance['film'] = $reservation->getSeance()->getFilm()->toArray();
            $imageName = $reservation->getSeance()->getFilm()->getImageName();
            $seance['film']['image'] = $imageName
                ? $this->getParameter('films_images_directory') . '/image_film/' . $imageName
                : null;
            $genreName = $reservation->getSeance()->getFilm()->getGenre()->getName();
            $seance['film']['genre'] = $genreName;
            $Allavis = $reservation->getSeance()->getFilm()->getAvis()->toArray();
            $seance['seance']['avis'] = [];
            $seance['seance']['notation'] = [];
            foreach ($Allavis as $avis) {
                if ($avis->getUser()->getId() == $user->getId()) {
                    $seance['seance']['avis'] = $avis->getDescription();
                    $seance['seance']['notation'] = $avis->getNotation();
                    break;
                }
            }
            $reservationArray[] = $seance;
        }

        return $this->render('commandes/employe.html.twig', [
            'controller_name' => 'CommandesEmployeController',
            'reservations' => $reservationArray,
        ]);

    }
    #[Route('/administrateur/mon_espace/commandes', name: 'app_commandes_admin')]
    public function indexEmploye(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $reservations = $user->getReservations();
        $reservationArray = [];

        foreach ($reservations as $reservation) {
            $seance = [];
            $cinemaId = $reservation->getCinema()->getId();
            $cinema = $reservation->toArray();
            $seance['seance'] = $reservation->getSeance()->toArray($cinemaId);

            // Récupérer les heures de début et de fin
            $heureDebut =  $reservation->getSeance()->getHeureDebut();
            $heureFin = $reservation->getSeance()->getHeureFin();

            if ($heureDebut && $heureFin) {
                $diff = $heureDebut->diff($heureFin);

                if ($diff->i === 0) {
                    // Si les minutes sont 0, afficher uniquement les heures
                    $duree = sprintf('%dh', $diff->h);
                } else {
                    // Sinon, afficher heures et minutes
                    $duree = sprintf('%dh %dm', $diff->h, $diff->i);
                }
            } else {
                $duree = null; // En cas de données incorrectes
            }

            $seance['seance']['duree'] = $duree;
            $seance['seance']['cinema'] = $cinema['cinema'];
            $seance['reservation_id'] = $reservation->getId();
            $seance['seance']['sieges_reserves'] = $reservation->getSiege();
            $seance['seance']['qrCode'] = $reservation->getQrCode();
            $seance['film'] = $reservation->getSeance()->getFilm()->toArray();
            $imageName = $reservation->getSeance()->getFilm()->getImageName();
            $seance['film']['image'] = $imageName
                ? $this->getParameter('films_images_directory') . '/image_film/' . $imageName
                : null;
            $genreName = $reservation->getSeance()->getFilm()->getGenre()->getName();
            $seance['film']['genre'] = $genreName;
            $Allavis = $reservation->getSeance()->getFilm()->getAvis()->toArray();
            $seance['seance']['avis'] = [];
            $seance['seance']['notation'] = [];
            foreach ($Allavis as $avis) {
                if ($avis->getUser()->getId() == $user->getId()) {
                    $seance['seance']['avis'] = $avis->getDescription();
                    $seance['seance']['notation'] = $avis->getNotation();
                    break;
                }
            }
            $reservationArray[] = $seance;
        }

        return $this->render('commandes/admin.html.twig', [
            'controller_name' => 'CommandesAdminController',
            'reservations' => $reservationArray,
        ]);

    }
    #[Route('/utilisateur/mon_espace/commandes/notation', name: 'app_commandes_user_notation')]
    public function notation(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validation des données d'entrée
        if (empty($data['reservation_id']) || !isset($data['rating'])) {
            return new JsonResponse(['success' => false, 'error' => 'Invalid data'], Response::HTTP_BAD_REQUEST);
        }

        $reservationId = $data['reservation_id'];
        $comment = $data['comment'] ?? '';
        $rating = (int) $data['rating'];

        // Récupération de la réservation
        $reservation = $entityManager->getRepository(Reservation::class)->find($reservationId);
        if (!$reservation) {
            return new JsonResponse(['success' => false, 'error' => 'Reservation not found'], Response::HTTP_NOT_FOUND);
        }

        // Récupération du film associé
        $film = $reservation->getSeance()->getFilm();
        if (!$film) {
            return new JsonResponse(['success' => false, 'error' => 'Film not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $this->getUser();

        // Création de l'avis
        $avis = new Avis();
        $avis->setDescription($comment);
        $avis->setNotation($rating);
        $user->addAvis($avis);
        $film->addAvis($avis);

        $entityManager->persist($avis);
        $entityManager->flush();

        return new JsonResponse(['success' => true, 'avis' => $comment, 'notation' => $rating], Response::HTTP_OK);
    }
    #[Route(path: 'api/commande', name: 'api_commande')]
    public function apiCommandes(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedException('Utilisateur non authentifié.');
        }

        $reservations = $user->getReservations();
        $reservationArray = [];

        foreach ($reservations as $reservation) {
            $cinemaId = $reservation->getCinema()->getId();
            $cinema = $reservation->toArray();
            $seanceArray = $reservation->getSeance()->toArray($cinemaId);
            $filmArray = $reservation->getSeance()->getFilm()->toArray();
            $seance['film'] = $filmArray['name'];
            $imageName = $reservation->getSeance()->getFilm()->getImageName();
            $seance['image'] = $imageName
                ? $this->getParameter('films_images_directory') . '/image_film/' . $imageName
                : null;
            $seance['date'] = $seanceArray['date'];
            $seance['heure_debut'] = $seanceArray['heure_debut_seance'];
            $seance['heure_fin'] = $seanceArray['heure_fin_seance'];
            $seance['cinema'] = $cinema['cinema'];
            $seance['salle'] = $seanceArray['salle'];
            $seance['sieges_reserves'] = $reservation->getSiege();
            $seance['qrCode'] = $reservation->getQrCode();
            $reservationArray[] = $seance;
        }

        return new JsonResponse($reservationArray);
    }
}
