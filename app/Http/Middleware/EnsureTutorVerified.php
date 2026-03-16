<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureTutorVerified
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->role === 'tutor') {
            $status = $user->tutorProfile?->verification_status;

            if ($status !== 'verified') {
                return redirect()->route('tutor.pending-approval');
            }
        }

        return $next($request);
    }
}
