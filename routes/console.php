<?php

use App\Supports\Payment\PaymentGateway;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('payments:expire', function () {
    app(PaymentGateway::class)->expireOverduePayments();

    $this->info('Expired overdue payments.');
})->purpose('Mark overdue active payments as expired');

Schedule::command('payments:expire')->everyMinute();
