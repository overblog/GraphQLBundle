<?php

namespace Overblog\GraphQLBundle\Command;

use Overblog\GraphQLBundle\Resolver\MutationResolver;
use Overblog\GraphQLBundle\Resolver\ResolverInterface;
use Overblog\GraphQLBundle\Resolver\ResolverResolver;
use Overblog\GraphQLBundle\Resolver\TypeResolver;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DebugCommand extends Command
{
    private static $categories = ['type', 'mutation', 'resolver'];

    /**
     * @var TypeResolver
     */
    private $typeResolver;

    /**
     * @var MutationResolver
     */
    private $mutationResolver;

    /**
     * @var ResolverResolver
     */
    private $resolverResolver;

    public function __construct(
        TypeResolver $typeResolver,
        MutationResolver $mutationResolver,
        ResolverResolver $resolverResolver
    ) {
        parent::__construct();
        $this->typeResolver = $typeResolver;
        $this->mutationResolver = $mutationResolver;
        $this->resolverResolver = $resolverResolver;
    }

    protected function configure()
    {
        $this
            ->setName('graphql:debug')
            ->setAliases(['debug:graphql'])
            ->addOption(
                'category',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                sprintf('filter by a category (%s).', implode(', ', self::$categories))
            )
            ->setDescription('Display current GraphQL services (types, resolvers and mutations)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $categoriesOption = $input->getOption('category');
        $categoriesOption = is_array($categoriesOption) ? $categoriesOption : [$categoriesOption];
        $notAllowed = array_diff($categoriesOption, self::$categories);
        if (!empty($notAllowed)) {
            throw new \InvalidArgumentException(sprintf('Invalid category (%s)', implode(',', $notAllowed)));
        }

        $categories = empty($categoriesOption) ? self::$categories : $categoriesOption;

        $io = new SymfonyStyle($input, $output);
        $tableHeaders = ['id', 'aliases'];
        foreach ($categories as $category) {
            $io->title(sprintf('GraphQL %ss Services', ucfirst($category)));

            /** @var ResolverInterface $resolver */
            $resolver = $this->{$category.'Resolver'};

            $solutions = $this->retrieveSolutions($resolver);
            $this->renderTable($tableHeaders, $solutions, $io);
        }
    }

    private function renderTable(array $tableHeaders, array $solutions, SymfonyStyle $io)
    {
        $tableRows = [];
        foreach ($solutions as $id => &$options) {
            ksort($options['aliases']);
            $tableRows[] = [$id, implode("\n", $options['aliases'])];
        }
        $io->table($tableHeaders, $tableRows);
        $io->write("\n\n");
    }

    private function retrieveSolutions(ResolverInterface $resolver)
    {
        $data = [];
        foreach ($resolver->getSolutions() as $alias => $solution) {
            $options = $resolver->getSolutionOptions($alias);

            $id = $options['id'];
            if (!isset($data[$id]['aliases'])) {
                $data[$id]['aliases'] = [];
            }
            $data[$id]['aliases'][] = $options['alias'].(isset($options['method']) ? ' (method: '.$options['method'].')' : '');
        }
        ksort($data);

        return $data;
    }

    public static function getCategories()
    {
        return self::$categories;
    }
}
