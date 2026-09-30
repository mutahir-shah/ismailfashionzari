<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Contracts\Support\Arrayable;

class SuccessDataCollection extends ResourceCollection
{
    protected ?string $message;

    public function __construct($resource, ?string $message = null)
    {
        parent::__construct($resource);
        $this->message = $message;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = $this->collection;

        if ($data instanceof Arrayable && method_exists($data, 'toArray') && count($data) === 1) {
            $data = $data->first();
        }

        return [
            'success' => true,
            'message' => $this->message,
            'data'    => $data instanceof Arrayable ? $data->toArray() : $data
        ];
    }
}
