<?php

namespace HiEvents\Resources\Attendee;

use HiEvents\DomainObjects\AttendeeDomainObject;
use HiEvents\DomainObjects\Enums\QuestionBelongsTo;
use HiEvents\Resources\Product\ProductMinimalResourcePublic;
use HiEvents\Resources\Question\QuestionAnswerViewResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AttendeeDomainObject
 */
class AttendeeResourcePublic extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'email' => $this->getEmail(),
            'status' => $this->getStatus(),
            'first_name' => $this->getFirstName(),
            'last_name' => $this->getLastName(),
            'public_id' => $this->getPublicId(),
            'short_id' => $this->getShortId(),
            'printed_ticket_number' => $this->getPrintedTicketNumber(),
            'table_number' => $this->getTableNumber(),
            'seat_number' => $this->getSeatNumber(),
            'order_attendee_count' => $this->getOrderAttendeeCount(),
            'product_id' => $this->getProductId(),
            'product_price_id' => $this->getProductPriceId(),
            'product' => $this->when((bool)$this->getProduct(), fn() => new ProductMinimalResourcePublic($this->getProduct())),
            'question_answers' => $this->when(
                $this->getQuestionAndAnswerViews() !== null,
                fn() => QuestionAnswerViewResource::collection(
                    $this->getQuestionAndAnswerViews()
                        ?->filter(fn($qav) => $qav->getBelongsTo() === QuestionBelongsTo::PRODUCT->name)
                )
            ),
            'locale' => $this->getLocale(),
        ];
    }
}
