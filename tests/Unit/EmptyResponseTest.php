<?php

namespace AntistressStore\Test\Unit;

use AntistressStore\CdekSDK2\CdekClientV2;
use AntistressStore\CdekSDK2\Entity\Requests\LocationSuggest;
use AntistressStore\CdekSDK2\Exceptions\CdekV2RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\{Client as GuzzleClient, HandlerStack};
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class EmptyResponseTest extends TestCase
{
    public function testEmptyListMeansNothingFound(): void
    {
        $client = $this->clientRespondingWith('[]');

        $this->assertSame([], $client->suggestCity((new LocationSuggest())->setName('G')));
    }

    public function testMissingBodyIsStillAnError(): void
    {
        $client = $this->clientRespondingWith('');

        $this->expectException(CdekV2RequestException::class);
        $client->suggestCity((new LocationSuggest())->setName('G'));
    }

    public function testEmptyObjectIsStillAnError(): void
    {
        $client = $this->clientRespondingWith('{}');

        $this->expectException(CdekV2RequestException::class);
        $client->getOrderInfoByUuid('72753031-5e38-4ad6-8b2e-2f0b5f1f5c11');
    }

    private function clientRespondingWith(string $body): CdekClientV2
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['access_token' => 'token', 'expires_in' => 3600])),
            new Response(200, ['Content-Type' => 'application/json'], $body),
        ]);

        $client = new CdekClientV2('TEST');
        $http = new \ReflectionProperty(CdekClientV2::class, 'http');
        $http->setAccessible(true);
        $http->setValue($client, new GuzzleClient(['handler' => HandlerStack::create($mock)]));

        return $client;
    }
}
