<?php

namespace App\Support\Payments;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Billplz collection.
 *
 * Billplz reports a payment twice: a server-to-server webhook, and a browser
 * redirect the payer lands on. Only the webhook is trustworthy — the redirect
 * is a URL the payer controls, so they decide whether, when and with what
 * parameters to visit it. Both carry an X-Signature; both are verified here,
 * but only the webhook is allowed to settle a payment.
 */
class Billplz
{
    public function configured(): bool
    {
        return filled($this->apiKey()) && filled($this->collectionId());
    }

    public function apiKey(): ?string
    {
        return Setting::get('billplz_api_key');
    }

    public function collectionId(): ?string
    {
        return Setting::get('billplz_collection_id');
    }

    public function signatureKey(): ?string
    {
        return Setting::get('billplz_x_signature_key');
    }

    public function sandbox(): bool
    {
        return Setting::get('billplz_sandbox', '1') === '1';
    }

    public function baseUrl(): string
    {
        return $this->sandbox()
            ? 'https://www.billplz-sandbox.com/api/v3'
            : 'https://www.billplz.com/api/v3';
    }

    /**
     * Create a bill and return where to send the payer, or null on failure.
     *
     * @param  array{email: string, name: string, amount: float, description: string, reference: string, callbackUrl: string, redirectUrl: string}  $bill
     * @return array{id: string, url: string}|null
     */
    public function createBill(array $bill): ?array
    {
        if (! $this->configured()) {
            return null;
        }

        try {
            $response = Http::withBasicAuth($this->apiKey(), '')
                ->timeout(15)
                ->asForm()
                ->post($this->baseUrl().'/bills', [
                    'collection_id' => $this->collectionId(),
                    'email' => $bill['email'],
                    'name' => $bill['name'],
                    // Billplz works in the smallest currency unit.
                    'amount' => (int) round($bill['amount'] * 100),
                    'description' => $bill['description'],
                    'callback_url' => $bill['callbackUrl'],
                    'redirect_url' => $bill['redirectUrl'],
                    'reference_1_label' => 'Payment',
                    'reference_1' => $bill['reference'],
                ]);

            if (! $response->successful()) {
                Log::error('Billplz bill creation failed', [
                    'status' => $response->status(),
                    'body' => $response->json() ?? $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            if (! isset($data['id'], $data['url'])) {
                return null;
            }

            return ['id' => $data['id'], 'url' => $data['url']];
        } catch (\Throwable $e) {
            Log::error('Billplz unreachable', ['message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Verify the X-Signature on a server-to-server webhook.
     *
     * Billplz signs by joining "key" and "value" for every parameter except
     * x_signature, sorting those pairs, joining them with a pipe, and taking
     * an HMAC-SHA256 with the collection's X-Signature key.
     */
    public function webhookSignatureIsValid(array $payload): bool
    {
        $signature = $payload['x_signature'] ?? null;
        unset($payload['x_signature']);

        return $this->signatureMatches($payload, $signature, prefix: '');
    }

    /**
     * Verify the X-Signature on the browser redirect.
     *
     * The redirect nests its parameters under billplz[...], and the signed
     * source uses the flattened "billplz" + key form.
     */
    public function redirectSignatureIsValid(array $billplzParams): bool
    {
        $signature = $billplzParams['x_signature'] ?? null;
        unset($billplzParams['x_signature']);

        return $this->signatureMatches($billplzParams, $signature, prefix: 'billplz');
    }

    private function signatureMatches(array $params, ?string $signature, string $prefix): bool
    {
        // Without a signing key nothing can be verified, and an unverifiable
        // message must never be treated as genuine.
        if (blank($this->signatureKey()) || blank($signature)) {
            return false;
        }

        $pairs = [];

        foreach ($params as $key => $value) {
            if (is_array($value)) {
                continue;
            }

            $pairs[] = $prefix.$key.($value ?? '');
        }

        sort($pairs, SORT_STRING);

        $expected = hash_hmac('sha256', implode('|', $pairs), $this->signatureKey());

        // Constant time, so a wrong signature does not leak how wrong it was.
        return hash_equals($expected, (string) $signature);
    }

    /**
     * Ask Billplz what it thinks the bill's state is.
     *
     * The authority of last resort: used to confirm a redirect rather than
     * believing what the browser handed over.
     */
    public function fetchBill(string $billId): ?array
    {
        if (! $this->configured()) {
            return null;
        }

        try {
            $response = Http::withBasicAuth($this->apiKey(), '')
                ->timeout(10)
                ->get($this->baseUrl().'/bills/'.$billId);

            return $response->successful() ? $response->json() : null;
        } catch (\Throwable $e) {
            Log::warning('Billplz bill lookup failed', ['bill' => $billId, 'message' => $e->getMessage()]);

            return null;
        }
    }
}
