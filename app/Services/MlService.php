<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;
use Illuminate\Support\Facades\Log;

class MlService
{
    private string $mlApiUrl;
    private int $timeout;

    public function __construct()
    {
        $this->mlApiUrl = config('services.ml.api_url', 'http://127.0.0.1:8000');
        $this->timeout = config('services.ml.timeout', 30);
    }

    /**
     * Call ML API untuk prediksi penjualan
     * 
     * @param string $produk - Nama produk
     * @param string $kategori - Kategori produk
     * @param string $satuan - Satuan (pcs, box, dll)
     * @param int $lag1 - Penjualan kemarin (lag 1 hari)
     * @param int $lag7 - Penjualan seminggu lalu (lag 7 hari)
     * @param int $days - Jumlah hari prediksi (default: 7)
     * 
     * @return array Hasil prediksi dari ML
     * @throws Exception
     */
    public function predict(
        string $produk,
        string $kategori,
        string $satuan,
        int $lag1,
        int $lag7,
        int $days = 7
    ): array {
        try {
            Log::info('Calling ML Service', [
                'url' => $this->mlApiUrl . '/predict',
                'produk' => $produk,
                'kategori' => $kategori,
            ]);

            $response = Http::timeout($this->timeout)
                ->post($this->mlApiUrl . '/predict', [
                    'produk' => $produk,
                    'kategori' => $kategori,
                    'satuan' => $satuan,
                    'lag_1' => $lag1,
                    'lag_7' => $lag7,
                    'jumlah_hari' => $days,
                ]);

            if (!$response->successful()) {
                throw new Exception('ML Service returned status: ' . $response->status());
            }

            $data = $response->json();

            if ($data['status'] !== 'sukses') {
                throw new Exception('ML Service returned error: ' . ($data['message'] ?? 'Unknown error'));
            }

            // Normalize response keys to expected Indonesian names if necessary
            return $data;

        } catch (Exception $e) {
            Log::error('ML Service Error', [
                'message' => $e->getMessage(),
                'produk' => $produk,
            ]);

            throw new Exception('Failed to get ML prediction: ' . $e->getMessage());
        }
    }

    /**
     * Check apakah ML Service aktif
     */
    public function isHealthy(): bool
    {
        try {
            $response = Http::timeout(5)
                ->get($this->mlApiUrl . '/docs');

            return $response->successful();

        } catch (Exception $e) {
            Log::warning('ML Service Health Check Failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get status ML Service
     */
    public function getStatus(): array
    {
        return [
            'healthy' => $this->isHealthy(),
            'api_url' => $this->mlApiUrl,
            'timeout' => $this->timeout,
        ];
    }
}
