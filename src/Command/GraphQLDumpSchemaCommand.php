<?php

declare(strict_types=1);

namespace Overblog\GraphQLBundle\Command;

use GraphQL\Type\Introspection;
use GraphQL\Utils\SchemaPrinter;
use Overblog\GraphQLBundle\Request\Executor as RequestExecutor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class GraphQLDumpSchemaCommand extends Command
{
    /** @var RequestExecutor */
    private $requestExecutor;

    /** @var string */
    private $baseExportPath;

    public function __construct($baseExportPath, RequestExecutor $requestExecutor)
    {
        parent::__construct();
        $this->baseExportPath = $baseExportPath;
        $this->requestExecutor = $requestExecutor;
    }

    public function getRequestExecutor(): RequestExecutor
    {
        return $this->requestExecutor;
    }

    protected function configure(): void
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
                'The schema format to generate ("graphql" or "json").',
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
            ->addOption(
                'with-descriptions',
                null,
                InputOption::VALUE_NONE,
                'Dump schema including descriptions.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = $this->createFile($input);
        $io->success(\sprintf('GraphQL schema "%s" was successfully dumped.', \realpath($file)));

        return 0;
    }

    private function createFile(InputInterface $input)
    {
        $format = \strtolower($input->getOption('format'));
        $schemaName = $input->getOption('schema');
        $includeDescription = $input->getOption('with-descriptions');

        $file = $input->getOption('file') ?: $this->baseExportPath.\sprintf('/var/schema%s.%s', $schemaName ? '.'.$schemaName : '', $format);

        switch ($format) {
            case 'json':
                $request = [
                    'query' => Introspection::getIntrospectionQuery(['descriptions' => $includeDescription]),
                    'variables' => [],
                    'operationName' => null,
                ];

                $modern = $this->useModernJsonFormat($input);

                $result = $this->getRequestExecutor()
                    ->execute($schemaName, $request)
                    ->toArray();

                $content = \json_encode($modern ? $result : $result['data'], \JSON_PRETTY_PRINT);
                break;

            case 'graphql':
                $content = SchemaPrinter::doPrint($this->getRequestExecutor()->getSchema($schemaName));
                break;

            default:
                throw new \InvalidArgumentException(\sprintf('Unknown format %s.', \json_encode($format)));
        }

        \file_put_contents($file, $content);

        return $file;
    }

    private function useModernJsonFormat(InputInterface $input)
    {
        $modern = $input->getOption('modern');
        $classic = $input->getOption('classic');
        if ($modern && $classic) {
            throw new \InvalidArgumentException('"modern" and "classic" options should not be used together.');
        }

        return true === $modern;
    }
}
