<?php

namespace App\Traits;

trait HandlesAuthServiceResponses
{
    /**
     * Handle AuthService response for web controllers
     * Converts service responses to appropriate web responses (redirects, errors, etc.)
     */
    protected function handleAuthServiceResponse($result, $successRoute = null, $successMessage = null, $errorRoute = null)
    {
        if ($result['status'] === 'success') {
            $message = $successMessage ?? $result['message'];
            
            if ($successRoute) {
                return redirect()->route($successRoute)->with('success', $message);
            } else {
                return back()->with('success', $message);
            }
        }

        // Handle different error types
        $errorField = $this->mapErrorStatusToField($result['status']);
        
        if ($errorRoute) {
            return redirect()->route($errorRoute)->withErrors([$errorField => $result['message']]);
        }
        
        return back()->withErrors([$errorField => $result['message']])->withInput();
    }

    /**
     * Handle AuthService response with session data for web controllers
     */
    protected function handleAuthServiceResponseWithSession($result, $sessionData, $successRoute, $successMessage = null, $errorRoute = null)
    {
        if ($result['status'] === 'success') {
            // Store session data on success
            session($sessionData);
            
            $message = $successMessage ?? $result['message'];
            return redirect()->route($successRoute)->with('success', $message);
        }

        // Handle errors same as above
        $errorField = $this->mapErrorStatusToField($result['status']);
        
        if ($errorRoute) {
            return redirect()->route($errorRoute)->withErrors([$errorField => $result['message']]);
        }
        
        return back()->withErrors([$errorField => $result['message']])->withInput();
    }

    /**
     * Map AuthService error status to appropriate form field
     */
    protected function mapErrorStatusToField($status)
    {
        $fieldMap = [
            'invalid_phone' => 'phone',
            'invalid_referral'  => 'referal_code',
            'rate_limited' => 'phone',
            'conflict' => 'phone',
            'not_found' => 'identifier',
            'invalid' => 'otp',
            'inactive' => 'general',
            'verification_required' => 'general',
            'service_limit' => 'general',
            'error' => 'general',
        ];

        return $fieldMap[$status] ?? 'general';
    }

    /**
     * Handle AuthService response for API controllers
     * Converts service responses to appropriate JSON responses
     */
    protected function formatApiResponse($result)
    {
        $statusCodeMap = [
            'success' => 200,
            'created' => 201,
            'invalid_phone' => 422,
            'rate_limited' => 429,
            'conflict' => 409,
            'not_found' => 404,
            'invalid' => 422,
            'inactive' => 403,
            'verification_required' => 422,
            'service_limit' => 503,
            'error' => 500,
        ];

        $statusCode = $statusCodeMap[$result['status']] ?? 500;
        
        // For success responses, include additional data
        if ($result['status'] === 'success' || $result['status'] === 'created') {
            return response()->json($result, $statusCode);
        }
        
        // For error responses, format consistently
        return response()->json([
            'status' => $result['status'],
            'message' => $result['message']
        ], $statusCode);
    }
}