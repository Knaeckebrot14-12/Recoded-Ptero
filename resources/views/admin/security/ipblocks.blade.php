@extends('layouts.admin')

@section('title')
    @lang('admin/ipblock.title')
@endsection

@section('content-header')
    <h1>@lang('admin/ipblock.title')<small>@lang('admin/ipblock.subheading')</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">@lang('admin/layout.breadcrumb_admin')</a></li>
        <li class="active">@lang('admin/ipblock.title')</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="alert {{ $lockout->enabled() ? 'alert-info' : 'alert-warning' }}" style="margin-bottom:15px;">
            @if($lockout->enabled())
                @lang('admin/ipblock.status_on', ['attempts' => $lockout->maxAttempts(), 'window' => $lockout->windowMinutes()])
            @else
                @lang('admin/ipblock.status_off')
            @endif
            @if(Auth::user()->isOwner())
                <a href="{{ route('admin.settings.iplockout') }}" class="pull-right"><i class="fa fa-cog"></i> @lang('admin/ipblock.settings_link')</a>
            @endif
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-8">
        <div class="box box-danger">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('admin/ipblock.active_heading') <span class="label label-danger">{{ $active->count() }}</span></h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tbody>
                        <tr>
                            <th>@lang('admin/ipblock.table.ip')</th>
                            <th>@lang('admin/ipblock.table.until')</th>
                            <th class="text-center">@lang('admin/ipblock.table.failures')</th>
                            <th>@lang('admin/ipblock.table.usernames')</th>
                            <th class="text-center">@lang('admin/ipblock.table.blocks')</th>
                            <th>@lang('admin/ipblock.table.reason')</th>
                            <th></th>
                        </tr>
                        @forelse($active as $block)
                            <tr>
                                <td>@if($block->isDevice())<span class="label label-info" title="{{ $block->ip }}"><i class="fa fa-desktop"></i> @lang('admin/ipblock.device')</span> <code>{{ substr($block->ip, 2, 10) }}…</code>@else<code>{{ $block->ip }}{{ str_contains($block->ip, ":") ? "/64" : "" }}</code>@endif</td>
                                <td style="white-space:nowrap;">
                                    {{ $block->blocked_until->format('Y-m-d H:i:s') }}
                                    <br><small class="text-muted">@lang('admin/ipblock.remaining', ['time' => $block->blocked_until->diffForHumans(null, true)])</small>
                                </td>
                                <td class="text-center">{{ $block->failures }}</td>
                                <td style="max-width:220px;word-break:break-word;">
                                    @forelse(array_slice($block->usernameList(), 0, 8) as $name)
                                        <span class="label label-default">{{ $name }}</span>
                                    @empty
                                        —
                                    @endforelse
                                    @if(count($block->usernameList()) > 8)<small class="text-muted">+{{ count($block->usernameList()) - 8 }}</small>@endif
                                </td>
                                <td class="text-center">{{ $blockCounts[$block->ip] ?? 0 }}</td>
                                <td><span class="label {{ $block->reason === 'manual' ? 'label-warning' : 'label-danger' }}">@lang('admin/ipblock.reason.' . $block->reason)</span></td>
                                <td class="text-right">
                                    <form action="{{ route('admin.security.ipblocks.delete', $block->id) }}" method="POST" style="display:inline;"
                                          onsubmit="return confirm('{{ e(trans('admin/ipblock.unblock_confirm')) }}');">
                                        {!! csrf_field() !!}
                                        {!! method_field('DELETE') !!}
                                        <button type="submit" class="btn btn-xs btn-success"><i class="fa fa-unlock"></i> @lang('admin/ipblock.unblock')</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">@lang('admin/ipblock.none_active')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('admin/ipblock.manual_heading')</h3>
            </div>
            <form action="{{ route('admin.security.ipblocks') }}" method="POST">
                <div class="box-body">
                    <div class="form-group">
                        <label class="control-label" for="ip">@lang('admin/ipblock.ip_label')</label>
                        <input id="ip" type="text" name="ip" class="form-control" value="{{ old('ip') }}" placeholder="203.0.113.5" required maxlength="45">
                    </div>
                    <div class="form-group">
                        <label class="control-label" for="minutes">@lang('admin/ipblock.duration_label')</label>
                        <select id="minutes" name="minutes" class="form-control">
                            @foreach($durations as $minutes)
                                <option value="{{ $minutes }}" @if((int) old('minutes', 1440) === $minutes) selected @endif>@lang('admin/ipblock.durations.' . $minutes)</option>
                            @endforeach
                        </select>
                    </div>
                    <p class="text-muted small">@lang('admin/ipblock.manual_hint')</p>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    <button type="submit" class="btn btn-sm btn-warning pull-right"><i class="fa fa-ban"></i> @lang('admin/ipblock.block_button')</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-8">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('admin/ipblock.failures_heading')</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tbody>
                        <tr>
                            <th>@lang('admin/ipblock.table.when')</th>
                            <th>@lang('admin/ipblock.table.ip')</th>
                            <th>@lang('admin/ipblock.table.name')</th>
                            <th>@lang('admin/ipblock.table.kind')</th>
                        </tr>
                        @forelse($failures as $failure)
                            <tr>
                                <td style="white-space:nowrap;">{{ $failure->created_at->format('Y-m-d H:i:s') }}</td>
                                <td>
                                    <code>{{ $failure->ip }}</code>
                                    @if($failure->ip === 'unknown' || $lockout::isSharedProxy($failure->ip))<small class="text-muted">({{ trans('admin/ipblock.shared_address') }})</small>@elseif($lockout->isExempt($failure->ip))<small class="text-muted">({{ trans('admin/ipblock.never_blocked') }})</small>@endif
                                    @if($failure->device)<small class="text-muted" title="{{ $failure->device }}"><i class="fa fa-desktop"></i> {{ substr($failure->device, 2, 6) }}</small>@endif
                                </td>
                                <td>{{ $failure->username ?? '—' }}</td>
                                <td>@lang('admin/ipblock.kind.' . $failure->type)</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">@lang('admin/ipblock.failures_none')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('admin/ipblock.targeted_heading')</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <tbody>
                        @if($targeted->isNotEmpty())
                            <tr>
                                <th>@lang('admin/ipblock.table.name')</th>
                                <th class="text-center">@lang('admin/ipblock.table.attempts')</th>
                                <th class="text-center">@lang('admin/ipblock.table.ips')</th>
                            </tr>
                        @endif
                        @forelse($targeted as $row)
                            <tr>
                                <td style="word-break:break-word;">{{ $row->username }}</td>
                                <td class="text-center">{{ $row->attempts }}</td>
                                <td class="text-center">{{ $row->ips }}</td>
                            </tr>
                        @empty
                            <tr><td class="text-center text-muted">@lang('admin/ipblock.targeted_none')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($history->isNotEmpty())
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">@lang('admin/ipblock.history_heading')</h3>
                </div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
                        <tbody>
                            <tr>
                                <th>@lang('admin/ipblock.table.ip')</th>
                                <th>@lang('admin/ipblock.table.when')</th>
                                <th>@lang('admin/ipblock.table.status')</th>
                            </tr>
                            @foreach($history as $block)
                                <tr>
                                    <td>@if($block->isDevice())<span class="label label-info" title="{{ $block->ip }}"><i class="fa fa-desktop"></i> @lang('admin/ipblock.device')</span> <code>{{ substr($block->ip, 2, 10) }}…</code>@else<code>{{ $block->ip }}{{ str_contains($block->ip, ":") ? "/64" : "" }}</code>@endif</td>
                                    <td style="white-space:nowrap;">{{ $block->created_at->format('Y-m-d H:i') }}</td>
                                    <td>@lang('admin/ipblock.history_status.' . ($block->unblocked_at ? 'lifted' : 'expired'))</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
