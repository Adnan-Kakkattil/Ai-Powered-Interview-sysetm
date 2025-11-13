<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

/**
 * Execute an HTTP request to the backend API.
 *
 * @param string $path e.g. '/admin/interviews'
 * @param string $method HTTP method
 * @param array<string,mixed>|null $payload
 * @param bool $requireAuth whether to include JWT automatically
 * @return array{data:mixed,error:?string,status:int}
 */
function apiRequest(string $path, string $method = 'GET', ?array $payload = null, bool $requireAuth = true): array
{
    $url = rtrim(API_BASE_URL, '/') . '/' . ltrim($path, '/');
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];

    if ($requireAuth && ($token = getAuthToken())) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    if (!is_null($payload)) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    $responseBody = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return [
            'data' => null,
            'error' => $curlError,
            'status' => 0,
        ];
    }

    $decoded = json_decode((string) $responseBody, true);

    $errorMessage = null;

    if ($statusCode >= 400) {
      if (is_array($decoded) && isset($decoded['message'])) {
        $errorMessage = $decoded['message'];
      } elseif (is_array($decoded) && isset($decoded['errors']) && is_array($decoded['errors'])) {
        $firstError = $decoded['errors'][0] ?? null;
        if (is_array($firstError) && isset($firstError['msg'])) {
          $errorMessage = $firstError['msg'];
        } elseif (is_string($firstError)) {
          $errorMessage = $firstError;
        }
      }

      if (!$errorMessage) {
        $errorMessage = 'API error';
      }
    }

    return [
        'data' => $decoded,
        'error' => $errorMessage,
        'status' => $statusCode,
    ];
}

