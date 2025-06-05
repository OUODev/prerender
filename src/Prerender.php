<?php

namespace Ouodev\Prerender;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;

class Prerender
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldShowPrerenderedPage($request)) {
            info('Crawler detected', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
            ]);

            $prerenderUrl = rtrim(config('prerender.prerender_url'), '/') . '/render?url=' . ltrim($request->fullurl(), '/');

            try {
                $response = Http::timeout(config('prerender.timeout'))->withHeaders([
                    'User-Agent' => $request->userAgent(),
                    'Accept' => 'text/html',
                    'Referer' => $request->header('referer', ''),
                ])->get($prerenderUrl);

                return response($response->body(), $response->status())
                    ->header('Content-Type', 'text/html');
            } catch (\Throwable $e) {
                info('Prerender request failed', [
                    'error' => $e->getMessage(),
                    'url' => $prerenderUrl,
                ]);

                return $next($request);
            }
        }

        return $next($request);
    }

    protected function shouldShowPrerenderedPage($request): bool
    {
        if (Str::contains(strtolower($request->userAgent()), 'prerender')) {
            return false;
        }

        if (!$request->isMethod('get')) {
            return false;
        }

        if (!(new Agent())->isRobot()) {
            return false;
        }

        foreach (config('prerender.blacklist', []) as $pattern) {
            if (Str::is($pattern, $request->path())) {
                return false;
            }
        }

        return true;
    }
}
