<?php

namespace App\Http\Controllers;

use App\Services\GroqService;
use App\Services\SqlAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AiChatController extends Controller
{
    public function __construct(
        private GroqService $groq,
        private SqlAnalyticsService $sql,
    ) {}

    public function chat(Request $request)
    {
        $request->validate([
            'question' => 'required|string|min:3|max:500',
        ]);

        $question = trim($request->input('question'));
        $history  = (array) $request->input('history', []);
        $user     = $request->user();
        $storeId  = $request->input('store_id') ?: $user?->store_id;

        if (!$storeId) {
            $storeId = \App\Models\Store::query()->value('store_id');
        }

        if (!$storeId) {
            return response()->json(['error' => 'Store tidak ditemukan.'], 422);
        }

        try {
            // Step 1: Generate SQL from question with conversation history
            $sql = $this->groq->generateSQL($question, (int) $storeId, $history);

            Log::info('AI Chat SQL generated', ['question' => $question, 'sql' => $sql]);

            // Step 2: Run SQL safely
            $data = $this->sql->runSafe($sql);

            // Step 3: Format answer
            $answer = $this->groq->formatAnswer($question, $data);

            return response()->json([
                'success' => true,
                'answer'  => $answer,
                'data'    => $data,
                'sql'     => config('app.debug') ? $sql : null,
            ]);
        } catch (\Exception $e) {
            Log::error('AI Chat error', ['question' => $question, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
