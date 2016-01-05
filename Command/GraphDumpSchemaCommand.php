<?php

namespace Overblog\GraphBundle\Command;

use GraphQL\Type\Introspection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GraphDumpSchemaCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('graph:dump-schema')
            ->setDescription('Dumps GraphQL schema')
            ->addOption(
                'file',
                null,
                InputOption::VALUE_OPTIONAL,
                'Path to generate schema file.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output = new SymfonyStyle($input, $output);

        $request = [
            'query' => Introspection::getIntrospectionQuery(false),
            'variables' => [],
            'operationName' => null,
        ];

        $container = $this->getContainer();
        $result = $container
            ->get('overblog_graph.request_executor')
            ->execute($request)
            ->toArray();

        if (isset($result['errors'])) {
            foreach ($result['errors'] as $error) {
                $output->error($error['message']);
            }

            return 1;
        }

        $file = $input->getOption('file');
        if (empty($file)) {
            $file = $container->getParameter('kernel.root_dir') . '/../var/schema.json';
        }

        $schema = json_encode($result['data']);

        file_put_contents($file, $schema);

        $output->success(sprintf('GraphQL schema "%s" was successfully dumped.', realpath($file)));
    }
}
