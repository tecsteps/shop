<?php

namespace App\Http\Middleware;

use BackedEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class CheckStoreRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        abort_unless(app()->bound('current_store') && $request->user() !== null, 403);
        $role = $request->user()->roleForStore(app('current_store'));
        $value = $role instanceof BackedEnum ? (string) $role->value : (string) $role;
        abort_unless(in_array($value, $roles, true), 403);

        return $next($request);
    }
}
