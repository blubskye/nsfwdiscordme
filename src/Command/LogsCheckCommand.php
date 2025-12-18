<?php
declare(strict_types=1);

namespace App\Command;

use Exception;
use Redis;
use SplFileObject;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:logs:check',
    description: 'Scan log files and send email alerts for errors'
)]
class LogsCheckCommand extends Command
{
    private const EMAIL_FROM = 'no-reply@headzoo.io';
    private const EMAIL_TO = 'sean@headzoo.io';
    private const SUBJECT = '[nsfwdiscordme log check]';

    public function __construct(
        private readonly string $logFile,
        private readonly Redis $redis,
        private readonly MailerInterface $mailer
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->redis->select(0);
        $lastLine = (int) $this->redis->get('app:logs:check:lastLine');
        $output->writeln(sprintf('Starting with line %d.', $lastLine));

        $file = new SplFileObject($this->logFile);
        $file->seek($lastLine);

        $reports = [];
        $currentLine = 0;
        while (!$file->eof()) {
            $currentLine++;
            if (preg_match('/^\[(.*?)\] ([\w]+)\.(CRITICAL|ERROR|WARNING): (.*)$/', $file->current(), $matches)) {
                $reports[] = $matches;
            }
            $file->next();
        }

        $countReports = count($reports);
        if ($countReports) {
            $this->sendReports($reports, $output);
        }

        $this->redis->set('app:logs:check:lastLine', $currentLine);
        $output->writeln(sprintf('Done! %d reports found. Last line = %d.', $countReports, $currentLine));

        return Command::SUCCESS;
    }

    protected function sendReports(array $reports, OutputInterface $output): void
    {
        $found = [];
        $counts = [
            'CRITICAL' => 0,
            'ERROR' => 0,
            'WARNING' => 0
        ];
        foreach ($reports as $report) {
            $counts[$report[3]]++;
            if (!$this->containsReport($found, $report)) {
                $found[] = $report;
            }
        }

        $message = '';
        foreach ($found as $value) {
            $message .= $value[0] . "\n";
        }

        if ($message) {
            $message = sprintf(
                "Found %d CRITICAL, %d ERROR, %d WARNING\n\n%s",
                $counts['CRITICAL'],
                $counts['ERROR'],
                $counts['WARNING'],
                $message
            );

            $output->writeln('Sending report.');
            $email = (new Email())
                ->from(self::EMAIL_FROM)
                ->to(self::EMAIL_TO)
                ->subject(self::SUBJECT)
                ->text($message);
            $this->mailer->send($email);
        }
    }

    protected function containsReport(array $found, array $report): bool
    {
        $needle = substr($report[4], 0, 50);
        foreach ($found as $p) {
            if (substr($p[4], 0, 50) === $needle) {
                return true;
            }
        }

        return false;
    }
}
