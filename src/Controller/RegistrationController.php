<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    #[Route('/mon_espace/inscription', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            // generate a signed url and email it to the user
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('gestioncinephoria@gmail.com', 'Validez votre email pour la création de votre compte Cinéphoria'))
                    ->to((string) $user->getEmail())
                    ->subject('Merci de vérifier votre adresse email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );

            $this->addFlash('success', 'Un email de validation a été envoyé à l\'adresse indiquée');

            // do anything else you need here, like send an email

            return $this->redirectToRoute('app_register');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, UserRepository $userRepository): Response
    {
        $id = $request->query->get('id');

        if (null === $id) {
            return $this->redirectToRoute('app_register');
        }

        $user = $userRepository->find($id);

        if (null === $user) {
            return $this->redirectToRoute('app_register');
        }

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $exception->getReason());

            return $this->redirectToRoute('app_register');
        }

        return $this->render('registration/confirmation_creation_account.html.twig', [
            'controller_name' => 'ConfirmationCreationAccountController',
        ]);
    }

    #[Route('/api/register', name: 'api_register')]
    public function apiRegister(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em, UserRepository $userRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        $firstname = $data['firstname'] ?? null;
        $name = $data['name'] ?? null;
        $username = $data['username'] ?? null;
        $plainPassword = $data['password'] ?? null;

        if (!$email || !$plainPassword) {
            return $this->json([
                'success' => false,
                'message' => 'Les champs "email" et "password" sont obligatoires.',
            ], 400);
        }

        if ($userRepository->findOneBy(['email' => $email])) {
            return $this->json([
                'success' => false,
                'message' => 'Un utilisateur existe déjà avec cet email.',
            ], 400);
        }

        $user = new User();
        $user->setFirstname($firstname);
        $user->setName($name);
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

        $em->persist($user);
        $em->flush();

        $this->emailVerifier->sendEmailConfirmation('app_verify_email_api', $user,
            (new TemplatedEmail())
                ->from(new Address('gestioncinephoria@gmail.com', 'Cinéphoria'))
                ->to((string) $user->getEmail())
                ->subject('Merci de vérifier votre adresse email')
                ->htmlTemplate('registration/confirmation_email.html.twig')
        );

        return $this->json([
            'success' => true,
            'message' => 'Compte créé. Un email de validation a été envoyé.',
        ], 201);
    }

    #[Route('/api/verify/email', name: 'app_verify_email_api')]
    public function verifyUserEmailApi(Request $request, UserRepository $userRepository): JsonResponse
    {
        $id = $request->query->get('id');

        if (null === $id) {
            return $this->json([
                'success' => false,
                'message' => 'Lien invalide : identifiant manquant.',
            ], 400);
        }

        $user = $userRepository->find($id);

        if (null === $user) {
            return $this->json([
                'success' => false,
                'message' => 'Lien invalide : utilisateur introuvable.',
            ], 400);
        }

        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getReason(),
            ], 400);
        }

        return $this->json([
            'success' => true,
            'message' => 'Votre adresse email a bien été vérifiée.',
        ]);
    }
}
