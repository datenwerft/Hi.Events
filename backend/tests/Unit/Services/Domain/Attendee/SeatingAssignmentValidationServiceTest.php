<?php

namespace Tests\Unit\Services\Domain\Attendee;

use HiEvents\Services\Domain\Attendee\SeatingAssignmentValidationService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SeatingAssignmentValidationServiceTest extends TestCase
{
    #[Test]
    public function it_validates_the_seat_capacity_of_the_selected_table_type(): void
    {
        $settingsQuery = Mockery::mock(Builder::class);
        $settingsQuery->shouldReceive('where')->with('event_id', 42)->andReturnSelf();
        $settingsQuery->shouldReceive('first')->andReturn((object) [
            'table_count' => 2,
            'seats_per_table' => 10,
            'table_types' => json_encode([
                ['id' => 'small', 'name' => 'Small', 'shape' => 'square', 'table_count' => 1, 'seats_per_table' => 4],
                ['id' => 'large', 'name' => 'Large', 'shape' => 'rectangle', 'table_count' => 1, 'seats_per_table' => 10],
            ]),
        ]);

        DB::shouldReceive('table')
            ->with('event_seating_settings')
            ->once()
            ->andReturn($settingsQuery);

        try {
            (new SeatingAssignmentValidationService)->validate(42, 1, 5);
            $this->fail('Expected the seat assignment to be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('seat_number', $exception->errors());
        }
    }
}
