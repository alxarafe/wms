<?php

declare(strict_types=1);

namespace Tests\Contract\Api;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;

final class HealthApiTest extends TestCase
{
    private const PHP_BASE = 'http://php-app:80';
    private const JAVA_BASE = 'http://java-app:8080';

    public function testPhpHealthEndpoint(): void
    {
        $base = getenv('PHP_BASE_URL') ?: self::PHP_BASE;
        $this->skipIfUnreachable($base);
        $response = $this->get($base . '/api/health');
        $data = json_decode($response, true);

        self::assertIsArray($data);
        self::assertSame('ok', $data['status']);
        self::assertArrayHasKey('timestamp', $data);
    }

    #[Group('java')]
    public function testJavaHealthEndpoint(): void
    {
        $base = getenv('JAVA_BASE_URL') ?: self::JAVA_BASE;
        $this->skipIfUnreachable($base);
        $response = $this->get($base . '/api/health');
        $data = json_decode($response, true);

        self::assertIsArray($data);
        self::assertSame('ok', $data['status']);
        self::assertArrayHasKey('timestamp', $data);
    }

    public function testPhpGreetingEndpointsAreGone(): void
    {
        $base = getenv('PHP_BASE_URL') ?: self::PHP_BASE;
        $this->skipIfUnreachable($base);
        $this->get($base . '/api/greet', 404);
        $this->get($base . '/api/greetings', 404);
    }

    #[Group('java')]
    public function testJavaGreetingEndpointsAreGone(): void
    {
        $base = getenv('JAVA_BASE_URL') ?: self::JAVA_BASE;
        $this->skipIfUnreachable($base);
        $this->get($base . '/api/greet', 404);
        $this->get($base . '/api/greetings', 404);
    }

    public function testPhpApiAllowsViteOrigin(): void
    {
        $base = getenv('PHP_BASE_URL') ?: self::PHP_BASE;
        $this->skipIfUnreachable($base);
        $headers = $this->getWithHeaders($base . '/api/health', ['Origin: http://localhost:5173'])['headers'];
        self::assertSame('http://localhost:5173', $headers['access-control-allow-origin'] ?? null);
    }

    public function testPhpApiAnswersCorsPreflight(): void
    {
        $base = getenv('PHP_BASE_URL') ?: self::PHP_BASE;
        $this->skipIfUnreachable($base);
        $headers = $this->send(
            $base . '/api/receipts',
            204,
            [
                'Origin: http://localhost:5173',
                'Access-Control-Request-Method: POST',
            ],
            'OPTIONS',
        )['headers'];
        self::assertSame('http://localhost:5173', $headers['access-control-allow-origin'] ?? null);
        self::assertStringContainsString('POST', $headers['access-control-allow-methods'] ?? '');
    }

    #[Group('java')]
    public function testJavaApiAllowsViteOrigin(): void
    {
        $base = getenv('JAVA_BASE_URL') ?: self::JAVA_BASE;
        $this->skipIfUnreachable($base);
        $headers = $this->getWithHeaders($base . '/api/health', ['Origin: http://localhost:5173'])['headers'];
        self::assertSame('http://localhost:5173', $headers['access-control-allow-origin'] ?? null);
    }

    #[Group('java')]
    public function testJavaApiAnswersCorsPreflight(): void
    {
        $base = getenv('JAVA_BASE_URL') ?: self::JAVA_BASE;
        $this->skipIfUnreachable($base);
        $headers = $this->send(
            $base . '/api/receipts',
            200,
            [
                'Origin: http://localhost:5173',
                'Access-Control-Request-Method: POST',
            ],
            'OPTIONS',
        )['headers'];
        self::assertSame('http://localhost:5173', $headers['access-control-allow-origin'] ?? null);
    }

    private function skipIfUnreachable(string $baseUrl): void
    {
        $host = parse_url($baseUrl, PHP_URL_HOST);
        $port = parse_url($baseUrl, PHP_URL_PORT) ?: 80;

        if (!is_string($host)) {
            self::markTestSkipped("Invalid URL: $baseUrl");
        }

        $sock = @fsockopen((string)$host, $port, $errno, $errstr, 2);
        if ($sock === false) {
            self::markTestSkipped("Service $host:$port is not reachable ($errstr)");
        }
        fclose($sock);
    }

    private function get(string $url, int $expectedStatus = 200): string
    {
        return $this->send($url, $expectedStatus)['body'];
    }

    /**
     * @param list<string> $requestHeaders
     * @return array{headers: array<string, string>, body: string}
     */
    private function getWithHeaders(string $url, array $requestHeaders = []): array
    {
        return $this->send($url, 200, $requestHeaders);
    }

    /**
     * @param list<string> $requestHeaders
     * @param non-empty-string $method
     * @return array{headers: array<string, string>, body: string}
     */
    private function send(string $url, int $expectedStatus = 200, array $requestHeaders = [], string $method = 'GET'): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_NOBODY => $method === 'OPTIONS',
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $requestHeaders,
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        self::assertIsString($result, "$method $url returned non-string");
        self::assertSame($expectedStatus, $httpCode, "$method $url returned $httpCode");

        $headerBlock = substr($result, 0, $headerSize);
        $body = substr($result, $headerSize);

        $headers = [];
        foreach (explode("\r\n", $headerBlock) as $line) {
            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($name))] = trim($value);
            }
        }

        return ['headers' => $headers, 'body' => $body];
    }
}
