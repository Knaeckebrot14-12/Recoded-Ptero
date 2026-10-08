@extends('layouts.admin')
@include('partials/admin.settings.nav', ['activeTab' => 'advanced'])

@section('title')
    @lang('admin/settings_advanced.title')
@endsection

@section('content-header')
    <h1>@lang('admin/settings_advanced.heading')<small>@lang('admin/settings_advanced.subheading')</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">@lang('admin/layout.breadcrumb_admin')</a></li>
        <li class="active">@lang('admin/settings.index.breadcrumb_settings')</li>
    </ol>
@endsection

@section('content')
    @yield('settings::nav')
    <div class="row">
        <div class="col-xs-12">
            <form action="" method="POST">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">@lang('admin/settings_advanced.recaptcha_heading')</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="control-label">@lang('admin/settings_advanced.status_label')</label>
                                <div>
                                    <select class="form-control" name="recaptcha:enabled">
                                        <option value="true">@lang('admin/settings_advanced.enabled')</option>
                                        <option value="false" @if(old('recaptcha:enabled', config('recaptcha.enabled')) == '0') selected @endif>@lang('admin/settings_advanced.disabled')</option>
                                    </select>
                                    <p class="text-muted small">@lang('admin/settings_advanced.recaptcha_status_description')</p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">@lang('admin/settings_advanced.site_key_label')</label>
                                <div>
                                    <input type="text" required class="form-control" name="recaptcha:website_key" value="{{ old('recaptcha:website_key', config('recaptcha.website_key')) }}">
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">@lang('admin/settings_advanced.secret_key_label')</label>
                                <div>
                                    <input type="text" required class="form-control" name="recaptcha:secret_key" value="{{ old('recaptcha:secret_key', config('recaptcha.secret_key')) }}">
                                    <p class="text-muted small">@lang('admin/settings_advanced.secret_key_description')</p>
                                </div>
                            </div>
                        </div>
                        @if($showRecaptchaWarning)
                            <div class="row">
                                <div class="col-xs-12">
                                    <div class="alert alert-warning no-margin">
                                        {!! trans('admin/settings_advanced.recaptcha_warning', [
                                            'link' => '<a href="https://www.google.com/recaptcha/admin">' . trans('admin/settings_advanced.recaptcha_warning_link_text') . '</a>',
                                        ]) !!}
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">@lang('admin/settings_advanced.http_connections_heading')</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="control-label">@lang('admin/settings_advanced.connect_timeout_label')</label>
                                <div>
                                    <input type="number" required class="form-control" name="pterodactyl:guzzle:connect_timeout" value="{{ old('pterodactyl:guzzle:connect_timeout', config('pterodactyl.guzzle.connect_timeout')) }}">
                                    <p class="text-muted small">@lang('admin/settings_advanced.connect_timeout_description')</p>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="control-label">@lang('admin/settings_advanced.request_timeout_label')</label>
                                <div>
                                    <input type="number" required class="form-control" name="pterodactyl:guzzle:timeout" value="{{ old('pterodactyl:guzzle:timeout', config('pterodactyl.guzzle.timeout')) }}">
                                    <p class="text-muted small">@lang('admin/settings_advanced.request_timeout_description')</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">@lang('admin/settings_advanced.auto_allocation_heading')</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="control-label">@lang('admin/settings_advanced.status_label')</label>
                                <div>
                                    <select class="form-control" name="pterodactyl:client_features:allocations:enabled">
                                        <option value="false">@lang('admin/settings_advanced.disabled')</option>
                                        <option value="true" @if(old('pterodactyl:client_features:allocations:enabled', config('pterodactyl.client_features.allocations.enabled'))) selected @endif>@lang('admin/settings_advanced.enabled')</option>
                                    </select>
                                    <p class="text-muted small">@lang('admin/settings_advanced.auto_allocation_status_description')</p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">@lang('admin/settings_advanced.starting_port_label')</label>
                                <div>
                                    <input type="number" class="form-control" name="pterodactyl:client_features:allocations:range_start" value="{{ old('pterodactyl:client_features:allocations:range_start', config('pterodactyl.client_features.allocations.range_start')) }}">
                                    <p class="text-muted small">@lang('admin/settings_advanced.starting_port_description')</p>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">@lang('admin/settings_advanced.ending_port_label')</label>
                                <div>
                                    <input type="number" class="form-control" name="pterodactyl:client_features:allocations:range_end" value="{{ old('pterodactyl:client_features:allocations:range_end', config('pterodactyl.client_features.allocations.range_end')) }}">
                                    <p class="text-muted small">@lang('admin/settings_advanced.ending_port_description')</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">@lang('admin/settings_advanced.phpmyadmin_heading')</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="control-label">@lang('admin/settings_advanced.status_label')</label>
                                <div>
                                    <select class="form-control" name="mcpanel:phpmyadmin:enabled">
                                        <option value="true">@lang('admin/settings_advanced.enabled')</option>
                                        <option value="false" @if(!filter_var(old('mcpanel:phpmyadmin:enabled', config('mcpanel.phpmyadmin.enabled')), FILTER_VALIDATE_BOOLEAN)) selected @endif>@lang('admin/settings_advanced.disabled')</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group col-md-8">
                                <label class="control-label">@lang('admin/settings_advanced.phpmyadmin_url_label')</label>
                                <div>
                                    <input type="text" readonly class="form-control" value="{{ \Pterodactyl\Services\Databases\PhpMyAdminService::url() }}">
                                </div>
                            </div>
                        </div>
                        <p class="text-muted small no-margin">@lang('admin/settings_advanced.phpmyadmin_description')</p>
                    </div>
                </div>
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">@lang('admin/settings_advanced.sleep_heading')</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="control-label">@lang('admin/settings_advanced.sleep_default_label')</label>
                                <div>
                                    <select class="form-control" name="mcpanel:sleep:enabled">
                                        <option value="false">@lang('admin/settings_advanced.disabled')</option>
                                        <option value="true" @if(filter_var(old('mcpanel:sleep:enabled', \Pterodactyl\Services\Servers\SleepSettingsService::defaultEnabled()), FILTER_VALIDATE_BOOLEAN)) selected @endif>@lang('admin/settings_advanced.enabled')</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="control-label">@lang('admin/settings_advanced.sleep_minutes_label')</label>
                                <div>
                                    <select class="form-control" name="mcpanel:sleep:minutes">
                                        @foreach(\Pterodactyl\Services\Servers\SleepSettingsService::MINUTES as $minutes)
                                            <option value="{{ $minutes }}" @if((int) old('mcpanel:sleep:minutes', \Pterodactyl\Services\Servers\SleepSettingsService::defaultMinutes()) === $minutes) selected @endif>@lang('admin/settings_advanced.sleep_minutes_option', ['count' => $minutes])</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <p class="text-muted small no-margin">@lang('admin/settings_advanced.sleep_description')</p>
                    </div>
                </div>
                <div class="box box-primary">
                    <div class="box-footer">
                        {{ csrf_field() }}
                        <button type="submit" name="_method" value="PATCH" class="btn btn-sm btn-primary pull-right">@lang('strings.save')</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
