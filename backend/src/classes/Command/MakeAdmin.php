<?php declare(strict_types=1);

namespace Neucore\Command;

use Neucore\Entity\Role;
use Neucore\Factory\RepositoryFactory;
use Neucore\Repository\PlayerRepository;
use Neucore\Repository\RoleRepository;
use Neucore\Service\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeAdmin extends Command
{
    /**
     * @var PlayerRepository
     */
    private $playerRepository;

    /**
     * @var RoleRepository
     */
    private $roleRepository;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function __construct(RepositoryFactory $repositoryFactory, ObjectManager $objectManager)
    {
        parent::__construct();

        $this->playerRepository = $repositoryFactory->getPlayerRepository();
        $this->roleRepository = $repositoryFactory->getRoleRepository();
        $this->objectManager = $objectManager;
    }

    protected function configure()
    {
        $this->setName('make-admin')
            ->setDescription(
                'Adds all available roles to the player account to which '.
                'the character with the ID from the argument belongs.'
            )
            ->addArgument('id', InputArgument::REQUIRED, 'Player ID.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $playerId = intval($input->getArgument('id'));

        $player = $this->playerRepository->find($playerId);
        if ($player === null) {
            $output->writeln('Player with ID "' . $playerId .'" not found');
            return null;
        }

        $newRoles = [
            Role::USER_ADMIN,
            Role::USER_MANAGER,
            Role::APP_ADMIN,
            Role::APP_MANAGER,
            Role::GROUP_ADMIN,
            Role::GROUP_MANAGER,
            Role::ESI,
            Role::SETTINGS,
            Role::TRACKING,
        ];
        foreach ($this->roleRepository->findBy(['name' => $newRoles]) as $newRole) {
            if (! $player->hasRole($newRole->getName())) {
                $player->addRole($newRole);
            }
        }

        if (! $this->objectManager->flush()) {
            return null;
        }

        $output->writeln('Added all applicable roles to the player account "' .$player->getName() . '"');
    }
}
