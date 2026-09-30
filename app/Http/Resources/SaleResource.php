<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Determine Sale Status
        if($this->sale_status == 1){
            $saleStatus = 'Completed';
        }
        elseif($this->sale_status == 2){
            $saleStatus = 'Pending';
        }
        elseif($this->sale_status == 3){
            $saleStatus = 'Draft';
        }
        elseif($this->sale_status == 4){
            $saleStatus = 'Returned';
        }
        elseif($this->sale_status == 5){
            $saleStatus = 'Processing';
        }
        
        // Determine Payment Status
        if($this->payment_status == 1){
            $paymentStatus = 'Pending';
        }
        elseif($this->payment_status == 2){
            $paymentStatus = 'Due';
        }
        elseif($this->payment_status == 3){
            $paymentStatus = 'Partial';
        }
        else{
            $paymentStatus = 'Paid';
        }
        
        // Determine Delivery Status
        
        if($this->delivery)
        {
            if($this->delivery->status == 1)
                $deliveryStatus = 'Packing';
            elseif($this->delivery->status == 2)
                $deliveryStatus = 'Delivering';
            elseif($this->delivery->status == 3)
                $deliveryStatus = 'Delivering';
        }
        else
           $deliveryStatus = 'N/A'; 
        
        
        $returned_amount = number_format($this->return()->sum('grand_total'), config('decimal'));
        // $grandTotal = number_format($this->grand_total, config('decimal'));
        // $returned_amount = number_format($this->return()->sum('grand_total'), config('decimal'));
        // $paidAmount = number_format($this->paid_amount, config('decimal'));
        return [
            'id' => $this->id,
            'date' => date(config('date_format').' h:i:s a', strtotime($this->created_at)),
            'reference_no' => $this->reference_no,
            'biller' => new BillerResource($this->biller),
            'customer' => new CustomerResource($this->customer),
            'warehouse' => new WarehouseResource($this->warehouse),
            'sale_status' => $saleStatus,
            'payment_status' => $paymentStatus,
            'payment_method' => $this->payments->map(function ($payment) {
                    return ucfirst($payment->paying_method) . '(' . number_format($payment->amount, 2) . ')';
                })->implode(', '),
            'delivery_status' => $deliveryStatus,
            'grand_total' => number_format($this->grand_total, config('decimal')),
            'returned_amount' => $returned_amount,
            'paid_amount' => number_format($this->paid_amount, config('decimal')),
            'due' => number_format($this->grand_total - $this->return()->sum('grand_total') - $this->paid_amount, config('decimal')),
            'products' => ProductSaleResource::collection($this->products), // Include products
            'currency' => new CurrencyResource($this->currency),
            'created_by' => new UserResource($this->user),
            'exchange_rate' => $this->exchange_rate,
        ];
    }
}
