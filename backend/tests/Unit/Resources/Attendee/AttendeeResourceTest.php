<?php

namespace Tests\Unit\Resources\Attendee;

use HiEvents\DomainObjects\AttendeeDomainObject;
use HiEvents\Resources\Attendee\AttendeeResource;
use Illuminate\Http\Request;
use Tests\TestCase;

class AttendeeResourceTest extends TestCase
{
    public function test_resource_includes_printed_ticket_number(): void
    {
        $attendee = (new AttendeeDomainObject())
            ->setId(1)
            ->setOrderId(10)
            ->setProductId(20)
            ->setProductPriceId(30)
            ->setEventId(40)
            ->setEmail('attendee@example.com')
            ->setStatus('ACTIVE')
            ->setFirstName('Jane')
            ->setLastName('Attendee')
            ->setPublicId('A-12345')
            ->setShortId('A-SHORT')
            ->setLocale('en')
            ->setPrintedTicketNumber('PRINT-4711')
            ->setCreatedAt('2026-07-18 00:00:00')
            ->setUpdatedAt('2026-07-18 00:00:00');

        $resource = (new AttendeeResource($attendee))->toArray(Request::create('/'));

        $this->assertSame('PRINT-4711', $resource['printed_ticket_number']);
    }
}
