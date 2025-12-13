<?php
namespace App\Command;

use App\Entity\Server;
use App\Services\DiscordService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:server:online',
    description: 'Updates the online member count for each server'
)]
class ServerOnlineCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly DiscordService $discord
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $serverRepository = $this->em->getRepository(Server::class);
        foreach ($serverRepository->findAll() as $server) {
            try {
                $online = $this->discord->fetchOnlineCount($server->getDiscordID());
                $server->setMembersOnline($online);
                $output->writeln(sprintf('Updating %s to %d members online.', $server->getDiscordID(), $online));
            } catch (Exception $e) {
                $output->writeln('Error: ' . $e->getMessage());
            }
        }

        $this->em->flush();
        $output->writeln('Done!');

        return Command::SUCCESS;
    }
}
