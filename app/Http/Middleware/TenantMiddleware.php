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
        $user = Auth::user();

        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        // Superadmin dan admin memiliki akses ke semua tenant
        if ($user->isAdministrator()) {
            return $next($request);
        }

        // Jika ada tenant_id di request, cek apakah user punya akses
        $tenantId = $request->route('tenant_id') ?? $request->input('tenant_id');

        if ($tenantId && !$user->canAccessTenant($tenantId)) {
            abort(403, 'Tidak memiliki akses ke tenant ini');
        }

        // Jika tidak ada tenant_id di request, tetapi user memiliki tenant,
        // set tenant_id di session agar bisa digunakan nanti
        if (!$tenantId && $user->tenant_id) {
            session(['tenant_id' => $user->tenant_id]);
        }

        // Atau jika ada tenant_id di request, simpan di session
        if ($tenantId) {
            session(['tenant_id' => $tenantId]);
        }

        return $next($request);
    }
}
