<?php

namespace Tests\Unit\Resources\Attendee;

use HiEvents\DomainObjects\AttendeeDomainObject;
use HiEvents\Resources\Attendee\AttendeeResourcePublic;
use Illuminate\Http\Request;
use Tests\TestCase;

class AttendeeResourcePublicTest extends TestCase
{
    public function test_public_ticket_resource_includes_printed_ticket_number_and_seating(): void
    {
        $attendee = (new AttendeeDomainObject())
            ->setId(1)
            ->setProductId(20)
            ->setProductPriceId(30)
            ->setEmail('attendee@example.com')
            ->setFirstName('Jane')
            ->setLastName('Attendee')
            ->setPublicId('A-12345')
            ->setShortId('a-short')
            ->setPrintedTicketNumber('PRINT-TEST-7')
            ->setTableNumber(2)
            ->setSeatNumber(6)
            ->setStatus('ACTIVE');

        $resource = (new AttendeeResourcePublic($attendee))->toArray(Request::create('/'));

        $this->assertSame('PRINT-TEST-7', $resource['printed_ticket_number']);
        $this->assertSame(2, $resource['table_number']);
        $this->assertSame(6, $resource['seat_number']);
    }
}
