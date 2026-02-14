<?php

namespace App\Http\Middleware;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * @param  array<int, string>  $guards
     */
    protected function unauthenticated($request, array $guards): never
    {
        throw new AuthenticationException(
            'Unauthenticated.',
            $guards,
            $this->redirectToPath($guards),
        );
    }

    /**
     * @param  array<int, string>  $guards
     */
    private function redirectToPath(array $guards): string
    {
        if (in_array('customer', $guards, true)) {
            return route('account.login');
        }

        return route('login');
    }
}
