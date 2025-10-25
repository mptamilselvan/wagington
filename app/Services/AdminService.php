<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Exception;

class AdminService
{
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';
    
    // Role constants
    private const ROLE_CUSTOMER = 'customer';
    
    // Default values
    private const DEFAULT_PER_PAGE = 15;
    private const STATUS_ACTIVE = 'active';
    private const STATUS_INACTIVE = 'inactive';

    /**
     * Get admin dashboard statistics
     */
    public function getDashboard(): array
    {
        $query = $this->getCustomerQuery();
        
        $totalUsers = $query->count();
        $activeUsers = $query->where('is_active', true)->count();
        $inactiveUsers = $totalUsers - $activeUsers;

        return [
            'status' => self::STATUS_SUCCESS,
            'data' => [
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'inactive_users' => $inactiveUsers
            ]
        ];
    }

    /**
     * Get base query for customers
     */
    private function getCustomerQuery()
    {
        return User::whereHas('roles', fn ($q) => $q->where('name', self::ROLE_CUSTOMER));
    }

    /**
     * List users with pagination and filtering
     */
    public function listUsers(int $perPage = self::DEFAULT_PER_PAGE, ?string $search = null, ?string $status = null): array
    {
        $query = $this->getCustomerQuery();
        
        $this->applyStatusFilter($query, $status);
        $this->applySearchFilter($query, $search);
        
        $users = $query->orderBy('created_at', 'desc')->paginate($perPage);
        
        return [
            'status' => self::STATUS_SUCCESS,
            'data' => [
                'users' => $users
            ]
        ];
    }

    /**
     * Apply status filter to query
     */
    private function applyStatusFilter($query, ?string $status): void
    {
        if ($status === self::STATUS_ACTIVE) {
            $query->where('is_active', true);
        } elseif ($status === self::STATUS_INACTIVE) {
            $query->where('is_active', false);
        }
    }

    /**
     * Apply search filter to query
     */
    private function applySearchFilter($query, ?string $search): void
    {
        if (!$search) {
            return;
        }

        $query->where(function ($q) use ($search) {
            $searchTerm = '%' . $search . '%';
            $q->where('first_name', 'like', $searchTerm)
              ->orWhere('last_name', 'like', $searchTerm)
              ->orWhere('email', 'like', $searchTerm)
              ->orWhere('phone', 'like', $searchTerm);
        });
    }

    /**
     * Add new user
     */
    public function addUser(array $data): User
    {
        try {
            DB::beginTransaction();
            
            $userData = $this->prepareUserData($data);
            $user = User::create($userData);
            $user->assignRole(self::ROLE_CUSTOMER);
            
            DB::commit();
            return $user;
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update user information
     */
    public function updateUser(int $id, array $data): User
    {
        $user = User::findOrFail($id);
        $user->update($data);
        return $user->fresh();
    }

    /**
     * Update user secondary contact information
     */
    public function updateUserSecondaryContact(int $id, array $data): User
    {
        $user = User::findOrFail($id);
        $user->update($data);
        return $user->fresh();
    }

    /**
     * Update user status
     */
    public function updateUserStatus(int $id, bool $isActive): User
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => $isActive]);
        return $user->fresh();
    }

    /**
     * Prepare user data for creation
     */
    private function prepareUserData(array $data): array
    {
        $preparedData = $data;
        
        // Ensure password is hashed if provided
        if (isset($preparedData['password'])) {
            $preparedData['password'] = Hash::make($preparedData['password']);
        } else {
            $preparedData['password'] = Hash::make(Str::random(12));
        }
        
        return $preparedData;
    }
}
