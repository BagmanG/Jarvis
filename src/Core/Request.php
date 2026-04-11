<?php
namespace MiniApp\Core;

class Request
{
    private $body;
    private $json;

    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function path(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        if (strpos($path, '/api/v1') === 0) {
            $path = substr($path, 7);
        }

        return $path ?: '/';
    }

    public function query(string $key = null, $default = null)
    {
        if ($key === null) {
            return $_GET;
        }

        return $_GET[$key] ?? $default;
    }

    public function input(string $key = null, $default = null)
    {
        $data = array_merge($this->json(), $_POST);

        if ($key === null) {
            return $data;
        }

        return array_key_exists($key, $data) ? $data[$key] : $default;
    }

    public function json(): array
    {
        if ($this->json !== null) {
            return $this->json;
        }

        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw ?: '{}', true);
        $this->json = is_array($decoded) ? $decoded : [];
        return $this->json;
    }

    public function bearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (!$header && function_exists('getallheaders')) {
            $headers = getallheaders();
            $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    public function file(string $key)
    {
        return $_FILES[$key] ?? null;
    }
}
