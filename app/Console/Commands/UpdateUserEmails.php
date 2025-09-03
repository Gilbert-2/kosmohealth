<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateUserEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:fix-emails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change host and admin demo emails to kosmotive emails, preserving all other fields';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $map = [
            'host.demo@example.com' => 'health@kosmotive.rw',
            'admin.demo@example.com' => 'digital@kosmotive.rw',
        ];

        DB::beginTransaction();
        try {
            foreach ($map as $old => $new) {
                $existing = DB::table('users')->where('email', $new)->first();
                if ($existing) {
                    $this->warn("Skipped: target email already exists => {$new}");
                    continue;
                }

                $updated = DB::table('users')->where('email', $old)->update(['email' => $new]);
                if ($updated > 0) {
                    $this->info("Updated {$old} -> {$new}");
                } else {
                    $this->warn("No user found with email {$old}");
                }
            }

            DB::commit();
            $this->info('Done.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}


