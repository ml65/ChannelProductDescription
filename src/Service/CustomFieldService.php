<?php

declare(strict_types=1);

namespace Channel\ProductDescription\Service;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

final class CustomFieldService
{
    private const FIELD_SET_NAME = 'channel_product_description';
    private const FIELD_PREFIX = 'channel_description_';

    public function __construct(
        private readonly EntityRepository $customFieldSetRepository,
        private readonly EntityRepository $customFieldRepository,
        private readonly EntityRepository $salesChannelRepository
    ) {
    }

    public function createFieldsForAllChannels(Context $context): void
    {
        $criteria = new Criteria();
        $salesChannels = $this->salesChannelRepository->search($criteria, $context);

        if ($salesChannels->count() === 0) {
            return;
        }

        $fieldSetId = $this->getOrCreateCustomFieldSet($context);

        foreach ($salesChannels as $channel) {
            $this->createFieldForChannelWithSetId($fieldSetId, $channel->getId(), $context);
        }
    }

    private function createCustomFieldSet(Context $context): string
    {
        $fieldSetId = Uuid::randomHex();

        $this->customFieldSetRepository->create([
            [
                'id' => $fieldSetId,
                'name' => self::FIELD_SET_NAME,
                'config' => [
                    'label' => [
                        'de-DE' => 'Kanal-Beschreibungen',
                        'en-GB' => 'Channel Descriptions'
                    ]
                ],
                'relations' => [
                    [
                        'id' => Uuid::randomHex(),
                        'entityName' => ProductDefinition::ENTITY_NAME
                    ]
                ]
            ]
        ], $context);

        return $fieldSetId;
    }

    private function createFieldForChannelWithSetId(string $fieldSetId, string $channelId, Context $context): void
    {
        $channel = $this->getSalesChannel($channelId, $context);
        $channelName = $channel ? $channel->getName() : 'Channel';

        $fieldName = self::FIELD_PREFIX . $channelId;

        $this->customFieldRepository->create([
            [
                'id' => Uuid::randomHex(),
                'name' => $fieldName,
                'type' => 'text',
                'customFieldSetId' => $fieldSetId,
                'config' => [
                    'type' => 'text',
                    'label' => [
                        'de-DE' => 'Beschreibung für ' . $channelName,
                        'en-GB' => 'Description for ' . $channelName
                    ],
                    'componentName' => 'sw-text-editor',
                ]
            ]
        ], $context);
    }

    public function createFieldForChannel(string $channelId, Context $context): void
    {
        $fieldSetId = $this->getOrCreateCustomFieldSet($context);
        $fieldName = self::FIELD_PREFIX . $channelId;

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $fieldName));
        $existingField = $this->customFieldRepository->search($criteria, $context);

        if ($existingField->count() > 0) {
            return;
        }

        $channel = $this->getSalesChannel($channelId, $context);
        $channelName = $channel ? $channel->getName() : 'Channel';

        $this->customFieldRepository->create([
            [
                'id' => Uuid::randomHex(),
                'name' => $fieldName,
                'type' => 'text',
                'customFieldSetId' => $fieldSetId,
                'config' => [
                    'type' => 'text',
                    'label' => [
                        'de-DE' => 'Beschreibung für ' . $channelName,
                        'en-GB' => 'Description for ' . $channelName
                    ],
                    'componentName' => 'sw-text-editor',
                ]
            ]
        ], $context);
    }

    public function deleteFieldForChannel(string $channelId, Context $context): void
    {
        $fieldName = self::FIELD_PREFIX . $channelId;

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $fieldName));
        $fields = $this->customFieldRepository->search($criteria, $context);

        if ($fields->count() === 0) {
            return;
        }

        $ids = [];
        foreach ($fields as $field) {
            $ids[] = ['id' => $field->getId()];
        }

        $this->customFieldRepository->delete($ids, $context);
    }

    private function getOrCreateCustomFieldSet(Context $context): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', self::FIELD_SET_NAME));
        $fieldSets = $this->customFieldSetRepository->search($criteria, $context);

        if ($fieldSets->count() > 0) {
            return $fieldSets->first()->getId();
        }

        return $this->createCustomFieldSet($context);
    }

    public function deleteCustomFieldSet(Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', self::FIELD_SET_NAME));

        $fieldSets = $this->customFieldSetRepository->search($criteria, $context);

        if ($fieldSets->count() === 0) {
            return;
        }

        $ids = [];
        foreach ($fieldSets as $fieldSet) {
            $ids[] = ['id' => $fieldSet->getId()];
        }

        $this->customFieldSetRepository->delete($ids, $context);
    }

    private function getSalesChannel(string $channelId, Context $context): ?SalesChannelEntity
    {
        $criteria = new Criteria([$channelId]);
        $channels = $this->salesChannelRepository->search($criteria, $context);

        if ($channels->count() === 0) {
            return null;
        }

        return $channels->first();
    }
}

