<?php

declare(strict_types=1);

namespace Rector\Php71\Rector\TryCatch;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\TryCatch;
use Rector\Core\Rector\AbstractRector;
use Rector\Core\ValueObject\PhpVersionFeature;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see https://wiki.php.net/rfc/multiple-catch
 * @see \Rector\Tests\Php71\Rector\TryCatch\MultiExceptionCatchRector\MultiExceptionCatchRectorTest
 */
final class MultiExceptionCatchRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Changes multi catch of same exception to single one | separated.',
            [
                new CodeSample(
<<<'CODE_SAMPLE'
try {
    // Some code...
} catch (ExceptionType1 $exception) {
    $sameCode;
} catch (ExceptionType2 $exception) {
    $sameCode;
}
CODE_SAMPLE
                    ,
<<<'CODE_SAMPLE'
try {
   // Some code...
} catch (ExceptionType1 | ExceptionType2 $exception) {
   $sameCode;
}
CODE_SAMPLE
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [TryCatch::class];
    }

    /**
     * @param TryCatch $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! $this->isAtLeastPhpVersion(PhpVersionFeature::MULTI_EXCEPTION_CATCH)) {
            return null;
        }

        if (count($node->catches) < 2) {
            return null;
        }

        $catchKeysByContent = $this->collectCatchKeysByContent($node);
        /** @var Catch_[] $catchKeys */
        foreach ($catchKeysByContent as $catchKeys) {
            // no duplicates
            $count = count($catchKeys);
            if ($count < 2) {
                continue;
            }

            $collectedTypes = $this->collectTypesFromCatchedByIds($node, $catchKeys);

            /** @var Catch_ $firstCatch */
            $firstCatch = array_shift($catchKeys);
            $firstCatch->types = $collectedTypes;

            foreach ($catchKeys as $catchKey) {
                $this->removeNode($catchKey);
            }
        }

        return $node;
    }

    /**
     * @return array<string, Catch_[]>
     */
    private function collectCatchKeysByContent(TryCatch $tryCatch): array
    {
        $catchKeysByContent = [];
        foreach ($tryCatch->catches as $catch) {
            $catchContent = $this->print($catch->stmts);
            $catchKeysByContent[$catchContent][] = $catch;
        }

        return $catchKeysByContent;
    }

    /**
     * @param Catch_[] $catches
     * @return Name[]
     */
    private function collectTypesFromCatchedByIds(TryCatch $tryCatch, array $catches): array
    {
        $collectedTypes = [];

        foreach ($catches as $catch) {
            $collectedTypes = array_merge($collectedTypes, $catch->types);
        }

        return $collectedTypes;
    }
}
