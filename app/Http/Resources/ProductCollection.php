<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCollection extends ResourceCollection
{
    protected $requirePagination;

    public function __construct($resource, $requirePagination)
    {
        parent::__construct($resource);
        $this->requirePagination = $requirePagination;
    }

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
             'require_pagination' => $this->requirePagination
        ];
    }
}
