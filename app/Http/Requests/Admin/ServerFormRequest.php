<?php

namespace Pterodactyl\Http\Requests\Admin;

use Pterodactyl\Models\Server;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ServerFormRequest extends AdminFormRequest
{
    /**
     * Rules to be applied to this request.
     */
    public function rules(): array
    {
        $rules = Server::getRules();
        $rules['description'][] = 'nullable';
        $rules['custom_image'] = 'sometimes|nullable|string';

        // "Automatic (best node)": the panel picks the node and one of its existing free allocations,
        // optionally limited like the API's deploy block.
        if ($this->boolean('auto_deploy')) {
            unset($rules['node_id'], $rules['allocation_id']);
            $rules['deploy.locations'] = 'sometimes|array';
            $rules['deploy.locations.*'] = 'nullable|integer|exists:locations,id';
            $rules['deploy.port_range'] = 'nullable|string|max:191';
        }

        return $rules;
    }

    /**
     * Run validation after the rules above have been applied.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $validator->sometimes('node_id', 'required|numeric|bail|exists:nodes,id', function ($input) {
                return !$input->auto_deploy;
            });

            $validator->sometimes('allocation_id', [
                'required',
                'numeric',
                'bail',
                Rule::exists('allocations', 'id')->where(function ($query) {
                    $query->where('node_id', $this->input('node_id'));
                    $query->whereNull('server_id');
                }),
            ], function ($input) {
                return !$input->auto_deploy;
            });

            $validator->sometimes('allocation_additional.*', [
                'sometimes',
                'required',
                'numeric',
                Rule::exists('allocations', 'id')->where(function ($query) {
                    $query->where('node_id', $this->input('node_id'));
                    $query->whereNull('server_id');
                }),
            ], function ($input) {
                return !$input->auto_deploy;
            });
        });
    }
}
