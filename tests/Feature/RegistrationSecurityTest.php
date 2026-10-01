<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_registration_rejects_duplicate_email_and_serial_numbers(): void
    {
        $staff = $this->createStaff();
        $payload = $this->validRegistration();

        $this->withSession(['user' => (array) $staff])
            ->postJson('/staff/personnel', $payload)
            ->assertOk();

        $this->withSession(['user' => (array) $staff])
            ->postJson('/staff/personnel', array_merge($payload, [
                'afpSerialNumber' => 'AFP-002',
                'pistolSerialNumber' => 'PISTOL-002',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->withSession(['user' => (array) $staff])
            ->postJson('/staff/personnel', array_merge($payload, [
                'email' => 'second@example.com',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['afpSerialNumber', 'pistolSerialNumber']);
    }

    public function test_staff_registration_requires_numeric_contact_and_known_issuer(): void
    {
        $staff = $this->createStaff();

        $this->withSession(['user' => (array) $staff])
            ->postJson('/staff/personnel', array_merge($this->validRegistration(), [
                'contactNumber' => '0917-ABC-1234',
                'issuedBy' => 'Unknown Person',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['contactNumber', 'issuedBy']);
    }

    public function test_step_one_availability_check_blocks_existing_identifiers(): void
    {
        $staff = $this->createStaff();
        $payload = $this->validRegistration();

        $this->withSession(['user' => (array) $staff])
            ->postJson('/staff/personnel', $payload)
            ->assertOk();

        $this->withSession(['user' => (array) $staff])
            ->postJson('/staff/personnel/check-availability', [
                'afpSerialNumber' => strtolower($payload['afpSerialNumber']),
                'email' => strtoupper($payload['email']),
                'contactNumber' => $payload['contactNumber'],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('available', false)
            ->assertJsonStructure([
                'errors' => ['afpSerialNumber', 'email', 'contactNumber'],
            ]);

        $this->withSession(['user' => (array) $staff])
            ->postJson('/staff/personnel/check-availability', [
                'pistolSerialNumber' => strtolower($payload['pistolSerialNumber']),
            ])
            ->assertUnprocessable()
            ->assertJsonPath('available', false)
            ->assertJsonValidationErrors('pistolSerialNumber');

        $this->withSession(['user' => (array) $staff])
            ->postJson('/staff/personnel', array_merge($payload, [
                'email' => 'different@example.com',
                'afpSerialNumber' => 'AFP-999',
                'pistolSerialNumber' => 'PISTOL-999',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('contactNumber');
    }

    public function test_login_requires_a_correct_single_use_captcha(): void
    {
        $this->withSession(['login_captcha_answer' => 10])
            ->postJson('/login', [
                'email' => 'nobody@example.com',
                'password' => 'Incorrect!1',
                'captcha' => 9,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['captcha_question']);

        $response = $this->withSession(['login_captcha_answer' => 10])
            ->postJson('/login', [
                'email' => 'nobody@example.com',
                'password' => 'Incorrect!1',
                'captcha' => 10,
            ]);

        $response->assertUnauthorized()->assertJsonStructure(['captcha_question']);

        $this->postJson('/login', [
            'email' => 'nobody@example.com',
            'password' => 'Incorrect!1',
            'captcha' => 99,
        ])->assertUnprocessable();
    }

    private function validRegistration(): array
    {
        return [
            'lastName' => 'Dela Cruz',
            'firstName' => 'Juan',
            'rank' => 'CPT',
            'unit' => 'APAO, PA',
            'dateOfBirth' => '1990-01-01',
            'email' => 'juan@example.com',
            'contactNumber' => '09171234567',
            'afpSerialNumber' => 'AFP-001',
            'pistolNomenclature' => 'Pistol 9mm, Glock 17',
            'pistolSerialNumber' => 'PISTOL-001',
            'issuedBy' => 'MS ROSEMARIE O VILBAR',
            'qtyAmmo' => 20,
        ];
    }

    private function createStaff(): object
    {
        $id = DB::table('users')->insertGetId([
            'name' => 'Staff User',
            'email' => 'staff@example.com',
            'password' => Hash::make('Correct!Pass1'),
            'role' => 'staff',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('users')->where('id', $id)->first();
    }
}
