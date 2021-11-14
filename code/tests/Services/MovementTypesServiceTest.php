<?php

namespace Tests\Services;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use Artisan;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Exceptions\AuthException;

class MovementTypesServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        $this->gas = \App\Gas::factory()->create();

        $this->userWithAdminPerm = $this->createRoleAndUser($this->gas, 'movements.types');
        $this->userWithNoPerms = \App\User::factory()->create(['gas_id' => $this->gas->id]);

        $this->sample_type = \App\MovementType::factory()->create([
            'name' => 'Test',
            'system' => false,
            'sender_type' => 'App\Gas',
            'target_type' => 'App\Supplier',
        ]);

        $this->service = new \App\Services\MovementTypesService();
    }

    /*
        Creazione Tipo di Movimento
    */
    public function testStore()
    {
        $this->actingAs($this->userWithAdminPerm);

        $type = $this->service->store(array(
            'name' => 'Donazione al GAS',
            'sender_type' => 'App\Gas',
            'target_type' => 'App\User',
        ));

        $this->assertEquals($type->exists, true);
    }

    /*
        Modifica Tipo di Movimento con permessi sbagliati
    */
    public function testFailsToUpdate()
    {
        $this->expectException(AuthException::class);
        $this->actingAs($this->userWithNoPerms);
        $this->service->update($this->sample_type->id, array());
    }

    /*
        Modifica Tipo di Movimento
    */
    public function testUpdate()
    {
        $this->actingAs($this->userWithAdminPerm);

        $this->service->update($this->sample_type->id, array(
            'default_notes' => 'Test nota',
            'sender_type' => 'App\Gas',
            'target_type' => 'App\Supplier',
            'cash' => true,
            'App\Gas-bank-cash' => 'decrement',
            'App\Supplier-bank-cash' => 'increment',
        ));

        $type = $this->service->show($this->sample_type->id);
        $this->assertEquals($type->hasPayment('bank'), false);
        $this->assertEquals($type->hasPayment('cash'), true);
    }

    /*
        Accesso Tipo di Movimento con ID non esistente
    */
    public function testFailsToShowInexistent()
    {
        $this->expectException(ModelNotFoundException::class);
        $this->actingAs($this->userWithAdminPerm);
        $this->service->show('random');
    }

    /*
        Accesso Tipo di Movimento
    */
    public function testShow()
    {
        $this->actingAs($this->userWithAdminPerm);

        $type = $this->service->show($this->sample_type->id);

        $this->assertEquals($this->sample_type->id, $type->id);
        $this->assertEquals($this->sample_type->sender_type, $type->sender_type);
        $this->assertEquals($this->sample_type->target_type, $type->target_type);
    }

    /*
        Cancellazione Tipo di Movimento con permessi sbagliati
    */
    public function testFailsToDestroy()
    {
        $this->expectException(AuthException::class);
        $this->actingAs($this->userWithNoPerms);
        $this->service->destroy($this->sample_type->id);
    }

    /*
        Cancellazione Tipo di Movimento
    */
    public function testDestroy()
    {
        $this->actingAs($this->userWithAdminPerm);
        $this->service->destroy($this->sample_type->id);
        $this->assertNull(\App\MovementType::find($this->sample_type->id));

        try {
            $this->service->show($this->sample_type->id);
            $this->fail('should never run');
        } catch (ModelNotFoundException $e) {
            //good boy
        }
    }
}
