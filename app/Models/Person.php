<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Person extends Model
{
    protected $table = 'people';

    protected $fillable = [
        'full_name',
        'name_en',
        'phone',
        'service_notes',
    ];

    public function schoolMemberships(): HasMany
    {
        return $this->hasMany(SchoolMembership::class, 'person_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(ScopedAppointment::class, 'person_id');
    }
}
