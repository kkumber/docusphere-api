<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'office',
        'status'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'roles'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected $appends = ['role'];

    // Custom function to return role automatically when returning user
    public function getRoleAttribute()
    {
        return $this->roles->pluck('name')->first();
    }

    public function actions()
    {
        return $this->hasMany(DocAssignmentAction::class, 'performed_by');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'uploaded_by');
    }

    public function documentFiles()
    {
        return $this->hasMany(DocumentFile::class, 'uploaded_by');
    }

    public function docResponseFiles()
    {
        return $this->hasMany(DocResponseFile::class, 'uploaded_by');
    }

    public function documentTrackingSent()
    {
        return $this->hasMany(DocumentTracking::class, 'from_user');
    }

    public function documentTrackingReceived()
    {
        return $this->hasMany(DocumentTracking::class, 'to_user');
    }

    public function documentVersions()
    {
        return $this->hasMany(DocumentVersion::class, 'uploaded_by');
    }

    public function documentComments()
    {
        return $this->hasMany(DocumentComment::class, 'user_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

}
