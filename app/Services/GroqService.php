<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl = 'https://api.groq.com/openai/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = config('services.groq.api_key');
        $this->model  = config('services.groq.model', 'llama-3.1-8b-instant');
    }

    public function generateSQL(string $question, int $storeId, array $history = []): string
    {
        $systemPrompt = $this->buildSystemPrompt($storeId);

        $userContent = "";
        if (!empty($history)) {
            $userContent .= "Percakapan sebelumnya:\n";
            foreach (array_slice($history, -3) as $h) {
                $q = trim($h['q'] ?? '');
                $a = trim($h['a'] ?? '');
                if ($q) $userContent .= "- User: {$q}\n";
                if ($a) $userContent .= "  AI: {$a}\n";
            }
            $userContent .= "\nPertanyaan sekarang: {$question}\n\n";
        } else {
            $userContent .= "Pertanyaan: {$question}\n\n";
        }
        $userContent .= "Berikan HANYA SQL SELECT query murni yang siap dieksekusi di MySQL. Dilarang menyertakan teks pengantar, markdown, atau komentar apapun.";

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type'  => 'application/json',
        ])->timeout(30)->post($this->baseUrl, [
            'model'       => $this->model,
            'temperature' => 0.1,
            'max_tokens'  => 600,
            'messages'    => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userContent],
            ],
        ]);

        if ($response->failed()) {
            Log::error('Groq generateSQL failed', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \Exception('Gagal menghubungi AI. Silakan coba lagi.');
        }

        $raw = trim($response->json('choices.0.message.content') ?? '');
        $raw = preg_replace('/^```[\w]*\n?/m', '', $raw);
        $raw = preg_replace('/\n?```$/m', '', $raw);

        // Ekstrak statement SELECT murni jika model menyertakan teks pengantar
        if (preg_match('/(SELECT\b[\s\S]+?)(?:;\s*$|\Z)/i', $raw, $matches)) {
            $sql = trim($matches[1]);
        } else {
            $sql = trim($raw);
        }

        return $sql;
    }

    public function formatAnswer(string $question, array $data): string
    {
        $dataJson = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type'  => 'application/json',
        ])->timeout(30)->post($this->baseUrl, [
            'model'       => $this->model,
            'temperature' => 0.5,
            'max_tokens'  => 400,
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => 'Kamu adalah asisten analitik bisnis POS kasir restoran Indonesia yang ramah dan solutif. Jawab pertanyaan user secara singkat, jelas, dan akurat menggunakan data yang diberikan. Gunakan Bahasa Indonesia dan tambahkan emoji yang relevan. Jika data kosong, jelaskan bahwa tidak ada data transaksi yang sesuai.',
                ],
                [
                    'role'    => 'user',
                    'content' => "Pertanyaan: {$question}\n\nData Hasil Query:\n{$dataJson}\n\nBerikan jawaban ramah berdasarkan data tersebut.",
                ],
            ],
        ]);

        if ($response->failed()) {
            Log::error('Groq formatAnswer failed', ['status' => $response->status(), 'body' => $response->body()]);
            return 'Data berhasil diambil namun gagal memformat jawaban.';
        }

        return trim($response->json('choices.0.message.content') ?? 'Tidak ada jawaban.');
    }

    private function buildSystemPrompt(int $storeId): string
    {
        return <<<PROMPT
Kamu adalah asisten analitik SQL cerdas untuk sistem POS toko/retail di Indonesia.
Tugasmu: menghasilkan HANYA satu perintah MySQL SELECT yang bisa menjawab pertanyaan bisnis toko user secara akurat.

=== ATURAN MUTLAK ===
1. Output WAJIB HANYA berupa query SQL valid. JANGAN gunakan markdown, jangan gunakan ```, jangan tulis kata pengantar, komentar, atau catatan penutup apapun.
2. HANYA statement SELECT yang diizinkan. DILARANG INSERT, UPDATE, DELETE, DROP, ALTER, TRUNCATE, REPLACE.
3. Selalu filter store_id = {$storeId} pada tabel transactions atau tabel yang memiliki store_id.
4. Filter status = 'completed' pada tabel transactions kecuali user secara spesifik menanyakan status transaksi lain (seperti void/refund/pending).
5. Pada query GROUP BY, gunakan alias yang didefinisikan di SELECT (contoh: GROUP BY hari atau GROUP BY tanggal).
6. Jika pertanyaan merujuk pada transaksi terakhir, pembeli terakhir, kasir terakhir, atau catatan terbaru, gunakan ORDER BY created_at DESC LIMIT 1.
7. Jika customer_name NULL atau kosong, artinya pembeli walk-in.
8. Jika user menanyakan hal bisnis seperti progress toko, omset, kategori paling laku, stok menipis, atau rekomendasi kulakan, prioritaskan query yang menggabungkan transaksi + item + produk + kategori + stock_levels agar jawaban relevan.
9. Jika user menanyakan "apa yang harus saya beli / kulakan / restock besok", buat query yang menampilkan produk dengan penjualan tinggi dan stok rendah, bukan hanya daftar produk yang populer tanpa memperhitungkan stok.
10. Jika user menanyakan "kinerja toko", "kemajuan toko", atau "bagaimana performa hari ini / minggu ini / bulan ini", gunakan periode dan agregasi yang sesuai dari created_at.
11. Kalau query memerlukan ROUND/COALESCE, boleh digunakan. Jangan menambahkan query non-SELECT.

=== JENIS PERTANYAAN YANG WAJIB DIDUKUNG ===
- progress toko, perkembangan toko, kinerja hari ini, minggu ini, bulan ini
- omset, pendapatan, total transaksi, rata-rata transaksi
- kategori terlaris, produk paling laku, best seller
- stok menipis, item habis, perlu restock, rekomendasi kulakan, apa yang harus dibeli besok
- pembeli paling sering, hari paling ramai, jam ramai, kasir paling aktif
- tren penjualan harian / mingguan / bulanan
- produk dengan penjualan tinggi tapi stok sedikit
- persentase kenaikan atau penurunan dibanding periode sebelumnya

=== DATABASE SCHEMA (MySQL) ===
1. transactions:
   - transaction_id (bigint unsigned, PK)
   - store_id (bigint unsigned)
   - user_id (bigint unsigned)
   - cashier_id (bigint unsigned)
   - transaction_code (varchar)
   - customer_name (varchar)
   - customer_type ('walkin', 'reguler', 'reseller')
   - payment_method ('cash', 'qris')
   - subtotal, tax, discount, total_amount, cash_paid, cash_change (decimal)
   - status ('pending_payment', 'completed', 'refunded', 'void')
   - notes (text)
   - created_at (timestamp)

2. transaction_items:
   - transaction_item_id (bigint unsigned, PK)
   - transaction_id (bigint unsigned)
   - product_id (bigint unsigned)
   - quantity (decimal/int)
   - unit_price (decimal)
   - discount (decimal)
   - subtotal (decimal)

3. products:
   - product_id (bigint unsigned, PK)
   - product_name (varchar, nama produk)
   - category_id (bigint unsigned)
   - purchase_price (decimal)
   - selling_price (decimal)
   - unit (varchar)
   - variant (varchar)

4. categories:
   - category_id (bigint unsigned, PK)
   - category_name (varchar, nama kategori)
   - name (varchar, alias kemungkinan nama kategori)

5. users:
   - user_id (bigint unsigned, PK)
   - name (varchar, nama pengguna)
   - role ('owner', 'cashier', 'pelayan')

6. stock_levels:
   - stock_level_id (bigint unsigned, PK)
   - product_id (bigint unsigned)
   - store_id (bigint unsigned)
   - quantity (decimal)
   - purchase_price (decimal)
   - unit_price (decimal)
   - discount (decimal)
   - subtotal (decimal)

=== CONTOH QUERY YANG SESUAI ===
-- 1. Kategori paling laris:
SELECT c.category_name AS kategori, SUM(ti.quantity) AS total_terjual, SUM(ti.subtotal) AS total_pendapatan
FROM transaction_items ti
JOIN transactions t ON ti.transaction_id = t.transaction_id
JOIN products p ON ti.product_id = p.product_id
LEFT JOIN categories c ON p.category_id = c.category_id
WHERE t.store_id = {$storeId} AND t.status = 'completed'
GROUP BY c.category_name
ORDER BY total_terjual DESC
LIMIT 10;

-- 2. Produk paling laris:
SELECT p.product_name, SUM(ti.quantity) AS total_terjual, SUM(ti.subtotal) AS total_pendapatan
FROM transaction_items ti
JOIN transactions t ON ti.transaction_id = t.transaction_id
JOIN products p ON ti.product_id = p.product_id
WHERE t.store_id = {$storeId} AND t.status = 'completed'
GROUP BY p.product_id, p.product_name
ORDER BY total_terjual DESC
LIMIT 10;

-- 3. Rekomendasi kulakan / restock
SELECT p.product_name, c.category_name AS kategori,
       COALESCE(SUM(ti.quantity),0) AS total_terjual_30_hari,
       COALESCE(sl.quantity,0) AS stok_saat_ini,
       COALESCE(p.purchase_price,0) AS harga_beli
FROM products p
LEFT JOIN categories c ON p.category_id = c.category_id
LEFT JOIN transaction_items ti ON ti.product_id = p.product_id
LEFT JOIN transactions t ON ti.transaction_id = t.transaction_id AND t.store_id = {$storeId} AND t.status = 'completed' AND t.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
LEFT JOIN stock_levels sl ON sl.product_id = p.product_id AND sl.store_id = {$storeId}
WHERE p.product_id IS NOT NULL
GROUP BY p.product_id, p.product_name, c.category_name, sl.quantity, p.purchase_price
HAVING COALESCE(sl.quantity,0) <= 10
ORDER BY total_terjual_30_hari DESC, COALESCE(sl.quantity,0) ASC
LIMIT 10;

-- 4. Kemajuan toko harian:
SELECT DATE(t.created_at) AS tanggal,
       COUNT(DISTINCT t.transaction_id) AS jumlah_transaksi,
       SUM(t.total_amount) AS total_omset,
       ROUND(AVG(t.total_amount),2) AS rata_rata_transaksi
FROM transactions t
WHERE t.store_id = {$storeId} AND t.status = 'completed'
  AND t.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY DATE(t.created_at)
ORDER BY tanggal DESC;

-- 5. Stok menipis:
SELECT p.product_name, c.category_name AS kategori, sl.quantity AS sisa_stok
FROM stock_levels sl
JOIN products p ON sl.product_id = p.product_id
LEFT JOIN categories c ON p.category_id = c.category_id
WHERE sl.store_id = {$storeId} AND sl.quantity <= 10
ORDER BY sl.quantity ASC, p.product_name ASC
LIMIT 20;

-- 6. Pembeli / transaksi terakhir:
SELECT t.transaction_code, t.customer_name, t.total_amount, t.created_at
FROM transactions t
WHERE t.store_id = {$storeId} AND t.status = 'completed'
ORDER BY t.created_at DESC
LIMIT 1;

=== REFERENSI WAKTU ===
- hari ini: DATE(t.created_at) = CURDATE()
- kemarin: DATE(t.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
- 7 hari terakhir: t.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
- 30 hari terakhir: t.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
- bulan ini: MONTH(t.created_at) = MONTH(CURDATE()) AND YEAR(t.created_at) = YEAR(CURDATE())
- bulan lalu: t.created_at >= DATE_SUB(DATE_FORMAT(NOW(), '%Y-%m-01'), INTERVAL 1 MONTH) AND t.created_at < DATE_FORMAT(NOW(), '%Y-%m-01')

=== PANDUAN KHUSUS UNTUK KULAKAN / RESTOCK ===
- Jika user bertanya tentang "yang harus dibeli besok", "kulakan", "restock", atau "stok yang perlu diisi ulang", cari produk dengan penjualan tinggi dan stok rendah.
- Prioritaskan `COALESCE(sl.quantity,0) <= threshold`, misalnya <= 10 atau <= 20, lalu urutkan berdasarkan `total_terjual_30_hari` DESC.
- Jika pertanyaannya bersifat prediksi pembelian berikutnya, hasil query bisa menampilkan produk, kategori, penjualan 7/30 hari terakhir, stok saat ini, dan harga beli.
- Inilah yang paling sering dibutuhkan oleh owner toko; jangan hanya menjawab produk populer tanpa memperhitungkan stok yang menipis.
PROMPT;
    }
}
