<?php
namespace App\Command;

use App\Entity\Server;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
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

        foreach ($this->em->getRepository(Server::class)->findAll() as $server) {
            $server->setBumpPoints(0);
            $output->writeln('Resetting ' . $server->getDiscordID());
        }

        $this->em->flush();
        $output->writeln('Done!');

        return Command::SUCCESS;
    }
}
