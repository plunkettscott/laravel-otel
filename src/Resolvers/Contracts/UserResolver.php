<?php

namespace PlunkettScott\LaravelOpenTelemetry\Resolvers\Contracts;

use Illuminate\Http\Request;
use PlunkettScott\LaravelOpenTelemetry\Data\EndUser;

interface UserResolver
{
    /**
     * Resolve the user from the request, populating and returning an
     * EndUser instance. If the request does not contain a user, or the
     * user cannot reliably be resolved, return null.
     *
     * @param Request $request
     * @return EndUser|null
     */
    public function resolve(Request $request): ?EndUser;
}
