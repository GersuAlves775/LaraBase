<?php

namespace gersonalves\laravelBase\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

trait ControllerTrait
{
    public function index(): JsonResponse
    {
        try {
            if (request()->limit)
                return response()->json($this->service->paginate());

            $response = $this->service->get();
            if (method_exists($this?->resource, 'collection')) {
                return $this->resource::collection($response);
            }

            return responseSuccess($response);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $response = $this->service->get($id);


            return response()->json();

        } catch (\Exception $e) {
            return response()->json($this->getErrorString($e, "Registro não encontrado."), 404);
        }
    }

    public function update(int $id, Request $request): JsonResponse
    {
        return response()->json($this->service->update($id, $request));
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json($this->service->store($request));
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->destroy($id);
            return response()->json([
                'success' => 'true',
                'message' => 'Registro deletado com sucesso',
            ]);
        } catch (\Exception $e) {
            return response()->json($this->getErrorString($e, "Registro não encontrado."), 404);
        }
    }

    public function getErrorString($e, string $customMessage = "Server error"): string
    {
        return env("APP_DEBUG") ? $e->getMessage() : $customMessage;
    }

    public function getTable(Request $request): JsonResponse
    {
        return DataTables::eloquent($this->service->query()->getModel())->toJson();
    }
}
