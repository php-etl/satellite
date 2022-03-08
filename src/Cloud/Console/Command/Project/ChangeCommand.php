<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Console\Command\Project;

use Gyroscops\Api;
use Kiboko\Component\Satellite;
use Symfony\Component\Console;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

final class ChangeCommand extends Console\Command\Command
{
    protected static $defaultName = 'project:change';

    protected function configure(): void
    {
        $this->setDescription('Sends configuration to the Gyroscops API.');
        $this->addOption('url', 'u', mode: Console\Input\InputArgument::OPTIONAL, description: 'Base URL of the cloud instance', default: 'https://app.gyroscops.com');
        $this->addOption('beta', mode: Console\Input\InputOption::VALUE_NONE, description: 'Shortcut to set the cloud instance to https://beta.gyroscops.com');
        $this->addOption('ssl', mode: Console\Input\InputOption::VALUE_NEGATABLE, description: 'Enable or disable SSL');

        $this->addArgument('project-id', mode: Console\Input\InputArgument::OPTIONAL, description: 'Project identifier');
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
        } else if ($input->getOption('url')) {
            $url = $input->getOption('url');
            $ssl = $input->getOption('ssl') ?? true;
        } else {
            $url = 'https://gyroscops.com';
            $ssl = $input->getOption('ssl') ?? true;
        }

        $auth = new Satellite\Cloud\Auth();
        try {
            $token = $auth->token($url);
        } catch (\OutOfBoundsException) {
            $style->error(sprintf('Your credentials were not found, please run <info>%s login</>.', $input->getFirstArgument()));
            return self::FAILURE;
        }

        $httpClient = HttpClient::createForBaseUri(
            $url,
            [
                'verify_peer' => $ssl,
                'auth_bearer' => $token
            ]
        );

        $psr18Client = new Psr18Client($httpClient);
        $client = Api\Client::create($psr18Client);

        $context = new Satellite\Cloud\Context();

        if ($input->getArgument('project-id')) {
            try {
                $project = $client->getProjectItem($input->getArgument('project-id'));
            } catch (Api\Exception\GetProjectItemNotFoundException) {
                $style->error(['The provided project identifier was not found.']);
                $style->writeln(['Please double check your input or run <info>cloud project:list</> command.']);

                return self::FAILURE;
            }

            $context->changeProject(new Satellite\Cloud\DTO\ProjectId($project->getId()));
            $context->dump();

            $style->success('The project has been successfully changed.');

            return self::SUCCESS;
        }

        $projects = $client->apiOrganizationsProjectsGetSubresourceOrganizationSubresource($context->organization()->asString());

        if (\count($projects) <= 0) {
            $style->note('The current organization has ho projects declared');
            $style->writeln('You may want to declare a new project with <info>cloud project:create</>.');

            return self::FAILURE;
        }

        $choices = [];
        foreach ($projects as $project) {
            $choices[$project->getId()] = $project->getName();
        }

        try {
            $currentProject = $context->project()->asString();
        } catch (Satellite\Cloud\NoProjectSelectedException) {
            $currentProject = null;
        }

        $choice = $style->choice('Choose your project:', $choices, $currentProject);

        $context->changeProject(new Satellite\Cloud\DTO\ProjectId($choice));

        $context->dump();

        $style->success('The project has been successfully changed.');

        return self::SUCCESS;
    }
}
