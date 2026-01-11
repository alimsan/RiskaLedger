# Multi-Tenant Migration Guide

## Overview
Proyek ini telah diupgrade dari **single tenant per user** menjadi **multi-tenant per user**. User sekarang dapat memiliki akses ke beberapa tenant dan dapat beralih antar tenant.

## Perubahan Database

### Tabel Baru
1. **tenant_user** (pivot table)
   - `id`: Primary key
   - `user_id`: Foreign key ke users
   - `tenant_id`: Foreign key ke tenants
   - `is_default`: Boolean untuk menandai tenant default
   - `timestamps`

### Perubahan Tabel Users
1. **Kolom Baru**: `current_tenant_id`
   - Menyimpan tenant yang sedang aktif dipilih user
   - Nullable
   - Foreign key ke tenants

2. **Kolom Legacy**: `tenant_id`
   - Masih ada untuk backward compatibility
   - Akan deprecated di masa depan

## Perubahan Model

### User Model
**Relasi Baru:**
- `tenants()`: BelongsToMany - Semua tenant yang dapat diakses user
- `currentTenant()`: BelongsTo - Tenant yang sedang aktif

**Method Baru:**
- `getCurrentTenantId()`: Mendapatkan tenant ID yang aktif (prioritas: current_tenant_id > tenant_id)
- `switchTenant($tenantId)`: Beralih ke tenant lain
- `getDefaultTenant()`: Mendapatkan tenant default

**Method Updated:**
- `canAccessTenant()`: Sekarang cek akses ke multiple tenants

## Fitur Baru

### 1. Tenant Switcher
**Lokasi:** `app/Filament/Pages/TenantSwitcher.php`

User dapat beralih antar tenant melalui halaman ini. Hanya muncul jika:
- User adalah administrator, ATAU
- User memiliki akses ke lebih dari 1 tenant

**Route:** `/admin/tenant-switcher`

### 2. Current Tenant Widget
**Lokasi:** `app/Filament/Widgets/CurrentTenantWidget.php`

Widget yang menampilkan tenant yang sedang aktif dan jumlah tenant yang tersedia.

### 3. Tenant Scope Trait
**Lokasi:** `app/Traits/HasTenantScope.php`

Trait helper untuk mempermudah scoping query berdasarkan tenant.

## Migration yang Dijalankan

1. **2025_10_16_011238_create_tenant_user_table.php**
   - Membuat tabel pivot tenant_user

2. **2025_10_16_011300_add_current_tenant_id_to_users_table.php**
   - Menambahkan kolom current_tenant_id ke tabel users

3. **2025_10_16_011318_migrate_existing_user_tenants_to_pivot_table.php**
   - Migrasi data existing dari tenant_id ke tabel pivot
   - Set current_tenant_id = tenant_id untuk semua user existing

## Perubahan Code

### Semua Widget & Resources
Semua referensi `auth()->user()->tenant_id` telah diupdate menjadi `auth()->user()->getCurrentTenantId()`

**File yang diupdate:**
- `app/Filament/Widgets/YearlyChartWidget.php`
- `app/Filament/Widgets/IncomeExpenseStatWidget.php`
- `app/Filament/Resources/KasirResource/Pages/KasirPage.php`
- `app/Filament/Resources/PiutangResource.php`
- `app/Filament/Resources/ProfitSharingResource.php`
- `app/Http/Controllers/TestPrinterController.php`
- `app/Filament/Resources/CashInOutTypeResource.php`
- `app/Filament/Resources/HasilAkhirResource.php`
- `app/Filament/Resources/ItemResource.php`
- Dan 5 file lainnya...

### TenantMiddleware
Updated untuk:
- Menggunakan `getCurrentTenantId()` untuk mendapatkan tenant aktif
- Auto-set current_tenant_id jika belum di-set
- Menyimpan tenant_id di session

### UserResource
**Form Fields Baru:**
- `tenants`: Multiple select untuk assign multiple tenants
- `current_tenant_id`: Select untuk set tenant aktif
- `tenant_id`: Masih ada tapi diberi label "Legacy"

**Table Columns Baru:**
- `tenants.name`: Menampilkan semua tenant user (badge)
- `currentTenant.name`: Menampilkan tenant aktif (badge hijau)

## Cara Menggunakan

### Assign Multiple Tenants ke User
1. Login sebagai admin/superadmin
2. Buka menu **Pengaturan > Pengguna**
3. Edit user yang ingin diberi akses multiple tenant
4. Di field **Tenants**, pilih beberapa tenant
5. Di field **Tenant Aktif**, pilih tenant yang akan aktif default
6. Save

### User Beralih Tenant
1. Login sebagai user yang memiliki multiple tenant
2. Klik menu **Ganti Tenant** (muncul di navigation)
3. Pilih tenant yang diinginkan
4. Klik **Ganti Tenant**
5. Dashboard akan reload dengan data tenant yang dipilih

### Untuk Developer

**Mendapatkan Tenant Aktif:**
```php
$tenantId = auth()->user()->getCurrentTenantId();
```

**Cek Akses Tenant:**
```php
if (auth()->user()->canAccessTenant($tenantId)) {
    // User dapat akses tenant ini
}
```

**Switch Tenant Programmatically:**
```php
$user = auth()->user();
if ($user->switchTenant($newTenantId)) {
    // Berhasil switch
} else {
    // User tidak punya akses ke tenant ini
}
```

**Query dengan Tenant Scope:**
```php
// Manual
$data = Model::where('tenant_id', auth()->user()->getCurrentTenantId())->get();

// Atau gunakan trait
use App\Traits\HasTenantScope;

class YourModel extends Model
{
    use HasTenantScope;
}

// Kemudian
$data = YourModel::tenant()->get();
```

## Backward Compatibility

- Kolom `tenant_id` di tabel users **masih ada** dan **masih berfungsi**
- Method `getCurrentTenantId()` akan fallback ke `tenant_id` jika `current_tenant_id` null
- User lama yang hanya punya 1 tenant akan tetap berfungsi normal
- Data existing telah dimigrate ke tabel pivot secara otomatis

## Known Issues & Fixes

### Issue #1: Ambiguous Column Name (FIXED)
**Error:** `SQLSTATE[HY000]: General error: 1 ambiguous column name: id`

**Penyebab:** Query pada relasi many-to-many tidak specify kolom mana yang diambil.

**Fix:** Tambahkan `select()` sebelum `pluck()`:
```php
$user->tenants()
    ->select('tenants.id', 'tenants.name')
    ->pluck('tenants.name', 'tenants.id');
```

### Issue #2: getCachedFormActions Method Not Found (FIXED)
**Error:** `BadMethodCallException: Method getCachedFormActions does not exist`

**Penyebab:** TenantSwitcher menggunakan API lama yang tidak kompatibel dengan Filament v3.

**Fix:** 
1. Implement `HasForms` dan `HasActions` interfaces
2. Gunakan `Action::make()` untuk button
3. Update blade view untuk menggunakan action component

**Files Modified:**
- `app/Filament/Pages/TenantSwitcher.php`
- `resources/views/filament/pages/tenant-switcher.blade.php`

## Testing Checklist

- [x] Migration berhasil dijalankan
- [x] Fix ambiguous column name error
- [ ] User dapat login normal
- [ ] User dengan 1 tenant tetap bisa akses data
- [ ] User dengan multiple tenant dapat beralih tenant
- [ ] Widget menampilkan data sesuai tenant aktif
- [ ] Resources menampilkan data sesuai tenant aktif
- [ ] Admin dapat assign multiple tenant ke user
- [ ] Tenant switcher hanya muncul untuk user dengan multiple tenant

Lihat `TESTING_NOTES.md` untuk testing checklist lengkap.

## Rollback

Jika perlu rollback:

```bash
php artisan migrate:rollback --step=3
```

Ini akan rollback 3 migration terakhir:
1. migrate_existing_user_tenants_to_pivot_table
2. add_current_tenant_id_to_users_table
3. create_tenant_user_table

**PERHATIAN:** Rollback akan menghapus data di tabel pivot dan kolom current_tenant_id!

## Support

Jika ada masalah, cek:
1. Log Laravel: `storage/logs/laravel.log`
2. Browser console untuk error JavaScript
3. Database: Pastikan migration berhasil
4. Session: Clear browser cache/session jika ada masalah

---

**Tanggal Update:** 16 Oktober 2025  
**Versi:** 1.0.0  
**Developer:** Cascade AI Assistant
