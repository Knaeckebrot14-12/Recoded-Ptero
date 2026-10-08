<?php

namespace Pterodactyl\Console\Commands\Node;

use Illuminate\Console\Command;
use Pterodactyl\Models\NodeDrain;
use Pterodactyl\Services\Nodes\NodeDrainService;

class ProcessNodeDrainsCommand extends Command
{
    protected $description = 'Moves the servers of drained nodes along ("Move all servers away" on the admin node page).';

    protected $signature = 'p:nodes:drain';

    public function __construct(private NodeDrainService $drains)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        foreach (NodeDrain::query()->where('status', NodeDrain::STATUS_RUNNING)->get() as $drain) {
            try {
                $this->drains->advance($drain);
            } catch (\Throwable $exception) {
                report($exception);
                $this->warn("Drain #{$drain->id}: {$exception->getMessage()}");
            }
        }

        return 0;
    }
}
