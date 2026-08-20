<?php

namespace HiEvents\Services\Domain\Question;

use HiEvents\DomainObjects\AttendeeDomainObject;
use HiEvents\DomainObjects\Enums\QuestionBelongsTo;
use HiEvents\DomainObjects\Generated\QuestionAnswerDomainObjectAbstract;
use HiEvents\DomainObjects\ProductDomainObject;
use HiEvents\DomainObjects\QuestionAnswerDomainObject;
use HiEvents\DomainObjects\QuestionDomainObject;
use HiEvents\Repository\Eloquent\Value\Relationship;
use HiEvents\Repository\Interfaces\QuestionAnswerRepositoryInterface;
use HiEvents\Repository\Interfaces\QuestionRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AttendeeQuestionAnswerService
{
    public function __construct(
        private readonly QuestionRepositoryInterface $questionRepository,
        private readonly QuestionAnswerRepositoryInterface $questionAnswerRepository,
    ) {}

    public function upsertProductAnswers(AttendeeDomainObject $attendee, int $eventId, array $answers): void
    {
        $questions = $this->getApplicableQuestions($attendee, $eventId);
        $answersByQuestionId = collect($answers)->keyBy('question_id');

        if ($answersByQuestionId->count() !== count($answers)) {
            $this->throwInvalidAnswers();
        }

        foreach ($answersByQuestionId as $questionId => $answerData) {
            if (! $questions->has((int) $questionId)) {
                $this->throwInvalidAnswers();
            }
        }

        foreach ($questions as $question) {
            $answerData = $answersByQuestionId->get($question->getId());
            $answer = $answerData['answer'] ?? null;

            if ($answer !== null && ! is_string($answer) && ! is_array($answer)) {
                $this->throwInvalidAnswers();
            }

            if ($question->getRequired() && ! $this->hasValue($answer)) {
                $this->throwInvalidAnswers();
            }

            if ($this->hasValue($answer) && ! $question->isAnswerValid($answer)) {
                $this->throwInvalidAnswers();
            }
        }

        $existingAnswers = $this->questionAnswerRepository
            ->findWhere([QuestionAnswerDomainObjectAbstract::ATTENDEE_ID => $attendee->getId()])
            ->keyBy(fn (QuestionAnswerDomainObject $answer) => $answer->getQuestionId());

        foreach ($questions as $question) {
            $answer = $answersByQuestionId->get($question->getId())['answer'] ?? null;
            $existingAnswer = $existingAnswers->get($question->getId());

            if (! $this->hasValue($answer)) {
                if ($existingAnswer) {
                    $this->questionAnswerRepository->deleteById($existingAnswer->getId());
                }

                continue;
            }

            if ($existingAnswer) {
                $this->questionAnswerRepository->updateWhere(
                    [QuestionAnswerDomainObjectAbstract::ANSWER => $answer],
                    [QuestionAnswerDomainObjectAbstract::ID => $existingAnswer->getId()],
                );

                continue;
            }

            $this->questionAnswerRepository->create([
                QuestionAnswerDomainObjectAbstract::QUESTION_ID => $question->getId(),
                QuestionAnswerDomainObjectAbstract::ANSWER => $answer,
                QuestionAnswerDomainObjectAbstract::ORDER_ID => $attendee->getOrderId(),
                QuestionAnswerDomainObjectAbstract::PRODUCT_ID => $attendee->getProductId(),
                QuestionAnswerDomainObjectAbstract::ATTENDEE_ID => $attendee->getId(),
            ]);
        }
    }

    private function getApplicableQuestions(AttendeeDomainObject $attendee, int $eventId): Collection
    {
        return $this->questionRepository
            ->loadRelation(new Relationship(ProductDomainObject::class))
            ->findByEventId($eventId)
            ->filter(fn (QuestionDomainObject $question) => $question->getBelongsTo() === QuestionBelongsTo::PRODUCT->name
                && ! $question->getIsHidden()
                && $question->getProducts()?->contains(
                    fn (ProductDomainObject $product) => $product->getId() === $attendee->getProductId()
                )
            )
            ->keyBy(fn (QuestionDomainObject $question) => $question->getId());
    }

    private function hasValue(mixed $answer): bool
    {
        if (is_string($answer)) {
            return trim($answer) !== '';
        }

        if (is_array($answer)) {
            return collect($answer)->contains(fn (mixed $value) => $this->hasValue($value));
        }

        return $answer !== null;
    }

    private function throwInvalidAnswers(): never
    {
        throw ValidationException::withMessages([
            'question_answers' => __('Please provide a valid answer'),
        ]);
    }
}
