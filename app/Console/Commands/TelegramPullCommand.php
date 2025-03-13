<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramPullCommand extends Command
{
    protected $signature = 'telegram:pull';
    protected $description = 'Telegram pull test command';

    public function handle()
    {
        $logger = logger()->channel('telegram');
        $lastUpdateId = 0; # todo: Получаем последний update_id

        $updates = Telegram::bot()->getUpdates(['offset' => $lastUpdateId + 1]);

        foreach ($updates as $update) {
            $message = $update->getMessage();
            if ($message) {
                $logger->info("Сообщение от {$message->getFrom()->getUsername()}: {$message->getText()}");
            }

            // Обновляем lastUpdateId
            $lastUpdateId = $update->getUpdateId();
        }

        # todo: Сохраняем последний update_id
    }
}
