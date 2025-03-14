<?php

declare(strict_types=1);

namespace LaminasTest\Mvc;

use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\ResponseSender\SendResponseEvent;
use Laminas\Mvc\SendResponseListener;
use Laminas\Stdlib\ResponseInterface;
use PHPUnit\Framework\TestCase;

use function array_values;

class SendResponseListenerTest extends TestCase
{
    public function testEventManagerIdentifiers(): void
    {
        $listener    = new SendResponseListener();
        $identifiers = $listener->getEventManager()->getIdentifiers();
        $expected    = [SendResponseListener::class];
        $this->assertEquals($expected, array_values($identifiers));
    }

    public function testSendResponseTriggersSendResponseEvent(): void
    {
        $listener = new SendResponseListener();
        $result   = [];
        $listener->getEventManager()->attach(
            SendResponseEvent::EVENT_SEND_RESPONSE,
            static function ($e) use (&$result): void {
                $result['target']   = $e->getTarget();
                $result['response'] = $e->getResponse();
            },
            10000
        );
        $mockResponse = $this->getMockForAbstractClass(ResponseInterface::class);
        $mockMvcEvent = $this->getMockBuilder(MvcEvent::class)
            ->onlyMethods(['getResponse'])
            ->getMock();
        $mockMvcEvent->expects($this->any())->method('getResponse')->willReturn($mockResponse);
        $listener->sendResponse($mockMvcEvent);
        $expected = [
            'target'   => $listener,
            'response' => $mockResponse,
        ];
        $this->assertEquals($expected, $result);
    }
}
