<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Cashier\Billable;

class Workspace extends Model
{
    use Billable;

    protected $guarded = ['id'];

    protected $hidden = ['stripe_id'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'workspace_user')->withPivot('role', 'status');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
