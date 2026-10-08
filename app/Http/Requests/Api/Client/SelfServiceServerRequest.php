<?php

namespace Pterodactyl\Http\Requests\Api\Client;

class SelfServiceServerRequest extends ClientApiRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|between:1,191',
            'egg_id' => 'required|integer|exists:eggs,id',
            // Empty means "Automatic": the panel picks the node.
            'node_id' => 'nullable|integer|exists:nodes,id',
            'memory' => 'required|integer|min:128',
            'disk' => 'required|integer|min:128',
            'cpu' => 'required|integer|min:25',
            'backup_limit' => 'required|integer|min:0',
        ];
    }
}
