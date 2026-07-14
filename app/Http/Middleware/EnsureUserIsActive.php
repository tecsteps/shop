<?php

namespace App\Http\Middleware;

use App\Enums\UserStatus;
use App\Models\User;
use BackedEnum;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user instanceof User || $this->status($user) === UserStatus::Active->value) {
            return $next($request);
        }

        $token = $user->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        Auth::guard('web')->logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest($request->is('admin', 'admin/*') ? '/admin/login' : '/login');
    }

    private function status(User $user): string
    {
        return $user->status instanceof BackedEnum ? (string) $user->status->value : (string) $user->status;
    }
}
