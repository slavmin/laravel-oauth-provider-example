<?php

namespace App\Http\Controllers\Auth\Providers;

use App\Http\Controllers\Controller;

use App\Models\User;
use App\Models\OauthProviders;
use App\Services\Esia\EsiaSigner;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Contracts\User as SocialiteUser;

// Support
use Illuminate\Support\Str;

class ProviderAuthController extends Controller
{
    protected static array $scopes = [
        'openid',
        'fullname',
        'inn',
        'snils',
        'id_doc',
        'birthplace',
        'email',
        'mobile',
        'gender',
    ];

    protected static array $orgScopes = [];

    protected static string $scopeSeparator = ' ';


    /**
     * Redirect the user to the Provider authentication page.
     *
     * @throws \Exception
     */
    public function redirectToProvider(): RedirectResponse
    {
        $requestParams = [
            'client_id' => config('services.auth.esia.client_id'),
            'scope' => implode(self::$scopeSeparator, self::$scopes),
            'scope_org' => implode(self::$scopeSeparator, self::$orgScopes),
            'timestamp' => now()->setTimezone('+3')->format('Y.m.d H:i:s e'),
            'state' => Str::uuid()->toString(),
            'redirect_uri' => config("services.auth.esia.redirect"),
        ];

        $clientSecret = self::generateClientSecret($requestParams);

        $requestParams = array_merge($requestParams, [
            'client_secret' => $clientSecret,
            'client_certificate_hash' => config('services.auth.esia.cert_hash'),
            'access_type' => 'online',
        ]);

        //dd($requestParams);

        return Socialite::driver('esia')->with($requestParams)->stateless()->redirect();
    }

    /**
     * Obtain the user information from Provider.
     */
    public function handleProviderCallback(string $redirectToUrl = '/'): RedirectResponse
    {
        try {
            $oAuthUser = Socialite::driver('esia')->stateless()->user();

            $appUser = $this->findOrCreateUser($oAuthUser, data_get($oAuthUser, 'oid'), 'esia');

            auth()->login($appUser, true);
        } catch (\Throwable $throwable) {
            \Log::error($throwable->getMessage(), $throwable->getTrace());
        }

        return redirect()->to($redirectToUrl);
    }

    /**
     * Return the user or create a new one.
     */
    public function findOrCreateUser(SocialiteUser $socialiteUser, string $oid, ?string $provider = 'esia'): User
    {
        $oauthUser = OauthProviders::query()
            ->where('provider_id', $oid)
            ->where('provider_name', $provider)
            ->first();

        if ($oauthUser) {
            return User::findOrFail($oauthUser->user->id);
        }

        $user = User::firstOrCreate([
            'name' => $socialiteUser->name,
            'email' => $socialiteUser->email,
        ]);

        $user->oauthProviders()->create([
            'provider_id' => $socialiteUser->getId(),
            'provider_name' => $provider,
        ]);

        return $user;
    }

    /**
     * Generate signature
     * @throws \Exception
     */
    private static function generateClientSecret(array $params): string
    {
        $contents = implode('', array_values($params));

        return (new EsiaSigner(config('services.auth.esia.cert_path'), config('services.auth.esia.private_key_path')))->sign($contents);
    }
}
