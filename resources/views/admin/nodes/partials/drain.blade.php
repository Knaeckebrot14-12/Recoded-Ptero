{{-- "Move all servers away" box of the node page; the state comes from admin.nodes.view.drain. --}}
<div class="col-xs-12">
    <div class="box box-warning" id="node-drain">
        <div class="box-header with-border">
            <h3 class="box-title">@lang('admin/placement.drain.heading')</h3>
            <div class="box-tools"><span class="label" id="drain-state" style="display:none;"></span></div>
        </div>
        <div class="box-body">
            <p class="no-margin">@lang('admin/placement.drain.description')</p>
            <p class="text-muted small" id="drain-meta" style="display:none; margin:10px 0 0;"></p>
            <p class="text-green small" id="drain-finished" style="display:none; margin:10px 0 0;">@lang('admin/placement.drain.finished_notice')</p>
            <p class="text-green small" id="drain-finished-back" style="display:none; margin:10px 0 0;">@lang('admin/placement.drain.finished_notice_back')</p>
        </div>
        <div class="box-body table-responsive no-padding" id="drain-table" style="display:none;">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>@lang('admin/placement.drain.table.server')</th>
                        <th>@lang('admin/placement.drain.table.status')</th>
                        <th>@lang('admin/placement.drain.table.target')</th>
                        <th>@lang('admin/placement.drain.table.details')</th>
                    </tr>
                </thead>
                <tbody id="drain-rows"></tbody>
            </table>
        </div>
        @if(Auth::user()->hasStaffPermission('servers.manage'))
            <div class="box-footer">
                <form id="drain-start" action="{{ route('admin.nodes.view.drain.start', $node->id) }}" method="POST" style="display:none;">
                    {!! csrf_field() !!}
                    <button type="submit" class="btn btn-warning btn-sm pull-right" {{ ($node->servers_count < 1) ? 'disabled' : '' }}><i class="fa fa-truck"></i> @lang('admin/placement.drain.start_button')</button>
                </form>
                <form id="drain-back" action="{{ route('admin.nodes.view.drain.back', $node->id) }}" method="POST" style="display:none;">
                    {!! csrf_field() !!}
                    <span class="small text-muted">@lang('admin/placement.drain.return_description')</span>
                    <button type="submit" class="btn btn-success btn-sm pull-right" style="margin-left:8px;"><i class="fa fa-undo"></i> <span id="drain-back-label"></span></button>
                </form>
                <form id="drain-cancel" action="{{ route('admin.nodes.view.drain.cancel', $node->id) }}" method="POST" style="display:none;">
                    {!! csrf_field() !!}
                    <span class="small text-muted">@lang('admin/placement.drain.cancel_description')</span>
                    <button type="submit" class="btn btn-default btn-sm pull-right"><i class="fa fa-stop"></i> @lang('admin/placement.drain.cancel_button')</button>
                </form>
            </div>
        @endif
    </div>
</div>
<script>
(function () {
    var url = @json(route('admin.nodes.view.drain', $node->id));
    var confirmText = @json(trans('admin/placement.drain.start_confirm', ['node' => $node->name]));
    var backConfirm = @json(trans('admin/placement.drain.return_confirm', ['node' => $node->name]));
    var backLabel = @json(trans('admin/placement.drain.return_button'));
    var colors = { queued: 'default', moving: 'primary', done: 'success', failed: 'danger', no_target: 'warning', cancelled: 'default', running: 'primary', finished: 'success' };
    var timer = null;

    function el(id) { return document.getElementById(id); }
    function show(id, visible) { if (el(id)) { el(id).style.display = visible ? '' : 'none'; } }
    function label(status, text) {
        var span = document.createElement('span');
        span.className = 'label label-' + (colors[status] || 'default');
        span.textContent = text;
        return span;
    }

    function render(drain, returnable) {
        var running = !!drain && drain.status === 'running';
        show('drain-start', !running);
        show('drain-cancel', running);
        show('drain-back', !running && returnable > 0);
        if (el('drain-back-label')) {
            el('drain-back-label').textContent = backLabel.replace(':count', returnable);
        }
        show('drain-state', !!drain);
        show('drain-meta', !!drain);
        show('drain-table', !!drain && drain.servers.length > 0);
        show('drain-finished', !!drain && drain.status === 'finished' && drain.mode !== 'back' && !(returnable > 0));
        show('drain-finished-back', !!drain && drain.status === 'finished' && drain.mode === 'back');
        if (!drain) {
            return;
        }

        el('drain-state').className = 'label label-' + (colors[drain.status] || 'default');
        el('drain-state').textContent = drain.status_label;
        el('drain-meta').textContent = drain.started + ' · ' + drain.summary;

        var body = el('drain-rows');
        body.innerHTML = '';
        drain.servers.forEach(function (server) {
            var tr = document.createElement('tr');
            [server.name, null, server.target || '–', server.details || ''].forEach(function (text, i) {
                var td = document.createElement('td');
                if (i === 1) {
                    td.appendChild(label(server.status, server.status_label));
                } else {
                    td.textContent = text;
                }
                if (i === 3) {
                    td.className = 'small text-muted';
                }
                tr.appendChild(td);
            });
            body.appendChild(tr);
        });
    }

    function load() {
        fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                render(data.drain, data.returnable || 0);
                clearTimeout(timer);
                if (data.drain && data.drain.status === 'running') {
                    timer = setTimeout(load, 5000);
                }
            })
            .catch(function () { timer = setTimeout(load, 15000); });
    }

    if (el('drain-start')) {
        el('drain-start').addEventListener('submit', function (event) {
            if (!window.confirm(confirmText)) {
                event.preventDefault();
            }
        });
    }
    if (el('drain-back')) {
        el('drain-back').addEventListener('submit', function (event) {
            if (!window.confirm(backConfirm)) {
                event.preventDefault();
            }
        });
    }
    load();
})();
</script>
