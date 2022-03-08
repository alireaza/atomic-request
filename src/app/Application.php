<?php

namespace AliReaza\Atomic;

use AliReaza\Atomic\Commands\ListenerCommand;
use AliReaza\DependencyInjection\DependencyInjectionContainer as DIC;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class Application implements HttpKernelInterface
{
    private JsonResponse $response;

    public function __construct(private DIC $container)
    {
        $this->response = $this->container->resolve(JsonResponse::class);
    }

    public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): JsonResponse
    {
        if (php_sapi_name() === 'cli') {
            if (is_callable($command = $this->container->call(ListenerCommand::class))) {
                call_user_func($command);
            }

            exit();
        }

        $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);
        $this->response->setContent('');

        return $this->response->send();
    }
}
