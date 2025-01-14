<?php

declare(strict_types=1);

namespace LaminasTest\Mvc;

use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\HttpMethodListener;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\Request;
use Laminas\Stdlib\Response;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Laminas\Mvc\HttpMethodListener
 */
class HttpMethodListenerTest extends TestCase
{
    /** @var HttpMethodListener */
    protected $listener;

    public function setUp(): void
    {
        $this->listener = new HttpMethodListener();
    }

    public function testConstructor(): void
    {
        $methods  = ['foo', 'bar'];
        $listener = new HttpMethodListener(false, $methods);

        $this->assertFalse($listener->isEnabled());
        $this->assertSame(['FOO', 'BAR'], $listener->getAllowedMethods());

        $listener = new HttpMethodListener(true, []);
        $this->assertNotEmpty($listener->getAllowedMethods());
    }

    public function testAttachesToRouteEvent(): void
    {
        $eventManager = $this->createMock(EventManagerInterface::class);
        $eventManager->expects($this->atLeastOnce())
                     ->method('attach')
                     ->with(MvcEvent::EVENT_ROUTE);

        $this->listener->attach($eventManager);
    }

    public function testDoesntAttachIfDisabled(): void
    {
        $this->listener->setEnabled(false);

        $eventManager = $this->createMock(EventManagerInterface::class);
        $eventManager->expects($this->never())
                     ->method('attach');

        $this->listener->attach($eventManager);
    }

    public function testOnRouteDoesNothingIfNotHttpEnvironment(): void
    {
        $event = new MvcEvent();
        $event->setRequest(new Request());

        $this->assertNull($this->listener->onRoute($event));

        $event->setRequest(new HttpRequest());
        $event->setResponse(new Response());

        $this->assertNull($this->listener->onRoute($event));
    }

    public function testOnRouteDoesNothingIfIfMethodIsAllowed(): void
    {
        $event   = new MvcEvent();
        $request = new HttpRequest();
        $request->setMethod('foo');
        $event->setRequest($request);
        $event->setResponse(new HttpResponse());

        $this->listener->setAllowedMethods(['foo']);

        $this->assertNull($this->listener->onRoute($event));
    }

    public function testOnRouteReturns405ResponseIfMethodNotAllowed(): void
    {
        $event   = new MvcEvent();
        $request = new HttpRequest();
        $request->setMethod('foo');
        $event->setRequest($request);
        $event->setResponse(new HttpResponse());

        $response = $this->listener->onRoute($event);

        $this->assertInstanceOf(HttpResponse::class, $response);
        $this->assertSame(405, $response->getStatusCode());
    }
}
