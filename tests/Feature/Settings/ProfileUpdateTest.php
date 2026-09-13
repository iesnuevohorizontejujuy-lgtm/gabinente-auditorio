<?php

namespace Tests\Feature\Settings;

use App\Models\Carrera;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $this->actingAs($user = User::factory()->create());

        $this->get(route('profile.edit'))->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('pages::settings.profile')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('carreras', 'Enfermería')
            ->call('updateProfileInformation');

        $response->assertHasNoErrors();

        $user->refresh();

        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('pages::settings.profile')
            ->set('name', 'Test User')
            ->set('email', $user->email)
            ->set('carreras', 'Enfermería')
            ->call('updateProfileInformation');

        $response->assertHasNoErrors();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_professor_can_associate_multiple_careers_from_their_profile(): void
    {
        $user = User::factory()->create();

        $response = Livewire::actingAs($user)
            ->test('pages::settings.profile')
            ->set('carreras', 'Enfermería, Desarrollo de Software, enfermería')
            ->call('updateProfileInformation');

        $response->assertHasNoErrors();

        $careerNames = $user->refresh()->carreras()->orderBy('nombre')->pluck('nombre')->all();

        $this->assertSame(['Desarrollo de Software', 'Enfermería'], $careerNames);
        $this->assertDatabaseCount('carreras', 2);
    }

    public function test_professor_must_provide_at_least_one_career(): void
    {
        $user = User::factory()->create();
        Carrera::factory()->create(['nombre' => 'Enfermería']);

        $response = Livewire::actingAs($user)
            ->test('pages::settings.profile')
            ->set('carreras', '   ')
            ->call('updateProfileInformation');

        $response->assertHasErrors(['carreras']);
        $this->assertDatabaseMissing('carrera_user', ['user_id' => $user->id]);
    }

    public function test_administrator_can_update_profile_without_careers(): void
    {
        $administrator = User::factory()->administrator()->create();

        $response = Livewire::actingAs($administrator)
            ->test('pages::settings.profile')
            ->set('name', 'Administración Académica')
            ->call('updateProfileInformation');

        $response->assertHasNoErrors();

        $this->assertSame('Administración Académica', $administrator->refresh()->name);
        $this->assertDatabaseMissing('carrera_user', ['user_id' => $administrator->id]);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('pages::settings.delete-user-modal')
            ->set('password', 'password')
            ->call('deleteUser');

        $response
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertNull($user->fresh());
        $this->assertFalse(auth()->check());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = Livewire::test('pages::settings.delete-user-modal')
            ->set('password', 'wrong-password')
            ->call('deleteUser');

        $response->assertHasErrors(['password']);

        $this->assertNotNull($user->fresh());
    }
}
