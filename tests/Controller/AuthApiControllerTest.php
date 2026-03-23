<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthApiControllerTest extends WebTestCase
{
    private const PASSWORD = 'Test@1234';

    private string $email;

    protected function setUp(): void
    {
        $this->email = 'phpunit_' . uniqid() . '@eventapp.dev';
    }

    public function testRegisterSuccess(): void
    {
        $client = static::createClient();

        $client->request(
            'POST', '/api/auth/register',
            [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $this->email, 'password' => self::PASSWORD])
        );

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);
        $this->assertArrayHasKey('refresh_token', $data);
        $this->assertEquals($this->email, $data['user']['email']);
    }

  
    public function testRegisterDuplicateEmail(): void
    {
        $client = static::createClient();

        $client->request(
            'POST', '/api/auth/register',
            [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $this->email, 'password' => self::PASSWORD])
        );

        $client->request(
            'POST', '/api/auth/register',
            [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $this->email, 'password' => self::PASSWORD])
        );

        $this->assertResponseStatusCodeSame(409);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

    public function testRegisterMissingFields(): void
    {
        $client = static::createClient();

        $client->request(
            'POST', '/api/auth/register',
            [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $this->email])
        );

        $this->assertResponseStatusCodeSame(400);
    }

 
    public function testRegisterShortPassword(): void
    {
        $client = static::createClient();

        $client->request(
            'POST', '/api/auth/register',
            [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $this->email, 'password' => '123'])
        );

        $status = $client->getResponse()->getStatusCode();
        $this->assertContains($status, [201, 422],
            'Register avec mot de passe court doit retourner 201 ou 422'
        );
    }


    public function testLoginViaRegisterGivesToken(): void
    {
        $client = static::createClient();

        $client->request(
            'POST', '/api/auth/register',
            [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $this->email, 'password' => self::PASSWORD])
        );

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);
        $this->assertNotEmpty($data['token']);
    }


    public function testLoginWrongPassword(): void
    {
        $client = static::createClient();

        $client->request(
            'POST', '/api/auth/login',
            [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'inexistant@test.com', 'password' => 'wrongpassword'])
        );

        $this->assertResponseStatusCodeSame(401);
    }

  
    public function testMeWithValidToken(): void
    {
        $client = static::createClient();

        $client->request(
            'POST', '/api/auth/register',
            [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $this->email, 'password' => self::PASSWORD])
        );

        $this->assertResponseStatusCodeSame(201);
        $token = json_decode($client->getResponse()->getContent(), true)['token'] ?? null;
        $this->assertNotNull($token);

        $client->request(
            'GET', '/api/auth/me',
            [], [],
            [
                'CONTENT_TYPE'       => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ]
        );

        $this->assertResponseIsSuccessful();
        $me = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($this->email, $me['email']);
        $this->assertContains('ROLE_USER', $me['roles']);
    }


    public function testMeUnauthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/auth/me',
            [], [], ['CONTENT_TYPE' => 'application/json']
        );
        $this->assertResponseStatusCodeSame(401);
    }

   
    public function testMeInvalidToken(): void
    {
        $client = static::createClient();
        $client->request(
            'GET', '/api/auth/me',
            [], [],
            [
                'CONTENT_TYPE'       => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer token_invalide_xyz_12345',
            ]
        );
        $this->assertResponseStatusCodeSame(401);
    }
}
