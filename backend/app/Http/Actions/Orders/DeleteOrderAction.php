<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Orders;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\Exceptions\ResourceConflictException;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Services\Application\Handlers\Order\DeleteOrderHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class DeleteOrderAction extends BaseAction
{
    public function __construct(
        private readonly DeleteOrderHandler $deleteOrderHandler,
    ) {}

    public function __invoke(int $eventId, int $orderId): JsonResponse|Response
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        try {
            $this->deleteOrderHandler->handle($orderId, $eventId);
        } catch (ResourceConflictException $exception) {
            return $this->errorResponse($exception->getMessage(), HttpResponse::HTTP_CONFLICT);
        }

        return $this->deletedResponse();
    }
}
