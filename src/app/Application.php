<?php

namespace AliReaza\Atomic;

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
        if ($this->isCli()) {
            $this->runCommand();
            exit(0);
        }

        $this->response->setStatusCode(Response::HTTP_BAD_REQUEST);
        $this->response->setContent('');

        return $this->response->send();
    }

    private function isCli(): bool
    {
        return php_sapi_name() === 'cli';
    }

    private function runCommand(): void
    {
        $options = getopt('c:', ['command:']);

        if (!isset($options['c']) && !isset($options['command'])) {
            exit(0);
        }

        $command_name = $options['c'] ?? $options['command'];

        if (is_callable($command = $this->container->call($command_name))) {
            call_user_func($command);
        }
    }
}
