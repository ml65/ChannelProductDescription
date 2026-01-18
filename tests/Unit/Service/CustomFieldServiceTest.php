<?php

declare(strict_types=1);

namespace Channel\ProductDescription\Test\Unit\Service;

use Channel\ProductDescription\Service\CustomFieldService;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

final class CustomFieldServiceTest extends TestCase
{
    private EntityRepository $customFieldSetRepository;
    private EntityRepository $customFieldRepository;
    private EntityRepository $salesChannelRepository;
    private CustomFieldService $customFieldService;
    private Context $context;

    protected function setUp(): void
    {
        $this->customFieldSetRepository = $this->createMock(EntityRepository::class);
        $this->customFieldRepository = $this->createMock(EntityRepository::class);
        $this->salesChannelRepository = $this->createMock(EntityRepository::class);
        $this->context = Context::createDefaultContext();

        $this->customFieldService = new CustomFieldService(
            $this->customFieldSetRepository,
            $this->customFieldRepository,
            $this->salesChannelRepository
        );
    }

    public function testCreateFieldsForAllChannelsWithEmptyChannels(): void
    {
        $emptySearchResult = $this->createMock(EntitySearchResult::class);
        $emptySearchResult->method('count')->willReturn(0);

        $this->salesChannelRepository
            ->expects($this->once())
            ->method('search')
            ->willReturn($emptySearchResult);

        $this->customFieldSetRepository
            ->expects($this->never())
            ->method('create');

        $this->customFieldService->createFieldsForAllChannels($this->context);
    }

    public function testCreateFieldForChannelCallsRepository(): void
    {
        $channelId = 'test-channel-id';
        $channelName = 'Test Channel';

        $channel = $this->createMock(SalesChannelEntity::class);
        $channel->method('getId')->willReturn($channelId);
        $channel->method('getName')->willReturn($channelName);

        $channelSearchResult = $this->createMock(EntitySearchResult::class);
        $channelSearchResult->method('first')->willReturn($channel);

        $fieldSetSearchResult = $this->createMock(EntitySearchResult::class);
        $fieldSetSearchResult->method('count')->willReturn(0);

        $fieldSearchResult = $this->createMock(EntitySearchResult::class);
        $fieldSearchResult->method('count')->willReturn(0);

        $this->salesChannelRepository
            ->expects($this->once())
            ->method('search')
            ->willReturn($channelSearchResult);

        $this->customFieldSetRepository
            ->expects($this->once())
            ->method('search')
            ->willReturn($fieldSetSearchResult);

        $this->customFieldSetRepository
            ->expects($this->once())
            ->method('create');

        $this->customFieldRepository
            ->expects($this->once())
            ->method('search')
            ->willReturn($fieldSearchResult);

        $this->customFieldRepository
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($data) use ($channelId, $channelName) {
                return $data[0]['name'] === 'channel_description_' . $channelId
                    && \str_contains($data[0]['config']['label']['en-GB'], $channelName);
            }));

        $this->customFieldService->createFieldForChannel($channelId, $this->context);
    }

    public function testDeleteFieldForChannelCallsRepository(): void
    {
        $channelId = 'test-channel-id';
        $fieldId = 'field-id';

        $fieldEntity = $this->createMock(\Shopware\Core\Framework\DataAbstractionLayer\Entity::class);
        $fieldEntity->method('getId')->willReturn($fieldId);

        $fieldSearchResult = $this->createMock(EntitySearchResult::class);
        $fieldSearchResult->method('count')->willReturn(1);
        $fieldSearchResult->method('getIterator')->willReturn(new \ArrayIterator([$fieldEntity]));

        $this->customFieldRepository
            ->expects($this->once())
            ->method('search')
            ->willReturn($fieldSearchResult);

        $this->customFieldRepository
            ->expects($this->once())
            ->method('delete')
            ->with($this->callback(function ($data) use ($fieldId) {
                return isset($data[0]['id']) && $data[0]['id'] === $fieldId;
            }));

        $this->customFieldService->deleteFieldForChannel($channelId, $this->context);
    }

    public function testDeleteFieldForChannelWithNoField(): void
    {
        $channelId = 'test-channel-id';

        $emptySearchResult = $this->createMock(EntitySearchResult::class);
        $emptySearchResult->method('count')->willReturn(0);

        $this->customFieldRepository
            ->expects($this->once())
            ->method('search')
            ->willReturn($emptySearchResult);

        $this->customFieldRepository
            ->expects($this->never())
            ->method('delete');

        $this->customFieldService->deleteFieldForChannel($channelId, $this->context);
    }

    public function testCreateFieldForChannelWithExistingField(): void
    {
        $channelId = 'test-channel-id';
        $channelName = 'Test Channel';

        $channel = $this->createMock(SalesChannelEntity::class);
        $channel->method('getId')->willReturn($channelId);
        $channel->method('getName')->willReturn($channelName);

        $channelSearchResult = $this->createMock(EntitySearchResult::class);
        $channelSearchResult->method('first')->willReturn($channel);

        $fieldSetSearchResult = $this->createMock(EntitySearchResult::class);
        $fieldSetSearchResult->method('count')->willReturn(1);
        $fieldSetEntity = $this->createMock(\Shopware\Core\Framework\DataAbstractionLayer\Entity::class);
        $fieldSetEntity->method('getId')->willReturn('field-set-id');
        $fieldSetSearchResult->method('first')->willReturn($fieldSetEntity);

        $existingFieldSearchResult = $this->createMock(EntitySearchResult::class);
        $existingFieldSearchResult->method('count')->willReturn(1);

        $this->salesChannelRepository
            ->expects($this->once())
            ->method('search')
            ->willReturn($channelSearchResult);

        $this->customFieldSetRepository
            ->expects($this->once())
            ->method('search')
            ->willReturn($fieldSetSearchResult);

        $this->customFieldSetRepository
            ->expects($this->never())
            ->method('create');

        $this->customFieldRepository
            ->expects($this->once())
            ->method('search')
            ->willReturn($existingFieldSearchResult);

        $this->customFieldRepository
            ->expects($this->never())
            ->method('create');

        $this->customFieldService->createFieldForChannel($channelId, $this->context);
    }

    public function testCreateFieldsForAllChannelsWithExistingFields(): void
    {
        $channelId1 = 'channel-1';
        $channelId2 = 'channel-2';
        $channelName1 = 'Channel 1';
        $channelName2 = 'Channel 2';

        $channel1 = $this->createMock(SalesChannelEntity::class);
        $channel1->method('getId')->willReturn($channelId1);
        $channel1->method('getName')->willReturn($channelName1);

        $channel2 = $this->createMock(SalesChannelEntity::class);
        $channel2->method('getId')->willReturn($channelId2);
        $channel2->method('getName')->willReturn($channelName2);

        $channelsSearchResult = $this->createMock(EntitySearchResult::class);
        $channelsSearchResult->method('count')->willReturn(2);
        $channelsSearchResult->method('getIterator')->willReturn(new \ArrayIterator([$channel1, $channel2]));

        $fieldSetSearchResult = $this->createMock(EntitySearchResult::class);
        $fieldSetSearchResult->method('count')->willReturn(1);
        $fieldSetEntity = $this->createMock(\Shopware\Core\Framework\DataAbstractionLayer\Entity::class);
        $fieldSetEntity->method('getId')->willReturn('field-set-id');
        $fieldSetSearchResult->method('first')->willReturn($fieldSetEntity);

        $existingFieldSearchResult = $this->createMock(EntitySearchResult::class);
        $existingFieldSearchResult->method('count')->willReturn(1);

        $this->salesChannelRepository
            ->expects($this->once())
            ->method('search')
            ->willReturn($channelsSearchResult);

        $this->customFieldSetRepository
            ->expects($this->once())
            ->method('search')
            ->willReturn($fieldSetSearchResult);

        $this->customFieldSetRepository
            ->expects($this->never())
            ->method('create');

        $this->customFieldRepository
            ->expects($this->exactly(2))
            ->method('search')
            ->willReturn($existingFieldSearchResult);

        $this->customFieldRepository
            ->expects($this->never())
            ->method('create');

        $this->customFieldService->createFieldsForAllChannels($this->context);
    }
}

