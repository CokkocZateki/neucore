<?php declare(strict_types=1);

namespace Tests\Functional\Command;

use Neucore\Entity\Role;
use Neucore\Entity\SystemVariable;
use Neucore\Factory\RepositoryFactory;
use Tests\Functional\ConsoleTestCase;
use Tests\Helper;

class DoctrineFixturesLoadTest extends ConsoleTestCase
{
    public function testExecute()
    {
        // setup

        $helper = new Helper();
        $helper->emptyDb();
        $em = $helper->getEm();

        $em->persist((new Role(8))->setName(Role::ESI));
        $em->persist((new SystemVariable(SystemVariable::ALLOW_CHARACTER_DELETION))->setValue('1'));
        $em->persist((new SystemVariable(SystemVariable::DIRECTOR_CHAR . '1'))
            ->setScope(SystemVariable::SCOPE_SETTINGS));
        $em->flush();

        // run

        $output = explode("\n", $this->runConsoleApp('doctrine-fixtures-load'));

        $this->assertSame(3, count($output));
        $this->assertSame('loading Neucore\DataFixtures\RoleFixtureLoader', $output[0]);
        $this->assertSame('loading Neucore\DataFixtures\SystemVariablesFixtureLoader', $output[1]);
        $this->assertSame('', $output[2]);

        $repoFactory = new RepositoryFactory($em);
        $roles = $repoFactory->getRoleRepository()->findBy([]);
        $vars = $repoFactory->getSystemVariableRepository()->findBy([], ['name' => 'asc']);

        $this->assertSame(15, count($roles)); // 14 from seed + 1 from setup
        $this->assertSame(22, count($vars)); // 21 from seed + 1 from setup

        // check that value was not changed
        $this->assertSame(SystemVariable::ALLOW_CHARACTER_DELETION, $vars[1]->getName());
        $this->assertSame('1', $vars[1]->getValue());
    }
}
