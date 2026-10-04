@extends('layouts.admin')
@include('partials/admin.settings.nav', ['activeTab' => 'iplockout'])

@section('title')
    @lang('admin/ipblock.settings.title')
@endsection

@section('content-header')
    <h1>@lang('admin/ipblock.settings.title')<small>@lang('admin/ipblock.settings.subheading')</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">@lang('admin/layout.breadcrumb_admin')</a></li>
        <li class="active">@lang('admin/ipblock.settings.title')</li>
    </ol>
@endsection

@section('content')
    @yield('settings::nav')
    <div class="row">
        <div class="col-md-8">
            <form action="{{ route('admin.settings.iplockout') }}" method="POST">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">@lang('admin/ipblock.settings.heading')</h3>
                    </div>
                    <div class="box-body">
                        <div class="form-group">
                            <input type="hidden" name="enabled" value="0">
                            <div class="checkbox checkbox-primary" style="margin-top:0;">
                                <input id="enabled" type="checkbox" name="enabled" value="1" @if(old('enabled', filter_var(config('mcpanel.ip_lockout.enabled'), FILTER_VALIDATE_BOOLEAN) ? 1 : 0)) checked @endif>
                                <label for="enabled">@lang('admin/ipblock.settings.enabled_label')</label>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-xs-6">
                                <label class="control-label" for="max_attempts">@lang('admin/ipblock.settings.attempts_label')</label>
                                <input id="max_attempts" type="number" min="3" max="1000" class="form-control" name="max_attempts" value="{{ old('max_attempts', (int) config('mcpanel.ip_lockout.max_attempts')) }}">
                            </div>
                            <div class="form-group col-xs-6">
                                <label class="control-label" for="window_minutes">@lang('admin/ipblock.settings.window_label')</label>
                                <input id="window_minutes" type="number" min="1" max="1440" class="form-control" name="window_minutes" value="{{ old('window_minutes', (int) config('mcpanel.ip_lockout.window_minutes')) }}">
                            </div>
                        </div>
                        <p class="text-muted small">@lang('admin/ipblock.settings.attempts_help')</p>

                        <label class="control-label">@lang('admin/ipblock.settings.durations_heading')</label>
                        <div class="row">
                            <div class="form-group col-xs-4">
                                <label class="control-label small text-muted" for="block_minutes">@lang('admin/ipblock.settings.block1_label')</label>
                                <input id="block_minutes" type="number" min="1" max="43200" class="form-control" name="block_minutes" value="{{ old('block_minutes', (int) config('mcpanel.ip_lockout.block_minutes')) }}">
                            </div>
                            <div class="form-group col-xs-4">
                                <label class="control-label small text-muted" for="block_minutes_2">@lang('admin/ipblock.settings.block2_label')</label>
                                <input id="block_minutes_2" type="number" min="1" max="43200" class="form-control" name="block_minutes_2" value="{{ old('block_minutes_2', (int) config('mcpanel.ip_lockout.block_minutes_2')) }}">
                            </div>
                            <div class="form-group col-xs-4">
                                <label class="control-label small text-muted" for="block_minutes_3">@lang('admin/ipblock.settings.block3_label')</label>
                                <input id="block_minutes_3" type="number" min="1" max="43200" class="form-control" name="block_minutes_3" value="{{ old('block_minutes_3', (int) config('mcpanel.ip_lockout.block_minutes_3')) }}">
                            </div>
                        </div>
                        <p class="text-muted small">@lang('admin/ipblock.settings.durations_help')</p>

                        <div class="form-group">
                            <label class="control-label" for="allowlist">@lang('admin/ipblock.settings.allowlist_label')</label>
                            <textarea id="allowlist" name="allowlist" rows="5" class="form-control" style="font-family:monospace;" placeholder="203.0.113.5&#10;198.51.100.0/24">{{ old('allowlist', (string) config('mcpanel.ip_lockout.allowlist')) }}</textarea>
                            <p class="text-muted small">@lang('admin/ipblock.settings.allowlist_help')</p>
                        </div>
                        <p class="small">
                            @lang('admin/ipblock.settings.your_ip', ['ip' => $currentIp])
                            <br><span class="text-muted">@lang('admin/ipblock.settings.mode_' . $currentMode)</span>
                        </p>
                    </div>
                    <div class="box-footer">
                        {!! csrf_field() !!}
                        {!! method_field('PATCH') !!}
                        <a href="{{ route('admin.security.ipblocks') }}" class="btn btn-sm btn-default"><i class="fa fa-ban"></i> @lang('admin/ipblock.settings.blocks_page')</a>
                        <button type="submit" class="btn btn-sm btn-primary pull-right">@lang('admin/ipblock.settings.save')</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
