<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Exception;

class OneMapService
{
    // API Configuration
    private string $baseUrl;
    private ?string $email;
    private ?string $password;
    private string $publicEndpoint;
    
    // Cache constants
    private const CACHE_TOKEN_KEY = 'onemap_auth_token';
    private const CACHE_TOKEN_HOURS = 23; // Token expires after 24 hours
    
    // API endpoints
    private const ENDPOINT_AUTH = '/auth/post/getToken';
    private const ENDPOINT_SEARCH = '/common/elastic/search';
    
    // Validation constants
    private const POSTAL_CODE_PATTERN = '/^\d{6}$/';
    private const ADDRESS_LINE_MAX_LENGTH = 45;
    private const DEFAULT_PAGE_NUM = 1;
    
    // Response parameters
    private const RETURN_GEOM_DEFAULT = 'Y';
    private const GET_ADDR_DETAILS_DEFAULT = 'Y';
    
    // Address constants
    private const SINGAPORE_SUFFIX = 'SINGAPORE';
    private const NIL_VALUE = 'NIL';
    
    // Error messages
    private const ERROR_INVALID_POSTAL = 'Invalid postal code format. Please enter a 6-digit Singapore postal code.';
    private const ERROR_NO_ADDRESS_FOUND = 'No address found for this postal code.';
    private const ERROR_FETCH_FAILED = 'Unable to fetch address details. Please try again.';
    private const ERROR_SERVICE_UNAVAILABLE = 'Service temporarily unavailable. Please enter address manually.';

    public function __construct()
    {
        $this->baseUrl = config('services.onemap.base_url');
        $this->email = config('services.onemap.email');
        $this->password = config('services.onemap.password');
        $this->publicEndpoint = config('services.onemap.public_endpoint');
    }

    /**
     * Get authentication token for OneMap API
     * Tokens are cached for 23 hours (they expire after 24 hours)
     */
    public function getAuthToken(): ?string
    {
        // Return cached token if available
        $cachedToken = $this->getCachedToken();
        if ($cachedToken) {
            return $cachedToken;
        }

        // If no credentials configured, return null to use public endpoint
        if (!$this->hasCredentials()) {
            return null;
        }

        return $this->requestNewToken();
    }

    /**
     * Get cached authentication token
     */
    private function getCachedToken(): ?string
    {
        return Cache::get(self::CACHE_TOKEN_KEY);
    }

    /**
     * Check if credentials are configured
     */
    private function hasCredentials(): bool
    {
        return !empty($this->email) && !empty($this->password);
    }

    /**
     * Request new authentication token from API
     */
    private function requestNewToken(): ?string
    {
        try {
            $response = Http::post($this->baseUrl . self::ENDPOINT_AUTH, [
                'email' => $this->email,
                'password' => $this->password,
            ]);

            if ($response->successful()) {
                return $this->processTokenResponse($response->json());
            }

        } catch (Exception $e) {
            // Authentication error occurred
        }

        return null;
    }

    /**
     * Process token response and cache it
     */
    private function processTokenResponse(array $data): ?string
    {
        if (!isset($data['access_token'])) {
            return null;
        }

        $token = $data['access_token'];
        Cache::put(self::CACHE_TOKEN_KEY, $token, now()->addHours(self::CACHE_TOKEN_HOURS));
        
        return $token;
    }



    /**
     * Search for address by postal code
     */
    public function searchByPostalCode(
        string $postalCode, 
        string $returnGeom = self::RETURN_GEOM_DEFAULT, 
        string $getAddrDetails = self::GET_ADDR_DETAILS_DEFAULT
    ): array {
        // Validate postal code format
        if (!$this->isValidPostalCode($postalCode)) {
            return $this->createErrorResponse(self::ERROR_INVALID_POSTAL);
        }

        try {
            $searchParams = $this->buildSearchParams($postalCode, $returnGeom, $getAddrDetails);
            $response = $this->performSearch($searchParams);

            if ($response->successful()) {
                return $this->processSearchResponse($response->json());
            }

            return $this->createErrorResponse(self::ERROR_FETCH_FAILED);

        } catch (Exception $e) {
            return $this->createErrorResponse(self::ERROR_SERVICE_UNAVAILABLE);
        }
    }

    /**
     * Validate Singapore postal code format
     */
    private function isValidPostalCode(string $postalCode): bool
    {
        return preg_match(self::POSTAL_CODE_PATTERN, $postalCode) === 1;
    }

    /**
     * Build search parameters
     */
    private function buildSearchParams(string $postalCode, string $returnGeom, string $getAddrDetails): array
    {
        return [
            'searchVal' => $postalCode,
            'returnGeom' => $returnGeom,
            'getAddrDetails' => $getAddrDetails,
            'pageNum' => self::DEFAULT_PAGE_NUM
        ];
    }

    /**
     * Perform the search request
     */
    private function performSearch(array $params)
    {
        $token = $this->getAuthToken();
        $url = $this->getSearchUrl($token);
        $headers = $token ? ['Authorization' => $token] : [];

        return Http::withHeaders($headers)->get($url, $params);
    }

    /**
     * Get appropriate search URL based on authentication
     */
    private function getSearchUrl(?string $token): string
    {
        return $token 
            ? $this->baseUrl . self::ENDPOINT_SEARCH
            : $this->publicEndpoint;
    }

    /**
     * Process search response data
     */
    private function processSearchResponse(array $data): array
    {
        if ($data['found'] > 0 && !empty($data['results'])) {
            return [
                'success' => true,
                'error' => null,
                'data' => $data['results'][0],
                'total_found' => $data['found']
            ];
        }

        return $this->createErrorResponse(self::ERROR_NO_ADDRESS_FOUND);
    }

    /**
     * Create standardized error response
     */
    private function createErrorResponse(string $errorMessage): array
    {
        return [
            'success' => false,
            'error' => $errorMessage,
            'data' => null
        ];
    }



    /**
     * Format address data for frontend consumption
     */
    public function formatAddressData(?array $addressData): ?array
    {
        if (!$addressData) {
            return null;
        }

        $formatted = $this->extractBasicAddressFields($addressData);
        $formatted['coordinates'] = $this->extractCoordinates($addressData);
        
        // Split address intelligently into two lines
        $this->splitAddressLines($formatted);

        return $formatted;
    }

    /**
     * Extract basic address fields
     */
    private function extractBasicAddressFields(array $addressData): array
    {
        return [
            'postal_code' => $addressData['POSTAL'] ?? '',
            'full_address' => $addressData['ADDRESS'] ?? '',
            'block_number' => $addressData['BLK_NO'] ?? '',
            'road_name' => $addressData['ROAD_NAME'] ?? '',
            'building' => $addressData['BUILDING'] ?? '',
        ];
    }

    /**
     * Extract coordinate information
     */
    private function extractCoordinates(array $addressData): array
    {
        return [
            'latitude' => $addressData['LATITUDE'] ?? null,
            'longitude' => $addressData['LONGITUDE'] ?? null,
            'x' => $addressData['X'] ?? null,
            'y' => $addressData['Y'] ?? null,
        ];
    }

    /**
     * Split address into two lines intelligently
     */
    private function splitAddressLines(array &$formatted): void
    {
        $fullAddress = $formatted['full_address'];
        
        if ($fullAddress) {
            $this->splitFromFullAddress($formatted, $fullAddress);
        } else {
            $this->constructFromComponents($formatted);
        }
    }

    /**
     * Split from full address string
     */
    private function splitFromFullAddress(array &$formatted, string $fullAddress): void
    {
        // Clean address by removing Singapore suffix and postal code
        $cleanAddress = $this->cleanAddressSuffix($fullAddress);
        
        // Always use smart splitting logic (similar to customer side)
        $this->smartAddressSplit($formatted, $cleanAddress);
    }

    /**
     * Clean address suffix (SINGAPORE and postal code)
     */
    private function cleanAddressSuffix(string $address): string
    {
        return preg_replace('/\s+' . self::SINGAPORE_SUFFIX . '\s+\d{6}$/', '', $address);
    }

    /**
     * Split long address into two lines
     */
    private function splitLongAddress(array &$formatted, string $cleanAddress): void
    {
        // Use smart splitting logic similar to customer side Alpine.js
        $this->smartAddressSplit($formatted, $cleanAddress);
    }

    /**
     * Smart address splitting logic (similar to customer side Alpine.js)
     */
    private function smartAddressSplit(array &$formatted, string $address): void
    {
        // If we have building and road name separately, use them
        if (!empty($formatted['building']) && !empty($formatted['road_name'])) {
            // Put block number + road name in line 1
            $line1Parts = [];
            if (!empty($formatted['block_number'])) {
                $line1Parts[] = $formatted['block_number'];
            }
            if (!empty($formatted['road_name'])) {
                $line1Parts[] = $formatted['road_name'];
            }
            
            $formatted['address_line_1'] = implode(' ', $line1Parts);
            $formatted['address_line_2'] = $formatted['building'];
        } else {
            // Try to split the address intelligently
            $parts = explode(' ', $address);
            
            if (count($parts) > 4) {
                // Look for building indicators to make a smart split
                $splitPoint = $this->findBuildingSplitPoint($parts);
                
                $formatted['address_line_1'] = implode(' ', array_slice($parts, 0, $splitPoint));
                $formatted['address_line_2'] = implode(' ', array_slice($parts, $splitPoint));
            } else {
                // Short address, put it all in line 1
                $formatted['address_line_1'] = $address;
                $formatted['address_line_2'] = '';
            }
        }
    }

    /**
     * Find the best split point for building names
     */
    private function findBuildingSplitPoint(array $parts): int
    {
        $midPoint = ceil(count($parts) / 2);
        
        // Look for building indicators to make a smart split
        for ($i = 1; $i < count($parts) - 1; $i++) {
            $word = strtoupper($parts[$i]);
            if (strpos($word, 'BUILDING') !== false || 
                strpos($word, 'TOWER') !== false || 
                strpos($word, 'PLAZA') !== false || 
                strpos($word, 'CENTRE') !== false || 
                strpos($word, 'CENTER') !== false || 
                strpos($word, 'MALL') !== false ||
                strpos($word, 'SPRING') !== false ||
                strpos($word, 'PARK') !== false ||
                strpos($word, 'COURT') !== false ||
                strpos($word, 'GARDENS') !== false) {
                return $i;
            }
        }
        
        return $midPoint;
    }

    /**
     * Construct address from individual components
     */
    private function constructFromComponents(array &$formatted): void
    {
        $addressParts = array_filter([
            $formatted['block_number'],
            $formatted['road_name'],
            $formatted['building'] !== self::NIL_VALUE ? $formatted['building'] : null
        ]);
        
        $formatted['address_line_1'] = implode(' ', $addressParts);
        $formatted['address_line_2'] = '';
    }

    /**
     * Get API status and configuration info
     */
    public function getApiStatus(): array
    {
        return [
            'has_credentials' => $this->hasCredentials(),
            'base_url' => $this->baseUrl,
            'public_endpoint' => $this->publicEndpoint,
            'token_cached' => Cache::has(self::CACHE_TOKEN_KEY),
        ];
    }
}