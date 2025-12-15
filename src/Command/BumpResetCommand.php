<?php
declare(strict_types=1);

namespace App\Command;

use App\Entity\Server;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:bumps:reset',
    description: 'Reset bump points on the 1st and 15th of each month'
)]
class BumpResetCommand extends Command
{
    private const BATCH_SIZE = 500;

    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $day = date('d');
        if ($day !== '01' && $day !== '15') {
            $output->writeln('Nothing to do!');
            return Command::SUCCESS;
        }

        $serverRepo = $this->em->getRepository(Server::class);
        $processed = 0;
        $offset = 0;

        // Batch process to avoid memory exhaustion on large datasets
        do {
            $servers = $serverRepo->createQueryBuilder('s')
                ->setFirstResult($offset)
                ->setMaxResults(self::BATCH_SIZE)
                ->getQuery()
                ->getResult();

            foreach ($servers as $server) {
                $server->setBumpPoints(0);
                $output->writeln('Resetting ' . $server->getDiscordID());
                $processed++;
            }

            $this->em->flush();
            $this->em->clear(Server::class);
            $offset += self::BATCH_SIZE;
        } while (count($servers) === self::BATCH_SIZE);

        $output->writeln(sprintf('Done! Reset %d servers.', $processed));

        return Command::SUCCESS;
    }
}
