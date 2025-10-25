<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdminService;
use App\Http\Requests\AdminValidation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    protected $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    public function adminDashboard()
    {
        // In your AuthenticateAdmin middleware, temporarily add:
        logger(get_class(Auth::user())); 
        return response()->json($this->adminService->getDashboard());
    }

    public function listUsers(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');
        $status = $request->get('status');
        
        return response()->json($this->adminService->listUsers($perPage, $search, $status));
    }


    public function adminAddUser(Request $request)
    {
        $validated = AdminValidation::validateAddUser($request->all());
        $user = $this->adminService->addUser($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'User created successfully.',
            'user' => $user
        ], 200);
    }

    public function adminAddUserSecondaryContact(Request $request, $id)
    {
        try {
            $validated = AdminValidation::validateSecondaryContact($request->all());
            $user = $this->adminService->updateUserSecondaryContact($id, $validated);

            return response()->json([
                'status' => 'success',
                'message' => 'OTP sent to your email.',
                'user' => $user
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'User not found.'
            ], 404);
        }
    }
    public function adminUpdateUser(Request $request, $id)
    {
        try {
            $validated = AdminValidation::validateUpdateUser($request->all(), $id);
            $user = $this->adminService->updateUser($id, $validated);

            return response()->json([
                'status' => 'success',
                'message' => 'User updated successfully.',
                'user' => $user
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'User not found.'
            ], 404);
        }
    }

    public function adminUpdateUserStatus(Request $request, $id)
    {
        try {
            // Prevent admin from updating their own status
            if (Auth::id() == $id) {
                return response()->json([
                    'message' => 'You cannot update your own status.'
                ], 422);
            }

            $validated = AdminValidation::validateUserStatus($request->all());
            $user = $this->adminService->updateUserStatus($id, $validated['is_active']);

            $statusMessage = $validated['is_active']
                ? 'Customer reactivated successfully.'
                : 'Customer deactivated successfully.';

            return response()->json([
                'status' => 'success',
                'message' => 'User status updated successfully.',
                'user' => $user
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'User not found.'
            ], 404);
        }
    }
}
