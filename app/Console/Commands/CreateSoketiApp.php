<?php

namespace App\Console\Commands;

use App\Models\SoketiApp;
use Illuminate\Support\Str;
use Illuminate\Console\Command;

class CreateSoketiApp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:soketi-app';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Soketi app if not exists';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (SoketiApp::count()) {
            return;
        }

        SoketiApp::forceCreate([
            'id' => rand(100000000, 999999999),
            'key' => Str::random(16),
            'secret' => Str::random(24),
            'max_connections' => -1,
            'enable_client_messages' => true,
            'enabled' => true,
            'max_backend_events_per_sec' => -1,
            'max_client_events_per_sec' => -1,
            'max_read_req_per_sec' => -1,
            'webhooks' => [],
            'enable_user_authentication' => false,
        ]);
    }
}
