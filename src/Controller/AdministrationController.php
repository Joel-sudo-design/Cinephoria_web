<?php

namespace App\Controller;

use App\Document\ReservationMongo;
use App\Entity\Cinema;
use App\Entity\Film;
use App\Entity\Genre;
use App\Entity\Salle;
use App\Entity\Seance;
use App\Entity\User;
use App\Form\AccountEmployeFormType;
use App\Form\ChangePasswordFormType;
use App\Repository\CinemaRepository;
use App\Repository\FilmRepository;
use App\Repository\GenreRepository;
use App\Repository\UserRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/administrateur/administration')]
class AdministrationController extends AbstractController
{
    use ResetPasswordControllerTrait;
    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher) {}

    #[Route('', name: 'app_administration')]
    public function index(CinemaRepository $cinemaRepository, GenreRepository $genreRepository): Response {

        // Récupérer les cinémas et les genres
        $cinemas = $cinemaRepository->findAll();
        $genres = $genreRepository->findAll();
        return $this->render('administration/index.html.twig', [
            'controller_name' => 'AdministrationController',
            'cinemas' => $cinemas,
            'genres' => $genres,
        ]);
    }
    #[Route('/film', name: 'app_administration_film')]
    public function Film(EntityManagerInterface $entityManager, FilmRepository $filmRepository): Response
    {
        // Récupérer tous les films
        $AllFilms = $filmRepository->findAll();
        $AllFilmsArray = [];
        $salles = $entityManager->getRepository(Salle::class)->findAll();

        // Parcourir les films
        foreach ($AllFilms as $film) {
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
    #[Route('/film/create', name: 'app_administration_creation_film')]
    public function CreateFilm(EntityManagerInterface $entityManager): Response
    {
        $film = new Film();
        $entityManager->persist($film);
        $entityManager->flush();
        return new JsonResponse(['status' => 'film created']);
    }
    #[Route('/film/delete', name: 'app_administration_delete_film')]
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
    #[Route('/film/validate', name: 'app_administration_validate_film')]
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
    #[Route('/film/reset', name: 'app_administration_reset_film')]
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
    #[Route('/account_employe', name: 'app_administration_account_employe')]
    public function accountEmploye(Request $request,EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher, UserRepository $userRepository, MailerInterface $mailer): Response
    {
        $employes = $userRepository->findByRole('ROLE_EMPLOYE');

        $user = new User();
        $form = $this->createForm(AccountEmployeFormType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('password')->getData();
            $user->setRoles(['ROLE_EMPLOYE']);
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            $user->setVerified(true);
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Le compte est créé');
            return $this->redirectToRoute('app_administration_account_employe');
        }
        $employeId = $request->request->get('employe_id');
        if ($employeId) {
            $employe = $userRepository->find($employeId);
            $email = $employe->getEmail();
            return $this->processSendingPasswordResetEmail($email, $mailer);
        }
        return $this->render('administration/accountEmploye.html.twig', [
            'employeForm' => $form,
            'employes' => $employes,
        ]);
    }
    #[Route('/passwordReset', name: 'app_reset')]
    public function resetConfirmed(): Response
    {
        return $this->render('reset_password/confirmation_password_reset.twig', [
            'controller_name' => 'ResetPasswordController',]);
    }
    #[Route('/reset/{token}', name: 'app_reset_password')]
    public function reset(Request $request, UserPasswordHasherInterface $passwordHasher, ?string $token = null): Response
    {
        if ($token) {
            // We store the token in session and remove it from the URL, to avoid the URL being
            // loaded in a browser and potentially leaking the token to 3rd party JavaScript.
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('app_reset_password');
        }

        $token = $this->getTokenFromSession();

        if (null === $token) {
            throw $this->createNotFoundException('Aucun token trouvé dans l\'URL.');
        }

        try {
            /** @var User $user */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('reset_password_error', sprintf(
                '%s - %s',
                ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE,
                $e->getReason()
            ));

            return $this->redirectToRoute('app_forgot_password_request');
        }

        // The token is valid; allow the user to change their password.
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $provisionalPassword = $form->get('provisionalPassword')->getData();

            if (!$passwordHasher->isPasswordValid($user, $provisionalPassword)) {

                $this->addFlash('reset_password_error', 'Le mot de passe provisoire est incorrect');
                return $this->redirectToRoute('app_reset_password', ['token' => $token]);

            } else {

                // A password reset token should be used only once, remove it.
                $this->resetPasswordHelper->removeResetRequest($token);

                /** @var string $plainPassword */
                $plainPassword = $form->get('plainPassword')->getData();

                // Encode(hash) the plain password, and set it.
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
                $user->setPasswordMustChange(false);
                $this->entityManager->flush();

                // The session is cleaned up after the password has been changed.
                $this->cleanSessionAfterReset();}

            return $this->redirectToRoute('app_reset');
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form,
        ]);
    }
    private function processSendingPasswordResetEmail(string $emailFormData, MailerInterface $mailer): RedirectResponse
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $emailFormData]);

        // Do not reveal whether a user account was found or not.
        if (!$user) {
            return $this->redirectToRoute('app_administration_account_employe');
        } else {
            $temporaryPassword = bin2hex(random_bytes(4));
            $passwordHasher = $this->passwordHasher;
            $hashedPassword = $passwordHasher->hashPassword($user, $temporaryPassword);
            $user->setPassword($hashedPassword);
            if (!$user->getPasswordMustChange()) {
                $user->setPasswordMustChange(true);
            }
            $this->entityManager->flush();
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('reset_password_error', sprintf(
                'Une erreur est survenue lors de la génération du token de réinitialisation - %s',
                $e->getReason()
            ));

            return $this->redirectToRoute('app_administration_account_employe');
        }

        $email = (new TemplatedEmail())
            ->from(new Address('gestioncinephoria@gmail.com', 'Mot de passe temporaire Cinéphoria'))
            ->to((string) $user->getEmail())
            ->subject('Votre demande de réinitialisation de mot de passe')
            ->htmlTemplate('reset_password/email.html.twig')
            ->context(['resetToken' => $resetToken,
                'temporaryPassword' => $temporaryPassword,]);

        $mailer->send($email);

        // Store the token object in session for retrieval in check-email route.
        $this->setTokenObjectInSession($resetToken);

        $this->addFlash('successReset', 'Un email a été envoyé à l\'employé');

        return $this->redirectToRoute('app_administration_account_employe');
    }
    #[Route('/reservations', name: 'app_administration_reservations')]
    public function reservations(): Response
    {
        return $this->render('administration/reservations.html.twig');
    }
    #[Route('/reservationsMongo', name: 'app_administration_reservationsMongo')]
    public function reservationsMongo(DocumentManager $documentManager): Response
    {
        $aggregationBuilder = $documentManager->createAggregationBuilder(ReservationMongo::class);

        $aggregationBuilder
            ->group()
            ->field('_id')->expression([
                'film' => '$film',
                'date' => '$date'
            ])
            ->field('count')->sum(1)
            ->sort(['_id.film' => 1, '_id.date' => 1]);

        $aggregationCursor = $aggregationBuilder->getAggregation();

        $groupedReservations = [];

        foreach ($aggregationCursor as $result) {

            if (!isset($result['_id']['film']) || !isset($result['_id']['date'])) {
                continue;
            }

            $film = $result['_id']['film'];

            $dateObject = $result['_id']['date'];
            $date = $dateObject->toDateTime();
            $date->setTimezone(new \DateTimeZone('Europe/Paris'));
            $formattedDate = $date->format('d/m/Y');

            if (!isset($groupedReservations[$film])) {
                $groupedReservations[$film] = ['name' => $film];
            }

            if (!isset($groupedReservations[$film][$formattedDate])) {
                $groupedReservations[$film][$formattedDate] = 0;
            }

            $groupedReservations[$film][$formattedDate] += $result['count'];
        }

        $resultArray = array_values($groupedReservations);

        return new JsonResponse([
            'reservations' => $resultArray,
        ]);
    }
}

