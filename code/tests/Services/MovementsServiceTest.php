<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\Model;

use Artisan;

class MovementsServiceTest extends TestCase
{
    use DatabaseTransactions;

    private $userWithAdminPerm;
    private $userWithReferrerPerms;
    private $userWithNoPerms;
    private $gas;
    private $service;
    private $sample_movement;

    public function setUp(): void
    {
        parent::setUp();
        Model::unguard();

        $this->gas = factory(\App\Gas::class)->create();

        $admin_role = \App\Role::create([
            'name' => 'Admin',
            'actions' => 'movements.admin'
        ]);
        $this->userWithAdminPerm = factory(\App\User::class)->create([
            'gas_id' => $this->gas->id
        ]);
        $this->userWithAdminPerm->addRole($admin_role, $this->gas);

        $treasurer_role = \App\Role::create([
            'name' => 'Treasurer',
            'actions' => 'movements.view'
        ]);
        $this->userWithReferrerPerms = factory(\App\User::class)->create([
            'gas_id' => $this->gas->id
        ]);
        $this->userWithReferrerPerms->addRole($treasurer_role, $this->gas);

        $this->userWithNoPerms = factory(\App\User::class)->create([
            'gas_id' => $this->gas->id
        ]);

        $this->sample_movement = factory(\App\Movement::class)->create([
            'type' => 'donation-from-gas',
            'method' => 'bank',
            'sender_id' => $this->gas->id,
            'sender_type' => 'App\Gas',
            'registerer_id' => $this->userWithAdminPerm->id
        ]);

        Model::reguard();

        $this->service = new \App\Services\MovementsService();
    }

    public function testStore()
    {
        $this->actingAs($this->userWithAdminPerm);

        $this->service->store(array(
            'type' => 'donation-from-gas',
            'method' => 'bank',
            'sender_id' => $this->gas->id,
            'sender_type' => 'App\Gas',
            'amount' => 100
        ));

        $amount = 100 + $this->sample_movement->amount;

        $this->assertEquals($amount * -1, $this->gas->current_balance_amount);
    }

    /**
     * @expectedException \App\Exceptions\AuthException
     */
    public function testFailsToUpdate()
    {
        $this->actingAs($this->userWithReferrerPerms);

        $this->service->update($this->sample_movement->id, array());
    }

    /**
     * @expectedException \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function testFailsToUpdateBecauseNoMovementWithID()
    {
        $this->actingAs($this->userWithAdminPerm);

        $this->service->update('id', array());
    }

    public function testUpdate()
    {
        $this->actingAs($this->userWithAdminPerm);

        $this->service->update($this->sample_movement->id, array(
            'amount' => 50
        ));

        $this->assertEquals(-50, $this->gas->current_balance_amount);
    }

    /**
     * @expectedException \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function testFailsToShowInexistent()
    {
        $this->actingAs($this->userWithReferrerPerms);

        $this->service->show('random');
    }

    public function testShow()
    {
        $this->actingAs($this->userWithReferrerPerms);

        $movement = $this->service->show($this->sample_movement->id);

        $this->assertEquals($this->sample_movement->id, $movement->id);
        $this->assertEquals($this->sample_movement->amount, $movement->amount);
    }

    /**
     * @expectedException \App\Exceptions\AuthException
     */
    public function testFailsToDestroy()
    {
        $this->actingAs($this->userWithNoPerms);

        $this->service->destroy($this->sample_movement->id);
    }

    public function testDestroy()
    {
        $this->actingAs($this->userWithAdminPerm);
        $this->service->destroy($this->sample_movement->id);
        $this->assertEquals(0, $this->gas->current_balance_amount);

        try {
            $this->service->show($this->sample_movement->id);
            $this->fail('should never run');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            //good boy
        }
    }

    public function testUserFees()
    {
        $this->actingAs($this->userWithAdminPerm);

        $this->userWithNoPerms->gas->setConfig('annual_fee_amount', 5);

        $this->service->store(array(
            'type' => 'annual-fee',
            'method' => 'bank',
            'target_id' => $this->userWithNoPerms->gas->id,
            'target_type' => 'App\Gas',
            'sender_id' => $this->userWithNoPerms->id,
            'sender_type' => 'App\User',
            'amount' => $this->userWithNoPerms->gas->getConfig('annual_fee_amount'),
        ));

        $reloaded = \App\User::find($this->userWithNoPerms->id);
        $this->assertNotEquals($reloaded->fee_id, 0);
        $fee = \App\Movement::find($reloaded->fee_id);
        $this->assertEquals($fee->amount, 5);

        $expiration = date('Y-m-d', strtotime('-5 days'));
        $this->userWithNoPerms->gas->setConfig('year_closing', $expiration);
        Artisan::call('check:fees');

        $this->userWithNoPerms->fresh();
        $this->assertEquals($this->userWithNoPerms->fee_id, 0);

        $new_date = $this->userWithNoPerms->gas->getConfig('year_closing');
        $expiration = date('Y-m-d', strtotime($expiration . ' +1 years'));
        $this->assertEquals($new_date, $expiration);
    }
}
