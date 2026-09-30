<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;

trait APIPaginationTrait
{
    protected function resolveCollection(
        Builder $query,
        Request $request,
        int $defaultCount = 12
    ): Collection {
        $count = (int) $request->query('count', $defaultCount);

        if ($request->has('page')) {
            return $query->paginate($count)->getCollection();
        }

        return $query->get();
    }

    protected function resolvePagination(
        Builder $query,
        Request $request,
        int $defaultCount = 12
    ): ?array {
        if (!$request->has('page')) {
            return null;
        }

        $count = (int) $request->query('count', $defaultCount);
        $paginator = $query->paginate($count);

        return [
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'per_page'     => $paginator->perPage(),
            'total'        => $paginator->total(),
            'from'         => $paginator->firstItem(),
            'to'           => $paginator->lastItem(),
            'links'        => $paginator->linkCollection(),
        ];
    }
}
