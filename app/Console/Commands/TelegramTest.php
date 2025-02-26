<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TelegramTest extends Command
{
    protected $signature = 'app:telegram-test';
    protected $description = 'Telegram test command';

    public function handle()
    {
        logger()->channel('telegram')->info("Hello");
    }
}
