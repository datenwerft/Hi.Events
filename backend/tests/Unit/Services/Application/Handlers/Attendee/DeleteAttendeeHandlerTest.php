<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Application\Handlers\Attendee;

use HiEvents\DomainObjects\AttendeeDomainObject;
use HiEvents\DomainObjects\Status\AttendeeStatus;
use HiEvents\Exceptions\ResourceConflictException;
use HiEvents\Repository\Interfaces\AttendeeRepositoryInterface;
use HiEvents\Services\Application\Handlers\Attendee\DeleteAttendeeHandler;
use Illuminate\Database\DatabaseManager;
use Mockery;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class DeleteAttendeeHandlerTest extends TestCase
{
    public function test_permanently_deletes_cancelled_attendee(): void
    {
        $attendee = (new AttendeeDomainObject)
            ->setId(7)
            ->setEventId(42)
            ->setStatus(AttendeeStatus::CANCELLED->name);

        $attendeeRepository = Mockery::mock(AttendeeRepositoryInterface::class);
        $attendeeRepository->shouldReceive('findFirstWhere')
            ->once()
            ->with(['id' => 7, 'event_id' => 42])
            ->andReturn($attendee);
        $attendeeRepository->shouldReceive('permanentlyDeleteById')
            ->once()
            ->with(7)
            ->andReturnTrue();

        $handler = $this->createHandler($attendeeRepository);

        $handler->handle(7, 42);
    }

    public function test_rejects_active_attendee(): void
    {
        $attendee = (new AttendeeDomainObject)
            ->setId(7)
            ->setEventId(42)
            ->setStatus(AttendeeStatus::ACTIVE->name);

        $attendeeRepository = Mockery::mock(AttendeeRepositoryInterface::class);
        $attendeeRepository->shouldReceive('findFirstWhere')
            ->once()
            ->with(['id' => 7, 'event_id' => 42])
            ->andReturn($attendee);
        $attendeeRepository->shouldNotReceive('permanentlyDeleteById');

        $this->expectException(ResourceConflictException::class);
        $this->expectExceptionMessage('Only cancelled attendees can be permanently deleted');

        $this->createHandler($attendeeRepository)->handle(7, 42);
    }

    public function test_rejects_attendee_outside_event(): void
    {
        $attendeeRepository = Mockery::mock(AttendeeRepositoryInterface::class);
        $attendeeRepository->shouldReceive('findFirstWhere')
            ->once()
            ->with(['id' => 7, 'event_id' => 42])
            ->andReturnNull();
        $attendeeRepository->shouldNotReceive('permanentlyDeleteById');

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Attendee not found');

        $this->createHandler($attendeeRepository)->handle(7, 42);
    }

    private function createHandler(AttendeeRepositoryInterface $attendeeRepository): DeleteAttendeeHandler
    {
        $databaseManager = Mockery::mock(DatabaseManager::class);
        $databaseManager->shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $callback) => $callback());

        return new DeleteAttendeeHandler(
            attendeeRepository: $attendeeRepository,
            databaseManager: $databaseManager,
        );
    }
}
