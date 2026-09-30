<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BillerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $biller_image = htmlspecialchars($this->image);

        // Process biller image
        if ($biller_image && $biller_image != 'zummXD2dvAtI.png') {
            $smallImagePath = public_path("images/biller/small/{$biller_image}");
            $largeImagePath = public_path("images/biller/{$biller_image}");

            if (file_exists($smallImagePath)) {
                $imageUrl = url("images/biller/small/{$biller_image}");
            } elseif (file_exists($largeImagePath)) {
                $imageUrl = url("images/biller/{$biller_image}");
            } else {
                $imageUrl = url("images/zummXD2dvAtI.png");
            }
        } else {
            $imageUrl = url("images/zummXD2dvAtI.png");
        }

        return [
            'id' => $this->id,
            'image_url' => $imageUrl,
            'name' => $this->name,
            'company_name' => $this->company_name,
            'vat_number' => $this->vat_number,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'address' => $this->address,
        ];
    }
}
