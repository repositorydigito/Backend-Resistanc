<?php

namespace App\Extensions;

use Dedoc\Scramble\Support\RouteInfo;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Types\StringType;

class AddAuthorizationHeader extends OperationExtension
{
    public function handle(Operation $operation, RouteInfo $routeInfo)
    {
        // Solo agregar el header Authorization a rutas protegidas
        $route = $routeInfo->route;
        $middlewares = $route->gatherMiddleware();

        if (in_array('auth:sanctum', $middlewares)) {
            $operation->addParameters([
                Parameter::make('Authorization', 'header')
                    ->setSchema(
                        Schema::fromType(new StringType())
                    )
                    ->required(true)
                    ->example("Bearer 9|RRZPrI7wx5baofjy3wFT4RGfp87DnO3UyrWYjCwGce5031ab")
                    ->description("Token de autorización Bearer. Obtén tu token haciendo login en /api/auth/login")
            ]);
        }
    }
}
