<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AdminMiddleware
{

    public function handle(Request $request, $next)
    {
        $key = $request->input("key");
        if ($key !== env("ADMIN_KEY")) {
            throw new HttpException(401, "Unauthorized.");
        }

        return $next($request);
    }

}
