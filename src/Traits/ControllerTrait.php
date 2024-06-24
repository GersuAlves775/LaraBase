<?php

namespace gersonalves\laravelBase\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Yajra\DataTables\Facades\DataTables;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

trait ControllerTrait
{

    public function search(Request $request): \Illuminate\Support\Collection|Collection|LengthAwarePaginator|array
    {
        $allowedFilters = [AllowedFilter::trashed()];
        $allowedFilters = array_merge($allowedFilters, $this->service->getModel()->getFillable());
        $allowedFilters = array_merge($allowedFilters, $this->extraFields ?? []);

        $query = QueryBuilder::for($this->service->getModel()->withRelations())
            ->orderBy('created_at', 'desc')
            ->allowedFilters(
                $allowedFilters
            )
            ->allowedSorts(array_merge($this->service->getModel()->getFillable(), ['created_at']));

        if ($request->has('paginate'))
            return $query
                ->paginate($request->get('per_page', 10));

        return $query->get();
    }

    public function index(): JsonResponse|Response
    {
        try {
            if (request()->limit) {
                return response()->json($this->service->paginate());
            }

            $response = $this->service->get();
            if (property_exists($this, 'resource') && method_exists($this?->resource, 'collection')) {
                return new $this->resource($response);
            }

            return responseSuccess(200, 'success', $response);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function show(int $id)
    {
        try {
            $response = $this->service->get($id);

            if (property_exists($this, 'resource') && method_exists($this?->resource, 'resource')) {
                return $this->resource::resource($response);
            }

            return responseSuccess(200, 'success', $response);
        } catch (\Exception $e) {
            return response()->json($this->getErrorString($e, 'Registro não encontrado.'), 404);
        }
    }

    public function update(int $id, Request $request): JsonResponse|Response
    {
        return responseSuccess(200, 'success', $this->service->update($id, $request));

    }

    public function store(Request $request): JsonResponse|Response
    {
        return responseSuccess(200, 'success', $this->service->store($request));
    }

    public function destroy(int $id): JsonResponse|Response
    {
        try {
            $this->service->destroy($id);

            return response()->json([
                'success' => 'true',
                'message' => 'Registro deletado com sucesso',
            ]);
        } catch (\Exception $e) {
            return response()->json($this->getErrorString($e, 'Registro não encontrado.'), 404);
        }
    }

    public function getErrorString($e, string $customMessage = 'Server error'): string
    {
        return env('APP_DEBUG') ? $e->getMessage() : $customMessage;
    }

    public function getTable(Request $request): JsonResponse|Response
    {
        return DataTables::eloquent($this->service->query()->getModel())->toJson();
    }
}
