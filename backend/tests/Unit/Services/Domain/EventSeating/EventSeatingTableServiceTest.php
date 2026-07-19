<?php

namespace Tests\Unit\Services\Domain\EventSeating;

use HiEvents\Services\Domain\EventSeating\EventSeatingTableService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EventSeatingTableServiceTest extends TestCase
{
    #[Test]
    public function it_expands_mixed_table_types_into_numbered_tables(): void
    {
        $service = new EventSeatingTableService;
        $types = $service->normalize([
            ['id' => 'rounds', 'name' => 'Rounds', 'shape' => 'round', 'table_count' => 2, 'seats_per_table' => 8],
            ['id' => 'banquet', 'name' => 'Banquet', 'shape' => 'rectangle', 'table_count' => 1, 'seats_per_table' => 12],
        ]);

        $this->assertSame([
            ['table_number' => 1, 'table_type_id' => 'rounds', 'type_name' => 'Rounds', 'shape' => 'round', 'seats_per_table' => 8],
            ['table_number' => 2, 'table_type_id' => 'rounds', 'type_name' => 'Rounds', 'shape' => 'round', 'seats_per_table' => 8],
            ['table_number' => 3, 'table_type_id' => 'banquet', 'type_name' => 'Banquet', 'shape' => 'rectangle', 'seats_per_table' => 12],
        ], $service->expand($types));
    }

    #[Test]
    public function it_converts_legacy_uniform_settings_to_a_round_table_type(): void
    {
        $types = (new EventSeatingTableService)->normalize([], 3, 10);

        $this->assertSame('round', $types[0]['shape']);
        $this->assertSame(3, $types[0]['table_count']);
        $this->assertSame(10, $types[0]['seats_per_table']);
    }
}
