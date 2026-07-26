<?php

namespace App\Console\Commands;

use App\Models\Bank;
use Illuminate\Support\Facades\Http;
use Illuminate\Console\Command;

class FetchBanks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'paystack:fetch-banks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $response = Http::withToken(config('services.paystack.secret'))
            ->get('https://api.paystack.co/bank');

        if ($response->successful()) {
            foreach ($response['data'] as $bank) {
                Bank::updateOrCreate(
                    ['code' => $bank['code']],
                    ['name' => $bank['name'], 'slug' => $bank['slug']]
                );
            }
            $this->info('Banks updated successfully!');
        } else {
            $this->error('Failed to fetch banks from Paystack');
        }
    }
}
