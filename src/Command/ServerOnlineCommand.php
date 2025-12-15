<?php
declare(strict_types=1);

namespace App\Command;

use App\Entity\Server;
use App\Services\DiscordService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
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
    private const BATCH_SIZE = 100;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly DiscordService $discord
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $serverRepo = $this->em->getRepository(Server::class);
        $processed = 0;
        $errors = 0;
        $offset = 0;

        // Batch process to avoid memory exhaustion on large datasets
        do {
            $servers = $serverRepo->createQueryBuilder('s')
                ->where('s.isEnabled = :enabled')
                ->setParameter('enabled', true)
                ->setFirstResult($offset)
                ->setMaxResults(self::BATCH_SIZE)
                ->getQuery()
                ->getResult();

            foreach ($servers as $server) {
                try {
                    $online = $this->discord->fetchOnlineCount($server->getDiscordID());
                    $server->setMembersOnline($online);
                    $output->writeln(sprintf('Updating %s to %d members online.', $server->getDiscordID(), $online));
                    $processed++;
                } catch (Exception $e) {
                    $output->writeln('Error: ' . $e->getMessage());
                    $errors++;
                }
            }

            $this->em->flush();
            $this->em->clear(Server::class);
            $offset += self::BATCH_SIZE;
        } while (count($servers) === self::BATCH_SIZE);

        $output->writeln(sprintf('Done! Updated %d servers, %d errors.', $processed, $errors));

        return Command::SUCCESS;
    }
}
