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

            return response()->json($this->service->get());
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            return response()->json($this->service->get($id));

        } catch (\Exception $e) {
            return response()->json($this->getErrorString($e, "Registro não encontrado."), 404);
        }
    }

    public function update(int $id, Request $request): JsonResponse
    {
        return response()->json($this->service->update($request));
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json($this->service->store($request));
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            return response()->json($this->service->destroy($id));
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
