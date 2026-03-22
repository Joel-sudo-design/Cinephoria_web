<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

class FilmControllerTest extends WebTestCase
{
    protected static function createKernel(array $options = []): KernelInterface
    {
        // Définir la variable d'environnement DATABASE_URL
        putenv('DATABASE_URL=mysql://root:root@127.0.0.1:3306/cinephoria?serverVersion=10.4.32-MariaDB');

        // Retourne une instance du kernel avec les bonnes options pour les tests
        return new \App\Kernel('test', true);
    }

    public function testFilmLoadingRoute()
    {
        // Créer un client pour faire des requêtes
        $client = static::createClient();

        // Faire une requête GET à la route /films/loading
        $client->request('GET', '/films/loading');

        // Vérifier que la réponse est un statut HTTP 200 (succès)
        $this->assertResponseIsSuccessful();

        // Vérifier que la réponse est bien au format JSON
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        // Décoder la réponse JSON
        $responseData = json_decode($client->getResponse()->getContent(), true);

        // Vérifier que la réponse contient un tableau de films
        $this->assertIsArray($responseData);

        // Vérifier que chaque film a les clés attendues
        foreach ($responseData as $film) {
            $this->assertArrayHasKey('id', $film);
            $this->assertArrayHasKey('name', $film);
            $this->assertArrayHasKey('description', $film);
            $this->assertArrayHasKey('label', $film);
            $this->assertArrayHasKey('age_minimum', $film);
            $this->assertArrayHasKey('notation', $film);

            // Vérifier que l'image est présente et valide
            $this->assertArrayHasKey('image', $film);
            $this->assertNotEmpty($film['image']);
            $this->assertStringContainsString('image_film/', $film['image']);

            // Vérifier que le genre est défini
            $this->assertArrayHasKey('genre', $film);
            $this->assertNotEmpty($film['genre']);
        }
    }
}



