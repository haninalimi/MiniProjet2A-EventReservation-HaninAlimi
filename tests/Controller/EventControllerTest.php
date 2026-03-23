<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EventControllerTest extends WebTestCase
{

    public function testEventIndexIsPublic(): void
    {
        $client = static::createClient();
        $client->request('GET', '/events');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body');
    }

   
    public function testEventShowNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/events/99999');

        $this->assertResponseStatusCodeSame(404);
    }

 
    public function testHomePageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
    }

 
    public function testLoginPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
    }

   
    public function testRegisterPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/register');

        $this->assertResponseIsSuccessful();
    }


    public function testAdminRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');

        $this->assertResponseRedirects('/admin/login');
    }

  
    public function testAdminLoginPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/login');

        $this->assertResponseIsSuccessful();
    }

  
    public function testReserveEventNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/events/99999/reserve');

        $this->assertResponseStatusCodeSame(404);
    }


    public function testConfirmNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/events/reservation/99999/confirm');

        $this->assertResponseStatusCodeSame(404);
    }

 
    public function testApiMeWithoutToken(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/auth/me');

        $this->assertResponseStatusCodeSame(401);
    }
}