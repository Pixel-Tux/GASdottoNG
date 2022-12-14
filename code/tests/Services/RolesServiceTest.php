<?php

namespace Tests\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use App\Exceptions\AuthException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Artisan;

use App\Role;
use App\User;
use App\Supplier;

class RolesServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        $this->supplier1 = Supplier::factory()->create();
        $this->supplier2 = Supplier::factory()->create();

		$this->userWithAdminPerm = $this->createRoleAndUser($this->gas, 'gas.permissions,users.admin');
        $this->userWithNoPerms = User::factory()->create(['gas_id' => $this->gas->id]);
    }

	/*
        Salvataggio Ruolo con permessi sbagliati
    */
    public function testFailsToStore()
    {
        $this->expectException(AuthException::class);
        $this->actingAs($this->userWithNoPerms);

        $this->services['roles']->store(array(
            'name' => 'Pippo',
            'parent_id' => 0,
        ));
    }

    /*
        Salvataggio Ruolo
    */
    public function testStore()
    {
        $this->actingAs($this->userWithAdminPerm);

        $role = $this->services['roles']->store(array(
            'name' => 'Pippo',
			'actions' => ['supplier.view', 'users.view'],
        ));

        $this->assertEquals('Pippo', $role->name);
        $this->assertEquals(0, $role->parent_id);
		$this->assertTrue($role->enabledAction('supplier.view'));
		$this->assertTrue($role->enabledAction('users.view'));
		$this->assertFalse($role->enabledAction('supplier.modify'));
    }

    /*
        Modifica Ruolo con permessi sbagliati
    */
    public function testFailsToUpdate()
    {
        $this->expectException(AuthException::class);
        $this->actingAs($this->userWithNoPerms);
        $this->services['roles']->update(0, array());
    }

    /*
        Modifica Ruolo con ID non esistente
    */
    public function testFailsToUpdateNoID()
    {
        $this->expectException(ModelNotFoundException::class);
        $this->actingAs($this->userWithAdminPerm);
        $this->services['roles']->update('id', array());
    }

    /*
        Modifica Ruolo
    */
    public function testUpdate()
    {
        $this->actingAs($this->userWithAdminPerm);

		$role = Role::inRandomOrder()->first();
		$this->assertNotEquals('Mario', $role->name);

        $role = $this->services['roles']->update($role->id, array(
            'name' => 'Mario',
        ));

        $this->assertEquals('Mario', $role->name);
    }

    /*
        Cancellazione Ruolo con permessi sbagliati
    */
    public function testFailsToDestroy()
    {
        $this->expectException(AuthException::class);
        $this->actingAs($this->userWithNoPerms);
		$role = Role::inRandomOrder()->first();
        $this->services['roles']->destroy($role->id);
    }
}
