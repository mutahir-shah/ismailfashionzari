<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceSchema extends Model
{
    use HasFactory;
    protected $table = 'invoice_schemas';
    protected $fillable = [
        'prefix',
        'number_of_digit',
        'start_number',
        'last_invoice_number',
    ];
}
