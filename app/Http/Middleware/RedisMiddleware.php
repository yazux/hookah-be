<?php

namespace App\Http\Middleware;

use Closure;
use Redis;

use App\Exceptions\CustomException;

class RedisMiddleware
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     * @throws CustomException
     */
    public function handle($request, Closure $next)
    {
        try {
            //$redis = app()->make('redis');
            //$redis = new Redis();
            //$redis->connect(env('REDIS_HOST'), env('REDIS_PORT'));
            //$request['redis'] = $redis;
        } catch (Exception $e){
            throw new CustomException(
                $request, [], 500,
                'Redis connect error'
            );
        }

        return $next($request);
    }
}
