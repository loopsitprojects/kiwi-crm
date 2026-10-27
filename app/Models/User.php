<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    const ROLES = [
        'Finance Admin',
        'IT Admin',
        'Management',
        'HOD',
        'Manager',
        'HR Admin',
        'Staff',
    ];

    const DEPARTMENT_HIERARCHY = [
        'SBU' => [
            'Creative' => 'Creative',
            'Digital' => 'Digital',
            'Tech' => 'Tech',
            'PM' => 'PM',
            'Corporate' => 'Corporate',
        ],
        'Sales' => [
            'AM' => 'AM',
            'BD' => 'BD',
        ]
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'supervisor_id',
        'department',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function assignedHod()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function subordinates()
    {
        return $this->hasMany(User::class, 'supervisor_id');
    }

    /**
     * Resolve the Associated HOD User instance for this user.
     * Related HOD can be any user. The user's assigned HOD is their assigned supervisor/HOD.
     * 
     * @return \App\Models\User|null
     */
    public function getAssociatedHodAttribute()
    {
        if ($this->supervisor) {
            return $this->supervisor;
        }

        return null;
    }

    /**
     * Resolve the HOD Name for this user.
     * 
     * @return string
     */
    public function getHodNameAttribute()
    {
        return $this->associated_hod ? $this->associated_hod->name : 'Not Assigned';
    }

    /**
     * Automatically present legacy 'Super Admin' as 'Finance Admin'.
     */
    public function getRoleAttribute($value)
    {
        if ($value === 'Super Admin' || $value === 'super_admin') {
            return 'Finance Admin';
        }
        return $value;
    }

    /**
     * Automatically store 'Super Admin' input as 'Finance Admin'.
     */
    public function setRoleAttribute($value)
    {
        if ($value === 'Super Admin' || $value === 'super_admin') {
            $this->attributes['role'] = 'Finance Admin';
        } else {
            $this->attributes['role'] = $value;
        }
    }

    /**
     * Check if user has a specific role
     * 
     * @param string $role
     * @return bool
     */
    public function hasRole($role)
    {
        // Exact match
        if ($this->role === $role) {
            return true;
        }

        // Case-insensitive match normalization
        $normalizedInput = str_replace('_', ' ', strtolower(trim($role)));
        $normalizedStored = str_replace('_', ' ', strtolower(trim($this->role)));

        if ($normalizedInput === $normalizedStored) {
            return true;
        }

        // Equate Super Admin and Finance Admin
        if (in_array($normalizedInput, ['super admin', 'finance admin']) && in_array($normalizedStored, ['super admin', 'finance admin'])) {
            return true;
        }

        // IT Admin inherits all Super Admin / Finance Admin privileges
        if (in_array($normalizedInput, ['super admin', 'finance admin']) && $normalizedStored === 'it admin') {
            return true;
        }

        return false;
    }

    /**
     * Check if user has administrative privileges (Finance Admin, Super Admin, IT Admin, or Management).
     *
     * @return bool
     */
    public function hasAdminPrivileges(): bool
    {
        return in_array($this->role, ['Finance Admin', 'Super Admin', 'IT Admin', 'Management']) ||
               $this->hasRole('finance_admin') ||
               $this->hasRole('super_admin') ||
               $this->hasRole('it_admin') ||
               $this->hasRole('management');
    }

    /**
     * Check if user is a Finance Admin (or legacy Super Admin).
     *
     * @return bool
     */
    public function isFinanceAdmin(): bool
    {
        return in_array($this->role, ['Finance Admin', 'Super Admin']) ||
               $this->hasRole('finance_admin') ||
               $this->hasRole('super_admin');
    }

    /**
     * Check if user is in Management.
     *
     * @return bool
     */
    public function isManagement(): bool
    {
        return $this->role === 'Management' || $this->hasRole('management');
    }

    /**
     * Check if user is an IT Admin.
     *
     * @return bool
     */
    public function isITAdmin(): bool
    {
        $role = str_replace('_', ' ', strtolower(trim($this->role ?? '')));
        return $role === 'it admin';
    }

    /**
     * Check if user is an HR Admin.
     *
     * @return bool
     */
    public function isHRAdmin(): bool
    {
        return $this->role === 'HR Admin' || $this->hasRole('hr_admin') || $this->hasRole('hr admin');
    }


    /**
     * Check if user is authorized to edit the invoice number.
     * Strictly permitted for IT Admin and Finance Admin (including Super Admin).
     *
     * @return bool
     */
    public function canEditInvoiceNumber(): bool
    {
        return $this->hasRole('IT Admin') ||
               $this->hasRole('Finance Admin') ||
               $this->hasRole('Super Admin') ||
               $this->role === 'IT Admin' ||
               $this->role === 'Finance Admin';
    }

    public function deals()
    {
        return $this->belongsToMany(Deal::class, 'deal_user');
    }

    /**
     * Get dynamic department hierarchy grouped by category.
     * Falls back to static DEPARTMENT_HIERARCHY if table does not exist or has no active records.
     *
     * @return array
     */
    public static function getDepartmentHierarchy(): array
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('departments')) {
                $departments = \App\Models\Department::where('status', 'active')
                    ->orderBy('group')
                    ->orderBy('name')
                    ->get();

                if ($departments->isNotEmpty()) {
                    $hierarchy = [];
                    foreach ($departments as $dept) {
                        $group = $dept->group ?: 'General';
                        $hierarchy[$group][$dept->name] = $dept->name;
                    }
                    return $hierarchy;
                }
            }
        } catch (\Throwable $e) {
            // Graceful fallback on any exception
        }

        return self::DEPARTMENT_HIERARCHY;
    }

    /**
     * Get flat list of all active department names.
     *
     * @return array
     */
    public static function getDepartmentList(): array
    {
        $hierarchy = self::getDepartmentHierarchy();
        $list = [];
        foreach ($hierarchy as $group) {
            $list = array_merge($list, array_keys($group));
        }
        return array_values(array_unique($list));
    }
}
