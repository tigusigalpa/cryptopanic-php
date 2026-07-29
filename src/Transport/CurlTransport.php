<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Transport;

use Tigusigalpa\CryptoPanic\Exceptions\TransportException;

/**
 * Internal cURL-based HTTP transport.
 *
 * This is the default transport used when no PSR-18 client is provided.
 * It handles only GET requests, which is all the CryptoPanic API requires.
 */
class CurlTransport
{
    /**
     * Send an HTTP GET request and return the response.
     *
     * @param string               $url     Full request URL.
     * @param array<string,string> $headers HTTP headers to send.
     * @param float                $timeout Timeout in seconds.
     *
     * @return HttpResponse
     *
     * @throws TransportException On cURL errors.
     */
    public function get(string $url, array $headers, float $timeout): HttpResponse
    {
        $ch = curl_init();

        $headerLines = [];
        foreach ($headers as $key => $value) {
            $headerLines[] = $key . ': ' . $value;
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => (int)ceil($timeout),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPGET => true,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new TransportException("cURL error ({$errno}): {$error}");
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        $rawHeaders = substr((string)$response, 0, $headerSize);
        $body = substr((string)$response, $headerSize);

        $responseHeaders = $this->parseHeaders($rawHeaders);

        curl_close($ch);

        return new HttpResponse(
            statusCode: $statusCode,
            body: $body,
            headers: $responseHeaders,
        );
    }

    /**
     * Parse raw HTTP response headers into an associative array.
     */
    private function parseHeaders(string $rawHeaders): array
    {
        $headers = [];
        $lines = explode("\r\n", trim($rawHeaders));

        foreach ($lines as $line) {
            if (str_contains($line, ': ')) {
                [$key, $value] = explode(': ', $line, 2);
                $key = strtolower(trim($key));
                $headers[$key] = trim($value);
            }
        }

        return $headers;
    }
}
