<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('demo:reset')]
#[Description('Wipe and reseed the demo account')]
class DemoResetCommand extends Command
{
    public function handle(): void
    {
        User::where('email', 'demo@example.com')->first()?->delete();

        $this->call(DemoSeeder::class);
    }
}
