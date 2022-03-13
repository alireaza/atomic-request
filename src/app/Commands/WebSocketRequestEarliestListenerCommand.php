<?php

namespace AliReaza\Atomic\Commands;

use AliReaza\Atomic\Events\WebSocketRequestEvent;
use AliReaza\Atomic\Events\WebSocketResponseEvent;
use RdKafka;
use Swoole;
use Symfony\Component\HttpFoundation\Response;

class WebSocketRequestEarliestListenerCommand extends AbstractListenerCommand
{
    public function __invoke(): void
    {
        if (property_exists($this->listener->provider, 'conf') && $this->listener->provider->conf instanceof RdKafka\Conf) {
            $this->listener->provider->conf->set('auto.offset.reset', 'earliest');
        }

        $this->listenTo(WebSocketRequestEvent::class, function (WebSocketRequestEvent $request, string $event_id, string $correlation_id): void {
            $listener_process = new Swoole\Process(function (Swoole\Process $process) use ($request, $event_id, $correlation_id): void {
                $this->dispatcher->setCorrelationId($correlation_id);
                $this->dispatcher->setCausationId($event_id);

                $response = new WebSocketResponseEvent($request->content, Response::HTTP_OK, $request->fd, $request->sec);
                $this->dispatcher->dispatch($response);

                $process->exit(0);
            });

            $listener_process->start();
        });

        $this->listener->subscribe();
    }
}
