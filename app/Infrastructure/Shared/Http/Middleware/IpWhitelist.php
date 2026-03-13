<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Http\Middleware;

use App\Infrastructure\Shared\Http\Resources\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SECURITY FIX (ADMIN-001): IP Whitelist Middleware for Admin Routes
 * 
 * This middleware ensures that admin routes can only be accessed from whitelisted IP addresses.
 * Configure allowed IPs in .env using ADMIN_IP_WHITELIST (comma-separated).
 */
final class IpWhitelist
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $whitelist = $this->getWhitelist();
        
        // If whitelist is empty, log warning but allow (fail-open for development)
        // In production, this should be enforced with a non-empty list
        if (empty($whitelist)) {
            if (app()->environment('production')) {
                \Log::critical('ADMIN_IP_WHITELIST is empty in production environment');
                return ApiResponse::fail(
                    code: 'IP_WHITELIST_NOT_CONFIGURED',
                    message: 'Admin access is temporarily unavailable.',
                    status: 503
                );
            }
            
            \Log::warning('ADMIN_IP_WHITELIST is empty - allowing all IPs in non-production environment');
            return $next($request);
        }
        
        $clientIp = $this->getClientIp($request);
        
        if (!$this->isIpWhitelisted($clientIp, $whitelist)) {
            \Log::warning('Blocked admin access attempt from non-whitelisted IP', [
                'ip' => $clientIp,
                'user_id' => $request->attributes->get('auth_user_id'),
                'path' => $request->path(),
            ]);
            
            return ApiResponse::fail(
                code: 'IP_NOT_WHITELISTED',
                message: 'Access denied. Your IP address is not authorized to access admin functions.',
                status: 403
            );
        }
        
        return $next($request);
    }
    
    /**
     * Get whitelisted IP addresses from configuration
     * 
     * @return array<string>
     */
    private function getWhitelist(): array
    {
        $whitelistString = (string) env('ADMIN_IP_WHITELIST', '');
        
        if ($whitelistString === '') {
            return [];
        }
        
        return array_map('trim', explode(',', $whitelistString));
    }
    
    /**
     * Get the client's real IP address (handles proxies and load balancers)
     * 
     * @param Request $request
     * @return string
     */
    private function getClientIp(Request $request): string
    {
        // Check X-Forwarded-For header (for load balancers/proxies)
        // SECURITY: Only trust this in production with proper proxy configuration
        if ($request->server->has('HTTP_X_FORWARDED_FOR')) {
            $forwardedFor = $request->server->get('HTTP_X_FORWARDED_FOR');
            $ips = array_map('trim', explode(',', (string) $forwardedFor));
            // First IP in the chain is the client
            return $ips[0];
        }
        
        // Fallback to REMOTE_ADDR
        return $request->ip() ?? '0.0.0.0';
    }
    
    /**
     * Check if IP is in whitelist (supports CIDR notation)
     * 
     * @param string $ip
     * @param array<string> $whitelist
     * @return bool
     */
    private function isIpWhitelisted(string $ip, array $whitelist): bool
    {
        foreach ($whitelist as $whitelistedIp) {
            // Exact match
            if ($ip === $whitelistedIp) {
                return true;
            }
            
            // CIDR notation support (e.g., 192.168.1.0/24)
            if (str_contains($whitelistedIp, '/')) {
                if ($this->ipInCidr($ip, $whitelistedIp)) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Check if IP is in CIDR range
     * 
     * @param string $ip
     * @param string $cidr
     * @return bool
     */
    private function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr);
        
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        
        if ($ipLong === false || $subnetLong === false) {
            return false;
        }
        
        $maskLong = -1 << (32 - (int) $mask);
        
        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
}
