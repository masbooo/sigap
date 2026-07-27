<?php

namespace Tests\Feature;

use App\Http\Controllers\Landing\HomeController;
use App\Supports\Payment\BpkadPaymentGateway;
use App\Supports\Payment\PaymentGateway;
use App\Supports\Payment\PaymentTestGateway;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_health_endpoint_returns_a_successful_response(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->get('/up')->assertOk();
    }

    public function test_web_routes_use_laravel_controller_actions(): void
    {
        $homeRoute = Route::getRoutes()->getByName('home');

        $this->assertNotNull($homeRoute);
        $this->assertSame(HomeController::class.'@index', $homeRoute->getActionName());
        $this->assertNotNull(Route::getRoutes()->getByName('admin.dashboard'));
        $this->assertNotNull(Route::getRoutes()->getByName('user.reservasi.index'));
    }

    public function test_landing_footer_does_not_expose_database_configuration(): void
    {
        $html = view('partials.landing.footer')->render();

        $this->assertStringNotContainsString(strtoupper((string) config('database.default')), $html);
        $this->assertStringContainsString('Copyright', $html);
    }

    public function test_payment_gateway_defaults_to_safe_test_mode(): void
    {
        $this->assertSame('test', config('payment.gateway'));
        $this->assertInstanceOf(PaymentTestGateway::class, app(PaymentGateway::class));
    }

    public function test_bpkad_payment_callback_route_is_registered(): void
    {
        $route = Route::getRoutes()->getByName('payment.callback.bpkad');

        $this->assertNotNull($route);
        $this->assertSame('payment/callback/bpkad', $route->uri());
        $this->assertContains('POST', $route->methods());
    }

    public function test_bpkad_signature_uses_method_path_timestamp_and_body_hash(): void
    {
        config(['payment.bpkad.secret' => 'sandbox-secret']);

        $body = json_encode([
            'external_reference' => 'SIGAP-GSG-2026-000001',
            'service_code' => '33',
            'object_type' => 'GSG',
            'customer_name' => 'PT Contoh',
            'description' => 'Pembayaran sewa GSG',
            'amount' => 1500000,
            'year' => 2026,
        ], JSON_UNESCAPED_SLASHES);
        $timestamp = '2026-07-28T10:15:00+07:00';
        $path = '/api/sandbox/external/sigap_gsg/payments/va';
        $stringToSign = "POST\n{$path}\n{$timestamp}\n" . hash('sha256', (string) $body);
        $expected = hash_hmac('sha256', $stringToSign, 'sandbox-secret');

        $method = (new ReflectionClass(BpkadPaymentGateway::class))->getMethod('signature');

        $this->assertSame($expected, $method->invoke(new BpkadPaymentGateway(), 'POST', $path, $timestamp, (string) $body));
    }
}
