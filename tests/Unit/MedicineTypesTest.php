<?php

namespace Tests\Unit;

use App\Support\MedicineTypes;
use PHPUnit\Framework\TestCase;

class MedicineTypesTest extends TestCase
{
    public function test_normalizes_legacy_types_to_standard_values(): void
    {
        $this->assertSame('Tab.', MedicineTypes::normalize('Tablet'));
        $this->assertSame('Cap.', MedicineTypes::normalize('Capsule'));
        $this->assertSame('Syp.', MedicineTypes::normalize('Syrup'));
        $this->assertSame('Inj.', MedicineTypes::normalize('Inj'));
        $this->assertSame('Inj.', MedicineTypes::normalize('Injection'));
        $this->assertSame('Mix.', MedicineTypes::normalize('Cream'));
        $this->assertSame('Mix.', MedicineTypes::normalize('Drops'));
    }

    public function test_allowed_types_are_the_five_select_options(): void
    {
        $this->assertSame(['Tab.', 'Cap.', 'Syp.', 'Mix.', 'Inj.'], MedicineTypes::allowed());
    }

    public function test_is_injection_type_only_for_inj(): void
    {
        $this->assertTrue(MedicineTypes::isInjectionType('Inj.'));
        $this->assertTrue(MedicineTypes::isInjectionType('Injection'));
        $this->assertFalse(MedicineTypes::isInjectionType('Mix.'));
        $this->assertFalse(MedicineTypes::isInjectionType('Tab.'));
    }
}
