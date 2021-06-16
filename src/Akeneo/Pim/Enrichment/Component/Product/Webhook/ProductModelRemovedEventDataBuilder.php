<?php

declare(strict_types=1);

namespace Akeneo\Pim\Enrichment\Component\Product\Webhook;

use Akeneo\Platform\Component\Webhook\Context;
use Akeneo\UserManagement\Bundle\PublicApi\Query\GetUserById\User;
use Akeneo\Pim\Enrichment\Component\Product\Message\ProductModelRemoved;
use Akeneo\Platform\Component\EventQueue\BulkEventInterface;
use Akeneo\Platform\Component\Webhook\EventDataBuilderInterface;
use Akeneo\Platform\Component\Webhook\EventDataCollection;
use Akeneo\UserManagement\Component\Model\UserInterface;

/**
 * @copyright 2020 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ProductModelRemovedEventDataBuilder implements EventDataBuilderInterface
{
    public function supports(BulkEventInterface $event): bool
    {
        if (false === $event instanceof BulkEventInterface) {
            return false;
        }

        foreach ($event->getEvents() as $event) {
            if (false === $event instanceof ProductModelRemoved) {
                return false;
            }
        }

        return true;
    }

    public function build(BulkEventInterface $event, Context $context): EventDataCollection
    {
        $collection = new EventDataCollection();

        /** @var ProductModelRemoved $event */
        foreach ($event->getEvents() as $event) {
            $data = [
                'resource' => [
                    'code' => $event->getCode()
                ],
            ];
            $collection->setEventData($event, $data);
        }

        return $collection;
    }
}
