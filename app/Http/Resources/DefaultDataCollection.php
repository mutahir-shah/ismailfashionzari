<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Contracts\Support\Arrayable;

class DefaultDataCollection extends ResourceCollection
{
    protected bool $singleItem;

    public function __construct($resource, bool $singleItem = false)
    {
        parent::__construct($resource);
        $this->singleItem = $singleItem;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = $this->collection;

        if ($data instanceof Arrayable && method_exists($data, 'toArray') && count($data) === 1 && $this->singleItem == true) {
            $data = $data->first();
        }

        return [
            'success' => true,
            'data'    => $data instanceof Arrayable ? $data->toArray() : $data
        ];
    }
}
