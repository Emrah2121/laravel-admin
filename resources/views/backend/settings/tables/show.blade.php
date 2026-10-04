<div class="card-body">
    <div class="table-responsive">
        <table class="table table-light table-light-success">
            <tbody>
                <tr class="bg-gray-100">
                    <td class="table-row-title w-25">@lang('backend.labels.id')</td>
                    <td class="table-center">{{ $setting->id }}</td>
                </tr>
                <tr class="bg-gray-100">
                    <td class="table-row-title w-25">@lang('backend.labels.name')</td>
                    <td class="table-center">{{ $setting->name }}</td>
                </tr>
                @foreach ($setting->translations as $trans)
                    <tr class="bg-gray-100">
                        <td class="table-row-title w-25">
                            @lang('backend.labels.content') ({{ strtoupper($trans->locale) }})
                        </td>
                        <td class="table-center">{!! $trans->content !!}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
