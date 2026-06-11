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
        $this->cargarDatos();
    }

    private function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function getAccessToken(): string
    {
        return Cache::remember('analytics_token', 3500, function () {
            $credsPath = storage_path('app/analytics-credentials.json');

            if (!file_exists($credsPath)) {
                throw new \RuntimeException("Archivo de credenciales no encontrado en: {$credsPath}");
            }

            $creds = json_decode(file_get_contents($credsPath), true);

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
            openssl_sign($toSign, $signature, $creds['private_key'], 'SHA256');

            $jwt = $toSign . '.' . $this->b64url($signature);

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            $token = $response->json('access_token');

            if (!$token) {
                throw new \RuntimeException("No se obtuvo access_token: " . json_encode($response->json()));
            }

            return $token;
        });
    }

    private function cargarDatos(): void
    {
        try {
            $token   = $this->getAccessToken();
            $baseUrl = "https://analyticsdata.googleapis.com/v1beta/properties/{$this->propertyId}";

            // Usuarios activos en tiempo real (cache 1 min)
            $this->activeUsers = Cache::remember('analytics_realtime', 60, function () use ($token, $baseUrl) {
                $rt = Http::withToken($token)
                    ->post("{$baseUrl}:runRealtimeReport", [
                        'metrics' => [['name' => 'activeUsers']],
                    ])->json();

                return (int) ($rt['rows'][0]['metricValues'][0]['value'] ?? 0);
            });

            // Sesiones por período + top páginas (cache 10 min)
            $data = Cache::remember('analytics_data', 600, function () use ($token, $baseUrl) {

                $sessions = Http::withToken($token)
                    ->post("{$baseUrl}:runReport", [
                        'dateRanges' => [
                            ['startDate' => 'today',     'endDate' => 'today', 'name' => 'hoy'],
                            ['startDate' => '7daysAgo',  'endDate' => 'today', 'name' => '7dias'],
                            ['startDate' => '30daysAgo', 'endDate' => 'today', 'name' => '30dias'],
                        ],
                        'dimensions' => [['name' => 'dateRange']],
                        'metrics'    => [['name' => 'sessions']],
                    ])->json();

                $visitas = ['hoy' => 0, '7dias' => 0, '30dias' => 0];
                foreach ($sessions['rows'] ?? [] as $row) {
                    $range = $row['dimensionValues'][0]['value'];
                    if (array_key_exists($range, $visitas)) {
                        $visitas[$range] = (int) $row['metricValues'][0]['value'];
                    }
                }

                $pages = Http::withToken($token)
                    ->post("{$baseUrl}:runReport", [
                        'dateRanges' => [['startDate' => '30daysAgo', 'endDate' => 'today']],
                        'dimensions' => [['name' => 'pagePath']],
                        'metrics'    => [['name' => 'screenPageViews']],
                        'orderBys'   => [['metric' => ['metricName' => 'screenPageViews'], 'desc' => true]],
                        'limit'      => 5,
                    ])->json();

                $topPaginas = [];
                foreach ($pages['rows'] ?? [] as $row) {
                    $topPaginas[] = [
                        'pagina' => $row['dimensionValues'][0]['value'],
                        'vistas' => (int) $row['metricValues'][0]['value'],
                    ];
                }

                return compact('visitas', 'topPaginas');
            });

            $this->visitasHoy    = $data['visitas']['hoy'];
            $this->visitas7dias  = $data['visitas']['7dias'];
            $this->visitas30dias = $data['visitas']['30dias'];
            $this->topPaginas    = $data['topPaginas'];

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
