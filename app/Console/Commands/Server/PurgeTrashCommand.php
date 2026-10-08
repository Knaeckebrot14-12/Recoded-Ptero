<?php

namespace Pterodactyl\Console\Commands\Server;

use Illuminate\Console\Command;
use Pterodactyl\Services\Files\TrashService;

class PurgeTrashCommand extends Command
{
    protected $signature = 'p:files:purge-trash';

    protected $description = 'Deletes files that have been in the file manager\'s trash longer than allowed.';

    public function handle(TrashService $trash): int
    {
        $this->line(sprintf('Deleted %d trash entries.', $trash->purgeExpired()));

        return self::SUCCESS;
    }
}
