<?php

declare(strict_types=1);

namespace HiEvents\Services\Application\Handlers\Attendee;

use HiEvents\DomainObjects\Status\AttendeeStatus;
use HiEvents\Exceptions\ResourceConflictException;
use HiEvents\Repository\Interfaces\AttendeeRepositoryInterface;
use Illuminate\Database\DatabaseManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DeleteAttendeeHandler
{
    public function __construct(
        private readonly AttendeeRepositoryInterface $attendeeRepository,
        private readonly DatabaseManager $databaseManager,
    ) {}

    public function handle(int $attendeeId, int $eventId): void
    {
        $this->databaseManager->transaction(function () use ($attendeeId, $eventId): void {
            $attendee = $this->attendeeRepository->findFirstWhere([
                'id' => $attendeeId,
                'event_id' => $eventId,
            ]);

            if ($attendee === null) {
                throw new NotFoundHttpException(__('Attendee not found'));
            }

            if ($attendee->getStatus() !== AttendeeStatus::CANCELLED->name) {
                throw new ResourceConflictException(__('Only cancelled attendees can be permanently deleted'));
            }

            $this->attendeeRepository->permanentlyDeleteById($attendeeId);
        });
    }
}
