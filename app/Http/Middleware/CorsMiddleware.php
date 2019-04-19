<?php namespace App\Http\Middleware;

use Illuminate\Http\Request;

class CorsMiddleware
{

    public function handle(Request $request, $next)
    {
        $headers = [
            'Access-Control-Allow-Origin'      => '*',
            'Access-Control-Allow-Methods'     => 'POST, GET, OPTIONS, PUT, DELETE',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age'           => '86400',
            'Access-Control-Allow-Headers'     => implode(', ', [
                                                      'Accept',
                                                      'Access-Control-Allow-Headers',
                                                      'Access-Control-Request-Headers',
                                                      'Access-Control-Request-Method',
                                                      'Content-Type',
                                                      'Origin',
                                                      'User-Agent',
                                                      'X-Requested-With',
                                                  ])
        ];

        if ($request->isMethod('OPTIONS'))
        {
            return response()->json('{"method":"OPTIONS"}', 200, $headers);
        }

        $response = $next($request);
        foreach($headers as $key => $value)
        {
            $response->header($key, $value);
        }

        return $response;
    }

}