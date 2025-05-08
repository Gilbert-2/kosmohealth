<?php

namespace App\Traits;

use App\Helpers\SysHelper;
use App\Models\Config\Config;
use App\Models\SoketiApp;
use Illuminate\Support\Arr;

trait SocketService
{
    public function socketConfig()
    {
        if (! SysHelper::getApp('INSTALLED')) {
            return;
        }

        $pusher_config = Config::where('name', 'pusher')->first()?->value;

        if (! $pusher_config) {
            return;
        }

        if (Arr::get($pusher_config, 'use_soketi', false)) {
            $this->setupSoketi($pusher_config);
            return;
        }

        $this->setupPusher($pusher_config);
        return;
    }

    private function setupPusher(array $config = [])
    {
        config([
            'broadcasting.connections.pusher.key' => Arr::get($config, 'pusher_app_key', ''),
            'broadcasting.connections.pusher.secret' => Arr::get($config, 'pusher_app_secret', ''),
            'broadcasting.connections.pusher.app_id' => Arr::get($config, 'pusher_app_id', ''),
            'broadcasting.connections.pusher.options.cluster' => Arr::get($config, 'pusher_app_cluster', ''),
            'broadcasting.connections.pusher.options.useTLS' => true,
        ]);
    }

    private function setupSoketi()
    {
        $file = database_path('socket.json');

        if (!file_exists($file)) {
            return;
        }

        $soketiApp = SoketiApp::first();

        if (! $soketiApp) {
            return;
        }

        $hostname = request()->getHost();

        $data = json_decode(file_get_contents($file), true) ? : [];

        config([
            'broadcasting.connections.pusher.key'    => $soketiApp->key,
            'broadcasting.connections.pusher.secret' => $soketiApp->secret,
            'broadcasting.connections.pusher.app_id' => $soketiApp->id,
            'broadcasting.connections.pusher.options.host' => $hostname,
            'broadcasting.connections.pusher.options.port' => Arr::get($data, 'port'),
            'broadcasting.connections.pusher.options.scheme' => 'https',
            'broadcasting.connections.pusher.options.encrypted' => true,
            'broadcasting.connections.pusher.options.useTLS' => true,
            'broadcasting.connections.pusher.options.curl_options' => config('app.env') == 'local' ? [
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
            ] : [],
            'broadcasting.connections.pusher.client_options' => config('app.env') == 'local' ? [
                'verify' => false,
            ] : [],
            'broadcasting.connections.pusher.soketi_ssl_cert' => env('SOKETI_SSL_CERT', ''),
            'broadcasting.connections.pusher.soketi_ssl_key' => env('SOKETI_SSL_KEY', ''),
        ]);
    }

    private function setupSoketiFromJson()
    {
        $file = database_path('socket.json');

        if (!file_exists($file)) {
            return;
        }

        $data = json_decode(file_get_contents($file), true) ? : [];
        $app = Arr::first(Arr::get($data, 'appManager.array.apps', []));

        $hostname = request()->getHost();

        config([
            'broadcasting.connections.pusher.key'    => Arr::get($app, 'key', ''),
            'broadcasting.connections.pusher.secret' => Arr::get($app, 'secret', ''),
            'broadcasting.connections.pusher.app_id' => Arr::get($app, 'id', ''),
            'broadcasting.connections.pusher.options.host' => $hostname,
            'broadcasting.connections.pusher.options.port' => 6002,
            'broadcasting.connections.pusher.options.scheme' => 'https',
            'broadcasting.connections.pusher.options.encrypted' => true,
            'broadcasting.connections.pusher.options.useTLS' => true,
            'broadcasting.connections.pusher.options.curl_options' => config('app.env') == 'local' ? [
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0,
            ] : [],
            'broadcasting.connections.pusher.client_options' => config('app.env') == 'local' ? [
                'verify' => false,
            ] : [],
            'broadcasting.connections.pusher.soketi_ssl_cert' => env('SOKETI_SSL_CERT', ''),
            'broadcasting.connections.pusher.soketi_ssl_key' => env('SOKETI_SSL_KEY', ''),
        ]);
    }
}
