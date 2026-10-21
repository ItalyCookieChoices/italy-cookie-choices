<?php

declare(strict_types=1);

namespace ItalyCookieChoices\Tests\Integration;

use ItalyCookieChoices\Plugin;
use ItalyStrap\Event\SubscriberInterface;
use ItalyStrap\Event\SubscriberRegisterInterface;
use lucatume\WPBrowser\TestCase\WPTestCase;

final class PluginTest extends WPTestCase
{
    public function testBootAddsEverySubscriberInOrder(): void
    {
        $register = $this->makeRegister();
        $first = $this->makeSubscriber();
        $second = $this->makeSubscriber();

        (new Plugin($register, $first, $second))->boot();

        $this->assertSame([$first, $second], $register->added);
    }

    public function testBootWithoutSubscribersAddsNothing(): void
    {
        $register = $this->makeRegister();

        (new Plugin($register))->boot();

        $this->assertSame([], $register->added);
    }

    public function testThePluginIsBootedOnLoad(): void
    {
        $this->assertSame(1, \did_action('icc_plugin_loaded'));
    }

    /**
     * @return SubscriberRegisterInterface&object{added: list<SubscriberInterface>}
     */
    private function makeRegister(): SubscriberRegisterInterface
    {
        return new class implements SubscriberRegisterInterface {
            /**
             * @var list<SubscriberInterface>
             */
            public array $added = [];

            public function addSubscriber(SubscriberInterface $subscriber): void
            {
                $this->added[] = $subscriber;
            }

            public function removeSubscriber(SubscriberInterface $subscriber): void
            {
            }
        };
    }

    private function makeSubscriber(): SubscriberInterface
    {
        return new class implements SubscriberInterface {
            public function getSubscribedEvents(): iterable
            {
                return [];
            }
        };
    }
}
