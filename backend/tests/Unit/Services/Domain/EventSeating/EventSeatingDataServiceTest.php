<?php

namespace Tests\Unit\Services\Domain\EventSeating;

use HiEvents\Services\Domain\EventSeating\EventSeatingDataService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class EventSeatingDataServiceTest extends TestCase
{
    public function testItReturnsLayoutAndAttendeeAssignments(): void
    {
        $settingsQuery = Mockery::mock(Builder::class);
        $settingsQuery->shouldReceive('where')->with('event_id', 42)->andReturnSelf();
        $settingsQuery->shouldReceive('first')->andReturn((object)[
            'table_count' => 2,
            'seats_per_table' => 4,
            'blueprint_image_id' => null,
            'table_positions' => '[{"table_number":1,"x":25,"y":50,"size":125}]',
        ]);

        $attendeesQuery = Mockery::mock(Builder::class);
        $attendeesQuery->shouldReceive('select')->once()->andReturnSelf();
        $attendeesQuery->shouldReceive('where')->with('event_id', 42)->andReturnSelf();
        $attendeesQuery->shouldReceive('whereNull')->with('deleted_at')->andReturnSelf();
        $attendeesQuery->shouldReceive('whereNotNull')->with('table_number')->andReturnSelf();
        $attendeesQuery->shouldReceive('whereNotNull')->with('seat_number')->andReturnSelf();
        $attendeesQuery->shouldReceive('orderBy')->with('table_number')->andReturnSelf();
        $attendeesQuery->shouldReceive('orderBy')->with('seat_number')->andReturnSelf();
        $attendeesQuery->shouldReceive('get')->andReturn(new Collection([
            (object)[
                'id' => 7,
                'first_name' => 'Jane',
                'last_name' => 'Attendee',
                'email' => 'jane@example.com',
                'table_number' => 2,
                'seat_number' => 3,
            ],
        ]));

        $availableAttendeesQuery = Mockery::mock(Builder::class);
        $availableAttendeesQuery->shouldReceive('select')->once()->andReturnSelf();
        $availableAttendeesQuery->shouldReceive('where')->with('event_id', 42)->andReturnSelf();
        $availableAttendeesQuery->shouldReceive('where')->with('status', 'ACTIVE')->andReturnSelf();
        $availableAttendeesQuery->shouldReceive('whereNull')->with('deleted_at')->andReturnSelf();
        $availableAttendeesQuery->shouldReceive('whereNull')->with('table_number')->andReturnSelf();
        $availableAttendeesQuery->shouldReceive('whereNull')->with('seat_number')->andReturnSelf();
        $availableAttendeesQuery->shouldReceive('orderBy')->with('last_name')->andReturnSelf();
        $availableAttendeesQuery->shouldReceive('orderBy')->with('first_name')->andReturnSelf();
        $availableAttendeesQuery->shouldReceive('get')->andReturn(new Collection([
            (object)[
                'id' => 8,
                'first_name' => 'Open',
                'last_name' => 'Guest',
                'email' => 'open@example.com',
            ],
        ]));

        DB::shouldReceive('table')
            ->with('event_seating_settings')
            ->once()
            ->andReturn($settingsQuery);
        DB::shouldReceive('table')
            ->with('attendees')
            ->twice()
            ->andReturn($attendeesQuery, $availableAttendeesQuery);

        $result = (new EventSeatingDataService())->get(42);

        $this->assertSame(2, $result['table_count']);
        $this->assertSame(4, $result['seats_per_table']);
        $this->assertSame(8, $result['total_seats']);
        $this->assertSame(1, $result['assigned_seats']);
        $this->assertSame([
            'attendee_id' => 7,
            'first_name' => 'Jane',
            'last_name' => 'Attendee',
            'email' => 'jane@example.com',
            'table_number' => 2,
            'seat_number' => 3,
        ], $result['assignments'][0]);
        $this->assertSame([
            'attendee_id' => 8,
            'first_name' => 'Open',
            'last_name' => 'Guest',
            'email' => 'open@example.com',
        ], $result['available_attendees'][0]);
        $this->assertSame([
            ['table_number' => 1, 'x' => 25, 'y' => 50, 'size' => 125],
        ], $result['table_positions']);
        $this->assertNull($result['blueprint']);
    }
}
