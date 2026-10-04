<div class="d-flex flex-column flex-lg-row">
    <div class="d-flex flex-column gap-7 gap-lg-10 w-100 w-lg-300px mb-7 me-lg-10">
        <div class="card card-flush py-4">
            <div class="card-header">
                <div class="card-title">
                    <h2>@lang('backend.labels.image')</h2>
                </div>
            </div>
            <div class="card-body text-center pt-0">
                <div class="image-input image-input-outline image-input-placeholder mb-3">
                    <div class="image-input-wrapper w-150px h-150px"
                         style="background-image: url('{{ $blog->cover_image ? asset($blog->cover_image) : ($blog->first_image ? asset($blog->first_image) : asset('backend/img/noimage.jpg')) }}');">
                    </div>
                </div>
            </div>
        </div>

        <div class="card card-flush py-4">
            <div class="card-header">
                <div class="card-title">
                    <h2>@lang('backend.labels.details')</h2>
                </div>
            </div>
            <div class="card-body pt-0 fs-6">
                <div class="mb-5">
                    <div class="fw-bold text-gray-800">@lang('backend.labels.date')</div>
                    <div class="text-gray-600">{{ $blog->publish_date ?? '—' }}</div>
                </div>
                <div class="mb-5">
                    <div class="fw-bold text-gray-800">@lang('backend.labels.created_at')</div>
                    <div class="text-gray-600">{{ optional($blog->created_at)->format('d.m.Y H:i') ?? '—' }}</div>
                </div>
                <div class="mb-5">
                    <div class="fw-bold text-gray-800">@lang('backend.labels.updated_at')</div>
                    <div class="text-gray-600">{{ optional($blog->updated_at)->format('d.m.Y H:i') ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-column flex-row-fluid gap-7 gap-lg-10">
        <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold mb-n2">
            @foreach ($langs as $lang)
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4 @if($loop->first) active @endif"
                       data-bs-toggle="tab" href="#kt_blog_lang_{{ $lang->code }}">
                        {{ $lang->name }}
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="tab-content">
            @foreach ($langs as $lang)
                <div class="tab-pane fade @if($loop->first) show active @endif" id="kt_blog_lang_{{ $lang->code }}" role="tabpanel">
                    <div class="d-flex flex-column gap-7 gap-lg-10">
                        <div class="card card-flush py-4">
                            <div class="card-header">
                                <div class="card-title">
                                    <h2>{{ $lang->name }} @lang('backend.labels.content')</h2>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                @if($translations[$lang->code])
                                    <div class="mb-10">
                                        <label class="fw-bold fs-6 mb-2">@lang('backend.labels.title')</label>
                                        <div class="text-gray-800 fs-4 fw-bold">{{ $translations[$lang->code]->title }}</div>
                                    </div>
                                    @if($translations[$lang->code]->subtitle)
                                        <div class="mb-10">
                                            <label class="fw-bold fs-6 mb-2">@lang('backend.labels.subtitle')</label>
                                            <div class="text-gray-600">{{ $translations[$lang->code]->subtitle }}</div>
                                        </div>
                                    @endif
                                    <div class="mb-10">
                                        <label class="fw-bold fs-6 mb-2">@lang('backend.labels.content')</label>
                                        <div class="text-gray-600 border p-5 rounded bg-light bg-opacity-50">
                                            {!! $translations[$lang->code]->content !!}
                                        </div>
                                    </div>
                                @else
                                    <div class="text-muted fst-italic">@lang('backend.messages.error.not_found')</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card card-flush py-4">
            <div class="card-header">
                <div class="card-title">
                    <h2>@lang('backend.labels.documents')</h2>
                </div>
            </div>
            <div class="card-body pt-0">
                @include('backend.includes.media', [
                    'files' => $blog->getDocuments('blog_images'),
                    'media_collection_name' => 'blog_images',
                    'isDeleted' => false,
                    'isCovered' => false,
                ])
            </div>
        </div>
    </div>
</div>
