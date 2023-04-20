<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UtmSource {
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next) {
        // utm_source=aicn&utm_medium=chat&phone=18602946980&notify=http://ssss
        $utm_source = $request->query('utm_source');
        $utm_medium = $request->query('utm_medium') ?? '';
        $phone      = $request->query('phone') ?? '';
        $notify     = $request->query('notify') ?? '';
        if ($utm_source) {

            session([
                'utm_source' => [
                    'source' => $utm_source,
                    'medium' => $utm_medium,
                    'phone'  => $phone,
                    'notify' => $notify
                ]
            ]);
        }

        return $next($request);
    }
}
