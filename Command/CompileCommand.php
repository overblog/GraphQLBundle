<?php

namespace Overblog\GraphQLBundle\Command;

use Overblog\GraphQLBundle\Generator\TypeGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CompileCommand extends Command
{
    private $typeGenerator;

    public function __construct(TypeGenerator $typeGenerator)
    {
        parent::__construct();
        $this->typeGenerator = $typeGenerator;
    }

    protected function configure()
    {
        $this
            ->setName('graphql:compile')
            ->setDescription('Generate types manually.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Types compilation starts</info>');
        $classes = $this->typeGenerator->compile(TypeGenerator::MODE_WRITE | TypeGenerator::MODE_OVERRIDE);
        $output->writeln('<info>Types compilation ends successfully</info>');
        if ($output->getVerbosity() >= Output::VERBOSITY_VERBOSE) {
            $io = new SymfonyStyle($input, $output);
            $io->title('Summary');
            $rows = [];
            foreach ($classes as $class => $path) {
                $rows[] = [$class, $path];
            }
            $io->table(['class', 'path'], $rows);
        }
    }
}
