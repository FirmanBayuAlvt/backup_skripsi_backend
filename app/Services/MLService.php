<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MLService
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.ml_service.base_url', 'http://localhost:5000');
    }

    public function predict(array $features): ?array
    {
        try {
            $response = Http::timeout(30)->post($this->baseUrl . '/predict', $features);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('ML Service error', ['response' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('ML Service connection failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function health(): bool
    {
        try {
            $response = Http::timeout(5)->get($this->baseUrl . '/health');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
