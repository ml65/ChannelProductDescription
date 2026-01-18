<?php

declare(strict_types=1);

namespace Channel\ProductDescription;

use Channel\ProductDescription\Service\CustomFieldService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

final class ChannelProductDescription extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        if (!$this->container->has(CustomFieldService::class)) {
            // Services not loaded yet, create service manually
            $customFieldService = new CustomFieldService(
                $this->container->get('custom_field_set.repository'),
                $this->container->get('custom_field.repository'),
                $this->container->get('sales_channel.repository')
            );
        } else {
            $customFieldService = $this->container->get(CustomFieldService::class);
        }

        $customFieldService->createFieldsForAllChannels($installContext->getContext());
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        if (!$this->container->has('custom_field_set.repository')) {
            return;
        }

        if (!$this->container->has(CustomFieldService::class)) {
            $customFieldService = new CustomFieldService(
                $this->container->get('custom_field_set.repository'),
                $this->container->get('custom_field.repository'),
                $this->container->get('sales_channel.repository')
            );
        } else {
            $customFieldService = $this->container->get(CustomFieldService::class);
        }

        $customFieldService->deleteCustomFieldSet($uninstallContext->getContext());
    }
}

