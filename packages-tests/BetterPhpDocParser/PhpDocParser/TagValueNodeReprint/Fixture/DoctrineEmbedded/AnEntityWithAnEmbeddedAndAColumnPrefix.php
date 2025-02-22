<?php
declare(strict_types=1);

namespace Rector\Tests\BetterPhpDocParser\PhpDocParser\TagValueNodeReprint\Fixture\DoctrineEmbedded;

use Doctrine\ORM\Mapping as ORM;
use Rector\Tests\BetterPhpDocParser\PhpDocParser\TagValueNodeReprint\Source\Embeddable;

final class AnEntityWithAnEmbeddedAndAColumnPrefix
{
    /**
     * @ORM\Embedded(class="Embeddable", columnPrefix="prefix_")
     */
    private $embedded;
}
