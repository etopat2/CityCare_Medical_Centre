<?php

namespace App\Console\Commands;

use App\Services\PesapalService;
use Illuminate\Console\Command;

/**
 * Provides the register pesapal ipn application component and its related behavior.
 */
class RegisterPesapalIpn extends Command
{
    protected $signature = 'pesapal:register-ipn';

    protected $description = 'Register CityCare’s Pesapal payment notification URL';

    /**
     * Runs the middleware or command workflow and returns its result.
     */
    public function handle(PesapalService $pesapal): int
    {
        if (blank(config('services.pesapal.consumer_key')) || blank(config('services.pesapal.consumer_secret'))) {
            $this->components->error('Set PESAPAL_CONSUMER_KEY and PESAPAL_CONSUMER_SECRET before registering the Pesapal IPN.');

            return self::FAILURE;
        }

        $url = $pesapal->publicRoute('payments.pesapal.ipn');

        try {
            $result = $pesapal->registerIpn($url);
        } catch (\Throwable $exception) {
            report($exception);
            $this->components->error('Pesapal IPN registration failed. Check the application logs and Pesapal environment settings.');

            return self::FAILURE;
        }

        $ipnId = $result['ipn_id'] ?? null;
        if (! is_string($ipnId) || $ipnId === '') {
            $this->components->error('Pesapal did not return an IPN ID. Check the merchant account and application logs.');

            return self::FAILURE;
        }

        $this->components->info('Pesapal IPN registered. Set PESAPAL_IPN_ID in the application environment to: '.$ipnId);

        return self::SUCCESS;
    }
}
