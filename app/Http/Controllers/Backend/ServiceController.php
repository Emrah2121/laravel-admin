<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\StoreServiceRequest;
use App\Http\Requests\Backend\UpdateServiceRequest;
use App\Models\Service;
use App\Services\UploadDocumentService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ServiceController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('services index'), Response::HTTP_FORBIDDEN);

        if (request()->ajax()) {
            $query = Service::with(['translation', 'files'])->orderBy('id', 'desc');

            if (request()->filled('search')) {
                $search = request('search');
                $query->whereHas('translations', function ($q) use ($search) {
                    $q->where('title', 'like', "%$search%");
                });
            }

            return $this->dataTable($query);
        }

        return view('backend.services.index');
    }

    public function create()
    {
        abort_if(Gate::denies('services create'), Response::HTTP_FORBIDDEN);

        $edit = false;
        $langs = active_langs();

        return view('backend.services.form', compact('edit', 'langs'));
    }

    public function store(StoreServiceRequest $request, UploadDocumentService $uploadDocumentService)
    {
        abort_if(Gate::denies('services create'), Response::HTTP_FORBIDDEN);

        $data = $request->validated();

        DB::beginTransaction();

        try {
            $service = Service::create([
                'slug' => $data['slug'],
                'status' => $data['status'],
                'order' => $data['order'] ?? 1,
            ]);

            foreach (active_langs() as $lang) {
                $service->translations()->create([
                    'locale' => $lang->code,
                    'title' => $data['title:' . $lang->code],
                    'description' => $data['description:' . $lang->code] ?? null,
                ]);
            }

            if ($request->hasFile('image')) {
                $uploadDocumentService->upload('service', 'image', $service->id, 'service_image', true, $request);
            }

            DB::commit();

            return redirect(route('backend.services.index'))
                ->withSuccess(trans('backend.messages.success.create'));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return redirect()->back()->withError(trans('backend.messages.error.create'));
        }
    }

    public function show(Service $service)
    {
        abort_if(Gate::denies('services index'), Response::HTTP_FORBIDDEN);

        $service->load(['translations', 'files']);
        $langs = active_langs();
        $translations = $langs->mapWithKeys(fn ($lang) => [$lang->code => $service->translate($lang->code)]);

        return view('backend.services.show', compact('service', 'langs', 'translations'));
    }

    public function edit(Service $service)
    {
        abort_if(Gate::denies('services edit'), Response::HTTP_FORBIDDEN);

        $edit = true;
        $langs = active_langs();

        return view('backend.services.form', compact('service', 'edit', 'langs'));
    }

    public function update(UpdateServiceRequest $request, Service $service, UploadDocumentService $uploadDocumentService)
    {
        abort_if(Gate::denies('services edit'), Response::HTTP_FORBIDDEN);

        $data = $request->validated();

        DB::beginTransaction();

        try {
            $service->update([
                'slug' => $data['slug'],
                'status' => $data['status'],
                'order' => $data['order'] ?? $service->order,
            ]);

            foreach (active_langs() as $lang) {
                $service->translations()->updateOrCreate(
                    ['locale' => $lang->code],
                    [
                        'title' => $data['title:' . $lang->code],
                        'description' => $data['description:' . $lang->code] ?? null,
                    ]
                );
            }

            if ($request->hasFile('image')) {
                $uploadDocumentService->upload('service', 'image', $service->id, 'service_image', true, $request);
            }

            DB::commit();

            return redirect(route('backend.services.index'))
                ->withSuccess(trans('backend.messages.success.update'));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());

            return redirect()->back()->withError(trans('backend.messages.error.update'));
        }
    }

    public function destroy(Service $service)
    {
        abort_if(Gate::denies('services delete'), Response::HTTP_FORBIDDEN);

        try {
            foreach ($service->getDocuments('service_image') as $file) {
                Storage::disk('documents')->delete($file->getAttributes()['document']);
                $file->delete();
            }

            $service->delete();

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
            ->addColumn('service', function ($row) {
                $src = $row->cover_image ?: ($row->first_image ?: asset('backend/img/noimage.jpg'));
                $title = e($row->translation->title ?? 'No Title');

                return '
                <div class="d-flex align-items-center">
                    <a href="' . route('backend.services.edit', ['service' => $row->id]) . '" class="symbol symbol-50px">
                        <span class="symbol-label" style="background-image:url(' . $src . ');"></span>
                    </a>
                    <div class="ms-5">
                        <a href="' . route('backend.services.edit', ['service' => $row->id]) . '" class="text-gray-800 text-hover-primary fs-5 fw-bold">' . $title . '</a>
                    </div>
                </div>';
            })
            ->addColumn('status', fn ($row) => badge($row->status))
            ->addColumn('actions', fn ($row) => $this->permissions($row->id))
            ->rawColumns(['checkbox', 'service', 'status', 'actions'])
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

        if (admin()->can('services index')) {
            $result .= '<div class="menu-item px-3">
                            <a href="' . route('backend.services.show', ['service' => $id]) . '" class="menu-link px-3">' . trans('backend.buttons.view') . '</a>
                        </div>';
        }

        if (admin()->can('services edit')) {
            $result .= '<div class="menu-item px-3">
                            <a href="' . route('backend.services.edit', ['service' => $id]) . '" class="menu-link px-3">' . trans('backend.buttons.edit') . '</a>
                        </div>';
        }

        if (admin()->can('services delete')) {
            $result .= '<div class="menu-item px-3">
                            <a href="' . route('backend.services.destroy', ['service' => $id]) . '" class="menu-link px-3 btn-delete">' . trans('backend.buttons.delete') . '</a>
                        </div>';
        }

        $result .= '</div>';

        return $result;
    }
}
