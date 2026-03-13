<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\WebauthnCredentialRepository;
use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\ECDSA\ES384;
use Cose\Algorithm\Signature\ECDSA\ES512;
use Cose\Algorithm\Signature\RSA\RS256;
use Symfony\Component\HttpFoundation\RequestStack;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\TokenBinding\IgnoreTokenBindingHandler;

class PasskeyAuthService
{
    private const SESSION_REGISTRATION_KEY = 'webauthn_registration_options';
    private const SESSION_LOGIN_KEY = 'webauthn_login_options';

    public function __construct(
        private readonly string $rpName,
        private readonly string $rpId,
        private readonly UserRepository $userRepository,
        private readonly WebauthnCredentialRepository $credentialRepository,
        private readonly RequestStack $requestStack,
    ) {}

 
    public function getRegistrationOptions(User $user): array
    {
        $rp = PublicKeyCredentialRpEntity::create($this->rpName, $this->rpId);

        $userEntity = PublicKeyCredentialUserEntity::create(
            $user->getEmail(),
            (string) $user->getId(),
            $user->getEmail()
        );

        $challenge = random_bytes(32);

        $excludeCredentials = array_map(
            fn(PublicKeyCredentialSource $source) => PublicKeyCredentialDescriptor::create(
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                $source->publicKeyCredentialId
            ),
            $this->credentialRepository->findSourcesByUser($user)
        );

        $options = PublicKeyCredentialCreationOptions::create(
            rp: $rp,
            user: $userEntity,
            challenge: $challenge,
            pubKeyCredParams: $this->getSupportedAlgorithms(),
            excludeCredentials: $excludeCredentials,
        );

        $this->getSession()->set(self::SESSION_REGISTRATION_KEY, $options);

        return $options->jsonSerialize();
    }

    public function verifyRegistration(array $data, User $user): PublicKeyCredentialSource
    {
        $options = $this->getSession()->get(self::SESSION_REGISTRATION_KEY);
        if (!$options instanceof PublicKeyCredentialCreationOptions) {
            throw new \RuntimeException('Registration session expired. Please try again.');
        }

        $credential = PublicKeyCredential::createFromArray($data);
        $response = $credential->response;

        if (!$response instanceof AuthenticatorAttestationResponse) {
            throw new \RuntimeException('Invalid attestation response.');
        }

        $validator = $this->buildAttestationValidator();
        $request = $this->requestStack->getCurrentRequest();
        $host = $request->getSchemeAndHttpHost();

        $source = $validator->check(
            authenticatorAttestationResponse: $response,
            publicKeyCredentialCreationOptions: $options,
            request: $host,
        );

        $this->getSession()->remove(self::SESSION_REGISTRATION_KEY);

        return $source;
    }


    public function getLoginOptions(): array
    {
        $challenge = random_bytes(32);

        $options = PublicKeyCredentialRequestOptions::create(
            challenge: $challenge,
            rpId: $this->rpId,
        );

        $this->getSession()->set(self::SESSION_LOGIN_KEY, $options);

        return $options->jsonSerialize();
    }

    public function verifyLogin(array $data): User
    {
        $options = $this->getSession()->get(self::SESSION_LOGIN_KEY);
        if (!$options instanceof PublicKeyCredentialRequestOptions) {
            throw new \RuntimeException('Login session expired. Please try again.');
        }

        $credential = PublicKeyCredential::createFromArray($data);
        $response = $credential->response;

        if (!$response instanceof AuthenticatorAssertionResponse) {
            throw new \RuntimeException('Invalid assertion response.');
        }

        $credentialId = base64_encode($credential->rawId);
        $credentialEntity = $this->credentialRepository->findByCredentialId($credential->rawId);

        if (!$credentialEntity) {
            throw new \RuntimeException('Passkey not found.');
        }

        $validator = $this->buildAssertionValidator();
        $request = $this->requestStack->getCurrentRequest();
        $host = $request->getSchemeAndHttpHost();

        $source = $validator->check(
            credentialId: $credential->rawId,
            authenticatorAssertionResponse: $response,
            publicKeyCredentialRequestOptions: $options,
            request: $host,
            userHandle: null,
            securedRelyingPartyId: [$this->rpId],
            credentialSource: $credentialEntity->getCredentialSource(),
        );

        $credentialEntity->touch();
        $this->credentialRepository->getEntityManager()->flush();
        $this->getSession()->remove(self::SESSION_LOGIN_KEY);

        return $credentialEntity->getUser();
    }

    

    private function getSupportedAlgorithms(): array
    {
        return [
            PublicKeyCredentialParameters::create('public-key', -7),   
            PublicKeyCredentialParameters::create('public-key', -257),  
            PublicKeyCredentialParameters::create('public-key', -37),   
        ];
    }

    private function buildAlgorithmManager(): Manager
    {
        return Manager::create()->add(
            ES256::create(),
            ES384::create(),
            ES512::create(),
            RS256::create(),
        );
    }

    private function buildAttestationValidator(): AuthenticatorAttestationResponseValidator
    {
        $attestationManager = AttestationStatementSupportManager::create();
        $attestationManager->add(NoneAttestationStatementSupport::create());

        return AuthenticatorAttestationResponseValidator::create(
            attestationStatementSupportManager: $attestationManager,
            publicKeyCredentialSourceRepository: null,
            tokenBindingHandler: IgnoreTokenBindingHandler::create(),
            extensionOutputCheckerHandler: ExtensionOutputCheckerHandler::create(),
            algorithmManager: $this->buildAlgorithmManager(),
        );
    }

    private function buildAssertionValidator(): AuthenticatorAssertionResponseValidator
    {
        return AuthenticatorAssertionResponseValidator::create(
            algorithmManager: $this->buildAlgorithmManager(),
        );
    }

    private function getSession(): \Symfony\Component\HttpFoundation\Session\SessionInterface
    {
        return $this->requestStack->getSession();
    }
}