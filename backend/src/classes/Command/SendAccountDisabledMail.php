<?php declare(strict_types=1);

namespace Neucore\Command;

use Neucore\Entity\Player;
use Neucore\Factory\RepositoryFactory;
use Neucore\Repository\PlayerRepository;
use Neucore\Service\EveMail;
use Neucore\Service\ObjectManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SendAccountDisabledMail extends Command
{
    /**
     * @var EveMail
     */
    private $eveMail;

    /**
     * @var PlayerRepository
     */
    private $playerRepository;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var int
     */
    private $sleep;

    /**
     * @var bool
     */
    private $log;

    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(
        EveMail $eveMail,
        RepositoryFactory $repositoryFactory,
        ObjectManager $objectManager,
        LoggerInterface $logger
    ) {
        parent::__construct();

        $this->eveMail = $eveMail;
        $this->playerRepository = $repositoryFactory->getPlayerRepository();
        $this->objectManager = $objectManager;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this->setName('send-account-disabled-mail')
            ->setDescription('Sends "account disabled" EVE mail notification.')
            ->addOption(
                'sleep',
                's',
                InputOption::VALUE_OPTIONAL,
                'Time to sleep in seconds after each mail sent (ESI rate limit is 4/min)',
                20
            )
            ->addOption('log', 'l', InputOption::VALUE_NONE, 'Redirect output to log.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->sleep = intval($input->getOption('sleep'));
        $this->log = (bool) $input->getOption('log');
        $this->output = $output;

        $this->writeln('* Started "send-account-disabled-mail"');

        $this->send();

        $this->writeln('* Finished "send-account-disabled-mail"');
    }

    private function send()
    {
        $notActiveReason = $this->eveMail->accountDeactivatedIsActive();
        if ($notActiveReason !== '') {
            $this->writeln($notActiveReason);
            return;
        }

        $playerIds = [];
        $players = $this->playerRepository->findBy(['status' => Player::STATUS_STANDARD], ['lastUpdate' => 'ASC']);
        foreach ($players as $player) {
            $playerIds[] = $player->getId();
        }
        $this->objectManager->clear(); // detaches all objects from Doctrine

        foreach ($playerIds as $playerId) {
            $characterId = $this->eveMail->accountDeactivatedFindCharacter($playerId);
            if ($characterId === null) {
                $this->eveMail->accountDeactivatedMailSent($playerId, false);
                continue;
            }

            $mayNotSendReason = $this->eveMail->accountDeactivatedMaySend($characterId);
            if ($mayNotSendReason !== '') {
                continue;
            }

            $errMessage = $this->eveMail->accountDeactivatedSend($characterId);
            if ($errMessage === '') { // success
                $this->eveMail->accountDeactivatedMailSent($playerId, true);
                $this->writeln('Mail sent to ' . $characterId);
                usleep($this->sleep * 1000 * 1000);
            } else {
                $this->writeln($errMessage);
            }
        }
    }

    private function writeln($text)
    {
        if ($this->log) {
            $this->logger->info($text);
        } else {
            $this->output->writeln(date('Y-m-d H:i:s ') . $text);
        }
    }
}
