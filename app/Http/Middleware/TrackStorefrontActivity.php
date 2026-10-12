<?php

namespace App\Http\Middleware;

use App\Actions\Analytics\RecordStorefrontEvent;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TrackStorefrontActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if ($request->header('DNT') === '1' || $request->user()?->isAdmin() || ! $request->hasSession()) {
            return $response;
        }

        $route = $request->route()?->getName();
        $type = null;
        if ($request->isMethod('GET') && $response->isSuccessful() && in_array($route, ['storefront', 'catalog.index', 'catalog.show', 'products.show', 'search', 'articles.index', 'articles.show', 'pages.show'], true)) {
            $type = 'page_view';
        } elseif ($request->isMethod('POST') && $route === 'cart.add' && $response->isSuccessful()) {
            $type = 'cart_add';
        } elseif ($request->isMethod('POST') && $route === 'checkout.store' && $response->isRedirection()) {
            // Only the two successful checkout destinations; failed validation redirects elsewhere.
            $target = parse_url((string) $response->headers->get('Location'), PHP_URL_PATH);
            if (is_string($target) && preg_match('~\A/(checkout/success|cabinet/orders)/[^/]+\z~', $target)) {
                $type = 'order';
            }
        }

        if ($type !== null) {
            try {
                app(RecordStorefrontEvent::class)->record($request, $type);
            } catch (Throwable $exception) {
                // Do not log exception messages: SQL payloads can contain request-derived data.
                Log::warning('Storefront analytics event could not be saved.', ['event_type' => $type, 'exception_class' => $exception::class]);
            }
        }

        return $response;
    }
}
