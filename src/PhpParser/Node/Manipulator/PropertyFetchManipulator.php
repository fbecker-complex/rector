<?php

declare(strict_types=1);

namespace Rector\Core\PhpParser\Node\Manipulator;

use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ErrorType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeWithClassName;
use Rector\Core\Exception\ShouldNotHappenException;
use Rector\NodeNameResolver\NodeNameResolver;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\NodeTypeResolver\NodeTypeResolver;

/**
 * Utils for PropertyFetch Node:
 * "$this->property"
 */
final class PropertyFetchManipulator
{
    /**
     * @var NodeTypeResolver
     */
    private $nodeTypeResolver;

    /**
     * @var ReflectionProvider
     */
    private $reflectionProvider;

    /**
     * @var NodeNameResolver
     */
    private $nodeNameResolver;

    public function __construct(
        NodeTypeResolver $nodeTypeResolver,
        ReflectionProvider $reflectionProvider,
        NodeNameResolver $nodeNameResolver
    ) {
        $this->nodeTypeResolver = $nodeTypeResolver;
        $this->reflectionProvider = $reflectionProvider;
        $this->nodeNameResolver = $nodeNameResolver;
    }

    public function isPropertyToSelf(PropertyFetch $propertyFetch): bool
    {
        if (! $this->nodeNameResolver->isName($propertyFetch->var, 'this')) {
            return false;
        }

        /** @var Class_|null $class */
        $class = $propertyFetch->getAttribute(AttributeKey::CLASS_NODE);
        if ($class === null) {
            return false;
        }

        foreach ($class->getProperties() as $property) {
            if (! $this->nodeNameResolver->areNamesEqual($property->props[0], $propertyFetch)) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function isMagicOnType(PropertyFetch $propertyFetch, Type $type): bool
    {
        $varNodeType = $this->nodeTypeResolver->resolve($propertyFetch);

        if ($varNodeType instanceof ErrorType) {
            return true;
        }

        if ($varNodeType instanceof MixedType) {
            return false;
        }

        if ($varNodeType->isSuperTypeOf($type)->yes()) {
            return false;
        }

        $nodeName = $this->nodeNameResolver->getName($propertyFetch);
        if ($nodeName === null) {
            return false;
        }

        return ! $this->hasPublicProperty($propertyFetch, $nodeName);
    }

    /**
     * Matches:
     * "$this->someValue = $<variableName>;"
     */
    public function isVariableAssignToThisPropertyFetch(Node $node, string $variableName): bool
    {
        if (! $node instanceof Assign) {
            return false;
        }

        if (! $node->expr instanceof Variable) {
            return false;
        }

        if (! $this->nodeNameResolver->isName($node->expr, $variableName)) {
            return false;
        }

        if (! $node->var instanceof PropertyFetch) {
            return false;
        }

        // must be local property
        return $this->nodeNameResolver->isName($node->var->var, 'this');
    }

    /**
     * @param string[] $propertyNames
     */
    public function isLocalPropertyOfNames(Node $node, array $propertyNames): bool
    {
        if (! $this->isLocalProperty($node)) {
            return false;
        }

        /** @var PropertyFetch $node */
        return $this->nodeNameResolver->isNames($node->name, $propertyNames);
    }

    public function isLocalProperty(Node $node): bool
    {
        if (! $node instanceof PropertyFetch) {
            return false;
        }

        return $this->nodeNameResolver->isName($node->var, 'this');
    }

    /**
     * @return PropertyFetch|StaticPropertyFetch|null
     */
    public function matchPropertyFetch(Node $node): ?Node
    {
        if ($node instanceof PropertyFetch) {
            return $node;
        }

        if ($node instanceof StaticPropertyFetch) {
            return $node;
        }

        if ($node instanceof ArrayDimFetch) {
            $nestedNode = $node->var;

            while ($nestedNode instanceof ArrayDimFetch) {
                $nestedNode = $nestedNode->var;
            }

            return $this->matchPropertyFetch($nestedNode);
        }

        return null;
    }

    /**
     * Matches:
     * "$this->someValue = $<variableName>;"
     */
    public function isToThisPropertyFetchOfSpecificNameAssign(Node $node, string $propertyName): bool
    {
        if (! $node instanceof Assign) {
            return false;
        }

        if (! $node->var instanceof PropertyFetch) {
            return false;
        }

        if (! $this->nodeNameResolver->isName($node->var->var, 'this')) {
            return false;
        }

        return $this->nodeNameResolver->isName($node->var->name, $propertyName);
    }

    private function hasPublicProperty(PropertyFetch $propertyFetch, string $propertyName): bool
    {
        $nodeScope = $propertyFetch->getAttribute(AttributeKey::SCOPE);
        if ($nodeScope === null) {
            throw new ShouldNotHappenException();
        }

        $propertyFetchType = $nodeScope->getType($propertyFetch->var);
        if (! $propertyFetchType instanceof TypeWithClassName) {
            return false;
        }

        $propertyFetchType = $propertyFetchType->getClassName();
        if (! $this->reflectionProvider->hasClass($propertyFetchType)) {
            return false;
        }

        $classReflection = $this->reflectionProvider->getClass($propertyFetchType);
        if (! $classReflection->hasProperty($propertyName)) {
            return false;
        }

        $propertyReflection = $classReflection->getProperty($propertyName, $nodeScope);

        return $propertyReflection->isPublic();
    }
}
