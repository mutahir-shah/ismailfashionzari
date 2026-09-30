<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Courier extends Model
{
     protected $fillable = [
        "name",
        "type",
        "phone_number",
        "address",
        "is_active",
        // Steadfast
        "api_key",
        "secret_key",
        // Pathao
        "client_id",
        "client_secret",
        "username",
        "password",
        "base_url",
    ];
}
