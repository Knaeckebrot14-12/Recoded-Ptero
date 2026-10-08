@extends('layouts.admin')

@section('title')
    @lang('admin/servers_new.title')
@endsection

@section('content-header')
    <h1>@lang('admin/servers_new.heading')<small>@lang('admin/servers_new.subheading')</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">@lang('admin/layout.breadcrumb_admin')</a></li>
        <li><a href="{{ route('admin.servers') }}">@lang('admin/servers.breadcrumb_servers')</a></li>
        <li class="active">@lang('admin/servers_new.breadcrumb_create')</li>
    </ol>
@endsection

@section('content')
<form action="{{ route('admin.servers.new') }}" method="POST">
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('admin/servers_new.core_details_heading')</h3>
                </div>

                <div class="box-body row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="pName">@lang('admin/servers_new.name_label')</label>
                            <input type="text" class="form-control" id="pName" name="name" value="{{ old('name') }}" placeholder="@lang('admin/servers_new.name_placeholder')">
                            <p class="small text-muted no-margin">{!! trans('admin/servers_new.name_description', ['chars' => '<code>a-z A-Z 0-9 _ - .</code>', 'space' => '<code>[Space]</code>']) !!}</p>
                        </div>

                        <div class="form-group">
                            <label for="pUserId">@lang('admin/servers_new.owner_label')</label>
                            <select id="pUserId" name="owner_id" class="form-control" style="padding-left:0;"></select>
                            <p class="small text-muted no-margin">@lang('admin/servers_new.owner_description')</p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="pDescription" class="control-label">@lang('admin/servers_new.description_label')</label>
                            <textarea id="pDescription" name="description" rows="3" class="form-control">{{ old('description') }}</textarea>
                            <p class="text-muted small">@lang('admin/servers_new.description_description')</p>
                        </div>

                        <div class="form-group">
                            <div class="checkbox checkbox-primary no-margin-bottom">
                                <input id="pStartOnCreation" name="start_on_completion" type="checkbox" {{ \Pterodactyl\Helpers\Utilities::checked('start_on_completion', 1) }} />
                                <label for="pStartOnCreation" class="strong">@lang('admin/servers_new.start_on_creation_label')</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="overlay" id="allocationLoader" style="display:none;"><i class="fa fa-refresh fa-spin"></i></div>
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('admin/servers_new.allocation_management_heading')</h3>
                </div>

                <div class="box-body row">
                    <div class="form-group col-sm-4">
                        <label for="pAutoDeploy">@lang('admin/placement.create.mode_label')</label>
                        <select name="auto_deploy" id="pAutoDeploy" class="form-control">
                            <option value="0">@lang('admin/placement.create.mode_manual')</option>
                            <option value="1" @if(old('auto_deploy')) selected @endif>@lang('admin/placement.create.mode_auto')</option>
                        </select>
                        <p class="small text-muted no-margin">@lang('admin/placement.create.mode_description')</p>
                    </div>

                    <div class="form-group col-sm-4 auto-deploy-field">
                        <label for="pDeployLocation">@lang('admin/placement.create.location_label')</label>
                        <select name="deploy[locations][]" id="pDeployLocation" class="form-control">
                            <option value="">@lang('admin/placement.create.location_any')</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" @if((string) $location->id === (string) old('deploy.locations.0')) selected @endif>{{ $location->long }} ({{ $location->short }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-sm-4 auto-deploy-field">
                        <label for="pDeployPorts">@lang('admin/placement.create.port_range_label')</label>
                        <input type="text" id="pDeployPorts" name="deploy[port_range]" class="form-control" value="{{ old('deploy.port_range') }}" placeholder="25565-25600" />
                        <p class="small text-muted no-margin">@lang('admin/placement.create.port_range_description')</p>
                    </div>
                </div>

                <div class="box-body row manual-deploy-field">
                    <div class="form-group col-sm-4">
                        <label for="pNodeId">@lang('admin/servers_new.node_label')</label>
                        <select name="node_id" id="pNodeId" class="form-control">
                            @foreach($locations as $location)
                                <optgroup label="{{ $location->long }} ({{ $location->short }})">
                                @foreach($location->nodes as $node)

                                <option value="{{ $node->id }}"
                                    @if($location->id === old('location_id')) selected @endif
                                >{{ $node->name }}</option>

                                @endforeach
                                </optgroup>
                            @endforeach
                        </select>

                        <p class="small text-muted no-margin">@lang('admin/servers_new.node_description')</p>
                    </div>

                    <div class="form-group col-sm-4">
                        <label for="pAllocation">@lang('admin/servers_new.default_allocation_label')</label>
                        <select id="pAllocation" name="allocation_id" class="form-control"></select>
                        <p class="small text-muted no-margin">@lang('admin/servers_new.default_allocation_description')</p>
                    </div>

                    <div class="form-group col-sm-4">
                        <label for="pAllocationAdditional">@lang('admin/servers_new.additional_allocation_label')</label>
                        <select id="pAllocationAdditional" name="allocation_additional[]" class="form-control" multiple></select>
                        <p class="small text-muted no-margin">@lang('admin/servers_new.additional_allocation_description')</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="overlay" id="allocationLoader" style="display:none;"><i class="fa fa-refresh fa-spin"></i></div>
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('admin/servers_new.feature_limits_heading')</h3>
                </div>

                <div class="box-body row">
                    <div class="form-group col-xs-6">
                        <label for="pDatabaseLimit" class="control-label">@lang('admin/servers_new.database_limit_label')</label>
                        <div>
                            <input type="text" id="pDatabaseLimit" name="database_limit" class="form-control" value="{{ old('database_limit', 0) }}"/>
                        </div>
                        <p class="text-muted small">@lang('admin/servers_new.database_limit_description')</p>
                    </div>
                    <div class="form-group col-xs-6">
                        <label for="pAllocationLimit" class="control-label">@lang('admin/servers_new.allocation_limit_label')</label>
                        <div>
                            <input type="text" id="pAllocationLimit" name="allocation_limit" class="form-control" value="{{ old('allocation_limit', 0) }}"/>
                        </div>
                        <p class="text-muted small">@lang('admin/servers_new.allocation_limit_description')</p>
                    </div>
                    <div class="form-group col-xs-6">
                        <label for="pBackupLimit" class="control-label">@lang('admin/servers_new.backup_limit_label')</label>
                        <div>
                            <input type="text" id="pBackupLimit" name="backup_limit" class="form-control" value="{{ old('backup_limit', 0) }}"/>
                        </div>
                        <p class="text-muted small">@lang('admin/servers_new.backup_limit_description')</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('admin/servers_new.resource_management_heading')</h3>
                </div>

                <div class="box-body row">
                    <div class="form-group col-xs-6">
                        <label for="pCPU">@lang('admin/servers_new.cpu_limit_label')</label>

                        <div class="input-group">
                            <input type="text" id="pCPU" name="cpu" class="form-control" value="{{ old('cpu', 0) }}" />
                            <span class="input-group-addon">%</span>
                        </div>

                        <p class="text-muted small">{!! trans('admin/servers_new.cpu_limit_description', [
                            'zero' => '<code>0</code>',
                            'example' => '<code>(4 * 100 = 400)</code>',
                            'available' => '<code>400%</code>',
                            'fifty' => '<code>50</code>',
                            'twohundred' => '<code>200</code>',
                        ]) !!}<p>
                    </div>

                    <div class="form-group col-xs-6">
                        <label for="pThreads">@lang('admin/servers_new.cpu_pinning_label')</label>

                        <div>
                            <input type="text" id="pThreads" name="threads" class="form-control" value="{{ old('threads') }}" />
                        </div>

                        <p class="text-muted small"><strong>@lang('admin/servers_new.cpu_pinning_advanced')</strong> {!! trans('admin/servers_new.cpu_pinning_description', ['ex1' => '<code>0</code>', 'ex2' => '<code>0-1,3</code>', 'ex3' => '<code>0,1,3,4</code>']) !!}</p>
                    </div>
                </div>

                <div class="box-body row">
                    <div class="form-group col-xs-6">
                        <label for="pMemory">@lang('admin/servers_new.memory_label')</label>

                        <div class="input-group">
                            <input type="text" id="pMemory" name="memory" class="form-control" value="{{ old('memory') }}" />
                            <span class="input-group-addon">MiB</span>
                        </div>

                        <p class="text-muted small">{!! trans('admin/servers_new.memory_description', ['zero' => '<code>0</code>']) !!}</p>
                    </div>

                    <div class="form-group col-xs-6">
                        <label for="pSwap">@lang('admin/servers_new.swap_label')</label>

                        <div class="input-group">
                            <input type="text" id="pSwap" name="swap" class="form-control" value="{{ old('swap', 0) }}" />
                            <span class="input-group-addon">MiB</span>
                        </div>

                        <p class="text-muted small">{!! trans('admin/servers_new.swap_description', ['zero' => '<code>0</code>', 'neg_one' => '<code>-1</code>']) !!}</p>
                    </div>
                </div>

                <div class="box-body row">
                    <div class="form-group col-xs-6">
                        <label for="pDisk">@lang('admin/servers_new.disk_label')</label>

                        <div class="input-group">
                            <input type="text" id="pDisk" name="disk" class="form-control" value="{{ old('disk') }}" />
                            <span class="input-group-addon">MiB</span>
                        </div>

                        <p class="text-muted small">{!! trans('admin/servers_new.disk_description', ['zero' => '<code>0</code>']) !!}</p>
                    </div>

                    <div class="form-group col-xs-6">
                        <label for="pIO">@lang('admin/servers_new.io_label')</label>

                        <div>
                            <input type="text" id="pIO" name="io" class="form-control" value="{{ old('io', 500) }}" />
                        </div>

                        <p class="text-muted small"><strong>@lang('admin/servers_new.io_advanced')</strong>: {!! trans('admin/servers_new.io_description', [
                            'running' => '<em>' . trans('admin/servers_new.io_running') . '</em>',
                            'ten' => '<code>10</code>',
                            'thousand' => '<code>1000</code>',
                            'link' => '<a href="https://docs.docker.com/engine/reference/run/#block-io-bandwidth-blkio-constraint" target="_blank">' . trans('admin/servers_new.io_link_text') . '</a>',
                        ]) !!}</p>
                    </div>
                    <div class="form-group col-xs-12">
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input type="checkbox" id="pOomDisabled" name="oom_disabled" value="0" {{ \Pterodactyl\Helpers\Utilities::checked('oom_disabled', 0) }} />
                            <label for="pOomDisabled" class="strong">@lang('admin/servers_new.oom_killer_label')</label>
                        </div>

                        <p class="small text-muted no-margin">@lang('admin/servers_new.oom_killer_description')</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('admin/servers_new.nest_configuration_heading')</h3>
                </div>

                <div class="box-body row">
                    <div class="form-group col-xs-12">
                        <label for="pNestId">@lang('admin/servers_new.nest_label')</label>

                        <select id="pNestId" name="nest_id" class="form-control">
                            @foreach($nests as $nest)
                                <option value="{{ $nest->id }}"
                                    @if($nest->id === old('nest_id'))
                                        selected="selected"
                                    @endif
                                >{{ $nest->name }}</option>
                            @endforeach
                        </select>

                        <p class="small text-muted no-margin">@lang('admin/servers_new.nest_description')</p>
                    </div>

                    <div class="form-group col-xs-12">
                        <label for="pEggId">@lang('admin/servers_new.egg_label')</label>
                        <select id="pEggId" name="egg_id" class="form-control"></select>
                        <p class="small text-muted no-margin">@lang('admin/servers_new.egg_description')</p>
                    </div>
                    <div class="form-group col-xs-12">
                        <div class="checkbox checkbox-primary no-margin-bottom">
                            <input type="checkbox" id="pSkipScripting" name="skip_scripts" value="1" {{ \Pterodactyl\Helpers\Utilities::checked('skip_scripts', 0) }} />
                            <label for="pSkipScripting" class="strong">@lang('admin/servers_new.skip_install_script_label')</label>
                        </div>

                        <p class="small text-muted no-margin">@lang('admin/servers_new.skip_install_script_description')</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('admin/servers_new.docker_configuration_heading')</h3>
                </div>

                <div class="box-body row">
                    <div class="form-group col-xs-12">
                        <label for="pDefaultContainer">@lang('admin/servers_new.docker_image_label')</label>
                        <select id="pDefaultContainer" name="image" class="form-control"></select>
                        <input id="pDefaultContainerCustom" name="custom_image" value="{{ old('custom_image') }}" class="form-control" placeholder="@lang('admin/servers_new.docker_image_placeholder')" style="margin-top:1rem"/>
                        <p class="small text-muted no-margin">@lang('admin/servers_new.docker_image_description')</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('admin/servers_new.startup_configuration_heading')</h3>
                </div>

                <div class="box-body row">
                    <div class="form-group col-xs-12">
                        <label for="pStartup">@lang('admin/servers_new.startup_command_label')</label>
                        <input type="text" id="pStartup" name="startup" value="{{ old('startup') }}" class="form-control" />
                        <p class="small text-muted no-margin">{!! trans('admin/servers_new.startup_command_description', [
                            'mem' => '<code>@{{SERVER_MEMORY}}</code>',
                            'ip' => '<code>@{{SERVER_IP}}</code>',
                            'port' => '<code>@{{SERVER_PORT}}</code>',
                        ]) !!}</p>
                    </div>
                </div>

                <div class="box-header with-border" style="margin-top:-10px;">
                    <h3 class="box-title">@lang('admin/servers_new.service_variables_heading')</h3>
                </div>

                <div class="box-body row" id="appendVariablesTo"></div>

                <div class="box-footer">
                    {!! csrf_field() !!}
                    <input type="submit" class="btn btn-success pull-right" value="@lang('admin/servers_new.create_button')" />
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('footer-scripts')
    @parent
    {!! Theme::js('vendor/lodash/lodash.js') !!}

    <script type="application/javascript">
        // Persist 'Service Variables'
        function serviceVariablesUpdated(eggId, ids) {
            @if (old('egg_id'))
                // Check if the egg id matches.
                if (eggId != '{{ old('egg_id') }}') {
                    return;
                }

                @if (old('environment'))
                    @foreach (old('environment') as $key => $value)
                        $('#' + ids['{{ $key }}']).val('{{ $value }}');
                    @endforeach
                @endif
            @endif
            @if(old('image'))
                $('#pDefaultContainer').val('{{ old('image') }}');
            @endif
        }
        // END Persist 'Service Variables'
    </script>

    {!! Theme::js('js/admin/new-server.js?v=20220530') !!}

    <script type="application/javascript">
        // "Automatic (best node)": the panel picks node and allocation, so those fields are not sent.
        $('#pAutoDeploy').on('change', function () {
            var auto = $(this).val() === '1';
            $('.auto-deploy-field').toggle(auto).find('select, input').prop('disabled', !auto);
            $('.manual-deploy-field').toggle(!auto).find('select').prop('disabled', auto);
        }).change();
    </script>

    <script type="application/javascript">
        $(document).ready(function() {
            // Persist 'Server Owner' select2
            @if (old('owner_id'))
                $.ajax({
                    url: '/admin/users/accounts.json?user_id={{ old('owner_id') }}',
                    dataType: 'json',
                }).then(function (data) {
                    initUserIdSelect([ data ]);
                });
            @else
                initUserIdSelect();
            @endif
            // END Persist 'Server Owner' select2

            // Persist 'Node' select2
            @if (old('node_id'))
                $('#pNodeId').val('{{ old('node_id') }}').change();

                // Persist 'Default Allocation' select2
                @if (old('allocation_id'))
                    $('#pAllocation').val('{{ old('allocation_id') }}').change();
                @endif
                // END Persist 'Default Allocation' select2

                // Persist 'Additional Allocations' select2
                @if (old('allocation_additional'))
                    const additional_allocations = [];

                    @for ($i = 0; $i < count(old('allocation_additional')); $i++)
                        additional_allocations.push('{{ old('allocation_additional.'.$i)}}');
                    @endfor

                    $('#pAllocationAdditional').val(additional_allocations).change();
                @endif
                // END Persist 'Additional Allocations' select2
            @endif
            // END Persist 'Node' select2

            // Persist 'Nest' select2
            @if (old('nest_id'))
                $('#pNestId').val('{{ old('nest_id') }}').change();

                // Persist 'Egg' select2
                @if (old('egg_id'))
                    $('#pEggId').val('{{ old('egg_id') }}').change();
                @endif
                // END Persist 'Egg' select2
            @endif
            // END Persist 'Nest' select2
        });
    </script>
@endsection
