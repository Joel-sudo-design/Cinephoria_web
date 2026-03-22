<?php

namespace App\Email;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

class EmailContact
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    public function sendEmailContact(String $username,String $object, String $description, TemplatedEmail $email): void
    {
        $context = $email->getContext();
        $context['username'] = $username;
        $context['object'] = $object;
        $context['description'] = $description;
        $email->context($context);
        $this->mailer->send($email);
    }

}

