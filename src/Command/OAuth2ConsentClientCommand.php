<?php

namespace App\Command;

use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:oauth2-server:consent-client', description: 'Consents an OAuth2 client')]
class OAuth2ConsentClientCommand extends Command
{
    /**
     * @var ClientManagerInterface
     */
    private $clientManager;

    public function __construct(ClientManagerInterface $clientManager)
    {
        parent::__construct();

        $this->clientManager = $clientManager;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Consents an OAuth2 client, allowing it to skip the consent screen')

            ->addOption('consent', null, InputOption::VALUE_NEGATABLE, 'Consent the client', true)

            ->addArgument('identifier', InputArgument::REQUIRED, 'The client identifier')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var \App\Entity\OAuth2Client */
        $client = $this->clientManager->find($input->getArgument('identifier'));
        if (!$client) {
            $io->error(\sprintf('OAuth2 client identified as "%s" does not exist.', $input->getArgument('identifier')));

            return 1;
        }

        $client->setConsented($this->getClientConsentedFromInput($input, $client->isConsented()));

        $this->clientManager->save($client);

        $io->success('OAuth2 client updated successfully.');

        return 0;
    }

    private function getClientConsentedFromInput(InputInterface $input, bool $actual): bool
    {
        $active = $actual;

        if ($input->getOption('consent')) {
            $active = true;
        }

        if ($input->getOption('no-consent')) {
            $active = false;
        }

        return $active;
    }
}
