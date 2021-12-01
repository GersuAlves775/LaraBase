<?php

namespace gersonalves\laravelBase\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


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
            return response()->json($e->getMessage(), 404);
        }
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $validators = $this->validators ?? [];
        $exclude = $this->excludeOnUpdate ?? [];
        if(count($exclude)){
            $validators = array_filter($validators, function ($k) use ($exclude) {
                return !(in_array($k, $exclude));
            }, ARRAY_FILTER_USE_KEY);
        }

        $request->validate($validators);
        return response()->json($this->service->update($request));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate($this->validators);
        return response()->json($this->service->store($request));
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            return response()->json($this->service->destroy($id));
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 404);
        }
    }

    public function getErrorString(Exception $e, string $customMessage = "Server error"): string
    {
        return env("APP_DEBUG") ? $e->getMessage() : $customMessage;
    }
}
