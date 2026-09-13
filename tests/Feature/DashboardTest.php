<?php

namespace Tests\Feature;

use App\Models\Sala;
use App\Models\SalaImagen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_see_active_spaces_with_their_primary_image(): void
    {
        $user = User::factory()->create();
        Storage::fake('public');
        $aulaMagna = Sala::factory()->create([
            'nombre' => 'Aula Magna',
            'descripcion' => 'Sala para conferencias y actos institucionales.',
            'capacidad' => 120,
            'hora_inicio_operativo' => '07:30:00',
            'hora_fin_operativo' => '20:30:00',
        ]);
        $gabinete = Sala::factory()->create(['nombre' => 'Gabinete de Informática', 'capacidad' => 30]);
        $salaInactiva = Sala::factory()->inactiva()->create(['nombre' => 'Sala en mantenimiento']);
        $primaryImage = SalaImagen::factory()->for($aulaMagna)->create([
            'ruta' => 'salas/'.$aulaMagna->id.'/portada.jpg',
            'orden' => 1,
        ]);
        SalaImagen::factory()->for($aulaMagna)->create([
            'ruta' => 'salas/'.$aulaMagna->id.'/secundaria.jpg',
            'orden' => 2,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response
            ->assertSee('Aula Magna')
            ->assertSee('Sala para conferencias y actos institucionales.')
            ->assertSee('Horario operativo:')
            ->assertSee('07:30 a 20:30')
            ->assertSee('Gabinete de Informática')
            ->assertDontSee('ESPACIO INSTITUCIONAL')
            ->assertDontSee($salaInactiva->nombre)
            ->assertSee(Storage::disk('public')->url($primaryImage->ruta), false)
            ->assertDontSee('secundaria.jpg', false)
            ->assertSee(route('calendar', ['sala' => $aulaMagna->id]), false)
            ->assertSee(route('calendar', ['sala' => $gabinete->id]), false)
            ->assertDontSee(route('calendar', ['sala' => $salaInactiva->id]), false);
    }
}
