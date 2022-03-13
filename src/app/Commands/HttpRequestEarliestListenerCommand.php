<?php

namespace AliReaza\Atomic\Commands;

use AliReaza\Atomic\Events\HttpRequestEvent;
use AliReaza\Atomic\Events\HttpResponseEvent;
use Symfony\Component\HttpFoundation\Response;
use RdKafka;
use Swoole;

class HttpRequestEarliestListenerCommand extends AbstractListenerCommand
{
    public function __invoke(): void
    {
        if (property_exists($this->listener->provider, 'conf') && $this->listener->provider->conf instanceof RdKafka\Conf) {
            $this->listener->provider->conf->set('auto.offset.reset', 'earliest');
        }

        $this->listenTo(HttpRequestEvent::class, function (HttpRequestEvent $request, string $event_id, string $correlation_id): void {
            $listener_process = new Swoole\Process(function (Swoole\Process $process) use ($request, $event_id, $correlation_id): void {
                $this->dispatcher->setCorrelationId($correlation_id);
                $this->dispatcher->setCausationId($event_id);

                $response = new HttpResponseEvent($request->content, Response::HTTP_OK);
                $this->dispatcher->dispatch($response);

                $process->exit(0);
            });

            $listener_process->start();
        });

        $this->listener->subscribe();
    }
}
