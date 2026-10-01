<?php

namespace Tests\Unit;

use App\Models\Personnel;
use PHPUnit\Framework\TestCase;

class PersonnelRenewalValidityTest extends TestCase
{
    public function test_validity_uses_the_birthday_two_calendar_years_after_renewal(): void
    {
        $validity = Personnel::renewalValidityDate('1990-04-15', '2026-09-30');

        $this->assertSame('2028-04-15', $validity->toDateString());
    }

    public function test_leap_day_birthday_uses_february_28_in_a_non_leap_year(): void
    {
        $validity = Personnel::renewalValidityDate('1992-02-29', '2025-06-10');

        $this->assertSame('2027-02-28', $validity->toDateString());
    }

    public function test_missing_birthday_falls_back_to_two_years_from_renewal(): void
    {
        $validity = Personnel::renewalValidityDate(null, '2026-09-30');

        $this->assertSame('2028-09-30', $validity->toDateString());
    }
}
