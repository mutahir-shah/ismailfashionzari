<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ErrorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // $this->resource could be:
        // - An array of messages
        // - A MessageBag (from Validator)
        // - A string
        // - null

        $errors = $this->resource;

        // Handle null case
        if ($errors === null) {
            $errors = ['An error occurred'];
        }

        // If it's already an array, check if it has a 'message' key
        if (is_array($errors)) {
            if (isset($errors['message'])) {
                // If it's an array with a message key, use that message
                $message = $errors['message'];
                $errorList = [$message];
            } else {
                // If it's a plain array of messages
                $errorList = $errors;
                $message = $errorList[0] ?? 'An error occurred';
            }
        } else {
            // Convert MessageBag or other object to plain array of strings
            if (is_object($errors) && method_exists($errors, 'all')) {
                $errorList = $errors->all();
                $message = $errorList[0] ?? 'An error occurred';
            } else {
                // It's a string or other type
                $message = (string) $errors;
                $errorList = [$message];
            }
        }

        return [
            'success' => false,
            'message' => $message,
            'errors' => $errorList,
        ];
    }
}
