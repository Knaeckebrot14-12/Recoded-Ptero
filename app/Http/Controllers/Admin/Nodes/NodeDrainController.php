<?php

namespace Pterodactyl\Http\Controllers\Admin\Nodes;

use Illuminate\Http\Request;
use Pterodactyl\Models\Node;
use Illuminate\Http\JsonResponse;
use Pterodactyl\Services\StaffAudit;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Models\NodeDrainServer;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Services\Nodes\NodeDrainService;

/**
 * "Move all servers away" on the admin node page (see NodeDrainService).
 */
class NodeDrainController extends Controller
{
    public function __construct(private AlertsMessageBag $alert, private NodeDrainService $drains)
    {
    }

    /**
     * The latest drain of the node with every server's state, for the node page (polled while it runs).
     */
    public function status(Node $node): JsonResponse
    {
        $drain = NodeDrainService::latest($node);
        if ($drain?->isRunning()) {
            $this->drains->advance($drain);
            $drain->refresh();
        }

        $returnable = NodeDrainService::returnable($node)->count();
        if (is_null($drain)) {
            return new JsonResponse(['drain' => null, 'returnable' => $returnable]);
        }

        $rows = $drain->servers()->with(['server:id,name', 'targetNode:id,name'])->orderBy('id')->get();

        return new JsonResponse(['returnable' => $returnable, 'drain' => [
            'status' => $drain->status,
            'mode' => $drain->mode,
            'status_label' => trans('admin/placement.drain.state_' . $drain->status) . ($drain->isReturn() ? ' · ' . trans('admin/placement.drain.mode_back') : ''),
            'started' => trans('admin/placement.drain.started_by', [
                'time' => $drain->created_at->diffForHumans(),
                'user' => $drain->user?->username ?? '–',
            ]),
            'summary' => trans($drain->isReturn() ? 'admin/placement.drain.summary_back' : 'admin/placement.drain.summary', [
                'done' => $rows->where('status', NodeDrainServer::STATUS_DONE)->count(),
                'total' => $rows->count(),
            ]),
            'servers' => $rows->map(fn (NodeDrainServer $row) => [
                'name' => $row->server->name ?? ('#' . $row->server_id),
                'status' => $row->status,
                'status_label' => trans('admin/placement.drain.status.' . $row->status),
                'target' => $row->targetNode?->name,
                'details' => self::details($row->details),
            ])->values()->all(),
        ]]);
    }

    /**
     * @throws \Throwable
     */
    public function start(Request $request, Node $node): RedirectResponse
    {
        if ($node->servers()->count() < 1) {
            $this->alert->warning(trans('admin/placement.drain.no_servers'))->flash();

            return redirect()->route('admin.nodes.view', $node->id);
        }

        $drain = $this->drains->start($node, $request->user());
        StaffAudit::record('nodes.drain_started', $node->name, ['count' => $drain->servers()->count()]);

        $this->alert->success(trans('admin/placement.drain.started', ['node' => $node->name]))->flash();

        return redirect()->route('admin.nodes.view', $node->id);
    }

    /**
     * "Move them back": the servers the latest run moved away return to this node.
     *
     * @throws \Throwable
     */
    public function back(Request $request, Node $node): RedirectResponse
    {
        $drain = $this->drains->startReturn($node, $request->user());
        StaffAudit::record('nodes.drain_returned', $node->name, ['count' => $drain->servers()->count()]);

        $this->alert->success(trans('admin/placement.drain.returning', ['node' => $node->name]))->flash();

        return redirect()->route('admin.nodes.view', $node->id);
    }

    public function cancel(Node $node): RedirectResponse
    {
        $drain = NodeDrainService::latest($node);
        if ($drain?->isRunning()) {
            $this->drains->cancel($drain);
            StaffAudit::record('nodes.drain_cancelled', $node->name);
            $this->alert->success(trans('admin/placement.drain.cancelled'))->flash();
        }

        return redirect()->route('admin.nodes.view', $node->id);
    }

    private static function details(?array $details): ?string
    {
        if (empty($details)) {
            return null;
        }

        if (isset($details['reasons'])) {
            return collect($details['reasons'])->map(fn ($reason, $node) => $node . ': ' . trans('admin/placement.reasons.' . $reason))->implode('; ')
                ?: trans('admin/placement.drain.details.no_nodes');
        }

        return trans('admin/placement.drain.details.' . ($details['key'] ?? ''), ['error' => $details['error'] ?? '']);
    }
}
