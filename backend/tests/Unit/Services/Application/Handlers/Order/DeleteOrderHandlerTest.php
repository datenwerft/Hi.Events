<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Application\Handlers\Order;

use HiEvents\DomainObjects\OrderDomainObject;
use HiEvents\DomainObjects\Status\OrderStatus;
use HiEvents\Exceptions\ResourceConflictException;
use HiEvents\Repository\Interfaces\OrderRepositoryInterface;
use HiEvents\Services\Application\Handlers\Order\DeleteOrderHandler;
use Illuminate\Database\DatabaseManager;
use Mockery;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class DeleteOrderHandlerTest extends TestCase
{
    public function test_permanently_deletes_cancelled_order(): void
    {
        $order = (new OrderDomainObject)
            ->setId(7)
            ->setEventId(42)
            ->setStatus(OrderStatus::CANCELLED->name);

        $orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepository->shouldReceive('findFirstWhere')
            ->once()
            ->with(['id' => 7, 'event_id' => 42])
            ->andReturn($order);
        $orderRepository->shouldReceive('permanentlyDeleteById')
            ->once()
            ->with(7)
            ->andReturnTrue();

        $this->createHandler($orderRepository)->handle(7, 42);
    }

    public function test_rejects_non_cancelled_order(): void
    {
        $order = (new OrderDomainObject)
            ->setId(7)
            ->setEventId(42)
            ->setStatus(OrderStatus::COMPLETED->name);

        $orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepository->shouldReceive('findFirstWhere')
            ->once()
            ->with(['id' => 7, 'event_id' => 42])
            ->andReturn($order);
        $orderRepository->shouldNotReceive('permanentlyDeleteById');

        $this->expectException(ResourceConflictException::class);
        $this->expectExceptionMessage('Only cancelled orders can be permanently deleted');

        $this->createHandler($orderRepository)->handle(7, 42);
    }

    public function test_rejects_order_outside_event(): void
    {
        $orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepository->shouldReceive('findFirstWhere')
            ->once()
            ->with(['id' => 7, 'event_id' => 42])
            ->andReturnNull();
        $orderRepository->shouldNotReceive('permanentlyDeleteById');

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Order not found');

        $this->createHandler($orderRepository)->handle(7, 42);
    }

    private function createHandler(OrderRepositoryInterface $orderRepository): DeleteOrderHandler
    {
        $databaseManager = Mockery::mock(DatabaseManager::class);
        $databaseManager->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $callback) => $callback());

        return new DeleteOrderHandler(
            orderRepository: $orderRepository,
            databaseManager: $databaseManager,
        );
    }
}
