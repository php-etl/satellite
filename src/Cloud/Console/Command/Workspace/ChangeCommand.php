<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Console\Command\Workspace;

use Gyroscops\Api;
use Kiboko\Component\Satellite;
use Kiboko\Component\Satellite\Cloud\AccessDeniedException;
use Symfony\Component\Console;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

final class ChangeCommand extends Console\Command\Command
{
    protected static $defaultName = 'workspace:change';

    protected function configure(): void
    {
        $this->setDescription('Sends configuration to the Gyroscops API.');
        $this->addOption('url', 'u', mode: Console\Input\InputArgument::OPTIONAL, description: 'Base URL of the cloud instance', default: 'https://app.gyroscops.com');
        $this->addOption('beta', mode: Console\Input\InputOption::VALUE_NONE, description: 'Shortcut to set the cloud instance to https://beta.gyroscops.com');
        $this->addOption('ssl', mode: Console\Input\InputOption::VALUE_NEGATABLE, description: 'Enable or disable SSL');

        $this->addArgument('workspace-id', mode: Console\Input\InputArgument::OPTIONAL, description: 'Workspace identifier');
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): int
    {
        $style = new Console\Style\SymfonyStyle(
            $input,
            $output,
        );

        if ($input->getOption('beta')) {
            $url = 'https://beta.gyroscops.com';
            $ssl = $input->getOption('ssl') ?? true;
        } elseif ($input->getOption('url')) {
            $url = $input->getOption('url');
            $ssl = $input->getOption('ssl') ?? true;
        } else {
            $url = 'https://gyroscops.com';
            $ssl = $input->getOption('ssl') ?? true;
        }

        $auth = new Satellite\Cloud\Auth();
        try {
            $token = $auth->token($url);
        } catch (AccessDeniedException) {
            $style->error('Your credentials were not found, please run <info>cloud login</>.');

            return self::FAILURE;
        }

        $httpClient = HttpClient::createForBaseUri(
            $url,
            [
                'verify_peer' => $ssl,
                'auth_bearer' => $token,
            ]
        );

        $psr18Client = new Psr18Client($httpClient);
        $client = Api\Client::create($psr18Client);
        $context = new Satellite\Cloud\Context($client, $auth, $url);

        if ($input->getArgument('workspace-id')) {
            try {
                $workspace = $client->getWorkspaceItem($input->getArgument('workspace-id'));
            } catch (Api\Exception\GetWorkspaceItemNotFoundException) {
                $style->error(['The provided workspace identifier was not found.']);
                $style->writeln(['Please double check your input or run <info>cloud workspace:list</> command.']);

                return self::FAILURE;
            }

            $workspace = new Satellite\Cloud\DTO\WorkspaceId($workspace->getId());
            $context->changeWorkspace($workspace);

            $style->success('The workspace has been successfully changed.');

            return self::SUCCESS;
        }

        $workspaces = $client->apiOrganizationsWorkspacesGetSubresourceOrganizationSubresource($context->organization()->asString());

        if ($workspaces === null || \count($workspaces) <= 0) {
            $style->note('The current organization has no workspaces declared');
            $style->writeln('You may want to declare a new workspace with <info>cloud workspace:create</>.');

            return self::FAILURE;
        }

        $choices = [];
        foreach ($workspaces as $workspace) {
            $choices[$workspace?->getId()] = $workspace?->getName();
        }

        $currentWorkspace = $context->workspace();

        $choice = $style->choice('Choose your workspace:', $choices, $currentWorkspace->asString());

        $workspace = new Satellite\Cloud\DTO\WorkspaceId($choice);
        $context->changeWorkspace($workspace);

        $style->success('The workspace has been successfully changed.');

        return self::SUCCESS;
    }
}
