<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SuccessResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = $this->resource;

        // Default structure
        $response = [
            'success' => true,
        ];

        // If it's an array, merge it
        if (is_array($data)) {
            $response = array_merge($response, $data);
        } else {
            // If it's a string message
            $response['message'] = $data;
        }

        return $response;
    }
}
