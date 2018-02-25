<?php
namespace Tests\Functional\CoreApiUser;

use Tests\Functional\BaseTestCase;
use Tests\Helper;

class InfoTest extends BaseTestCase
{

    public function setUp()
    {
        $_SESSION = null;
    }

    public function testGetInfo401()
    {
        $response = $this->runApp('GET', '/api/user/info');
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function testGetInfo200()
    {
        $h = new Helper();
        $h->emptyDb();
        $h->addCharacterMain('Test User', 123456, ['user', 'admin'], ['group1', 'another-group']);
        $this->loginUser(123456);

        $response = $this->runApp('GET', '/api/user/info');
        $this->assertEquals(200, $response->getStatusCode());

        $this->assertSame([
            'name' => 'Test User',
            'roles' => ['admin', 'user'],
            'groups' => ['another-group', 'group1'],
            'characters' => [
                ['id' => 123456, 'name' => 'Test User', 'main' => true],
            ],
            'managerGroups' => [],
            'managerApps' => [],
        ], $this->parseJsonBody($response));
    }
}
