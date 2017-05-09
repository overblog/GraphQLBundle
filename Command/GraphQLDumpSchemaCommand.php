<?php

/*
 * This file is part of the OverblogGraphQLBundle package.
 *
 * (c) Overblog <http://github.com/overblog/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Overblog\GraphQLBundle\Command;

use GraphQL\Type\Introspection;
use GraphQL\Utils\SchemaPrinter;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GraphQLDumpSchemaCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('graphql:dump-schema')
            ->setAliases(['graph:dump-schema'])
            ->setDescription('Dumps GraphQL schema')
            ->addOption(
                'file',
                null,
                InputOption::VALUE_OPTIONAL,
                'Path to generate schema file.'
            )
            ->addOption(
                'schema',
                null,
                InputOption::VALUE_OPTIONAL,
                'The schema name to generate.'
            )
            ->addOption(
                'format',
                null,
                InputOption::VALUE_OPTIONAL,
                'The schema name to generate ("graphqls" or "json").',
                'json'
            )
            ->addOption(
                'modern',
                null,
                InputOption::VALUE_NONE,
                'Enabled modern json format: { "data": { "__schema": {...} } }.'
            )
            ->addOption(
                'classic',
                null,
                InputOption::VALUE_NONE,
                'Enabled classic json format: { "__schema": {...} }.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $file = $this->createFile($input);
        $io->success(sprintf('GraphQL schema "%s" was successfully dumped.', realpath($file)));
    }

    private function createFile(InputInterface $input)
    {
        $container = $this->getContainer();
        $format = strtolower($input->getOption('format'));
        $schemaName = $input->getOption('schema');
        $requestExecutor = $container->get('overblog_graphql.request_executor');
        $file = $input->getOption('file') ?: $container->getParameter('kernel.root_dir').sprintf('/../var/schema%s.%s', $schemaName ? '.'.$schemaName : '', $format);

        switch ($format) {
            case 'json':
                $request = [
                    'query' => Introspection::getIntrospectionQuery(false),
                    'variables' => [],
                    'operationName' => null,
                ];

                $modern = $this->useModernJsonFormat($input);

                $result = $requestExecutor
                    ->execute($request, [], $schemaName)
                    ->toArray();

                $content = json_encode($modern ? $result : $result['data'], \JSON_PRETTY_PRINT);
                break;

            case 'graphqls':
                $content = SchemaPrinter::doPrint($requestExecutor->getSchema($schemaName));
                break;

            default:
                throw new \InvalidArgumentException(sprintf('Unknown format %s.', json_encode($format)));
        }

        file_put_contents($file, $content);

        return $file;
    }

    private function useModernJsonFormat(InputInterface $input)
    {
        $modern = $input->getOption('modern');
        $classic = $input->getOption('classic');
        if ($modern && $classic) {
            throw new \InvalidArgumentException('"modern" and "classic" options should not be used together.');
        }

        // none chosen so fallback on default behavior
        if (!$modern && !$classic) {
            return 'modern' === $this->getContainer()->getParameter('overblog_graphql.versions.relay');
        }

        return $modern;
    }
}
