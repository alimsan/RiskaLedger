<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
{
    if(env('APP_DEBUG', false)){
        // Debug info
        \Log::debug('TenantMiddleware running', [
            'path' => $request->path(),
            'method' => $request->method(),
            'user' => auth()->check() ? auth()->id() : 'guest'
        ]);

        // PENTING: Bypass untuk route login Filament
        if (str_contains($request->path(), 'admin/login')) {
            \Log::info('Bypassing tenant check for admin login');
            return $next($request);
        }
    }
    $user = Auth::user();

    // Jika tidak ada user, redirect ke login
    if (!$user) {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Alih-alih abort langsung, redirect ke login
        return redirect()->route('filament.admin.auth.login');
    }

    // Superadmin dan admin memiliki akses ke semua tenant
    if ($user->isAdministrator()) {
        return $next($request);
    }

    // Jika ada tenant_id di request, cek apakah user punya akses
    $tenantId = $request->route('tenant_id') ?? $request->input('tenant_id');

    if ($tenantId && !$user->canAccessTenant($tenantId)) {
        \Log::warning('User denied access to tenant', [
            'user_id' => $user->id,
            'requested_tenant' => $tenantId,
            'user_tenant' => $user->tenant_id
        ]);

        // Kirim pesan yang lebih informatif
        return response()->view('errors.forbidden', [
            'message' => 'Tidak memiliki akses ke tenant ini'
        ], 403);
    }

    // Set current tenant ID in session
    // Priority: current_tenant_id > tenant_id (legacy)
    $currentTenantId = $user->getCurrentTenantId();
    
    if (!$tenantId && $currentTenantId) {
        session(['tenant_id' => $currentTenantId]);
    }

    // Atau jika ada tenant_id di request, simpan di session
    if ($tenantId) {
        session(['tenant_id' => $tenantId]);
    }

    // Auto-set current_tenant_id if not set but user has tenants
    if (!$user->current_tenant_id && !$user->tenant_id) {
        $firstTenant = $user->tenants()->first();
        if ($firstTenant) {
            $user->update(['current_tenant_id' => $firstTenant->id]);
            session(['tenant_id' => $firstTenant->id]);
        }
    }

    return $next($request);
}
}
