<?php

namespace App\Http\Controllers;

use App\Models\MlPrediction;
use App\Models\Product;
use App\Models\Store;
use App\Models\TransactionItem;
use App\Services\MlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PredictionController extends Controller
{
    protected MlService $mlService;

    public function __construct(MlService $mlService)
    {
        $this->mlService = $mlService;
    }

    /**
     * Get prediction untuk suatu produk
     * 
     * POST /api/predictions
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function predict(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,product_id',
            'store_id' => 'required|exists:stores,store_id',
            'days' => 'nullable|integer|min:1|max:30',
        ]);

        try {
            $product = Product::findOrFail($request->product_id);
            $store = Store::findOrFail($request->store_id);
            $days = $request->get('days', 7);

            Log::info('Processing prediction', [
                'product_id' => $request->product_id,
                'store_id' => $request->store_id,
                'days' => $days,
            ]);

            // Ambil data penjualan terakhir untuk lag features
            $lag1 = $this->getLag1($product->product_id, $store->store_id);
            $lag7 = $this->getLag7($product->product_id, $store->store_id);

            Log::debug('Lag values', [
                'lag_1' => $lag1,
                'lag_7' => $lag7,
            ]);

            // Call ML Service
            $mlResult = $this->mlService->predict(
                $product->product_name,
                $product->category?->category_name ?? 'Uncategorized',
                $product->unit ?? 'pcs',
                $lag1,
                $lag7,
                $days
            );

            // Simpan ke database (simpan rentang prediksi sebagai start_date dan end_date)
            $prediksiHarian = $mlResult['prediksi_harian'] ?? [];
            $totalPrediksi = $mlResult['total_prediksi'] ?? 0;

            // Determine start and end dates from the ML response if available
            $startDate = null;
            $endDate = null;
            if (!empty($prediksiHarian) && is_array($prediksiHarian)) {
                $startDate = $prediksiHarian[0]['tanggal'] ?? null;
                $endDate = $prediksiHarian[count($prediksiHarian) - 1]['tanggal'] ?? null;
            }

            $prediction = MlPrediction::create([
                'store_id' => $store->store_id,
                'product_id' => $product->product_id,
                'prediction_date' => now()->toDateString(),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'selling_price' => $product->selling_price ?? 0,
                'predicted_revenue' => ($totalPrediksi) * ($product->selling_price ?? 0),
                'predicted_quantity' => (int) $totalPrediksi,
                'confidence_level' => 0.85,
                'featured_used' => [
                    'lag_1' => $lag1,
                    'lag_7' => $lag7,
                    'days' => $days,
                    'kategori' => $product->category?->category_name ?? 'N/A',
                    'satuan' => $product->unit ?? 'pcs',
                ],
                'model_version' => '1.0',
            ]);

            Log::info('Prediction saved', [
                'ml_id' => $prediction->ml_id,
                'predicted_revenue' => $prediction->predicted_revenue,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Prediksi berhasil dibuat',
                'data' => [
                    'prediction_id' => $prediction->ml_id,
                    'product' => [
                        'id' => $product->product_id,
                        'name' => $product->product_name,
                        'selling_price' => $product->selling_price,
                    ],
                    'store' => [
                        'id' => $store->store_id,
                        'name' => $store->name_store,
                    ],
                    'daily_predictions' => $mlResult['prediksi_harian'] ?? [],
                    'total_prediction' => $mlResult['total_prediksi'] ?? 0,
                    'buffer_stock' => $mlResult['buffer_20_persen'] ?? 0,
                    'purchase_recommendation' => $mlResult['rekomendasi_kulakan'] ?? 0,
                    'predicted_revenue' => number_format($prediction->predicted_revenue, 0, ',', '.'),
                    'prediction_period' => $days . ' hari',
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Prediction Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat prediksi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get history prediksi dengan filtering
     * 
     * GET /api/predictions/history
     */
    public function history(Request $request)
    {
        try {
            $query = MlPrediction::query();

            // Filter by product
            if ($request->has('product_id') && $request->product_id) {
                $query->where('product_id', $request->product_id);
            }

            // Filter by store
            if ($request->has('store_id') && $request->store_id) {
                $query->where('store_id', $request->store_id);
            }

            // Filter by date range
            if ($request->has('start_date') && $request->start_date) {
                $query->whereDate('prediction_date', '>=', $request->start_date);
            }

            if ($request->has('end_date') && $request->end_date) {
                $query->whereDate('prediction_date', '<=', $request->end_date);
            }

            $predictions = $query
                ->orderBy('prediction_date', 'desc')
                ->with(['product', 'store'])
                ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $predictions,
            ]);

        } catch (\Exception $e) {
            Log::error('History Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil history prediksi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detail prediksi
     * 
     * GET /api/predictions/{id}
     */
    public function show($id)
    {
        try {
            $prediction = MlPrediction::with(['product', 'store'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $prediction,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Prediksi tidak ditemukan',
            ], 404);
        }
    }

    /**
     * Get ML Service Status
     * 
     * GET /api/ml-status
     */
    public function mlStatus()
    {
        $status = $this->mlService->getStatus();

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    /**
     * Get lag_1 (penjualan 1 hari terakhir)
     */
    private function getLag1(int $productId, int $storeId): int
    {
        return (int) TransactionItem::query()
            ->where('product_id', $productId)
            ->whereHas('transaction', function ($query) use ($storeId): void {
                $query->where('store_id', $storeId)
                    ->whereDate('created_at', now()->subDay());
            })
            ->sum('quantity') ?? 0;
    }

    /**
     * Get lag_7 (penjualan 7 hari terakhir)
     */
    private function getLag7(int $productId, int $storeId): int
    {
        return (int) TransactionItem::query()
            ->where('product_id', $productId)
            ->whereHas('transaction', function ($query) use ($storeId): void {
                $query->where('store_id', $storeId)
                    ->whereDate('created_at', '>=', now()->subDays(7));
            })
            ->sum('quantity') ?? 0;
    }
}
