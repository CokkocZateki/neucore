<?php declare(strict_types=1);

namespace Tests\Unit\Entity;

use Neucore\Entity\SystemVariable;
use PHPUnit\Framework\TestCase;

class SystemVariableTest extends TestCase
{
    public function testJsonSerialize()
    {
        $var = new SystemVariable('nam');
        $var->setValue('val');

        $this->assertSame(
            ['name' => 'nam', 'value' => 'val'],
            json_decode((string) json_encode($var), true)
        );
    }

    public function testGetName()
    {
        $var = new SystemVariable('nam');
        $this->assertSame('nam', $var->getName());
    }

    public function testSetValue()
    {
        // testing a variable that has no validation
        $var = new SystemVariable(SystemVariable::MAIL_TOKEN);
        $this->assertSame('abc', $var->setValue('abc')->getValue());
    }

    public function testSetValueAllowCharacterDeletion()
    {
        // ALLOW_LOGIN_MANAGED, GROUPS_REQUIRE_VALID_TOKEN and MAIL_ACCOUNT_DISABLED_ACTIVE
        // have the same validation, so no extra tests for those.

        $var = new SystemVariable(SystemVariable::ALLOW_CHARACTER_DELETION);
        $this->assertSame('0', $var->setValue('0')->getValue());
        $this->assertSame('1', $var->setValue('1')->getValue());
        $this->assertSame('1', $var->setValue('some text')->getValue());
        $this->assertSame('0', $var->setValue('')->getValue());
    }

    public function testSetValueAccountDeactivationDelay()
    {
        $var = new SystemVariable(SystemVariable::ACCOUNT_DEACTIVATION_DELAY);
        $this->assertSame('0', $var->setValue('abc')->getValue());
        $this->assertSame('10', $var->setValue('-10')->getValue());
        $this->assertSame('10', $var->setValue('10')->getValue());
        $this->assertSame('0', $var->setValue('')->getValue());
    }

    public function testSetValueMailAccountDisabledAlliances()
    {
        $var = new SystemVariable(SystemVariable::MAIL_ACCOUNT_DISABLED_ALLIANCES);
        $this->assertSame('123,456', $var->setValue(' 123 , 456 , abc, ')->getValue());
    }

    public function testSetValueMailAccountDisabledBody()
    {
        $var = new SystemVariable(SystemVariable::MAIL_ACCOUNT_DISABLED_BODY);
        $this->assertSame(" Multiline \ntext. ", $var->setValue(" Multiline \ntext. ")->getValue());
    }

    public function testSetValueHomeLogo()
    {
        // CUSTOMIZATION_NAV_LOGO has the same validation

        $var = new SystemVariable(SystemVariable::CUSTOMIZATION_HOME_LOGO);
        $this->assertSame('', $var->setValue('abc')->getValue());
        $this->assertSame('', $var->setValue('data:text/plain;base64,T3==')->getValue());
        $this->assertSame('data:image/png;base64,T/3+a=', $var->setValue('data:image/png;base64,T/3+a=')->getValue());
    }

    public function testSetValueMailAccountDisabledSubject()
    {
        // this is the default validation, single line text, so no extra test for others like this.

        $var = new SystemVariable(SystemVariable::MAIL_ACCOUNT_DISABLED_SUBJECT);
        $this->assertSame('Test this line', $var->setValue(" Test\nthis\r\nline ")->getValue());
    }

    public function testSetGetScope()
    {
        $var = new SystemVariable('nam');
        $this->assertSame($var, $var->setScope(SystemVariable::SCOPE_PUBLIC));
        $this->assertSame(SystemVariable::SCOPE_PUBLIC, $var->getScope());
    }
}
