<?php

namespace AntistressStore\Test\Unit;

use AntistressStore\CdekSDK2\CdekClientV2;
use AntistressStore\CdekSDK2\Exceptions\CdekV2ForeignEntityException;
use AntistressStore\CdekSDK2\Exceptions\CdekV2RequestException;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class RequestExceptionTest extends TestCase
{
    /** @dataProvider foreignEntityErrors */
    public function testForeignEntityErrorsAreTyped(string $code, bool $nested, int $statusCode): void
    {
        $error = ['code' => $code, 'message' => 'Untranslated carrier message'];
        $payload = ['state' => 'INVALID', 'errors' => [$error]];
        $client = $this->clientRespondingWith($nested ? ['requests' => [$payload]] : $payload, $statusCode);

        try {
            $client->getOrderInfoByCdekNumber('1234567890');
            self::fail('A foreign shipment must raise a typed exception.');
        } catch (CdekV2ForeignEntityException $exception) {
            self::assertInstanceOf(CdekV2RequestException::class, $exception);
            self::assertSame($code, $exception->getApiErrorCode());
            self::assertSame($statusCode, $exception->getCode());
            self::assertStringContainsString('Untranslated carrier message', $exception->getMessage());
        }
    }

    public static function foreignEntityErrors(): array
    {
        return [
            ['v2_entity_forbidden', false, 403],
            ['v2_order_forbidden', false, 200],
            ['v2_entity_forbidden', true, 200],
            ['v2_order_forbidden', true, 403],
        ];
    }

    public function testUnrelatedErrorsRemainRequestExceptions(): void
    {
        $exception = CdekV2RequestException::fromApiError('orders', [
            'code' => 'v2_entity_not_found',
            'message' => 'Not found',
        ], 404);

        self::assertNotInstanceOf(CdekV2ForeignEntityException::class, $exception);
        self::assertSame('v2_entity_not_found', $exception->getApiErrorCode());
        self::assertSame(404, $exception->getCode());
    }

    public function testHttpForbiddenAloneDoesNotMeanAnotherCustomer(): void
    {
        $exception = CdekV2RequestException::fromApiError('orders', [
            'code' => 'unrelated_access_error',
            'message' => 'Access denied',
        ], 403);

        self::assertNotInstanceOf(CdekV2ForeignEntityException::class, $exception);
    }

    public function testExistingConstructorStillWorks(): void
    {
        $exception = new CdekV2RequestException('Original message', 500);

        self::assertSame('Original message', $exception->getMessage());
        self::assertSame(500, $exception->getCode());
        self::assertNull($exception->getApiErrorCode());
    }

    private function clientRespondingWith(array $body, int $statusCode): CdekClientV2
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['access_token' => 'token', 'expires_in' => 3600])),
            new Response($statusCode, ['Content-Type' => 'application/json'], json_encode($body)),
        ]);
        $client = new CdekClientV2('TEST');
        $http = new \ReflectionProperty(CdekClientV2::class, 'http');
        $http->setAccessible(true);
        $http->setValue($client, new Client([
            'handler' => HandlerStack::create($mock),
            'http_errors' => false,
        ]));

        return $client;
    }
}
