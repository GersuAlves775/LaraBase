<?php

namespace gersonalves\laravelBase\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

trait ControllerTrait
{

    public function search(Request $request, Builder $builder = null, $resource = null): \Illuminate\Support\Collection|Collection|LengthAwarePaginator|array
    {
        $resource = $resource ?? $this->resource ?? null;
        $baseClass = $this->service->getModel()::class;
        $queryBase = $this->service->getModel()->query();
        if (method_exists($baseClass, 'scopeWithRelations')) {
            $queryBase = $queryBase->withRelations();
        }
        $query = $builder ?? $this->makeQuery($queryBase, $request);

        if ($request->has('paginate')) {
            $paginated = $query->paginate($request->get('per_page', 10));
            if (property_exists($this, 'resource') && method_exists($resource, 'resource')) {
                $paginated->getCollection()->transform(function ($item) use ($resource) {
                    return $resource::resource($item);
                });
            }
            return $paginated;
        }
        if ($request->has('limit')) {
            $response = $query->limit($request->get('limit'))->get();
        } else {
            $response = $query->get();
        }

        if (property_exists($this, 'resource') && method_exists($resource, 'collection')) {
            return $response->transform(function ($item) use ($resource) {
                return $resource::resource($item);
            });
        }

        return $response;

    }

    public function makeQuery($subject, Request $request)
    {
        $allowedFilters = array_merge(
            $this->service->getModel()->getFillable(),
            $this->extraFilters ?? [],
            [AllowedFilter::trashed(), AllowedFilter::exact($this->service->getModel()->getKeyName())]
        );

        foreach ($allowedFilters as $index => $allowedFilter) {
            if ($allowedFilter instanceof AllowedFilter) {
                $position = array_search($allowedFilter->getName(), $allowedFilters);
                if ($position !== false)
                    unset($allowedFilters[$position]);
            }
        }

        $has_created_at = Schema::hasColumn($this->service->getModel()->getTable(), 'created_at');
        $sortables = array_merge(
            $has_created_at ? ['created_at', $this->service->getModel()->getKeyName()] : [$this->service->getModel()->getKeyName()],
            $this->extraSortables ?? [],
            $this->service->getModel()->getFillable()
        );

        $query = QueryBuilder::for($subject)
            ->allowedFilters(
                $allowedFilters
            )
            ->allowedSorts($sortables);

        if ($has_created_at && !$query->getQuery()->orders)
            $query->orderBy('created_at', 'desc');

        return $query;
    }

    public function index()
    {
        try {
            if (request()->limit) {
                return response()->json($this->service->paginate());
            }

            $response = $this->service->get(null, request());
            if (property_exists($this, 'resource') && method_exists($this?->resource, 'collection')) {
                return new $this->resource($response);
            }

            return responseSuccess(200, 'success', $response);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function show(int|string $id, Request $request)
    {
        try {
            $response = $this->service->get($id, $request);

            if (property_exists($this, 'resource') && method_exists($this?->resource, 'resource')) {
                return $this->resource::resource($response);
            }

            return responseSuccess(200, 'success', $response);
        } catch (\Exception $e) {
            return response()->json($this->getErrorString($e, 'Registro não encontrado.'), 404);
        }
    }

    public function update(int|string $id, Request $request): JsonResponse|Response
    {
        return responseSuccess(200, 'success', $this->service->update($id, $request));

    }

    public function store(Request $request): JsonResponse|Response
    {
        return responseSuccess(200, 'success', $this->service->store($request));
    }

    public function destroy(int|string $id): JsonResponse|Response
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
