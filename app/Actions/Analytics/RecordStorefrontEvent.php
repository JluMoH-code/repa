<?php

namespace App\Actions\Analytics;

use App\Models\AnalyticsEvent;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RecordStorefrontEvent
{
    public function record(Request $request, string $type): void
    {
        $token = $request->session()->get('analytics.visitor');
        if (! is_string($token)) {
            $token = hash('sha256', Str::random(64));
            $request->session()->put('analytics.visitor', $token);
        }

        $source = $request->session()->get('analytics.source');
        if (! is_string($source)) {
            $source = $this->source($request);
            $request->session()->put('analytics.source', $source);
        }

        $product = $request->route('product');
        AnalyticsEvent::create([
            'event_type' => $type,
            'visitor_token' => $token,
            'path' => Str::limit('/'.ltrim($request->path(), '/'), 512, ''),
            'source' => $source,
            'product_id' => $product instanceof Product ? $product->id : null,
        ]);
    }

    private function source(Request $request): string
    {
        $campaign = $request->query('utm_source', $request->query('source'));
        // Accept campaign labels, never email addresses, free text or URL payloads.
        if (is_string($campaign) && preg_match('/\A[a-zA-Z0-9_-]{1,60}\z/', $campaign)) {
            return strtolower($campaign);
        }

        $host = parse_url((string) $request->header('referer', ''), PHP_URL_HOST);
        if (is_string($host) && strlen($host) <= 100 && ! filter_var($host, FILTER_VALIDATE_IP) && preg_match('/\A[a-zA-Z0-9.-]+\z/', $host) && strcasecmp($host, $request->getHost()) !== 0) {
            return strtolower($host);
        }

        return 'direct';
    }
}
