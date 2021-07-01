<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\CastNotation\CastSpacesFixer;
use PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\Operator\ConcatSpaceFixer;
use PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->import(SetList::SYMFONY);
    $containerConfigurator->import(SetList::PSR_12);

    $services = $containerConfigurator->services();

    $services->set(ArraySyntaxFixer::class)
        ->call('configure', [['syntax' => 'short']]);

    $services->set(CastSpacesFixer::class)
        ->call('configure', [['space' => 'none']]);

    $services->set(ConcatSpaceFixer::class)
        ->call('configure', [['spacing' => 'none']]);

    $services->set(BinaryOperatorSpacesFixer::class)
        ->call('configure', [[
            'operators' => [
                '='  => 'align',
                '=>' => 'align',
            ],
        ]]);

    $parameters = $containerConfigurator->parameters();

    $parameters->set(Option::SKIP, [
        NoSuperfluousPhpdocTagsFixer::class,
    ]);
};
