<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\StoreBlogRequest;
use App\Http\Requests\Backend\UpdateBlogRequest;
use App\Models\Blog;
use App\Services\UploadDocumentService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class BlogController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('blogs index'), Response::HTTP_FORBIDDEN);

        if (request()->ajax()) {
            $query = Blog::with(['translation'])->orderBy('id', 'desc');

            if (request()->filled('search')) {
                $search = request('search');
                $query->whereHas('translations', function ($q) use ($search) {
                    $q->where('title', 'like', "%$search%");
                });
            }

            return $this->dataTable($query);
        }

        return view('backend.blogs.index');
    }

    public function create()
    {
        abort_if(Gate::denies('blogs create'), Response::HTTP_FORBIDDEN);

        $edit = false;
        $langs = active_langs();

        return view('backend.blogs.form', compact('edit', 'langs'));
    }

    public function store(StoreBlogRequest $request, UploadDocumentService $uploadDocumentService)
    {
        abort_if(Gate::denies('blogs create'), Response::HTTP_FORBIDDEN);

        $data = $request->validated();

        DB::beginTransaction();

        try {
            $blog = Blog::create([
                'slug' => $data['slug'],
                'status' => $data['status'],
                'publish_date' => $data['publish_date'],
                'order' => $data['order'] ?? 1,
            ]);

            foreach (active_langs() as $lang) {
                $blog->translations()->create([
                    'locale' => $lang->code,
                    'title' => $data['title:' . $lang->code],
                    'subtitle' => $data['subtitle:' . $lang->code] ?? null,
                    'content' => $data['content:' . $lang->code] ?? null,
                ]);
            }

            if ($request->hasFile('images')) {
                $uploadDocumentService->upload('blog', 'images', $blog->id, 'blog_images', true, $request);
            }

            DB::commit();

            return redirect(route('backend.blogs.index'))
                ->withSuccess(trans('backend.messages.success.create'));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return redirect()->back()->withError(trans('backend.messages.error.create'));
        }
    }

    public function show(Blog $blog)
    {
        abort_if(Gate::denies('blogs index'), Response::HTTP_FORBIDDEN);

        $blog->load(['translations', 'files']);
        $langs = active_langs();
        $translations = $langs->mapWithKeys(fn ($lang) => [$lang->code => $blog->translate($lang->code)]);

        return view('backend.blogs.show', compact('blog', 'langs', 'translations'));
    }

    public function edit(Blog $blog)
    {
        abort_if(Gate::denies('blogs edit'), Response::HTTP_FORBIDDEN);

        $edit = true;
        $langs = active_langs();

        return view('backend.blogs.form', compact('blog', 'edit', 'langs'));
    }

    public function update(UpdateBlogRequest $request, Blog $blog, UploadDocumentService $uploadDocumentService)
    {
        abort_if(Gate::denies('blogs edit'), Response::HTTP_FORBIDDEN);

        $data = $request->validated();

        DB::beginTransaction();

        try {
            $blog->update([
                'slug' => $data['slug'],
                'status' => $data['status'],
                'publish_date' => $data['publish_date'],
                'order' => $data['order'] ?? $blog->order,
            ]);

            foreach (active_langs() as $lang) {
                $blog->translations()->updateOrCreate(
                    ['locale' => $lang->code],
                    [
                        'title' => $data['title:' . $lang->code],
                        'subtitle' => $data['subtitle:' . $lang->code] ?? null,
                        'content' => $data['content:' . $lang->code] ?? null,
                    ]
                );
            }

            if ($request->hasFile('images')) {
                $uploadDocumentService->upload('blog', 'images', $blog->id, 'blog_images', true, $request);
            }

            DB::commit();

            return redirect(route('backend.blogs.index'))
                ->withSuccess(trans('backend.messages.success.update'));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return redirect()->back()->withError(trans('backend.messages.error.update'));
        }
    }

    public function destroy(Blog $blog)
    {
        abort_if(Gate::denies('blogs delete'), Response::HTTP_FORBIDDEN);

        try {
            foreach ($blog->getDocuments('blog_images') as $file) {
                Storage::disk('documents')->delete($file->getAttributes()['document']);
                $file->delete();
            }

            $blog->delete();

            return response(['status' => 1]);
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return response(['status' => 0]);
        }
    }

    protected function dataTable($query)
    {
        return datatables()
            ->of($query)
            ->addColumn('checkbox', fn ($row) => '<div class="form-check form-check-sm form-check-custom form-check-solid">
                        <input class="form-check-input" type="checkbox" value="' . $row->id . '" />
                    </div>')
            ->addColumn('blog', function ($row) {
                $src = $row->cover_image ?: ($row->first_image ?: asset('backend/img/noimage.jpg'));
                $title = $row->translation->title ?? 'No Title';

                return '
                <div class="d-flex align-items-center">
                    <a href="' . route('backend.blogs.edit', ['blog' => $row->id]) . '" class="symbol symbol-50px">
                        <span class="symbol-label" style="background-image:url(' . $src . ');"></span>
                    </a>
                    <div class="ms-5">
                        <a href="' . route('backend.blogs.edit', ['blog' => $row->id]) . '" class="text-gray-800 text-hover-primary fs-5 fw-bold">' . $title . '</a>
                    </div>
                </div>';
            })
            ->addColumn('status', fn ($row) => badge($row->status))
            ->addColumn('publish_date', fn ($row) => is_object($row->publish_date) ? $row->publish_date->format('Y-m-d') : $row->publish_date)
            ->addColumn('actions', fn ($row) => $this->permissions($row->id))
            ->rawColumns(['checkbox', 'blog', 'status', 'actions'])
            ->make(true);
    }

    protected function permissions($id)
    {
        $result = '<a href="#"
        class="btn btn-light btn-active-light-primary btn-flex btn-center btn-sm"
        data-kt-menu-trigger="click"
        data-kt-menu-placement="bottom-end">'
            . trans('backend.labels.actions') .
            '<i class="ki-outline ki-down fs-5 ms-1"></i>
        </a>';

        $result .= '<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">';

        if (admin()->can('blogs index')) {
            $result .= '<div class="menu-item px-3">
                            <a href="' . route('backend.blogs.show', ['blog' => $id]) . '" class="menu-link px-3">' . trans('backend.buttons.view') . '</a>
                        </div>';
        }

        if (admin()->can('blogs edit')) {
            $result .= '<div class="menu-item px-3">
                            <a href="' . route('backend.blogs.edit', ['blog' => $id]) . '" class="menu-link px-3">' . trans('backend.buttons.edit') . '</a>
                        </div>';
        }

        if (admin()->can('blogs delete')) {
            $result .= '<div class="menu-item px-3">
                            <a href="' . route('backend.blogs.destroy', ['blog' => $id]) . '" class="menu-link px-3 btn-delete">' . trans('backend.buttons.delete') . '</a>
                        </div>';
        }

        $result .= '</div>';

        return $result;
    }
}
