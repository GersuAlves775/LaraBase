<?php

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

if (!Collection::hasMacro('paginate')) {
    Collection::macro('paginate',
        function ($perPage = 15, $page = null, $options = []) {
            $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
            return (new LengthAwarePaginator(
                $this->forPage($page, $perPage), $this->count(), $perPage, $page, $options))
                ->withPath('');
        });
}

if (!function_exists('is_base64')) {
    function is_base64($s): bool
    {
        $s = explode(",", $s);
        $s = is_array($s) && count($s) > 1 ? $s[1] : $s[0];

        return (bool)preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $s);
    }
}


if (!function_exists('responseSuccess')) {
    function responseSuccess(int $httpCode, $message = null, $data = null): Response|Application|ResponseFactory
    {
        if (empty($message)) {
            $message = 'success';
        }

        return response([
            'message' => $message,
            'data' => $data
        ], $httpCode);
    }
}
/**
 * Response error
 *
 * @param int $httpCode
 * @param null $message
 * @param null $errors
 * @return Application|ResponseFactory|Response
 */
if (!function_exists('responseError')) {
    function responseError(int $httpCode, $message = null, $errors = null): Response|Application|ResponseFactory
    {
        if (empty($message)) {
            $message = 'error';
        }

        return response([
            'message' => $message,
            'errors' => $errors,
        ], $httpCode);
    }
}


