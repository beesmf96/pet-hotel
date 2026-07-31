<?php

namespace Tests\Feature\Filament\Admin;

use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\ViewUser;
use App\Filament\Resources\UserResource\RelationManagers\PetsRelationManager;
use App\Models\Pet;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    }

    public function test_list_page_renders_users(): void
    {
        $users = User::factory()->count(3)->create();

        Livewire::test(ListUsers::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords($users->push($this->admin));
    }

    public function test_list_page_can_search_by_email(): void
    {
        $match = User::factory()->create(['email' => 'findme@example.test']);
        $other = User::factory()->create(['email' => 'someone@example.test']);

        Livewire::test(ListUsers::class)
            ->searchTable('findme@example.test')
            ->assertCanSeeTableRecords([$match])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_list_page_counts_pets_per_user(): void
    {
        $user = User::factory()->create();
        Pet::factory()->count(2)->create(['user_id' => $user->id]);

        Livewire::test(ListUsers::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$user]);

        $this->assertSame(2, $user->pets()->count());
    }

    public function test_view_page_renders_a_user(): void
    {
        $user = User::factory()->create();

        Livewire::test(ViewUser::class, ['record' => $user->getRouteKey()])
            ->assertSuccessful();
    }

    public function test_pets_relation_manager_lists_the_users_pets(): void
    {
        $user = User::factory()->create();
        $pets = Pet::factory()->count(2)->create(['user_id' => $user->id]);

        Livewire::test(PetsRelationManager::class, [
            'ownerRecord' => $user,
            'pageClass' => ViewUser::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords($pets);
    }

    public function test_pets_relation_manager_excludes_other_users_pets(): void
    {
        $user = User::factory()->create();
        $foreign = Pet::factory()->create();

        Livewire::test(PetsRelationManager::class, [
            'ownerRecord' => $user,
            'pageClass' => ViewUser::class,
        ])
            ->assertCanNotSeeTableRecords([$foreign]);
    }
}
