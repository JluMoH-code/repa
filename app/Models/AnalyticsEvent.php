<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsEvent extends Model
{
    protected $fillable = ['event_type', 'visitor_token', 'path', 'source', 'product_id'];
}
