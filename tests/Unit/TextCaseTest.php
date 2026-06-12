<?php

namespace Tests\Unit;

use App\Support\TextCase;
use PHPUnit\Framework\TestCase;

class TextCaseTest extends TestCase
{
    public function test_capitalizes_first_letter_of_each_word(): void
    {
        $this->assertSame('Panadol Extra', TextCase::capitalizeWords('panadol extra'));
        $this->assertSame('Ali Khan', TextCase::capitalizeWords('ali khan'));
    }

    public function test_preserves_existing_uppercase_letters_in_word(): void
    {
        $this->assertSame('MRI Scan', TextCase::capitalizeWords('MRI scan'));
    }

    public function test_capitalizes_nested_request_fields(): void
    {
        $input = [
            'patient_name' => 'ali raza',
            'medicines' => [
                ['mdcn_name' => 'panadol', 'mdcn_type' => 'tablet'],
            ],
            'email' => 'dr@example.com',
        ];

        $patterns = [
            'patient_name',
            'medicines.*.mdcn_name',
        ];
        $result = TextCase::capitalizeInputArray($input, $patterns);

        $this->assertSame('Ali Raza', $result['patient_name']);
        $this->assertSame('Panadol', $result['medicines'][0]['mdcn_name']);
        $this->assertSame('tablet', $result['medicines'][0]['mdcn_type']);
        $this->assertSame('dr@example.com', $result['email']);
    }
}
