<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class WorkflowCommand extends Command
{
    private $output;

    protected function configure()
    {
        $this->setName('workflow:github');
        $this->setDescription('Create workflow on github project');
        $this->addArgument('token', InputArgument::REQUIRED, 'token github');
        $this->addArgument('orga', InputArgument::REQUIRED, 'name of organisation');
        $this->addArgument('repo', InputArgument::OPTIONAL, 'name of repo');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $orga = $input->getArgument('orga');
        $repo = $input->getArgument('repo', null);
        $this->output = $output;

        $labelsConfig = Yaml::parse(file_get_contents('config/labels.yml'));
        $labelsConfig = $labelsConfig['labels'];

        $client = new \Github\Client(
                new \Github\HttpClient\CachedHttpClient(array('cache_dir' => 'app/cache/github-api-cache'))
            );

        $client->authenticate($input->getArgument('token'), \Github\Client::AUTH_HTTP_TOKEN);

        $organizationApi = $client->api('organization');

        $paginator = new \Github\ResultPager($client);
        $parameters = array($orga, 'all');
        $result = $paginator->fetchAll($organizationApi, 'repositories', $parameters);

        do {
            foreach ($result as $repos) {
                if (!is_null($repo) && $repo !== $repos['name']) {
                    continue;
                }

                if (is_null($repo)) {
                    $helper = $this->getHelper('question');
                    $question = new ConfirmationQuestion('Faire la modification pour le projet ' . $repos['name'] .  ' ?', false);

                    if (!$helper->ask($input, $output, $question)) {
                        continue;
                    }
                }

                $this->prepareLabels($client, $orga, $repos, $labelsConfig);
                $this->prepareContributing($client, $orga, $repos);
                $this->preparePullRequestTemplate($client, $orga, $repos);
            }

            $result = $paginator->fetchNext();
        } while ($paginator->hasNext());
    }

    private function prepareContributing($client, $orga, $repos)
    {
        try {
            $content = $client->api('repo')->contents()->show($orga, $repos['name'], '.github/CONTRIBUTING.md', 'master');
        } catch (\Exception $e) {
            $content = null;
        }

        $newPullRequestTemplate = file_get_contents('config/CONTRIBUTING.md');
        $commit = true;
        $update = false;

        if ($content) {
            $pullRequestTemplate = file_get_contents($content['download_url']);

            if ($newPullRequestTemplate === $pullRequestTemplate) {
                $commit = false;
            } else {
                $update = true;
            }
        }

        if ($commit) {
            $master = $client->api('repo')->branches($orga, $repos['name'], 'master');
            $branch = 'contributing-update';

            try {
                $client->api('git')->references()->create($orga, $repos['name'],
                    array(
                        'ref' => 'refs/heads/' . $branch,
                        'sha' => $master['commit']['sha']
                    )
                );
            } catch (\Exception $e) {
                $this->output->writeln('<error>' . $e->getMessage() . '</error>');
            }

            if ($update) {
                try {
                    $client->api('repo')->contents()->update(
                         $orga,
                         $repos['name'],
                         '.github/CONTRIBUTING.md',
                         file_get_contents('config/CONTRIBUTING.md'),
                         'chore: update contributing',
                         $content['sha'],
                         'refs/heads/' . $branch
                     );
                } catch (\Exception $e) {
                    $this->output->writeln('<error>' . $e->getMessage() . '</error>');
                    return;
                }
            } else {
                try {
                    $client->api('repo')->contents()->create(
                        $orga,
                        $repos['name'],
                        '.github/CONTRIBUTING.md',
                        file_get_contents('config/CONTRIBUTING.md'),
                        'chore: create contributing file',
                        'refs/heads/' . $branch
                    );
                } catch (\Exception $e) {
                    $this->output->writeln('<error>' . $e->getMessage() . '</error>');
                    return;
                }
            }

            try {
                $request = $client->api('pull_request')->create($orga, $repos['name'], array(
                    'base'  => 'master',
                    'head'  => $branch,
                    'title' => 'Mise en place du contributing',
                    'body'  => "# Description \n Mise en place du fichier de contributing"
                ));
            } catch (\Exception $e) {
                $this->output->writeln('<error>' . $e->getMessage() . '</error>');
                return;
            }

            if (isset($request['number'])) {
                $client->api('issue')->update($orga, $repos['name'], $request['number'],
                    array('labels' => array('type/chore'))
                );
            }
        }
    }

    private function preparePullRequestTemplate($client, $orga, $repos)
    {
        try {
            $content = $client->api('repo')->contents()->show($orga, $repos['name'], '.github/PULL_REQUEST_TEMPLATE.md', 'master');
        } catch (\Exception $e) {
            $content = null;
        }

        $newPullRequestTemplate = file_get_contents('config/PULL_REQUEST_TEMPLATE.md');
        $commit = true;
        $update = false;

        if ($content) {
            $pullRequestTemplate = file_get_contents($content['download_url']);

            if ($newPullRequestTemplate === $pullRequestTemplate) {
                $commit = false;
            } else {
                $update = true;
            }
        }

        if ($commit) {
            $master = $client->api('repo')->branches($orga, $repos['name'], 'master');
            $branch = 'pull_request_template-update';

            try {
                $client->api('git')->references()->create($orga, $repos['name'],
                    array(
                        'ref' => 'refs/heads/' . $branch,
                        'sha' => $master['commit']['sha']
                    )
                );
            } catch (\Exception $e) {
                $this->output->writeln('<error>' . $e->getMessage() . '</error>');
            }

            if ($update) {
                try {
                    $client->api('repo')->contents()->update(
                         $orga,
                         $repos['name'],
                         '.github/PULL_REQUEST_TEMPLATE.md',
                         file_get_contents('config/PULL_REQUEST_TEMPLATE.md'),
                         'chore: update pull_request_template',
                         $content['sha'],
                         'refs/heads/' . $branch
                     );
                } catch (\Exception $e) {
                    $this->output->writeln('<error>' . $e->getMessage() . '</error>');
                    return;
                }
            } else {
                try {
                    $client->api('repo')->contents()->create(
                        $orga,
                        $repos['name'],
                        '.github/PULL_REQUEST_TEMPLATE.md',
                        file_get_contents('config/PULL_REQUEST_TEMPLATE.md'),
                        'chore: create pull_request_template file',
                        'refs/heads/' . $branch
                    );
                } catch (\Exception $e) {
                    $this->output->writeln('<error>' . $e->getMessage() . '</error>');
                    return;
                }
            }

            try {
                $request = $client->api('pull_request')->create($orga, $repos['name'], array(
                    'base'  => 'master',
                    'head'  => $branch,
                    'title' => 'Mise en place du pull_request_template',
                    'body'  => "# Description \n Mise en place du fichier de pull_request_template"
                ));
            } catch (\Exception $e) {
                $this->output->writeln('<error>' . $e->getMessage() . '</error>');
                return;
            }

            if (isset($request['number'])) {
                $client->api('issue')->update($orga, $repos['name'], $request['number'],
                    array('labels' => array('type/chore'))
                );
            }
        }
    }

    private function prepareLabels($client, $orga, $repos, $labelsConfig)
    {
        $labels = $client->api('issue')->labels()->all($orga, $repos['name']);

        $labelExist = array();
        foreach ($labels as $label) {
            if (!array_key_exists($label['name'], $labelsConfig)) {
                $client->api('issue')->labels()->deleteLabel($orga, $repos['name'], $label['name']);
            } else {
                $labelExist[$label['name']] = $labelsConfig[$label['name']];
                if ($label['color'] !== $labelsConfig[$label['name']]) {
                    $client->api('issue')->labels()->update($orga, $repos['name'], $label['name'], $label['name'], $labelsConfig[$label['name']]);
                }
            }
        }

        foreach ($labelsConfig as $name => $color) {
            if (!array_key_exists($name, $labelExist)) {
                $client->api('issue')->labels()->create($orga, $repos['name'], array(
                    'name' => $name,
                    'color' => $color,
                ));
            }
        }
    }
}
