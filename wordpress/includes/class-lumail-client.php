<?php
/**
 * Lumail HTTP client. No WordPress types — tests run this file alone.
 *
 * @package Lumail
 */

if (! defined('ABSPATH') && ! defined('LUMAIL_TEST')) {
    exit;
}

final class Lumail_Client
{
    public const DEFAULT_BASE_URL = 'https://lumail.io/api';

    /** @var callable(string,string,array<string,string>,?string):array{status:int,body:string,error:?string} */
    private $transport;

    public function __construct(
        private string $api_token,
        private string $base_url = self::DEFAULT_BASE_URL,
        ?callable $transport = null,
    ) {
        $this->base_url = rtrim($base_url !== '' ? $base_url : self::DEFAULT_BASE_URL, '/');
        $this->transport = $transport ?? [self::class, 'default_transport'];
    }

    public function is_configured(): bool
    {
        return str_starts_with($this->api_token, 'lum_') && strlen($this->api_token) > 8;
    }

    /**
     * @param array<string,mixed> $payload
     * @return array{ok:bool,status:int,data:array<string,mixed>,message:string}
     */
    public function create_subscriber(array $payload): array
    {
        return $this->request('POST', '/v2/subscribers', $payload);
    }

    /**
     * Cheap auth check: list one subscriber. 200 = token works.
     *
     * @return array{ok:bool,status:int,data:array<string,mixed>,message:string}
     */
    public function ping(): array
    {
        return $this->request('GET', '/v2/subscribers?limit=1', null);
    }

    /**
     * @param array<string,mixed>|null $payload
     * @return array{ok:bool,status:int,data:array<string,mixed>,message:string}
     */
    public function request(string $method, string $path, ?array $payload): array
    {
        if (! $this->is_configured()) {
            return [
                'ok' => false,
                'status' => 0,
                'data' => [],
                'message' => 'Missing Lumail API token. Add a lum_ token in Settings → Lumail.',
            ];
        }

        $url = $this->base_url . $path;
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_token,
            'Accept' => 'application/json',
            'User-Agent' => 'Lumail-WordPress/' . (defined('LUMAIL_VERSION') ? LUMAIL_VERSION : '0.1.0'),
        ];
        $body = null;
        if ($payload !== null) {
            $headers['Content-Type'] = 'application/json';
            $encoded = json_encode($payload, JSON_UNESCAPED_SLASHES);
            if ($encoded === false) {
                return [
                    'ok' => false,
                    'status' => 0,
                    'data' => [],
                    'message' => 'Could not encode the subscriber payload.',
                ];
            }
            $body = $encoded;
        }

        $raw = ($this->transport)($method, $url, $headers, $body);
        $status = (int) $raw['status'];
        $decoded = [];
        if (is_string($raw['body']) && $raw['body'] !== '') {
            $parsed = json_decode($raw['body'], true);
            if (is_array($parsed)) {
                $decoded = $parsed;
            }
        }

        if (is_string($raw['error']) && $raw['error'] !== '' && $status === 0) {
            return [
                'ok' => false,
                'status' => 0,
                'data' => $decoded,
                'message' => $raw['error'],
            ];
        }

        $ok = $status >= 200 && $status < 300;
        $message = '';
        if (isset($decoded['message']) && is_string($decoded['message'])) {
            $message = $decoded['message'];
        }
        if ($message === '' && ! $ok) {
            $message = $status === 401
                ? 'Lumail rejected the API token.'
                : 'Lumail request failed.';
        }

        return [
            'ok' => $ok,
            'status' => $status,
            'data' => $decoded,
            'message' => $message,
        ];
    }

    /**
     * @param array<string,string> $headers
     * @return array{status:int,body:string,error:?string}
     */
    public static function default_transport(
        string $method,
        string $url,
        array $headers,
        ?string $body,
    ): array {
        if (function_exists('wp_remote_request')) {
            $response = wp_remote_request(
                $url,
                [
                    'method' => $method,
                    'headers' => $headers,
                    'body' => $body ?? '',
                    'timeout' => 15,
                ]
            );
            if (is_wp_error($response)) {
                return [
                    'status' => 0,
                    'body' => '',
                    'error' => $response->get_error_message(),
                ];
            }

            return [
                'status' => (int) wp_remote_retrieve_response_code($response),
                'body' => (string) wp_remote_retrieve_body($response),
                'error' => null,
            ];
        }

        $header_lines = [];
        foreach ($headers as $name => $value) {
            $header_lines[] = $name . ': ' . $value;
        }

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $header_lines),
                'content' => $body ?? '',
                'ignore_errors' => true,
                'timeout' => 15,
            ],
        ]);
        $result = @file_get_contents($url, false, $context);
        $status = 0;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $match)) {
            $status = (int) $match[1];
        }

        if ($result === false) {
            return [
                'status' => $status,
                'body' => '',
                'error' => 'Could not reach Lumail.',
            ];
        }

        return [
            'status' => $status,
            'body' => $result,
            'error' => null,
        ];
    }
}
