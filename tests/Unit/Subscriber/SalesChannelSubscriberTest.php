<?php

declare(strict_types=1);

namespace Channel\ProductDescription\Test\Unit\Subscriber;

use Channel\ProductDescription\Service\CustomFieldService;
use Channel\ProductDescription\Subscriber\SalesChannelSubscriber;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityDeletedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\System\SalesChannel\SalesChannelEvents;

final class SalesChannelSubscriberTest extends TestCase
{
    private CustomFieldService $customFieldService;
    private SalesChannelSubscriber $subscriber;
    private Context $context;

    protected function setUp(): void
    {
        $this->customFieldService = $this->createMock(CustomFieldService::class);
        $this->subscriber = new SalesChannelSubscriber($this->customFieldService);
        $this->context = Context::createDefaultContext();
    }

    public function testGetSubscribedEvents(): void
    {
        $events = SalesChannelSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey('sales_channel.written', $events);
        $this->assertArrayHasKey(SalesChannelEvents::SALES_CHANNEL_DELETED, $events);
        $this->assertEquals('onSalesChannelWritten', $events['sales_channel.written']);
        $this->assertEquals('onSalesChannelDeleted', $events[SalesChannelEvents::SALES_CHANNEL_DELETED]);
    }

    public function testOnSalesChannelWrittenCallsService(): void
    {
        $channelId = 'test-channel-id';

        $event = $this->createMock(EntityWrittenEvent::class);
        $event->method('getIds')->willReturn([$channelId]);
        $event->method('getContext')->willReturn($this->context);

        $this->customFieldService
            ->expects($this->once())
            ->method('createFieldForChannel')
            ->with($channelId, $this->context);

        $this->subscriber->onSalesChannelWritten($event);
    }

    public function testOnSalesChannelWrittenWithMultipleIds(): void
    {
        $channelId1 = 'test-channel-id-1';
        $channelId2 = 'test-channel-id-2';

        $event = $this->createMock(EntityWrittenEvent::class);
        $event->method('getIds')->willReturn([$channelId1, $channelId2]);
        $event->method('getContext')->willReturn($this->context);

        $this->customFieldService
            ->expects($this->exactly(2))
            ->method('createFieldForChannel')
            ->withConsecutive([$channelId1, $this->context], [$channelId2, $this->context]);

        $this->subscriber->onSalesChannelWritten($event);
    }

    public function testOnSalesChannelDeletedCallsService(): void
    {
        $channelId = 'test-channel-id';

        $event = $this->createMock(EntityDeletedEvent::class);
        $event->method('getIds')->willReturn([$channelId]);
        $event->method('getContext')->willReturn($this->context);

        $this->customFieldService
            ->expects($this->once())
            ->method('deleteFieldForChannel')
            ->with($channelId, $this->context);

        $this->subscriber->onSalesChannelDeleted($event);
    }

    public function testOnSalesChannelDeletedWithMultipleIds(): void
    {
        $channelId1 = 'test-channel-id-1';
        $channelId2 = 'test-channel-id-2';

        $event = $this->createMock(EntityDeletedEvent::class);
        $event->method('getIds')->willReturn([$channelId1, $channelId2]);
        $event->method('getContext')->willReturn($this->context);

        $this->customFieldService
            ->expects($this->exactly(2))
            ->method('deleteFieldForChannel')
            ->withConsecutive([$channelId1, $this->context], [$channelId2, $this->context]);

        $this->subscriber->onSalesChannelDeleted($event);
    }
}

