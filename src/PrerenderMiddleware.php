<?php

namespace Ouodev\Prerender;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use Symfony\Component\HttpFoundation\Response;

class PrerenderMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldShowPrerenderedPage($request)) {
            info("Crawler detected: {$request->path()}", [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
                'user_agent' => $request->userAgent(),
            ]);

            $prerenderUrl = rtrim(config('prerender.prerender_url'), '/') . '/render?url=' . urlencode($request->fullUrl());

            try {
                $response = Http::timeout(config('prerender.timeout'))->withHeaders([
                    'Accept' => 'text/html',
                    'Referer' => $request->header('referer', ''),
                    'User-Agent' => 'prerender',
                ])->get($prerenderUrl);

                if ($response->successful()) {
                    return response($response->body(), $response->status())->header('Content-Type', 'text/html');
                }
            } catch (\Throwable $e) {
                info('Prerender request failed', [
                    'error' => $e->getMessage(),
                    'url' => $prerenderUrl,
                ]);
            }
        }

        return $next($request);
    }

    protected function shouldShowPrerenderedPage($request): bool
    {
        // 避免 prerender 自己呼叫自己
        if (Str::contains(strtolower($request->userAgent()), 'prerender')) {
            return false;
        }

        // 只處理 GET
        if (!$request->isMethod('get')) {
            return false;
        }

        // 必須是 HTML 請求
        if (!Str::contains($request->header('Accept'), 'text/html')) {
            return false;
        }

        // 只處理爬蟲
        if (!(new Agent())->isRobot()) {
            return false;
        }

        // 黑名單
        foreach (config('prerender.blacklist', []) as $pattern) {
            if (Str::is($pattern, $request->path())) {
                return false;
            }
        }

        return true;
    }
}
