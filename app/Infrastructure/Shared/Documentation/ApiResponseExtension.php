<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Documentation;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\RouteInfo;

final class ApiResponseExtension extends OperationExtension
{
    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        $middlewares = $routeInfo->route->middleware();

        if (in_array('auth.jwt', $middlewares, true)) {
            $operation->addResponse(
                Response::make(401)
                    ->setDescription('Não autenticado')
                    ->setContent('application/json', $this->errorSchema('AUTHENTICATION_ERROR'))
            );

            $operation->addResponse(
                Response::make(403)
                    ->setDescription('Não autorizado')
                    ->setContent('application/json', $this->errorSchema('AUTHORIZATION_ERROR'))
            );
        }

        $operation->addResponse(
            Response::make(422)
                ->setDescription('Erro de validação')
                ->setContent('application/json', $this->validationErrorSchema())
        );
    }

    private function errorSchema(string $code): Schema
    {
        $errorObject = (new ObjectType)
            ->addProperty('code', (new StringType)->default($code))
            ->addProperty('message', new StringType);

        $errorsArray = (new ArrayType)->setItems($errorObject);

        $responseObject = (new ObjectType)->addProperty('errors', $errorsArray);

        return Schema::fromType($responseObject);
    }

    private function validationErrorSchema(): Schema
    {
        $errorObject = (new ObjectType)
            ->addProperty('code', (new StringType)->default('VALIDATION_ERROR'))
            ->addProperty('message', new StringType)
            ->addProperty('field', (new StringType)->nullable(true));

        $errorsArray = (new ArrayType)->setItems($errorObject);

        $responseObject = (new ObjectType)->addProperty('errors', $errorsArray);

        return Schema::fromType($responseObject);
    }
}
