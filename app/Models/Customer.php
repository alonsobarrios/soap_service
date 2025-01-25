<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Customer extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'document',
        'name',
        'email',
        'phone',
    ];

    /**
     * Get the Wallet associated with the Customer.
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }
}
