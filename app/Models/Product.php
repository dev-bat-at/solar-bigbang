<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Product extends Model
{
    use SoftDeletes, LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'images' => 'array',
        'specifications' => 'array',
        'specifications_vi' => 'array',
        'specifications_en' => 'array',
        'documents' => 'array',
        'documents_vi' => 'array',
        'documents_en' => 'array',
        'faqs' => 'array',
        'faqs_vi' => 'array',
        'faqs_en' => 'array',
        'is_best_seller' => 'boolean',
        'is_price_contact' => 'boolean',
        'price' => 'integer',
    ];

    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function productSubcategory()
    {
        return $this->belongsTo(ProductCategory::class, 'product_subcategory_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty();
    }
}
