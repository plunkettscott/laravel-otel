<?php

namespace PlunkettScott\LaravelOpenTelemetry\Resolvers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use PlunkettScott\LaravelOpenTelemetry\Data\EndUser;
use PlunkettScott\LaravelOpenTelemetry\Resolvers\Contracts\UserResolver;

class DefaultUserResolver implements UserResolver
{
    /* @inheritDoc */
    public function resolve(Request $request): ?EndUser
    {
        if (! $request->user()) {
            return null;
        }

        $endUser = new EndUser();

        /** @var Authenticatable $user */
        $user = $request->user();
        $endUser->setId($user->getAuthIdentifier());

        // If the user has a getEndUserScopes method, we can use that to get the scopes
        if (method_exists($user, 'getEndUserScopes')) {
            $endUser->setScopes($user->getEndUserScopes());
        }

        // If Laravel Passport is installed, we can use the token to get the scopes
        if ($scopes = $this->getPassportScopes($user)) {
            $endUser->setScopes($scopes);
        }

        // If Laravel Sanctum is installed, we can use the token to get the scopes
        if ($scopes = $this->getSanctumScopes($user)) {
            $endUser->setScopes($scopes);
        }

        return $endUser;
    }

    private function getPassportScopes(Authenticatable $user): ?array
    {
        if (! class_exists('\Laravel\Passport\Token')) {
            return null;
        }

        if (! method_exists($user, 'token')) {
            return null;
        }

        if ($token = $user->token()) {
            return $token->scopes ?? [];
        }

        return null;
    }

    private function getSanctumScopes(Authenticatable $user): ?array
    {
        if (! class_exists('\Laravel\Sanctum\PersonalAccessToken')) {
            return null;
        }

        if (! method_exists($user, 'currentAccessToken')) {
            return null;
        }

        if ($token = $user->currentAccessToken()) {
            return $token->abilities ?? [];
        }

        return null;
    }
}
