<?php

namespace HiEvents\Services\Domain\EventSeating;

class EventSeatingTableService
{
    private const SHAPES = ['round', 'square', 'rectangle'];

    public function normalize(mixed $tableTypes, int $legacyTableCount = 0, int $legacySeatsPerTable = 0): array
    {
        if (is_string($tableTypes)) {
            $tableTypes = json_decode($tableTypes, true) ?: [];
        }

        if (! is_array($tableTypes) || $tableTypes === []) {
            return $legacyTableCount > 0 && $legacySeatsPerTable > 0
                ? [[
                    'id' => 'default',
                    'name' => 'Table type 1',
                    'shape' => 'round',
                    'table_count' => $legacyTableCount,
                    'seats_per_table' => $legacySeatsPerTable,
                ]]
                : [];
        }

        return array_values(array_map(static function (array $type, int $index): array {
            $shape = strtolower((string) ($type['shape'] ?? 'round'));

            return [
                'id' => (string) ($type['id'] ?? 'type-'.($index + 1)),
                'name' => (string) ($type['name'] ?? 'Table type '.($index + 1)),
                'shape' => in_array($shape, self::SHAPES, true) ? $shape : 'round',
                'table_count' => max(0, (int) ($type['table_count'] ?? 0)),
                'seats_per_table' => max(0, (int) ($type['seats_per_table'] ?? 0)),
            ];
        }, $tableTypes, array_keys($tableTypes)));
    }

    public function expand(array $tableTypes): array
    {
        $tables = [];
        $tableNumber = 1;

        foreach ($tableTypes as $type) {
            for ($index = 0; $index < $type['table_count']; $index++) {
                $tables[] = [
                    'table_number' => $tableNumber++,
                    'table_type_id' => $type['id'],
                    'type_name' => $type['name'],
                    'shape' => $type['shape'],
                    'seats_per_table' => $type['seats_per_table'],
                ];
            }
        }

        return $tables;
    }
}
