<?php

namespace App\Models;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyConverter
{
    private $apiKey;
    private $freeApiUrl;
    private $premiumApiUrl;

    public function __construct()
    {
        $this->apiKey = env('CURRENCY_API_KEY');
        $this->freeApiUrl = env('CURRENCY_API_URL', 'https://free.currconv.com/api/v7/convert');
        $this->premiumApiUrl = env('CURRENCY_API_PREMIUM_URL', 'https://api.currconv.com/api/v8/convert');
    }

    // Main function to convert PHP to USD
    public function convertToUSD($phpAmount)
    {
        $currencyPair = "PHP_USD";

        try {
            // Try premium API first if we have an API key
            if (!empty($this->apiKey)) {
                $premiumResult = $this->tryPremiumApi($currencyPair, $phpAmount);
                if ($premiumResult['success']) {
                    return $premiumResult;
                }
            }

            // If premium fails or no API key, try free API
            return $this->tryFreeApi($currencyPair, $phpAmount);

        } catch (Exception $e) {
            Log::error('Currency conversion error: ' . $e->getMessage());
            return $this->tryBackupApi($phpAmount, $e->getMessage());
        }
    }

    // Try using premium API
    private function tryPremiumApi($currencyPair, $phpAmount)
    {
        try {
            $url = $this->premiumApiUrl . "?q={$currencyPair}&compact=ultra&apiKey={$this->apiKey}";
            
            $response = Http::timeout(10)->get($url);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data[$currencyPair])) {
                    $exchangeRate = $data[$currencyPair];
                    $usdAmount = $phpAmount * $exchangeRate;

                    return [
                        'success' => true,
                        'php_amount' => $phpAmount,
                        'usd_amount' => round($usdAmount, 2),
                        'exchange_rate' => $exchangeRate,
                        'api_used' => 'currencyconverterapi.com (Premium)',
                        'timestamp' => now()
                    ];
                }
            }

            throw new Exception('Premium API returned invalid data');

        } catch (Exception $e) {
            Log::warning('Premium API error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Try using free API
    private function tryFreeApi($currencyPair, $phpAmount)
    {
        try {
            $url = $this->freeApiUrl . "?q={$currencyPair}&compact=ultra";
            
            $response = Http::timeout(10)->get($url);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data[$currencyPair])) {
                    $exchangeRate = $data[$currencyPair];
                    $usdAmount = $phpAmount * $exchangeRate;

                    return [
                        'success' => true,
                        'php_amount' => $phpAmount,
                        'usd_amount' => round($usdAmount, 2),
                        'exchange_rate' => $exchangeRate,
                        'api_used' => 'currencyconverterapi.com (Free)',
                        'timestamp' => now()
                    ];
                }
            }

            throw new Exception('Free API returned invalid data');

        } catch (Exception $e) {
            throw new Exception('Free API error: ' . $e->getMessage());
        }
    }

    // Backup API in case main APIs fail
    private function tryBackupApi($phpAmount, $originalError)
    {
        try {
            $response = Http::timeout(10)->get('https://api.exchangerate-api.com/v4/latest/PHP');

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['rates']['USD'])) {
                    $exchangeRate = $data['rates']['USD'];
                    $usdAmount = $phpAmount * $exchangeRate;

                    return [
                        'success' => true,
                        'php_amount' => $phpAmount,
                        'usd_amount' => round($usdAmount, 2),
                        'exchange_rate' => $exchangeRate,
                        'api_used' => 'exchangerate-api.com (Backup)',
                        'primary_error' => $originalError,
                        'timestamp' => now()
                    ];
                }
            }

            throw new Exception('Backup API also failed');

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => "Main API Error: {$originalError}. Backup API Error: " . $e->getMessage(),
                'php_amount' => $phpAmount,
                'usd_amount' => 0
            ];
        }
    }

    // Get list of supported currencies (bonus feature)
    public function getSupportedCurrencies()
    {
        try {
            // Use appropriate API based on whether we have key or not
            if (!empty($this->apiKey)) {
                $url = "https://api.currconv.com/api/v8/currencies?apiKey={$this->apiKey}";
            } else {
                $url = "https://free.currconv.com/api/v7/currencies";
            }

            $response = Http::timeout(10)->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            return ['error' => 'Could not get currencies list'];

        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}