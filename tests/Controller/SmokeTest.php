<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/** @group Functional */
final class SmokeTest extends WebTestCase
{
    public function testApiDocUrlIsSuccessful(): void
    {
        $client = self::createClient();
        $client->followRedirects(false);
        $client->request('GET', '/api/doc');

        self::assertResponseIsSuccessful();
    }

    public function testApiAccountUrlIsSecure(): void
    {
        $client = self::createClient();
        $client->followRedirects(false);
        $client->request('GET', '/api/account/me');

        self::assertResponseStatusCodeSame(401);
    }

    public function testLoginRouteCanConnectAValidUser(): void
    {
    $client = self::createClient();

    // Inscription
    $client->jsonRequest('POST', '/api/registration', [
        'firstName' => 'Alain',
        'lastName' => 'David',
        'guestNumber' => 1,
        'allergy' => 'aucune',
        'email' => 'alain.david.rg@gmail.com',
        'password' => 'password',
    ]);

    self::assertResponseStatusCodeSame(201); // ou 200 selon ton contrôleur

    // Connexion
    $client->jsonRequest('POST', '/api/login', [
        'email' => 'alain.david.rg@gmail.com',
        'password' => 'password',
    ]);

    self::assertResponseIsSuccessful();

    $content = $client->getResponse()->getContent();

    self::assertStringContainsString('apiToken', $content);
    self::assertStringContainsString('roles', $content);
    }

    public function testLoginRouteCannotConnectAnInvalidUser(): void
    {
        $client = self::createClient();

        $client->jsonRequest('POST', '/api/login', [
            'email' => 'invalid@example.com',
            'password' => 'invalidpassword',
        ]);

        self::assertResponseStatusCodeSame(401);
    }
}


