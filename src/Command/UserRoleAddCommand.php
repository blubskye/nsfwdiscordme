<?php
namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use RobThree\Auth\TwoFactorAuth;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

#[AsCommand(
    name: 'app:user:role-add',
    description: 'Adds a role to a user'
)]
class UserRoleAddCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $repo = $this->em->getRepository(User::class);
        $helper = $this->getHelper('question');

        $question = new Question('Discord email or ID: ', false);
        if (!($email = $helper->ask($input, $output, $question))) {
            return Command::FAILURE;
        }

        if (is_numeric($email)) {
            $user = $repo->findByDiscordID($email);
        } else {
            $user = $repo->findByDiscordEmail($email);
        }
        if (!$user) {
            $output->writeln('User not found.');
            return Command::FAILURE;
        }

        $question = new Question('Role to add, i.e. ROLE_ADMIN: ', false);
        if (!($role = $helper->ask($input, $output, $question))) {
            return Command::FAILURE;
        }

        $user->addRole($role);
        $this->em->flush();
        $output->writeln('Role added. The user should log out and log back in now.');

        if (strtoupper($role) === User::ROLE_ADMIN) {
            $tfa = new TwoFactorAuth('nsfwdiscord.me');
            $secret = $tfa->createSecret();
            $user->setGoogleAuthenticatorSecret($secret);
            $this->em->flush();

            $qr = $tfa->getQRCodeImageAsDataUri('nsfwdiscord.me', $secret);
            $output->writeln('Google Authenticator QR Code: ' . $qr);
        }

        return Command::SUCCESS;
    }
}
