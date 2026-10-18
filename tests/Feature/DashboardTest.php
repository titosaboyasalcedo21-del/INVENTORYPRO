<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_requires_authentication(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_dashboard_renders_the_inventorypro_workspace_without_fake_metrics(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Panel principal')
            ->assertSee('Ventas recientes')
            ->assertSee('Productos activos')
            ->assertSee('href="'.route('products.index').'"', false)
            ->assertSee(route('profile.edit'))
            ->assertDontSee("You're logged in!");
    }
}
