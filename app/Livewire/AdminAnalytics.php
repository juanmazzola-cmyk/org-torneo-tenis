<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Component;

class AdminAnalytics extends Component
{
    public int $activeUsers = 0;
    public int $visitasHoy = 0;
    public int $visitas7dias = 0;
    public int $visitas30dias = 0;
    public array $topPaginas = [];
    public ?string $error = null;
    public array $debug = [];

    private string $propertyId = '541293754';

    public function mount(): void
    {
        $this->cargarDatos();
    }

    public function refrescar(): void
    {
        Cache::forget('analytics_token');
        Cache::forget('analytics_realtime');
        Cache::forget('analytics_data');
        $this->error = null;
        $this->debug = [];
        $this->cargarDatos();
    }

    private function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function getAccessToken(): string
    {
        // No cacheamos durante debug para ver respuestas frescas
        $credsPath = storage_path('app/analytics-credentials.json');

        if (!file_exists($credsPath)) {
            throw new \RuntimeException("Archivo de credenciales no encontrado en: {$credsPath}");
        }

        $creds = json_decode(file_get_contents($credsPath), true);

        if (!$creds) {
            throw new \RuntimeException("No se pudo parsear analytics-credentials.json");
        }

        $this->debug['credentials_ok']    = true;
        $this->debug['client_email']       = $creds['client_email'] ?? '(vacío)';
        $this->debug['private_key_length'] = strlen($creds['private_key'] ?? '');

        $now     = time();
        $header  = $this->b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = $this->b64url(json_encode([
            'iss'   => $creds['client_email'],
            'scope' => 'https://www.googleapis.com/auth/analytics.readonly',
            'aud'   => 'https://oauth2.googleapis.com/token',
            'exp'   => $now + 3600,
            'iat'   => $now,
        ]));

        $toSign = $header . '.' . $payload;
        $signed = openssl_sign($toSign, $signature, $creds['private_key'], 'SHA256');

        $this->debug['openssl_sign_ok'] = $signed;

        if (!$signed) {
            throw new \RuntimeException("openssl_sign falló — private_key inválida o formato incorrecto");
        }

        $jwt = $toSign . '.' . $this->b64url($signature);

        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]);

        $this->debug['token_status']   = $tokenResponse->status();
        $this->debug['token_response'] = $tokenResponse->json();

        $token = $tokenResponse->json('access_token');

        if (!$token) {
            throw new \RuntimeException("No se obtuvo access_token. Respuesta: " . json_encode($tokenResponse->json()));
        }

        $this->debug['token_ok'] = true;

        return $token;
    }

    private function cargarDatos(): void
    {
        try {
            $token   = $this->getAccessToken();
            $baseUrl = "https://analyticsdata.googleapis.com/v1beta/properties/{$this->propertyId}";

            // Realtime
            $rtResponse = Http::withToken($token)
                ->post("{$baseUrl}:runRealtimeReport", [
                    'metrics' => [['name' => 'activeUsers']],
                ]);

            $this->debug['realtime_status']   = $rtResponse->status();
            $this->debug['realtime_response'] = $rtResponse->json();

            $rt = $rtResponse->json();
            $this->activeUsers = (int) ($rt['rows'][0]['metricValues'][0]['value'] ?? 0);

            // Sesiones por período
            $sessionsResponse = Http::withToken($token)
                ->post("{$baseUrl}:runReport", [
                    'dateRanges' => [
                        ['startDate' => 'today',     'endDate' => 'today', 'name' => 'hoy'],
                        ['startDate' => '7daysAgo',  'endDate' => 'today', 'name' => '7dias'],
                        ['startDate' => '30daysAgo', 'endDate' => 'today', 'name' => '30dias'],
                    ],
                    'dimensions' => [['name' => 'dateRange']],
                    'metrics'    => [['name' => 'sessions']],
                ]);

            $this->debug['sessions_status']   = $sessionsResponse->status();
            $this->debug['sessions_response'] = $sessionsResponse->json();

            $sessions = $sessionsResponse->json();
            $visitas  = ['hoy' => 0, '7dias' => 0, '30dias' => 0];
            foreach ($sessions['rows'] ?? [] as $row) {
                $range = $row['dimensionValues'][0]['value'];
                if (array_key_exists($range, $visitas)) {
                    $visitas[$range] = (int) $row['metricValues'][0]['value'];
                }
            }

            $this->visitasHoy    = $visitas['hoy'];
            $this->visitas7dias  = $visitas['7dias'];
            $this->visitas30dias = $visitas['30dias'];

            // Top páginas
            $pagesResponse = Http::withToken($token)
                ->post("{$baseUrl}:runReport", [
                    'dateRanges' => [['startDate' => '30daysAgo', 'endDate' => 'today']],
                    'dimensions' => [['name' => 'pagePath']],
                    'metrics'    => [['name' => 'screenPageViews']],
                    'orderBys'   => [['metric' => ['metricName' => 'screenPageViews'], 'desc' => true]],
                    'limit'      => 5,
                ]);

            $this->debug['pages_status'] = $pagesResponse->status();

            $pages = $pagesResponse->json();
            foreach ($pages['rows'] ?? [] as $row) {
                $this->topPaginas[] = [
                    'pagina' => $row['dimensionValues'][0]['value'],
                    'vistas' => (int) $row['metricValues'][0]['value'],
                ];
            }

        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.admin-analytics')
            ->layout('layouts.app');
    }
}
