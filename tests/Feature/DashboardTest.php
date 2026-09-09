<?php

namespace Tests\Feature;

use App\Models\Sala;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $gabinete = Sala::factory()->create(['nombre' => 'Gabinete de Informática', 'capacidad' => 30]);
        $auditorio = Sala::factory()->create(['nombre' => 'Sala Auditorio', 'capacidad' => 120]);
        $streaming = Sala::factory()->create(['nombre' => 'Sala de Streaming', 'capacidad' => 12]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertSee('Gabinete')
            ->assertSee('Auditorio')
            ->assertSee('Sala de Streaming')
            ->assertSee(route('calendar', ['sala' => $gabinete->id]), false)
            ->assertSee(route('calendar', ['sala' => $auditorio->id]), false)
            ->assertSee(route('calendar', ['sala' => $streaming->id]), false);
    }
}
