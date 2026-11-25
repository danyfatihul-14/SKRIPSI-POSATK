<?php

namespace Tests\Unit;

use App\Services\GroqService;
use PHPUnit\Framework\TestCase;

class GroqBusinessPromptTest extends TestCase
{
    public function test_prompt_supports_store_business_questions(): void
    {
        $reflection = new \ReflectionClass(GroqService::class);
        $service = $reflection->newInstanceWithoutConstructor();
        $method = new \ReflectionMethod($service, 'buildSystemPrompt');
        $method->setAccessible(true);

        $prompt = $method->invoke($service, 5);

        $this->assertStringContainsString('kategori terlaris', $prompt);
        $this->assertStringContainsString('rekomendasi kulakan', $prompt);
        $this->assertStringContainsString('stok menipis', $prompt);
        $this->assertStringContainsString('progress toko', $prompt);
        $this->assertStringContainsString('store_id = 5', $prompt);
    }
}
