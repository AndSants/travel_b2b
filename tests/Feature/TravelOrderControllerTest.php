<?php

namespace Tests\Feature;

use App\Http\Controllers\TravelOrder\TravelOrderController;
use Tests\TestCase;
use App\Models\User;
use App\Models\TravelOrder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class TravelOrderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user,'api');

        Notification::fake();
    }

    public function test_list_travel_orders_no_query_params()
    {
        TravelOrder::factory()->count(5)->create(['user_id' => $this->user->id]);

        $request = new Request();
        $controller = new TravelOrderController();
        $response = $controller->index($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_list_travel_orders_with_status_query_param()
    {
        TravelOrder::factory()->count(5)->create(['user_id' => $this->user->id, 'status' => 'aprovado']);

        $request = new Request(['status' => 'aprovado']);
        $controller = new TravelOrderController();
        $response = $controller->index($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_list_travel_orders_with_destination_query_param()
    {
        TravelOrder::factory()->count(5)->create(['user_id' => $this->user->id, 'destination' => 'New York']);

        $request = new Request(['destination' => 'New York']);
        $controller = new TravelOrderController();
        $response = $controller->index($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_list_travel_orders_with_departure_and_return_date_query_param()
    {
        TravelOrder::factory()->count(5)->create(['user_id' => $this->user->id, 'departure_date' => Carbon::now()->addDays(1)->toDateString(), 'return_date' => Carbon::now()->addDays(5)->toDateString()]);

        $request = new Request(['departure_date' => Carbon::now()->addDays(1)->toDateString(), 'return_date' => Carbon::now()->addDays(5)->toDateString()]);
        $controller = new TravelOrderController();
        $response = $controller->index($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_list_travel_orders_with_all_query_param()
    {
        TravelOrder::factory()->count(5)->create(['user_id' => $this->user->id, 'status' => 'aprovado', 'destination' => 'New York', 'departure_date' => Carbon::now()->addDays(1)->toDateString(), 'return_date' => Carbon::now()->addDays(5)->toDateString()]);

        $request = new Request(['status' => 'aprovado', 'destination' => 'New York', 'departure_date' => Carbon::now()->addDays(1)->toDateString(), 'return_date' => Carbon::now()->addDays(5)->toDateString()]);
        $controller = new TravelOrderController();
        $response = $controller->index($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_list_travel_orders_with_invalid_query_param()
    {
        TravelOrder::factory()->count(5)->create(['user_id' => $this->user->id]);

        $request = new Request(['invalid_param' => 'invalid_value']);
        $controller = new TravelOrderController();
        $response = $controller->index($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_validation_fails_when_destination_is_missing()
    {
        $request = new Request(['departure_date' => '2022-01-01', 'return_date' => '2022-01-02']);
        $controller = new TravelOrderController();
        $response = $controller->store($request);
        $this->assertEquals(422, $response->getStatusCode());
    }
    public function test_validation_fails_when_destination_is_too_long()
    {
        $request = new Request(['destination' => str_repeat('a', 256), 'departure_date' => '2022-01-01', 'return_date' => '2022-01-02']);
        $controller = new TravelOrderController();
        $response = $controller->store($request);
        $this->assertEquals(422, $response->getStatusCode());
    }
    public function test_validation_fails_when_departure_date_is_missing()
    {
        $request = new Request(['destination' => 'Test Destination', 'return_date' => '2022-01-02']);
        $controller = new TravelOrderController();
        $response = $controller->store($request);
        $this->assertEquals(422, $response->getStatusCode());
    }
    public function test_validation_fails_when_departure_date_is_invalid()
    {
        $request = new Request(['destination' => 'Test Destination', 'departure_date' => 'invalid-date', 'return_date' => '2022-01-02']);
        $controller = new TravelOrderController();
        $response = $controller->store($request);
        $this->assertEquals(422, $response->getStatusCode());
    }
    public function test_validation_fails_when_return_date_is_missing()
    {
        $request = new Request(['destination' => 'Test Destination', 'departure_date' => '2022-01-01']);
        $controller = new TravelOrderController();
        $response = $controller->store($request);
        $this->assertEquals(422, $response->getStatusCode());
    }
    public function test_validation_fails_when_return_date_is_invalid()
    {
        $request = new Request(['destination' => 'Test Destination', 'departure_date' => '2022-01-01', 'return_date' => 'invalid-date']);
        $controller = new TravelOrderController();
        $response = $controller->store($request);
        $this->assertEquals(422, $response->getStatusCode());
    }
    public function test_validation_fails_when_return_date_is_before_departure_date()
    {
        $request = new Request(['destination' => 'Test Destination', 'departure_date' => '2022-01-02', 'return_date' => '2022-01-01']);
        $controller = new TravelOrderController();
        $response = $controller->store($request);
        $this->assertEquals(422, $response->getStatusCode());
    }
    public function test_successful_creation_of_travel_order()
    {
        $request = new Request(['destination' => 'Test Destination', 'departure_date' => '2022-01-01', 'return_date' => '2022-01-02']);
        $controller = new TravelOrderController();
        $response = $controller->store($request);
        $this->assertEquals(201, $response->getStatusCode());
    }
    
    public function test_travel_order_not_found()
    {
        $user = User::factory()->create();
        $travelOrderId = 999; // non-existent travel order ID
        $response = $this->actingAs($user)->getJson(route('travel-orders.show', $travelOrderId));
        $response->assertStatus(404);
    }
    
    // public function test_update_travel_order_with_valid_request_approve_order()
    // {
    //     $travelOrder = TravelOrder::factory()->create(['user_id' => $this->user->id, 'status' => 'solicitado']);

    //     $response = $this->user->putJson(route('travel-orders.update', $travelOrder->id), [
    //         'status' => 'aprovado',
    //     ]);

    //     $response->assertStatus(200);
    // }
    
    // public function test_destroy_travel_order_success()
    // {
    //     $travelOrder = TravelOrder::factory()->create();
    //     $user = $travelOrder->user;
    //     $this->actingAs($user);
    //     $response = $this->deleteJson(route('travel-orders.destroy', $travelOrder->id));
    //     $response->assertStatus(201);
    // }

}
