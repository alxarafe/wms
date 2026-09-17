<?php

declare(strict_types=1);

namespace Tests\Contract\Api;

use PHPUnit\Framework\TestCase;

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

    public function testGreetingEndpointsAreGoneInBothStacks(): void
    {
        foreach ([getenv('PHP_BASE_URL') ?: self::PHP_BASE, getenv('JAVA_BASE_URL') ?: self::JAVA_BASE] as $base) {
            $this->skipIfUnreachable($base);
            $this->get($base . '/api/greet', 404);
            $this->get($base . '/api/greetings', 404);
        }
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
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_HTTPGET => true,
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        self::assertIsString($result, "GET $url returned non-string");
        self::assertSame($expectedStatus, $httpCode, "GET $url returned $httpCode");

        return $result;
    }
}
