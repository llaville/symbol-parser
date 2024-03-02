<?php

declare(strict_types=1);

/**
 * Try to find symbols with different strategy, on an input file.
 * Requires to install the `symfony/console` package as a dev dependency.
 *
 * @author Laurent Laville
 */

use ComposerUnused\SymbolParser\Parser\PHP\NameResolver;
use ComposerUnused\SymbolParser\Parser\PHP\NodeSymbolCollector;
use ComposerUnused\SymbolParser\Parser\PHP\Strategy;
use ComposerUnused\SymbolParser\Parser\PHP\SymbolNameParser;
use PhpParser\ParserFactory;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$inputDefinition = new InputDefinition();
$inputDefinition->addArgument(
    new InputArgument('file', InputArgument::REQUIRED)
);
$inputDefinition->addOption(
    new InputOption('debug', null, InputOption::VALUE_OPTIONAL, '', 'symbols')
);
$inputDefinition->addOption(
    new InputOption('strategy', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED)
);
$inputDefinition->addOption(
    new InputOption('node-attribute', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, '', [])
);
$inputDefinition->addOption(
    new InputOption('json-dump', null, InputOption::VALUE_NONE)
);

$input = new ArgvInput(null, $inputDefinition);
$debug = $input->getOption('debug');
$output = new ConsoleOutput();

$strategyDefinition = [
    'annotation' => function () {
        return new Strategy\AnnotationStrategy(
            new \PHPStan\PhpDocParser\Parser\ConstExprParser(),
            new \PHPStan\PhpDocParser\Lexer\Lexer()
        );
    },
    'define' => function () {
        return new Strategy\DefineStrategy();
    },
    'class-const' => function () {
        return new Strategy\ClassConstStrategy();
    },
    'const' => function () {
        return new Strategy\ConstStrategy();
    },
    'extends' => function () {
        return new Strategy\ExtendsParseStrategy();
    },
    'parameters' => function () {
        return new Strategy\FullQualifiedParameterStrategy();
    },
    'invocation' => function () {
        return new Strategy\FunctionInvocationStrategy();
    },
    'implements' => function () {
        return new Strategy\ImplementsParseStrategy();
    },
    'instanceof' => function () {
        return new Strategy\InstanceofStrategy();
    },
    'new' => function () {
        return new Strategy\NewStrategy();
    },
    'php-extension' => function () {
        return new Strategy\PhpExtensionStrategy(
            ['core', 'standard'],
            new \Psr\Log\NullLogger()
        );
    },
    'static' => function () {
        return new Strategy\StaticStrategy();
    },
    'typed-attributes' => function () {
        return new Strategy\TypedAttributeStrategy();
    },
    'use' => function () {
        return new Strategy\UseStrategy();
    },
    'used-extension' => function () {
        return new Strategy\UsedExtensionSymbolStrategy(
            ['core', 'standard'],
            new \Psr\Log\NullLogger()
        );
    },
];

if (in_array('*', $input->getOption('strategy'))) {
    $loadStrategy = array_keys($strategyDefinition);
} else {
    $loadStrategy = $input->getOption('strategy');
}

$strategies = [];

foreach ($loadStrategy as $strategyName) {
    if (isset($strategyDefinition[$strategyName])) {
        $strategyObject = $strategyDefinition[$strategyName]();
        if ($strategyObject instanceof Strategy\StrategyInterface) {
            $strategies[] = $strategyObject;
        }
    }
}

$nodeSymbolOptions = $input->getOption('node-attribute');

$symbolNameParser = new SymbolNameParser(
    (new ParserFactory())->createForNewestSupportedVersion(),
    new NameResolver(),
    $symbolCollector = new NodeSymbolCollector($strategies, $nodeSymbolOptions)
);

$file = $input->getArgument('file');
if (!file_exists($file)) {
    $output->writeln(sprintf('File "%s" provided does not exists.', $file));
    exit(1);
}
$code = file_get_contents($file);

$symbols = iterator_to_array($symbolNameParser->parseSymbolNames($code));

$output->writeln([
    sprintf('>>> <comment>Run SymbolNameParser with %s</comment>', get_class($symbolCollector)),
    '',
]);

$output->writeln([
    sprintf('>>> <comment>Symbols found :</comment> (%d)', count($symbols)),
    var_export($symbols, true)
]);

if (count($strategies) === 0) {
    $output->writeln([
        '<error>WARNING</error> : <comment>none strategy specified !</comment>',
        '',
    ]);
}

if (str_contains($debug, 'strategies')) {
    $output->writeln([
        sprintf('>>> <comment>Strategies used :</comment> (%d)', count($strategies)),
        var_export(array_map(fn($class) => get_class($class), $strategies), true),
        '',
    ]);
}

if (str_contains($debug, 'nodes')) {
    $handled = ($symbolCollector->getIterator());
    $output->writeln([
        sprintf('>>> <comment>Nodes handled :</comment> (%d)', count($handled)),
        var_export(iterator_to_array($handled), true),
        '',
    ]);
}
if ($input->getOption('json-dump')) {
    $output->writeln([
        sprintf('>>> <comment>Nodes dump :</comment> (%d)', count($symbolCollector->getIterator())),
        json_encode($symbolCollector, JSON_PRETTY_PRINT),
        '',
    ]);
}
