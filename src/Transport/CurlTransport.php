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
     * @param string               $authToken Token used to redact cURL error messages.
     *
     * @return HttpResponse
     *
     * @throws TransportException On cURL errors.
     */
    public function get(string $url, array $headers, float $timeout, string $authToken = ''): HttpResponse
    {
        $ch = curl_init();
        if ($ch === false) {
            throw new TransportException('Unable to initialize cURL.', 0, null, false);
        }

        $headerLines = [];
        foreach ($headers as $key => $value) {
            $headerLines[] = $key . ': ' . $value;
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT_MS => max(1, (int)round($timeout * 1_000)),
            // Required by libcurl for reliable sub-second timeouts on Unix.
            CURLOPT_NOSIGNAL => true,
            // The token is a query parameter. Following a cross-host redirect
            // would disclose it to the redirect target.
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPGET => true,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = $this->redactToken(curl_error($ch), $authToken);
            $errno = curl_errno($ch);
            curl_close($ch);
            throw new TransportException(
                "cURL error ({$errno}): {$error}",
                $errno,
                null,
                $this->isRetryableCurlError($errno),
            );
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

    /**
     * Only retry cURL failures that are plausibly transient. Invalid URLs,
     * certificate errors, and local configuration failures cannot succeed on
     * another identical request.
     */
    private function isRetryableCurlError(int $errno): bool
    {
        return in_array($errno, [
            CURLE_COULDNT_RESOLVE_HOST,
            CURLE_COULDNT_CONNECT,
            CURLE_OPERATION_TIMEDOUT,
            CURLE_PARTIAL_FILE,
            CURLE_GOT_NOTHING,
            CURLE_SEND_ERROR,
            CURLE_RECV_ERROR,
        ], true);
    }

    private function redactToken(string $value, string $authToken): string
    {
        if ($authToken === '') {
            return $value;
        }

        return str_replace(
            array_unique([$authToken, rawurlencode($authToken), urlencode($authToken)]),
            '[REDACTED]',
            $value,
        );
    }
}
