<?php

declare(strict_types=1);

use Murtukov\PHPCodeGenerator\Arrays\AssocArray;
use Murtukov\PHPCodeGenerator\Arrays\NumericArray;
use Murtukov\PHPCodeGenerator\Call;
use Murtukov\PHPCodeGenerator\Comments\DocBlock;
use Murtukov\PHPCodeGenerator\ControlStructures\IfElse;
use Murtukov\PHPCodeGenerator\Functions\Argument;
use Murtukov\PHPCodeGenerator\Instance;
use Murtukov\PHPCodeGenerator\PhpFile;
use Murtukov\PHPCodeGenerator\Text;

require __DIR__ . '/vendor/autoload.php';


$file = new PhpFile("Mutation.php");
$class = $file->createClass('GetPussies')
    ->setFinal()
    ->addConst('NAME', "'Mutation'");

$class->createDocBlock("Never use this class pls.");
$constructor = $class->createConstructor();
$constructor->addArgument(Argument::create('name', 'string'));


$inputValidator = Instance::createMultiline(
    'InputValidator',
    Instance::create('Assert\NotNull', false),
    Instance::create(
        'Assert\Range',
        new NumericArray(['min' => 6, 'max' => 24]),
        Instance::createMultiline(
            'Assert\Malakos',
            "Matroskin",
            '$chibi'
        )
    )
);

$val = 15;

$array = [
    "names" => ["Timur", "Jeremiyah"],
    "age" => 25
];

$array = AssocArray::create()
    ->ifTrue(fn() => isset($array['names'][0]))->addItem("name", "Timur")
;

//$constructor->setReturn($inputValidator);

$prop = $class->createProperty('withDocBlock');
$prop->docBlock = new DocBlock("This just a simple property\n\n@var static");

// ELSE IF EXAMPLE
//echo IfElse::create('$name === 15')
//        ->append('$names = ', AssocArray::create(['name' => "Timur"]))
//    ->createElseIf(new Text('$name === 95'))
//        ->append('return null')
//    ->end()
//    ->createElseIf('$name === 95')
//        ->append('return null')
//    ->end()
//    ->createElse()
//        ->append('$x = 95')
//        ->append('return false')
//    ->end();

//echo $ifElse;

// CALL EXAMPLE
$call = new Call();

$notNull = $call(AssocArray::class)::notNull();
$string = $call('Type')::string();

$notNull->addArgumentAtFirst($string);

echo $notNull;
