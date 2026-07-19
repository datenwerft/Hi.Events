<?php

namespace Tests\Unit\Services\Application\Handlers\Attendee;

use HiEvents\DomainObjects\AttendeeDomainObject;
use HiEvents\DomainObjects\OrderDomainObject;
use HiEvents\DomainObjects\Status\AttendeeStatus;
use HiEvents\Repository\Interfaces\AttendeeRepositoryInterface;
use HiEvents\Repository\Interfaces\OrderRepositoryInterface;
use HiEvents\Services\Application\Handlers\Attendee\DTO\PartialEditAttendeeDTO;
use HiEvents\Services\Application\Handlers\Attendee\PartialEditAttendeeHandler;
use HiEvents\Services\Domain\EventStatistics\EventStatisticsCancellationService;
use HiEvents\Services\Domain\EventStatistics\EventStatisticsReactivationService;
use HiEvents\Services\Domain\Product\ProductQuantityUpdateService;
use HiEvents\Services\Infrastructure\DomainEvents\DomainEventDispatcherService;
use HiEvents\Services\Infrastructure\DomainEvents\Enums\DomainEventType;
use HiEvents\Services\Infrastructure\DomainEvents\Events\AttendeeEvent;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Event;
use Mockery;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class PartialEditAttendeeHandlerTest extends TestCase
{
    public function testCancellingAttendeeClearsSeatingAssignment(): void
    {
        Event::fake();

        $attendee = (new AttendeeDomainObject())
            ->setId(7)
            ->setEventId(42)
            ->setOrderId(11)
            ->setProductId(13)
            ->setProductPriceId(17)
            ->setStatus(AttendeeStatus::ACTIVE->name)
            ->setFirstName('Jane')
            ->setLastName('Attendee')
            ->setEmail('jane@example.com')
            ->setTableNumber(2)
            ->setSeatNumber(3);

        $order = (new OrderDomainObject())
            ->setId(11)
            ->setEventId(42)
            ->setCreatedAt('2026-07-19 10:00:00');

        $attendeeRepository = Mockery::mock(AttendeeRepositoryInterface::class);
        $attendeeRepository->shouldReceive('findFirstWhere')
            ->once()
            ->with(['id' => 7, 'event_id' => 42])
            ->andReturn($attendee);
        $attendeeRepository->shouldReceive('updateByIdWhere')
            ->once()
            ->with(
                7,
                [
                    'status' => AttendeeStatus::CANCELLED->name,
                    'first_name' => 'Jane',
                    'last_name' => 'Attendee',
                    'email' => 'jane@example.com',
                    'table_number' => null,
                    'seat_number' => null,
                ],
                ['event_id' => 42],
            )
            ->andReturn($attendee);

        $orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $orderRepository->shouldReceive('findFirstWhere')
            ->once()
            ->with(['id' => 11, 'event_id' => 42])
            ->andReturn($order);

        $productQuantityService = Mockery::mock(ProductQuantityUpdateService::class);
        $productQuantityService->shouldReceive('decreaseQuantitySold')->once()->with(17);

        $eventStatisticsCancellationService = Mockery::mock(EventStatisticsCancellationService::class);
        $eventStatisticsCancellationService->shouldReceive('decrementForCancelledAttendee')
            ->once()
            ->with(42, '2026-07-19 10:00:00');

        $domainEventDispatcherService = Mockery::mock(DomainEventDispatcherService::class);
        $domainEventDispatcherService->shouldReceive('dispatch')
            ->once()
            ->withArgs(fn(AttendeeEvent $event) =>
                $event->type === DomainEventType::ATTENDEE_CANCELLED && $event->attendeeId === 7
            );

        $databaseManager = Mockery::mock(DatabaseManager::class);
        $databaseManager->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn(callable $callback) => $callback());

        $handler = new PartialEditAttendeeHandler(
            attendeeRepository: $attendeeRepository,
            orderRepository: $orderRepository,
            productQuantityService: $productQuantityService,
            databaseManager: $databaseManager,
            domainEventDispatcherService: $domainEventDispatcherService,
            eventStatisticsCancellationService: $eventStatisticsCancellationService,
            eventStatisticsReactivationService: Mockery::mock(EventStatisticsReactivationService::class),
            logger: Mockery::mock(LoggerInterface::class),
        );

        $handler->handle(new PartialEditAttendeeDTO(
            attendee_id: 7,
            event_id: 42,
            first_name: null,
            last_name: null,
            email: null,
            status: AttendeeStatus::CANCELLED->name,
        ));
    }
}
