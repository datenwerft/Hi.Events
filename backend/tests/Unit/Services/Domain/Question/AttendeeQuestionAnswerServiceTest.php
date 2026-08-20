<?php

namespace Tests\Unit\Services\Domain\Question;

use HiEvents\DomainObjects\AttendeeDomainObject;
use HiEvents\DomainObjects\Enums\QuestionBelongsTo;
use HiEvents\DomainObjects\Enums\QuestionTypeEnum;
use HiEvents\DomainObjects\ProductDomainObject;
use HiEvents\DomainObjects\QuestionAnswerDomainObject;
use HiEvents\DomainObjects\QuestionDomainObject;
use HiEvents\Repository\Eloquent\Value\Relationship;
use HiEvents\Repository\Interfaces\QuestionAnswerRepositoryInterface;
use HiEvents\Repository\Interfaces\QuestionRepositoryInterface;
use HiEvents\Services\Domain\Question\AttendeeQuestionAnswerService;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class AttendeeQuestionAnswerServiceTest extends TestCase
{
    public function test_it_creates_missing_answers_and_updates_existing_answers(): void
    {
        $attendee = $this->createAttendee();
        $mealQuestion = $this->createQuestion(1, true);
        $allergiesQuestion = $this->createQuestion(2, true);
        $existingAnswer = (new QuestionAnswerDomainObject)
            ->setId(10)
            ->setQuestionId(1)
            ->setOrderId(11)
            ->setAttendeeId(7)
            ->setProductId(13)
            ->setAnswer('Vegetarian');

        $questionRepository = Mockery::mock(QuestionRepositoryInterface::class);
        $questionRepository->shouldReceive('loadRelation')
            ->once()
            ->with(Mockery::type(Relationship::class))
            ->andReturnSelf();
        $questionRepository->shouldReceive('findByEventId')
            ->once()
            ->with(42)
            ->andReturn(collect([$mealQuestion, $allergiesQuestion]));

        $answerRepository = Mockery::mock(QuestionAnswerRepositoryInterface::class);
        $answerRepository->shouldReceive('findWhere')
            ->once()
            ->with(['attendee_id' => 7])
            ->andReturn(collect([$existingAnswer]));
        $answerRepository->shouldReceive('updateWhere')
            ->once()
            ->with(['answer' => 'Vegan'], ['id' => 10]);
        $answerRepository->shouldReceive('create')
            ->once()
            ->with([
                'question_id' => 2,
                'answer' => 'Peanuts',
                'order_id' => 11,
                'product_id' => 13,
                'attendee_id' => 7,
            ]);

        $service = new AttendeeQuestionAnswerService($questionRepository, $answerRepository);

        $service->upsertProductAnswers($attendee, 42, [
            ['question_id' => 1, 'answer' => 'Vegan'],
            ['question_id' => 2, 'answer' => 'Peanuts'],
        ]);
    }

    public function test_it_rejects_missing_required_answers(): void
    {
        $questionRepository = Mockery::mock(QuestionRepositoryInterface::class);
        $questionRepository->shouldReceive('loadRelation')->once()->andReturnSelf();
        $questionRepository->shouldReceive('findByEventId')
            ->once()
            ->with(42)
            ->andReturn(collect([$this->createQuestion(1, true)]));

        $answerRepository = Mockery::mock(QuestionAnswerRepositoryInterface::class);
        $answerRepository->shouldNotReceive('findWhere');

        $service = new AttendeeQuestionAnswerService($questionRepository, $answerRepository);

        $this->expectException(ValidationException::class);

        $service->upsertProductAnswers($this->createAttendee(), 42, []);
    }

    public function test_it_rejects_questions_that_do_not_apply_to_the_attendee_product(): void
    {
        $otherProduct = (new ProductDomainObject)->setId(99);
        $question = $this->createQuestion(1, false)->setProducts(collect([$otherProduct]));

        $questionRepository = Mockery::mock(QuestionRepositoryInterface::class);
        $questionRepository->shouldReceive('loadRelation')->once()->andReturnSelf();
        $questionRepository->shouldReceive('findByEventId')
            ->once()
            ->with(42)
            ->andReturn(collect([$question]));

        $answerRepository = Mockery::mock(QuestionAnswerRepositoryInterface::class);
        $answerRepository->shouldNotReceive('findWhere');

        $service = new AttendeeQuestionAnswerService($questionRepository, $answerRepository);

        $this->expectException(ValidationException::class);

        $service->upsertProductAnswers($this->createAttendee(), 42, [
            ['question_id' => 1, 'answer' => 'Not applicable'],
        ]);
    }

    private function createAttendee(): AttendeeDomainObject
    {
        return (new AttendeeDomainObject)
            ->setId(7)
            ->setEventId(42)
            ->setOrderId(11)
            ->setProductId(13)
            ->setProductPriceId(17)
            ->setFirstName('Jane')
            ->setLastName('Attendee')
            ->setEmail('jane@example.com');
    }

    private function createQuestion(int $id, bool $required): QuestionDomainObject
    {
        $product = (new ProductDomainObject)->setId(13);

        return (new QuestionDomainObject)
            ->setId($id)
            ->setEventId(42)
            ->setTitle('Question '.$id)
            ->setRequired($required)
            ->setType(QuestionTypeEnum::SINGLE_LINE_TEXT->name)
            ->setBelongsTo(QuestionBelongsTo::PRODUCT->name)
            ->setIsHidden(false)
            ->setProducts(collect([$product]));
    }
}
