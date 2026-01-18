<?php

declare(strict_types=1);

namespace Channel\ProductDescription\Subscriber;

use Channel\ProductDescription\Service\CustomFieldService;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityDeletedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\System\SalesChannel\SalesChannelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class SalesChannelSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly CustomFieldService $customFieldService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SalesChannelEvents::SALES_CHANNEL_WRITTEN => 'onSalesChannelWritten',
            SalesChannelEvents::SALES_CHANNEL_DELETED => 'onSalesChannelDeleted',
        ];
    }

    public function onSalesChannelWritten(EntityWrittenEvent $event): void
    {
        foreach ($event->getIds() as $channelId) {
            $this->customFieldService->createFieldForChannel($channelId, $event->getContext());
        }
    }

    public function onSalesChannelDeleted(EntityDeletedEvent $event): void
    {
        foreach ($event->getIds() as $channelId) {
            $this->customFieldService->deleteFieldForChannel($channelId, $event->getContext());
        }
    }
}

