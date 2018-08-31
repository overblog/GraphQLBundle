<?php

namespace Overblog\GraphQLBundle\Command;

use GraphQL\Type\Introspection;
use GraphQL\Utils\SchemaPrinter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class GraphQLDumpSchemaCommand extends Command
{
    use RequestExecutorLazyLoaderTrait;

    /** @var string */
    private $baseExportPath;

    public function __construct($baseExportPath)
    {
        parent::__construct();
        $this->baseExportPath = $baseExportPath;
    }

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
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $file = $this->createFile($input);
        $io->success(\sprintf('GraphQL schema "%s" was successfully dumped.', \realpath($file)));
    }

    private function createFile(InputInterface $input)
    {
        $format = \strtolower($input->getOption('format'));
        $schemaName = $input->getOption('schema');

        $file = $input->getOption('file') ?: $this->baseExportPath.\sprintf('/../var/schema%s.%s', $schemaName ? '.'.$schemaName : '', $format);

        switch ($format) {
            case 'json':
                $request = [
                    // TODO(mcg-web): remove silence deprecation notices after removing webonyx/graphql-php <= 0.11
                    'query' => @Introspection::getIntrospectionQuery(false),
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
