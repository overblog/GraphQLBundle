<?php
/*
 * This file is part of the NelmioApiDocBundle package.
 *
 * (c) Nelmio
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Overblog\GraphQLBundle\Definition\Builder;

use GraphQL\Type\Definition\Type as GraphQLType;

use Nelmio\ApiDocBundle\Model\Model;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormConfigBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Component\PropertyInfo\Type;
/**
 * @internal
 */
final class FormDescriber
{
    
    const SIMPLE_TYPES = [
        'text'=>'string',
        'number'=>'float',
        'integer'=>'int',
        'date'=>'string',
        'datetime'=>'string',
        'password'=>'string',
        'checkbox'=>'boolean',
    ];
    
    private $formFactory;
    public function __construct(FormFactoryInterface $formFactory = null)
    {
        $this->formFactory = $formFactory;
    }
    public function describe($class)
    {
        if (method_exists(AbstractType::class, 'setDefaultOptions')) {
            throw new \LogicException('symfony/form < 3.0 is not supported, please upgrade to an higher version to use a form as a model.');
        }
        if (null === $this->formFactory) {
            throw new \LogicException('You need to enable forms in your application to use a form as a model.');
        }
        $form = $this->formFactory->create($class, null, []);
        return $this->parseForm($form);
    }

    private function parseForm(FormInterface $form)
    {
        $fields = [];
        foreach ($form as $name => $child) {
            $config = $child->getConfig();
            $required = $config->getRequired();
            $field = [
                'name'=>$name,
                'type'=>$this->getType($config, $required),
                
            ];
            $fields[] = $field;
        }
        
        return $fields;
    }
    
    private function getType(FormConfigBuilderInterface $config, $required)
    {
        $type = $config->getType();
        
        if (!$builtinFormType = $this->getBuiltinFormType($type)) {
            // if form type is not builtin in Form component.
            $type = get_class($type->getInnerType());
            //TODO : Create nested type
            return $type;
        }
        
        $blockPrefix = $builtinFormType->getBlockPrefix();
        if(in_array($blockPrefix, array_keys(self::SIMPLE_TYPES))) {
            $typeName = self::SIMPLE_TYPES[$blockPrefix];
            $type = GraphQLType::{$typeName}();

            return $required ? GraphQLType::nonNull($type) : $type;
        }
        
        if ('choice' === $blockPrefix) {
            $multiple = $config->getOption('multiple');
            if (($choices = $config->getOption('choices')) && is_array($choices) && count($choices)) {
                $enums = array_values($choices);
                if ($this->isNumbersArray($enums)) {
                    $type = 'number';
                } elseif ($this->isBooleansArray($enums)) {
                    $type = 'boolean';
                } else {
                    $type = 'string';
                } 
                $enumName = $this->createEnum($config->getName(), $enums);
                if ($multiple) {
                    return sprintf("[%s]%s", $enumName, $required?"!":"");
                }
                return sprintf("[%s]%s", $enumName, $required?"!":"");
            }
            if ($multiple) {
                return sprintf("[String]%s", $required?"!":"");
            }
            return sprintf("String%s", $required?"!":"");
        }

        if ('repeated' === $blockPrefix) {
            $property->setType('object');
            $property->setRequired([$config->getOption('first_name'), $config->getOption('second_name')]);
            $subType = $config->getOption('type');
            foreach (['first', 'second'] as $subField) {
                $subName = $config->getOption($subField.'_name');
                $subForm = $this->formFactory->create($subType, null, array_merge($config->getOption('options'), $config->getOption($subField.'_options')));
                $this->findFormType($subForm->getConfig(), $property->getProperties()->get($subName));
            }
        }
        if ('collection' === $blockPrefix) {
            $subType = $config->getOption('entry_type');
            $subOptions = $config->getOption('entry_options');
            $subForm = $this->formFactory->create($subType, null, $subOptions);
            $property->setType('array');
            $itemsProp = $property->getItems();
            $this->findFormType($subForm->getConfig(), $itemsProp);
            //break;
        }
        // The DocumentType is bundled with the DoctrineMongoDBBundle
        if ('entity' === $blockPrefix || 'document' === $blockPrefix) {
            $entityClass = $config->getOption('class');
            if ($config->getOption('multiple')) {
                $property->setFormat(sprintf('[%s id]', $entityClass));
                $property->setType('array');
                $property->getItems()->setType('string');
            } else {
                $property->setType('string');
                $property->setFormat(sprintf('%s id', $entityClass));
            }
            //break;
        }
    }

    /**
     * Finds and sets the schema type on $property based on $config info.
     *
     * Returns true if a native Swagger type was found, false otherwise
     *
     * @param FormConfigBuilderInterface $config
     * @param                            $property
     */
    private function findFormType(FormConfigBuilderInterface $config, $property)
    {
        $type = $config->getType();
        if (!$builtinFormType = $this->getBuiltinFormType($type)) {
            // if form type is not builtin in Form component.
            $model = new Model(new Type(Type::BUILTIN_TYPE_OBJECT, false, get_class($type->getInnerType())));
            $property->setRef($this->modelRegistry->register($model));
            return;
        }
        do {
            $blockPrefix = $builtinFormType->getBlockPrefix();
            if ('text' === $blockPrefix) {
                $property->setType('string');
                break;
            }
            if ('number' === $blockPrefix) {
                $property->setType('number');
                break;
            }
            if ('integer' === $blockPrefix) {
                $property->setType('integer');
                break;
            }
            if ('date' === $blockPrefix) {
                $property->setType('string');
                $property->setFormat('date');
                break;
            }
            if ('datetime' === $blockPrefix) {
                $property->setType('string');
                $property->setFormat('date-time');
                break;
            }
            if ('choice' === $blockPrefix) {
                if ($config->getOption('multiple')) {
                    $property->setType('array');
                } else {
                    $property->setType('string');
                }
                if (($choices = $config->getOption('choices')) && is_array($choices) && count($choices)) {
                    $enums = array_values($choices);
                    if ($this->isNumbersArray($enums)) {
                        $type = 'number';
                    } elseif ($this->isBooleansArray($enums)) {
                        $type = 'boolean';
                    } else {
                        $type = 'string';
                    }
                    if ($config->getOption('multiple')) {
                        $property->getItems()->setType($type)->setEnum($enums);
                    } else {
                        $property->setType($type)->setEnum($enums);
                    }
                }
                break;
            }
            if ('checkbox' === $blockPrefix) {
                $property->setType('boolean');
                break;
            }
            if ('password' === $blockPrefix) {
                $property->setType('string');
                $property->setFormat('password');
                break;
            }
            if ('repeated' === $blockPrefix) {
                $property->setType('object');
                $property->setRequired([$config->getOption('first_name'), $config->getOption('second_name')]);
                $subType = $config->getOption('type');
                foreach (['first', 'second'] as $subField) {
                    $subName = $config->getOption($subField.'_name');
                    $subForm = $this->formFactory->create($subType, null, array_merge($config->getOption('options'), $config->getOption($subField.'_options')));
                    $this->findFormType($subForm->getConfig(), $property->getProperties()->get($subName));
                }
                break;
            }
            if ('collection' === $blockPrefix) {
                $subType = $config->getOption('entry_type');
                $subOptions = $config->getOption('entry_options');
                $subForm = $this->formFactory->create($subType, null, $subOptions);
                $property->setType('array');
                $itemsProp = $property->getItems();
                $this->findFormType($subForm->getConfig(), $itemsProp);
                break;
            }
            // The DocumentType is bundled with the DoctrineMongoDBBundle
            if ('entity' === $blockPrefix || 'document' === $blockPrefix) {
                $entityClass = $config->getOption('class');
                if ($config->getOption('multiple')) {
                    $property->setFormat(sprintf('[%s id]', $entityClass));
                    $property->setType('array');
                    $property->getItems()->setType('string');
                } else {
                    $property->setType('string');
                    $property->setFormat(sprintf('%s id', $entityClass));
                }
                break;
            }
        } while ($builtinFormType = $builtinFormType->getParent());
    }
    /**
     * @param array $array
     *
     * @return bool true if $array contains only numbers, false otherwise
     */
    private function isNumbersArray(array $array): bool
    {
        foreach ($array as $item) {
            if (!is_numeric($item)) {
                return false;
            }
        }
        return true;
    }
    /**
     * @param array $array
     *
     * @return bool true if $array contains only booleans, false otherwise
     */
    private function isBooleansArray(array $array): bool
    {
        foreach ($array as $item) {
            if (!is_bool($item)) {
                return false;
            }
        }
        return true;
    }
    /**
     * @param ResolvedFormTypeInterface $type
     *
     * @return ResolvedFormTypeInterface|null
     */
    private function getBuiltinFormType(ResolvedFormTypeInterface $type)
    {
        do {
            $class = get_class($type->getInnerType());
            if (FormType::class === $class) {
                return null;
            }
            if ('entity' === $type->getBlockPrefix() || 'document' === $type->getBlockPrefix()) {
                return $type;
            }
            if (0 === strpos($class, 'Symfony\Component\Form\Extension\Core\Type\\')) {
                return $type;
            }
        } while ($type = $type->getParent());
        return null;
    }
}