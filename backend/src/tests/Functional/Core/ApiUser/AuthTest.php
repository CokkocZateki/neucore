<?php
namespace Tests\Functional\Core\ApiUser;

use Brave\Slim\Session\SessionData;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Monolog\Logger;
use Monolog\Handler\TestHandler;
use Psr\Log\LoggerInterface;
use Tests\Functional\WebTestCase;
use Tests\Helper;

class AuthTest extends WebTestCase
{

    public function setUp()
    {
        $_SESSION = null;
    }

    public function testGetLogin()
    {
        $redirect = '/index.html#auth';
        $response = $this->runApp('GET', '/api/user/auth/login?redirect_url='.urlencode($redirect));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['application/json;charset=utf-8'], $response->getHeader('Content-Type'));

        $body = $this->parseJsonBody($response);

        $this->assertSame(1, count($body));
        $this->assertContains('https://login.eveonline.com', $body['oauth_url']);

        $sess = new SessionData();
        $this->assertSame($redirect, $sess->get('auth_redirect_url'));
        $this->assertSame(32, strlen($sess->get('auth_state')));
    }

    public function testGetCallbackStateError()
    {
        $state = 'd2c55ec4cfefe6224a500f4127bcee31';
        $_SESSION = ['auth_state' => $state];

        $response = $this->runApp('GET', '/api/user/auth/callback?state=INVALID'); // fail early
        $this->assertSame(302, $response->getStatusCode());

        $sess = new SessionData();
        $this->assertSame(null, $sess->get('auth_state')); // test that it was deleted
        $this->assertSame(['success' => false, 'message' => 'OAuth state mismatch'], $sess->get('auth_result'));
    }

    public function testGetCallbackAccessTokenException()
    {
        $state = 'd2c55ec4cfefe6224a500f4127bcee31';
        $_SESSION = ['auth_state' => $state];

        $sso = $this->createMock(GenericProvider::class);
        $sso->method('getAccessToken')->will($this->throwException(new \Exception));

        $log = new Logger('ignore');
        $log->pushHandler(new TestHandler());

        $response = $this->runApp('GET', '/api/user/auth/callback?state='.$state, null, null, [
            GenericProvider::class => $sso,
            LoggerInterface::class => $log
        ]);
        $this->assertSame(302, $response->getStatusCode());

        $sess = new SessionData();
        $this->assertSame(['success' => false, 'message' => 'request token error'], $sess->get('auth_result'));
    }

    public function testGetCallbackResourceOwnerException()
    {
        $state = 'd2c55ec4cfefe6224a500f4127bcee31';
        $_SESSION = ['auth_state' => $state];

        $sso = $this->createMock(GenericProvider::class);
        $sso->method('getAccessToken')->willReturn(new AccessToken(['access_token' => 't']));
        $sso->method('getResourceOwner')->will($this->throwException(new \Exception));

        $log = new Logger('ignore');
        $log->pushHandler(new TestHandler());

        $response = $this->runApp('GET', '/api/user/auth/callback?state='.$state, null, null, [
            GenericProvider::class => $sso,
            LoggerInterface::class => $log
        ]);
        $this->assertSame(302, $response->getStatusCode());

        $sess = new SessionData();
        $this->assertSame(['success' => false, 'message' => 'request verify error'], $sess->get('auth_result'));
    }

    public function testGetCallbackResourceOwnerError()
    {
        $state = 'd2c55ec4cfefe6224a500f4127bcee31';
        $_SESSION = ['auth_state' => $state];

        $sso = $this->createMock(GenericProvider::class);
        $sso->method('getAccessToken')->willReturn(new AccessToken(['access_token' => 't']));

        $ro = $this->createMock(ResourceOwnerInterface::class);
        $ro->method('toArray')->willReturn(['invalid']);
        $sso->method('getResourceOwner')->willReturn($ro);

        $response = $this->runApp('GET', '/api/user/auth/callback?state='.$state, null, null, [
            GenericProvider::class => $sso
        ]);
        $this->assertSame(302, $response->getStatusCode());

        $sess = new SessionData();
        $this->assertSame(['success' => false, 'message' => 'request verify error'], $sess->get('auth_result'));
    }

    public function testGetCallbackAuthError()
    {
        (new Helper())->emptyDb();

        $state = 'd2c55ec4cfefe6224a500f4127bcee31';
        $_SESSION = ['auth_state' => $state];

        $sso = $this->createMock(GenericProvider::class);
        $sso->method('getAccessToken')->willReturn(new AccessToken(['access_token' => 't']));

        $ro = $this->createMock(ResourceOwnerInterface::class);
        $ro->method('toArray')->willReturn(['CharacterID' => 123, 'CharacterName' => 'Na', 'CharacterOwnerHash' => 'a']);
        $sso->method('getResourceOwner')->willReturn($ro);

        $log = new Logger('ignore');
        $log->pushHandler(new TestHandler());

        $response = $this->runApp('GET', '/api/user/auth/callback?state='.$state, null, null, [
            GenericProvider::class => $sso,
            LoggerInterface::class => $log
        ]);
        $this->assertSame(302, $response->getStatusCode());

        // fails because Role "user" is missing in database

        $sess = new SessionData();
        $this->assertSame(['success' => false, 'message' => 'Could not authenticate user.'], $sess->get('auth_result'));
    }

    public function testGetCallback()
    {
        $h = new Helper();
        $h->emptyDb();
        $h->addRoles(['user']);

        $state = 'd2c55ec4cfefe6224a500f4127bcee31';
        $_SESSION = ['auth_state' => $state];

        $sso = $this->createMock(GenericProvider::class);
        $sso->method('getAccessToken')->willReturn(new AccessToken(['access_token' => 't']));

        $ro = $this->createMock(ResourceOwnerInterface::class);
        $ro->method('toArray')->willReturn(['CharacterID' => 123, 'CharacterName' => 'Na', 'CharacterOwnerHash' => 'a']);
        $sso->method('getResourceOwner')->willReturn($ro);

        $response = $this->runApp('GET', '/api/user/auth/callback?state='.$state, null, null, [
            GenericProvider::class => $sso
        ]);
        $this->assertSame(302, $response->getStatusCode());

        $sess = new SessionData();
        $this->assertSame(['success' => true, 'message' => ''], $sess->get('auth_result'));
    }

    public function testGetResult()
    {
        $response = $this->runApp('GET', '/api/user/auth/result');
        $this->assertSame(200, $response->getStatusCode());

        $this->assertSame(
            ['success' => false, 'message' => 'No login attempt recorded.'],
            $this->parseJsonBody($response)
        );
    }

    public function testGetLogout403()
    {
        $response = $this->runApp('GET', '/api/user/auth/logout');
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testGetLogout200()
    {
        $h = new Helper();
        $h->emptyDb();
        $h->addCharacterMain('Test User', 123456, ['user']);
        $this->loginUser(123456);

        $response = $this->runApp('GET', '/api/user/auth/logout');
        $this->assertSame(200, $response->getStatusCode());
    }
}