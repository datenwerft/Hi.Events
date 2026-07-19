<?php

declare(strict_types=1);

namespace HiEvents\Services\Application\Handlers\Order;

use HiEvents\DomainObjects\Generated\OrderDomainObjectAbstract;
use HiEvents\DomainObjects\Status\OrderStatus;
use HiEvents\Exceptions\ResourceConflictException;
use HiEvents\Repository\Interfaces\OrderRepositoryInterface;
use Illuminate\Database\DatabaseManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DeleteOrderHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly DatabaseManager $databaseManager,
    ) {}

    public function handle(int $orderId, int $eventId): void
    {
        $this->databaseManager->transaction(function () use ($orderId, $eventId): void {
            $order = $this->orderRepository->findFirstWhere([
                OrderDomainObjectAbstract::ID => $orderId,
                OrderDomainObjectAbstract::EVENT_ID => $eventId,
            ]);

            if ($order === null) {
                throw new NotFoundHttpException(__('Order not found'));
            }

            if ($order->getStatus() !== OrderStatus::CANCELLED->name) {
                throw new ResourceConflictException(__('Only cancelled orders can be permanently deleted'));
            }

            $this->orderRepository->permanentlyDeleteById($orderId);
        });
    }
}
