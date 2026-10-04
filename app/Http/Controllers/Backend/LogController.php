<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Log;
use Exception;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log as LogFacade;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\Response;

class LogController extends Controller
{
    public function index()
    {
        abort_if(Gate::denies('logs index'), Response::HTTP_FORBIDDEN);

        if (request()->ajax()) {
            $limit = request('length', 15);
            $start = request('start', 0);

            $query = Log::latest();

            if (request()->filled('search')) {
                $search = request('search');
                $query->where(function ($q) use ($search) {
                    $q->where('log_name', 'like', "%$search%")
                        ->orWhere('description', 'like', "%$search%");
                });
            }

            $totalFiltered = $query->count();
            $data = $query->offset($start)->limit($limit)->get();

            return $this->dataTable($data, $totalFiltered);
        }

        return view('backend.logs.index');
    }

    public function destroy(Activity $log)
    {
        abort_if(Gate::denies('logs delete'), Response::HTTP_FORBIDDEN);

        try {
            $log->delete();

            return response(['status' => 1]);
        } catch (Exception $e) {
            LogFacade::error($e->getMessage());

            return response(['status' => 0]);
        }
    }

    protected function dataTable($data, $totalFiltered)
    {
        return datatables()
            ->of($data)
            ->editColumn('subject_id', function (Activity $model) {
                if (!$model->subject_type || !class_exists($model->subject_type)) {
                    return '';
                }

                $subject = $model->subject;

                return $subject->name ?? '';
            })
            ->editColumn('causer_id', fn (Activity $model) => $model->causer ? $model->causer->name : trans('backend.labels.system'))
            ->editColumn('properties', fn (Activity $model) => view('backend.logs._details', ['content' => $model->properties, 'id' => $model->id]))
            ->editColumn('created_at', fn (Activity $model) => $model->created_at->format('d M, Y H:i:s'))
            ->addColumn('actions', fn ($row) => $this->permissions($row->id))
            ->skipPaging()
            ->setTotalRecords($totalFiltered)
            ->setFilteredRecords($totalFiltered)
            ->rawColumns(['description', 'properties', 'actions'])
            ->make(true);
    }

    protected function permissions($id): string
    {
        $result = '<a href="#"
        class="btn btn-light btn-active-light-primary btn-flex btn-center btn-sm"
        data-kt-menu-trigger="click"
        data-kt-menu-placement="bottom-end">'
            . trans('backend.labels.actions') .
            '<i class="ki-outline ki-down fs-5 ms-1"></i>
        </a>';

        $result .= '<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">';

        if (admin()->can('logs delete')) {
            $result .= '<div class="menu-item px-3">
                            <a href="' . route('backend.logs.destroy', ['log' => $id]) . '" class="menu-link px-3 btn-delete">' . trans('backend.buttons.delete') . '</a>
                        </div>';
        }

        $result .= '</div>';

        return $result;
    }
}
