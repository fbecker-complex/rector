<?php

declare(strict_types=1);

namespace Rector\NodeTypeResolver\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeVisitorAbstract;
use Rector\NodeTypeResolver\Node\AttributeKey;

final class FunctionMethodAndClassNodeVisitor extends NodeVisitorAbstract
{
    /**
     * @var string|null
     */
    private $methodName;

    /**
     * @var string|null
     */
    private $className;

    /**
     * @var ClassLike[]|null[]
     */
    private $classStack = [];

    /**
     * @var ClassMethod[]|null[]
     */
    private $methodStack = [];

    /**
     * @var ClassLike|null
     */
    private $classLike;

    /**
     * @var ClassMethod|null
     */
    private $classMethod;

    /**
     * @param Node[] $nodes
     * @return Node[]|null
     */
    public function beforeTraverse(array $nodes): ?array
    {
        $this->classLike = null;
        $this->className = null;
        $this->methodName = null;
        $this->classMethod = null;

        return null;
    }

    public function enterNode(Node $node): ?Node
    {
        $this->processClass($node);
        $this->processMethod($node);

        return $node;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof ClassLike) {
            $classLike = array_pop($this->classStack);
            $this->setClassNodeAndName($classLike);
        }

        if ($node instanceof ClassMethod) {
            $this->classMethod = array_pop($this->methodStack);
            $this->methodName = (string) $this->methodName;
        }

        return null;
    }

    private function processClass(Node $node): void
    {
        if ($node instanceof ClassLike) {
            $this->classStack[] = $this->classLike;
            $this->setClassNodeAndName($node);
        }

        $node->setAttribute(AttributeKey::CLASS_NODE, $this->classLike);
        $node->setAttribute(AttributeKey::CLASS_NAME, $this->className);
    }

    private function processMethod(Node $node): void
    {
        if ($node instanceof ClassMethod) {
            $this->methodStack[] = $this->classMethod;

            $this->classMethod = $node;
            $this->methodName = (string) $node->name;
        }

        $node->setAttribute(AttributeKey::METHOD_NAME, $this->methodName);
        $node->setAttribute(AttributeKey::METHOD_NODE, $this->classMethod);
    }

    private function setClassNodeAndName(?ClassLike $classLike): void
    {
        $this->classLike = $classLike;
        if (! $classLike instanceof ClassLike || $classLike->name === null) {
            $this->className = null;
        } elseif (property_exists($classLike, 'namespacedName')) {
            $this->className = $classLike->namespacedName->toString();
        } else {
            $this->className = (string) $classLike->name;
        }
    }
}
