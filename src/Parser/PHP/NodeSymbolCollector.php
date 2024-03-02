<?php

declare(strict_types=1);

namespace ComposerUnused\SymbolParser\Parser\PHP;

use ComposerUnused\SymbolParser\Parser\PHP\Strategy\StrategyInterface;
use Iterator;
use IteratorAggregate;
use JsonSerializable;
use PhpParser\Node;
use PhpParser\NodeAbstract;
use SplObjectStorage;
use stdClass;

use function array_filter;
use function get_class;
use function get_object_vars;
use function in_array;

use const ARRAY_FILTER_USE_KEY;

/**
 * Collect symbols, depending on one or more strategy, and track their origins.
 *
 * @author Laurent Laville
 * @implements IteratorAggregate<SplObjectStorage>
 * @template T of SplObjectStorage<stdClass>
 */
class NodeSymbolCollector extends ConsumedSymbolCollector implements IteratorAggregate, JsonSerializable
{
    /**
     * @var T
     */
    protected SplObjectStorage $nodeStack;

    /**
     * @var string[]
     */
    protected array $options;

    /**
     * @param StrategyInterface[] $strategies
     * @param string[] $options
     */
    public function __construct(array $strategies, array $options = ['nodeType'])
    {
        parent::__construct($strategies);
        $this->nodeStack = new SplObjectStorage();
        $this->options = $options;
    }

    /**
     * @inheritDoc
     */
    public function enterNode(Node $node)
    {
        $symbols = [];

        $this->followIncludes($node);

        foreach ($this->strategies as $strategy) {
            if (!$strategy->canHandle($node)) {
                continue;
            }

            $collectedSymbols = $strategy->extractSymbolNames($node);
            if (!empty($collectedSymbols)) {
                $symbols[] = $collectedSymbols;
            }

            $object = new stdClass();
            $object->strategy = get_class($strategy);
            $object->symbols = $collectedSymbols;
            $object->nodeAttributes = $this->getAttributes($node);

            $this->nodeStack->attach($object);
        }

        if (count($symbols) > 0) {
            $this->symbols = array_merge($this->symbols, ...$symbols);
        }

        return null;
    }

    /**
     * @link https://www.php.net/manual/en/class.iteratoraggregate
     */
    public function getIterator(): Iterator
    {
        return $this->nodeStack;
    }

    /**
     * Specify data which should be serialized to customize the JSON representation with json_encode()
     *
     * @return array<int, array<string, mixed>>
     * @link https://www.php.net/manual/en/class.jsonserializable
     */
    public function jsonSerialize(): array
    {
        $handledNodes = [];
        foreach ($this->nodeStack as $item) {
            $handledNodes[] = get_object_vars($item);
        }
        return $handledNodes;
    }

    /**
     * Filter node attributes to customize object storage representation
     * @return array<string, mixed>
     */
    protected function getAttributes(Node $node): array
    {
        /** @var NodeAbstract $node */
        $attributes = $node->jsonSerialize();

        if (!empty($this->options)) {
            $attributes = array_filter($attributes, function ($key) {
                return in_array($key, $this->options);
            }, ARRAY_FILTER_USE_KEY);
        }

        return $attributes;
    }
}
