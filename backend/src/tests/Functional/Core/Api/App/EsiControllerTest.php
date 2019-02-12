<?php declare(strict_types=1);

namespace Tests\Functional\Core\Api\App;

use Brave\Core\Entity\Role;
use Brave\Core\Factory\RepositoryFactory;
use GuzzleHttp\ClientInterface;
use Tests\Client;
use Tests\WebTestCase;
use Tests\Helper;

class EsiControllerTest extends WebTestCase
{
    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var RepositoryFactory
     */
    private $repoFactory;

    public function setUp()
    {
        $this->helper = new Helper();
        $this->repoFactory = new RepositoryFactory($this->helper->getEm());
    }

    public function testEsiV1403()
    {
        $response1 = $this->runApp('GET', '/api/app/v1/esi');
        $this->assertEquals(403, $response1->getStatusCode());

        $this->helper->emptyDb();
        $appId = $this->helper->addApp('A1', 's1', [Role::APP])->getId();
        $headers = ['Authorization' => 'Bearer '.base64_encode($appId.':s1')];

        $response2 = $this->runApp('GET', '/api/app/v1/esi', null, $headers);
        $this->assertEquals(403, $response2->getStatusCode());
    }

    public function testEsiV1400()
    {
        $this->helper->emptyDb();
        $appId = $this->helper->addApp('A1', 's1', [Role::APP, Role::APP_ESI])->getId();
        $headers = ['Authorization' => 'Bearer '.base64_encode($appId.':s1')];

        $response1 = $this->runApp('GET', '/api/app/v1/esi', null, $headers);
        $this->assertSame(400, $response1->getStatusCode());
        $this->assertSame('Path cannot be empty.', $response1->getReasonPhrase());

        $response2 = $this->runApp(
            'GET',
            '/api/app/v1/esi/latest/characters/96061222/stats/',
            null,
            ['Authorization' => 'Bearer '.base64_encode($appId.':s1')]
        );
        $this->assertSame(400, $response2->getStatusCode());
        $this->assertSame(
            'The datasource parameter cannot be empty, it must contain an EVE character ID',
            $response2->getReasonPhrase()
        );

        $response3 = $this->runApp(
            'GET',
            '/api/app/v1/esi/latest/characters/96061222/stats/?datasource=96061222',
            null,
            ['Authorization' => 'Bearer '.base64_encode($appId.':s1')]
        );
        $this->assertSame(400, $response3->getStatusCode());
        $this->assertSame('Character not found.', $response3->getReasonPhrase());
    }

    public function testEsiV1200()
    {
        $this->helper->emptyDb();
        $this->helper->addCharacterMain('C1', 123, [Role::USER]);
        $appId = $this->helper->addApp('A1', 's1', [Role::APP, Role::APP_ESI])->getId();

        $httpClient = new Client();
        $httpClient->setResponse(new \GuzzleHttp\Psr7\Response(
            200,
            [
                'Content-Type' => ['application/json; charset=UTF-8'],
                'ETag' => ['686897696a7c876b7e'],
                'Expires' => ['Sun, 10 Feb 2019 19:22:52 GMT'],
                'X-Esi-Error-Limit-Remain' => [100],
                'X-Esi-Error-Limit-Reset' => [60],
                'X-Pages' => [3],
                'warning' => ['199 - This route has an upgrade available'],
            ],
            '{"key": "value"}'
        ));

        $response = $this->runApp(
            'GET',
            '/api/app/v1/esi/v3/characters/96061222/assets/?page=1&datasource=123',
            [],
            [
                'Authorization' => 'Bearer '.base64_encode($appId.':s1'),
                'If-None-Match' => '686897696a7c876b7e'
            ],
            [ClientInterface::class => $httpClient]
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"key": "value"}', $response->getBody()->__toString());
        $this->assertSame([
            'Content-Type' => ['application/json; charset=UTF-8'],
            'ETag' => ['686897696a7c876b7e'],
            'Expires' => ['Sun, 10 Feb 2019 19:22:52 GMT'],
            'X-Esi-Error-Limit-Remain' => ['100'],
            'X-Esi-Error-Limit-Reset' => ['60'],
            'X-Pages' => ['3'],
            'warning' => ['199 - This route has an upgrade available'],
        ], $response->getHeaders());
    }

    public function testEsiV1200PathAsParameter()
    {
        $this->helper->emptyDb();
        $this->helper->addCharacterMain('C1', 123, [Role::USER]);
        $appId = $this->helper->addApp('A1', 's1', [Role::APP, Role::APP_ESI])->getId();

        $httpClient = new Client();
        $httpClient->setResponse(new \GuzzleHttp\Psr7\Response(200, [], '{"key": "value"}'));

        $response = $this->runApp(
            'GET',
            '/api/app/v1/esi?esi-path-query='. urlencode('/v3/characters/96061222/assets/?page=1') . '&datasource=123',
            null,
            ['Authorization' => 'Bearer '.base64_encode($appId.':s1')],
            [ClientInterface::class => $httpClient]
        );
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"key": "value"}', $response->getBody()->__toString());
    }
}