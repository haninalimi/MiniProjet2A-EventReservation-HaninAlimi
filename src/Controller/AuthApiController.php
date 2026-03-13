<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\WebauthnCredentialRepository;
use App\Service\PasskeyAuthService;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/auth', name: 'api_auth_')]
class AuthApiController extends AbstractController
{
    public function __construct(
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly RefreshTokenGeneratorInterface $refreshGenerator,
        private readonly RefreshTokenManagerInterface $refreshManager,
        private readonly UserRepository $userRepository,
        private readonly WebauthnCredentialRepository $credentialRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

 
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Invalid credentials.'], Response::HTTP_UNAUTHORIZED);
        }
        return $this->buildTokenResponse($user);
    }


    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = $this->getJson($request);

        $email = $data['email'] ?? null;
        $plainPassword = $data['password'] ?? null;

        if (!$email || !$plainPassword) {
            return $this->json(['error' => 'Email and password are required.'], Response::HTTP_BAD_REQUEST);
        }

        if ($this->userRepository->findOneBy(['email' => $email])) {
            return $this->json(['error' => 'Email already registered.'], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        $this->userRepository->save($user, true);

        return $this->buildTokenResponse($user, Response::HTTP_CREATED);
    }


    #[Route('/passkey/register/options', name: 'passkey_register_options', methods: ['POST'])]
    public function passkeyRegisterOptions(
        Request $request,
        PasskeyAuthService $passkeyService
    ): JsonResponse {
        $data = $this->getJson($request);
        $email = $data['email'] ?? null;

        if (!$email) {
            return $this->json(['error' => 'Email is required.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            return $this->json(['error' => 'User not found.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($passkeyService->getRegistrationOptions($user));
    }

    #[Route('/passkey/register/verify', name: 'passkey_register_verify', methods: ['POST'])]
    public function passkeyRegisterVerify(
        Request $request,
        PasskeyAuthService $passkeyService
    ): JsonResponse {
        $data = $this->getJson($request);
        $email = $data['email'] ?? null;
        $credential = $data['credential'] ?? null;

        if (!$email || !$credential) {
            return $this->json(['error' => 'Email and credential are required.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            return $this->json(['error' => 'User not found.'], Response::HTTP_NOT_FOUND);
        }

        $source = $passkeyService->verifyRegistration($credential, $user);
        $this->credentialRepository->saveCredential($user, $source, $data['name'] ?? 'My Passkey');

        return $this->buildTokenResponse($user, Response::HTTP_CREATED);
    }

 
    #[Route('/passkey/login/options', name: 'passkey_login_options', methods: ['POST'])]
    public function passkeyLoginOptions(PasskeyAuthService $passkeyService): JsonResponse
    {
        return $this->json($passkeyService->getLoginOptions());
    }

    #[Route('/passkey/login/verify', name: 'passkey_login_verify', methods: ['POST'])]
    public function passkeyLoginVerify(
        Request $request,
        PasskeyAuthService $passkeyService
    ): JsonResponse {
        $credential = $this->getJson($request)['credential'] ?? null;

        if (!$credential) {
            return $this->json(['error' => 'Credential is required.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $passkeyService->verifyLogin($credential);

        return $this->buildTokenResponse($user);
    }

 
    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Not authenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'id'    => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ]);
    }


    private function buildTokenResponse(User $user, int $status = Response::HTTP_OK): JsonResponse
    {
        $jwt = $this->jwtManager->create($user);
        $refresh = $this->refreshGenerator->createForUserWithTtl($user, 2592000);
        $this->refreshManager->save($refresh);

        return $this->json([
            'token'         => $jwt,
            'refresh_token' => $refresh->getRefreshToken(),
            'user'          => [
                'id'    => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ],
        ], $status);
    }

    private function getJson(Request $request): array
    {
        return json_decode($request->getContent(), true) ?? [];
    }
}