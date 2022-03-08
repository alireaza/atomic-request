<?php

namespace AliReaza\Atomic\Commands;

use AliReaza\Atomic\Events\HttpRequestEvent;
use AliReaza\Atomic\Events\HttpResponseEvent;
use AliReaza\Atomic\Events\WebSocketRequestEvent;
use AliReaza\Atomic\Events\WebSocketResponseEvent;
use AliReaza\EventDriven\EventDispatcher;
use AliReaza\EventDriven\ListenerProvider;
use RdKafka;
use Swoole;
use Symfony\Component\HttpFoundation\Response;

class ListenerCommand
{
    public function __construct(private EventDispatcher $dispatcher, private ListenerProvider $listener)
    {
    }

    public function __invoke()
    {
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

        $this->listenTo(WebSocketRequestEvent::class, function (WebSocketRequestEvent $request, string $event_id, string $correlation_id) {
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

    private function listenTo(string $event_class, callable $callback): void
    {
        if (property_exists($this->listener->provider, 'conf') && $this->listener->provider->conf instanceof RdKafka\Conf) {
            $this->listener->provider->conf->set('group.id', 'Request-' . date("YmdHis") . time() . rand(1111, 9999));
        }

        $this->listener->addListener($event_class, function (object $request, string $event_id, string $correlation_id) use ($callback): void {
            $callback($request, $event_id, $correlation_id);
        });
    }
}
