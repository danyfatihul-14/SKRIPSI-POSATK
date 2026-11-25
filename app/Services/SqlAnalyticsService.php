<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SqlAnalyticsService
{
    private array $forbiddenKeywords = [
        'INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER',
        'TRUNCATE', 'CREATE', 'REPLACE', 'GRANT', 'REVOKE',
        'EXEC', 'EXECUTE', 'LOAD', 'OUTFILE', 'DUMPFILE',
    ];

    public function runSafe(string $sql): array
    {
        $sql = trim($sql);
        $sql = rtrim($sql, ';');

        $this->validate($sql);

        if (!str_contains(strtoupper($sql), 'LIMIT')) {
            $sql .= ' LIMIT 100';
        }

        try {
            // Nonaktifkan ONLY_FULL_GROUP_BY pada sesi ini agar query agregasi fleksibel
            DB::statement("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
            $results = DB::select($sql);
            return array_map(fn($row) => (array) $row, $results);
        } catch (\Exception $e) {
            Log::error('SqlAnalyticsService error', ['sql' => $sql, 'error' => $e->getMessage()]);
            throw new \Exception('Gagal menjalankan query: ' . $e->getMessage());
        }
    }

    private function validate(string $sql): void
    {
        $upper = strtoupper(trim($sql));

        if (!preg_match('/^\s*SELECT\b/i', $upper)) {
            throw new \Exception('Hanya SELECT query yang diizinkan.');
        }

        foreach ($this->forbiddenKeywords as $kw) {
            if (preg_match('/\b' . $kw . '\b/i', $upper)) {
                throw new \Exception("Query mengandung perintah terlarang: {$kw}");
            }
        }
    }
}
