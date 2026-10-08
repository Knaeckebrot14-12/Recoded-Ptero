@extends('layouts.admin')

@section('title')
    {{ $node->name }}
@endsection

@section('content-header')
    <h1>{{ $node->name }}<small>@lang('admin/node_view.about.subheading')</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">@lang('admin/layout.breadcrumb_admin')</a></li>
        <li><a href="{{ route('admin.nodes') }}">@lang('admin/nodes.breadcrumb_nodes')</a></li>
        <li class="active">{{ $node->name }}</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-xs-12">
        <div class="nav-tabs-custom nav-tabs-floating">
            <ul class="nav nav-tabs">
                <li class="active"><a href="{{ route('admin.nodes.view', $node->id) }}">@lang('admin/node_view.tabs.about')</a></li>
                <li><a href="{{ route('admin.nodes.view.settings', $node->id) }}">@lang('admin/node_view.tabs.settings')</a></li>
                <li><a href="{{ route('admin.nodes.view.configuration', $node->id) }}">@lang('admin/node_view.tabs.configuration')</a></li>
                <li><a href="{{ route('admin.nodes.view.allocation', $node->id) }}">@lang('admin/node_view.tabs.allocation')</a></li>
                <li><a href="{{ route('admin.nodes.view.servers', $node->id) }}">@lang('admin/node_view.tabs.servers')</a></li>
            </ul>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm-8">
        <div class="row">
            <div class="col-xs-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">@lang('admin/node_view.about.information_heading')</h3>
                    </div>
                    <div class="box-body table-responsive no-padding">
                        <table class="table table-hover">
                            <tr>
                                <td>@lang('admin/node_view.about.daemon_version_label')</td>
                                <td><code data-attr="info-version"><i class="fa fa-refresh fa-fw fa-spin"></i></code> ({{ trans('admin/node_view.about.latest_label', ['version' => $version->getDaemon()]) }})</td>
                            </tr>
                            <tr>
                                <td>@lang('admin/node_view.about.system_info_label')</td>
                                <td data-attr="info-system"><i class="fa fa-refresh fa-fw fa-spin"></i></td>
                            </tr>
                            <tr>
                                <td>@lang('admin/node_view.about.total_cpu_threads_label')</td>
                                <td data-attr="info-cpus"><i class="fa fa-refresh fa-fw fa-spin"></i></td>
                            </tr>
                        </table>
                    </div>
                    <div class="box-footer" id="wings-update-box" style="display:none;">
                        <form action="{{ route('admin.nodes.view.wings-update', $node->id) }}" method="POST" onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').innerHTML = '<i class=&quot;fa fa-spinner fa-spin&quot;></i> {{ e(trans('admin/monitoring.wings.updating')) }}';">
                            {!! csrf_field() !!}
                            <span class="text-orange" id="wings-update-text"></span>
                            <button type="submit" class="btn btn-sm btn-warning pull-right"><i class="fa fa-cloud-download"></i> @lang('admin/monitoring.wings.update_button')</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-xs-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">@lang('admin/monitoring.node.heading')</h3>
                        <div class="box-tools">
                            <div class="btn-group btn-group-xs" id="monitor-range">
                                <button type="button" class="btn btn-primary" data-range="24h">24h</button>
                                <button type="button" class="btn btn-default" data-range="7d">7d</button>
                            </div>
                        </div>
                    </div>
                    <div class="box-body">
                        <p class="text-muted" id="monitor-empty" style="display:none;"></p>
                        <div class="row text-center" id="monitor-now" style="margin-bottom:10px;">
                            <div class="col-xs-3"><small class="text-muted">CPU</small><br><strong data-now="cpu">–</strong></div>
                            <div class="col-xs-3"><small class="text-muted">@lang('admin/monitoring.node.memory')</small><br><strong data-now="memory">–</strong></div>
                            <div class="col-xs-3"><small class="text-muted">@lang('admin/monitoring.node.disk')</small><br><strong data-now="disk">–</strong></div>
                            <div class="col-xs-3"><small class="text-muted">@lang('admin/monitoring.node.uptime')</small><br><strong data-now="uptime">–</strong></div>
                        </div>
                        <canvas id="monitor-chart" height="110"></canvas>
                    </div>
                </div>
            </div>
            @if ($node->description)
                <div class="col-xs-12">
                    <div class="box box-default">
                        <div class="box-header with-border">
                            @lang('admin/node_view.about.description_heading')
                        </div>
                        <div class="box-body table-responsive">
                            <pre>{{ $node->description }}</pre>
                        </div>
                    </div>
                </div>
            @endif
            @include('admin.nodes.partials.drain')
            <div class="col-xs-12">
                <div class="box box-danger">
                    <div class="box-header with-border">
                        <h3 class="box-title">@lang('admin/node_view.about.delete_heading')</h3>
                    </div>
                    <div class="box-body">
                        <p class="no-margin">@lang('admin/node_view.about.delete_notice')</p>
                    </div>
                    <div class="box-footer">
                        <form action="{{ route('admin.nodes.view.delete', $node->id) }}" method="POST">
                            {!! csrf_field() !!}
                            {!! method_field('DELETE') !!}
                            <button type="submit" class="btn btn-danger btn-sm pull-right" {{ ($node->servers_count < 1) ?: 'disabled' }}>@lang('admin/node_view.about.delete_button')</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">@lang('admin/node_view.about.at_a_glance_heading')</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    @if($node->maintenance_mode)
                    <div class="col-sm-12">
                        <div class="info-box bg-orange">
                            <span class="info-box-icon"><i class="ion ion-wrench"></i></span>
                            <div class="info-box-content" style="padding: 23px 10px 0;">
                                <span class="info-box-text">@lang('admin/node_view.about.under_maintenance_prefix')</span>
                                <span class="info-box-number">@lang('admin/node_view.about.maintenance')</span>
                            </div>
                        </div>
                    </div>
                    @endif
                    <div class="col-sm-12">
                        <div class="info-box bg-{{ $stats['disk']['css'] }}">
                            <span class="info-box-icon"><i class="ion ion-ios-folder-outline"></i></span>
                            <div class="info-box-content" style="padding: 15px 10px 0;">
                                <span class="info-box-text">@lang('admin/node_view.about.disk_allocated_label')</span>
                                <span class="info-box-number">{{ $stats['disk']['value'] }} / {{ $stats['disk']['max'] }} MiB</span>
                                <div class="progress">
                                    <div class="progress-bar" style="width: {{ $stats['disk']['percent'] }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12">
                        <div class="info-box bg-{{ $stats['memory']['css'] }}">
                            <span class="info-box-icon"><i class="ion ion-ios-barcode-outline"></i></span>
                            <div class="info-box-content" style="padding: 15px 10px 0;">
                                <span class="info-box-text">@lang('admin/node_view.about.memory_allocated_label')</span>
                                <span class="info-box-number">{{ $stats['memory']['value'] }} / {{ $stats['memory']['max'] }} MiB</span>
                                <div class="progress">
                                    <div class="progress-bar" style="width: {{ $stats['memory']['percent'] }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12">
                        <div class="info-box bg-blue">
                            <span class="info-box-icon"><i class="ion ion-social-buffer-outline"></i></span>
                            <div class="info-box-content" style="padding: 23px 10px 0;">
                                <span class="info-box-text">@lang('admin/node_view.about.total_servers_label')</span>
                                <span class="info-box-number">{{ $node->servers_count }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-scripts')
    @parent
    <script>
    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    (function getInformation() {
        $.ajax({
            method: 'GET',
            url: '/admin/nodes/view/{{ $node->id }}/system-information',
            timeout: 5000,
        }).done(function (data) {
            $('[data-attr="info-version"]').html(escapeHtml(data.version));
            $('[data-attr="info-system"]').html(escapeHtml(data.system.type) + ' (' + escapeHtml(data.system.arch) + ') <code>' + escapeHtml(data.system.release) + '</code>');
            $('[data-attr="info-cpus"]').html(data.system.cpus);
        }).fail(function (jqXHR) {

        }).always(function() {
            setTimeout(getInformation, 10000);
        });
    })();
    </script>
    {!! Theme::js('vendor/chartjs/chart.min.js') !!}
    <script>
    (function () {
        var T = @json(trans('admin/monitoring.node.js'));
        var range = '24h', chart = null;
        var gib = function (b) { return (b / 1073741824).toFixed(1) + ' GiB'; };
        var pct = function (u, t) { return t > 0 ? Math.round(u / t * 100) + ' %' : '–'; };
        var uptime = function (s) {
            var d = Math.floor(s / 86400), h = Math.floor(s % 86400 / 3600);
            return d > 0 ? d + 'd ' + h + 'h' : h + 'h ' + Math.floor(s % 3600 / 60) + 'm';
        };

        function load() {
            $.get('{{ route('admin.nodes.view.monitoring', $node->id) }}', { range: range }).done(function (data) {
                if (data.outdated && data.capable) {
                    $('#wings-update-text').text(T.update_available.replace(':current', data.wings_version).replace(':latest', data.latest_wings));
                    $('#wings-update-box').show();
                } else if (data.outdated) {
                    $('#wings-update-text').text(T.update_manual.replace(':latest', data.latest_wings));
                    $('#wings-update-box').show().find('button').hide();
                }

                if (!data.capable) {
                    $('#monitor-now, #monitor-chart').hide();
                    $('#monitor-empty').text(data.last_seen_at ? T.not_capable : T.never_seen).show();
                    return;
                }

                var l = data.last;
                if (l) {
                    $('[data-now="cpu"]').text(l.cpu.toFixed(1) + ' %');
                    $('[data-now="memory"]').text(pct(l.memory_used, l.memory_total) + ' (' + gib(l.memory_used) + ' / ' + gib(l.memory_total) + ')');
                    $('[data-now="disk"]').text(pct(l.disk_used, l.disk_total) + ' (' + gib(l.disk_used) + ' / ' + gib(l.disk_total) + ')');
                    $('[data-now="uptime"]').text(uptime(l.uptime));
                }

                var labels = data.points.map(function (p) {
                    var d = new Date(p.t);
                    return range === '7d' ? d.toLocaleDateString([], { weekday: 'short' }) + ' ' + d.getHours() + ':00' : d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                });
                var sets = [
                    { label: 'CPU %', data: data.points.map(function (p) { return p.cpu; }), borderColor: '#3c8dbc', backgroundColor: 'rgba(60,141,188,0.1)', fill: true, pointRadius: 0, lineTension: 0.3 },
                    { label: T.memory + ' %', data: data.points.map(function (p) { return p.memory; }), borderColor: '#00a65a', fill: false, pointRadius: 0, lineTension: 0.3 },
                    { label: T.disk + ' %', data: data.points.map(function (p) { return p.disk; }), borderColor: '#f39c12', fill: false, pointRadius: 0, lineTension: 0.3 }
                ];
                if (chart) {
                    chart.data.labels = labels;
                    sets.forEach(function (s, i) { chart.data.datasets[i].data = s.data; });
                    chart.update();
                } else {
                    chart = new Chart(document.getElementById('monitor-chart'), {
                        type: 'line',
                        data: { labels: labels, datasets: sets },
                        options: { animation: false, legend: { position: 'bottom', labels: { fontColor: '#ccc' } }, scales: { yAxes: [{ ticks: { min: 0, max: 100, fontColor: '#aaa' }, gridLines: { color: 'rgba(255,255,255,0.05)' } }], xAxes: [{ ticks: { fontColor: '#aaa', maxTicksLimit: 12 }, gridLines: { display: false } }] } }
                    });
                }
            });
        }

        $('#monitor-range button').on('click', function () {
            range = $(this).data('range');
            $('#monitor-range button').removeClass('btn-primary').addClass('btn-default');
            $(this).removeClass('btn-default').addClass('btn-primary');
            load();
        });
        load();
        setInterval(load, 60000);
    })();
    </script>
@endsection
