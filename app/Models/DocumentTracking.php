<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentTracking extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentTrackingFactory> */
    use HasFactory;

    protected $fillable = [
        'document_id',
        'from_user',
        'to_user',
        'status_id',
        'remarks',
    ];


    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'from_user');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'to_user');
    }

}
