<?php

namespace App\Livewire;

use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\OrderBy;
use Google\Analytics\Data\V1beta\OrderBy\MetricOrderBy;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class AdminAnalytics extends Component
{
    public int $activeUsers = 0;
    public int $visitasHoy = 0;
    public int $visitas7dias = 0;
    public int $visitas30dias = 0;
    public array $topPaginas = [];
    public ?string $error = null;

    public function mount(): void
    {
        $this->cargarDatos();
    }

    public function refrescar(): void
    {
        Cache::forget('analytics_realtime');
        Cache::forget('analytics_data');
        $this->error = null;
        $this->cargarDatos();
    }

    private function cargarDatos(): void
    {
        try {
            $property    = 'properties/541293754';
            $credentials = storage_path('app/analytics-credentials.json');

            $client = new BetaAnalyticsDataClient([
                'credentials' => $credentials,
                'transport'   => 'rest',
            ]);

            // Usuarios activos en tiempo real (cache 1 min)
            $this->activeUsers = Cache::remember('analytics_realtime', 60, function () use ($client, $property) {
                $rt = $client->runRealtimeReport([
                    'property' => $property,
                    'metrics'  => [new Metric(['name' => 'activeUsers'])],
                ]);
                return $rt->getRowCount() > 0
                    ? (int) $rt->getRows()[0]->getMetricValues()[0]->getValue()
                    : 0;
            });

            // Sesiones por período + top páginas (cache 10 min)
            $data = Cache::remember('analytics_data', 600, function () use ($client, $property) {
                $sessionsResponse = $client->runReport([
                    'property'    => $property,
                    'date_ranges' => [
                        new DateRange(['start_date' => 'today',      'end_date' => 'today',   'name' => 'hoy']),
                        new DateRange(['start_date' => '7daysAgo',   'end_date' => 'today',   'name' => '7dias']),
                        new DateRange(['start_date' => '30daysAgo',  'end_date' => 'today',   'name' => '30dias']),
                    ],
                    'dimensions' => [new Dimension(['name' => 'dateRange'])],
                    'metrics'    => [new Metric(['name' => 'sessions'])],
                ]);

                $visitas = ['hoy' => 0, '7dias' => 0, '30dias' => 0];
                foreach ($sessionsResponse->getRows() as $row) {
                    $range = $row->getDimensionValues()[0]->getValue();
                    if (array_key_exists($range, $visitas)) {
                        $visitas[$range] = (int) $row->getMetricValues()[0]->getValue();
                    }
                }

                $pagesResponse = $client->runReport([
                    'property'    => $property,
                    'date_ranges' => [new DateRange(['start_date' => '30daysAgo', 'end_date' => 'today'])],
                    'dimensions'  => [new Dimension(['name' => 'pagePath'])],
                    'metrics'     => [new Metric(['name' => 'screenPageViews'])],
                    'order_bys'   => [new OrderBy([
                        'metric' => new MetricOrderBy(['metric_name' => 'screenPageViews']),
                        'desc'   => true,
                    ])],
                    'limit' => 5,
                ]);

                $topPaginas = [];
                foreach ($pagesResponse->getRows() as $row) {
                    $topPaginas[] = [
                        'pagina' => $row->getDimensionValues()[0]->getValue(),
                        'vistas' => (int) $row->getMetricValues()[0]->getValue(),
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
