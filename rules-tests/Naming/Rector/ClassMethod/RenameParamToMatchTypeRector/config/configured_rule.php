<?php

declare(strict_types=1);

use Rector\Naming\Rector\ClassMethod\RenameParamToMatchTypeRector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(RenameParamToMatchTypeRector::class);
};
