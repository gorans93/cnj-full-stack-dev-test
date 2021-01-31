<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ImportCsvMonthly extends Model
{
    protected $table = 'london_housing_monthly';

    protected $fillable = [
        'date',
        'area',
        'average_price',
        'code',
        'houses_sold',
        'no_of_crimes',
        'borough_flag',
        'created_at',
        'updated_at'
    ];
}
