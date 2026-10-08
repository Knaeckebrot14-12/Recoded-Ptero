<?php

namespace Pterodactyl\Http\Controllers\Admin\Servers;

use Illuminate\View\View;
use Pterodactyl\Models\Nest;
use Pterodactyl\Models\Node;
use Pterodactyl\Models\Location;
use Illuminate\Http\RedirectResponse;
use Prologue\Alerts\AlertsMessageBag;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Models\Objects\DeploymentObject;
use Pterodactyl\Services\Nodes\NodePlacementService;
use Pterodactyl\Repositories\Eloquent\NestRepository;
use Pterodactyl\Repositories\Eloquent\NodeRepository;
use Pterodactyl\Http\Requests\Admin\ServerFormRequest;
use Pterodactyl\Services\Servers\ServerCreationService;

class CreateServerController extends Controller
{
    /**
     * CreateServerController constructor.
     */
    public function __construct(
        private AlertsMessageBag $alert,
        private NestRepository $nestRepository,
        private NodeRepository $nodeRepository,
        private ServerCreationService $creationService,
        private NodePlacementService $placement,
    ) {
    }

    /**
     * Displays the create server page.
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function index(): View|RedirectResponse
    {
        $nodes = Node::all();
        if (count($nodes) < 1) {
            $this->alert->warning(trans('admin/server.alerts.node_required'))->flash();

            return redirect()->route('admin.nodes');
        }

        $nests = $this->nestRepository->getWithEggs();

        \JavaScript::put([
            'nodeData' => $this->nodeRepository->getNodesForServerCreation(),
            'nests' => $nests->map(function (Nest $item) {
                return array_merge($item->toArray(), [
                    'eggs' => $item->eggs->keyBy('id')->toArray(),
                ]);
            })->keyBy('id'),
        ]);

        return view('admin.servers.new', [
            'locations' => Location::all(),
            'nests' => $nests,
        ]);
    }

    /**
     * Create a new server on the remote system.
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Pterodactyl\Exceptions\Service\Deployment\NoViableAllocationException
     * @throws \Pterodactyl\Exceptions\Service\Deployment\NoViableNodeException
     * @throws \Throwable
     */
    public function store(ServerFormRequest $request): RedirectResponse
    {
        $data = $request->except(['_token', 'auto_deploy', 'deploy']);
        if (!empty($data['custom_image'])) {
            $data['image'] = $data['custom_image'];
            unset($data['custom_image']);
        }

        if ($request->boolean('auto_deploy')) {
            $this->placeAutomatically($request, $data);
        }

        $server = $this->creationService->handle($data);

        $this->alert->success(trans('admin/server.alerts.server_created'))->flash();

        return new RedirectResponse('/admin/servers/view/' . $server->id);
    }

    /**
     * Sets node_id and allocation_id to the best node and one of its existing free allocations.
     * Additional allocations are not picked automatically.
     *
     * @throws DisplayException when no node qualifies (nothing is created)
     */
    private function placeAutomatically(ServerFormRequest $request, array &$data): void
    {
        $deployment = (new DeploymentObject())
            ->setLocations(array_map('intval', array_filter((array) $request->input('deploy.locations', []))))
            ->setPorts(array_values(array_filter(array_map('trim', explode(',', (string) $request->input('deploy.port_range'))), 'strlen')));

        $memory = (int) $request->input('memory');
        $disk = (int) $request->input('disk');
        $cpu = (int) $request->input('cpu');

        $placement = $this->placement->find($memory, $disk, $cpu, $deployment);
        if (is_null($placement)) {
            $rows = $this->placement->evaluate($memory, $disk, $cpu, $deployment->getLocations());

            throw new DisplayException(trim(trans('exceptions.deployment.no_placement') . ' ' . NodePlacementService::describe($rows)));
        }

        $data['node_id'] = $placement['node']->id;
        $data['allocation_id'] = $placement['allocation']->id;
        unset($data['allocation_additional']);
    }
}
