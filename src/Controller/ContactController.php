<?php

namespace App\Controller;

use App\Email\EmailContact;
use App\Form\ContactType;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    public function __construct(private EmailContact $emailTicket)
    {
    }

    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $username = $form->get('username')->getData();
            $object = $form->get('object')->getData();
            $description = $form->get('description')->getData();

            $this->emailTicket->sendEmailContact($username, $object, $description,
                (new TemplatedEmail())
                    ->from(new Address('gestioncinephoria@gmail.com', 'Contact Cinéphoria'))
                    ->subject('Nouveau message de contact')
                    ->to(new Address(address: 'gestioncinephoria@gmail.com'))
                    ->htmlTemplate('email/contact.html.twig')
            );

            $this->addFlash('success', 'Votre message a bien été envoyé');
            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/index.html.twig', [
            'contactForm' => $form->createView(),
        ]);
    }
    #[Route('/utilisateur/contact', name: 'app_contact_user')]
    public function indexUser(Request $request): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $username = $form->get('username')->getData();
            $object = $form->get('object')->getData();
            $description = $form->get('description')->getData();

            $this->emailTicket->sendEmailContact($username, $object, $description,
                (new TemplatedEmail())
                    ->from(new Address('gestioncinephoria@gmail.com'))
                    ->to(new Address(address: 'gestioncinephoria@gmail.com'))
                    ->htmlTemplate('email/contact.html.twig')
            );

            $this->addFlash('success', 'Votre message a bien été envoyé');
            return $this->redirectToRoute('app_contact_user');
        }

        return $this->render('contact/user.html.twig', [
            'contactForm' => $form->createView(),
        ]);
    }
    #[Route('/employe/contact', name: 'app_contact_employe')]
    public function indexEmploye(Request $request): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $username = $form->get('username')->getData();
            $object = $form->get('object')->getData();
            $description = $form->get('description')->getData();

            $this->emailTicket->sendEmailContact($username, $object, $description,
                (new TemplatedEmail())
                    ->from(new Address('gestioncinephoria@gmail.com'))
                    ->to(new Address(address: 'gestioncinephoria@gmail.com'))
                    ->htmlTemplate('email/contact.html.twig')
            );

            $this->addFlash('success', 'Votre message a bien été envoyé');
            return $this->redirectToRoute('app_contact_employe');
        }

        return $this->render('contact/employe.html.twig', [
            'contactForm' => $form->createView(),
        ]);
    }
    #[Route('/administrateur/contact', name: 'app_contact_admin')]
    public function indexAdmin(Request $request): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $username = $form->get('username')->getData();
            $object = $form->get('object')->getData();
            $description = $form->get('description')->getData();

            $this->emailTicket->sendEmailContact($username, $object, $description,
                (new TemplatedEmail())
                    ->from(new Address('gestioncinephoria@gmail.com'))
                    ->to(new Address(address: 'gestioncinephoria@gmail.com'))
                    ->htmlTemplate('email/contact.html.twig')
            );

            $this->addFlash('success', 'Votre message a bien été envoyé');
            return $this->redirectToRoute('app_contact_admin');
        }

        return $this->render('contact/admin.html.twig', [
            'contactForm' => $form->createView(),
        ]);
    }
}
