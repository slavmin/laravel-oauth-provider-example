<?php

namespace App\Services\Auth\Providers;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User as SocialiteUser;

class EsiaOauthProvider extends AbstractProvider implements ProviderInterface
{
    protected $scopeSeparator = ' ';

    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(config('services.auth.esia.auth_url'), $state);
    }

    protected function getTokenUrl()
    {
        return config('services.auth.esia.token_url');
    }

    /**
     * @throws GuzzleException
     */
    public function getAccessTokenResponse($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            RequestOptions::HEADERS => $this->getTokenHeaders($code),
            RequestOptions::FORM_PARAMS => $this->getTokenFields($code),
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * @throws GuzzleException
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->post(config('services.auth.esia.user_info_url'), [
            RequestOptions::HEADERS => [
                'cache-control' => 'no-cache',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            RequestOptions::FORM_PARAMS => [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ],
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @inheritDoc
     */
    protected function mapUserToObject(array $user): SocialiteUser
    {
        return (new SocialiteUser())->setRaw($user);
    }
}
