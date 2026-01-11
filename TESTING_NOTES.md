# Testing Notes - Multi-Tenant Fix

## Issue Fixed
**Error:** `SQLSTATE[HY000]: General error: 1 ambiguous column name: id`

**Penyebab:** 
Ketika menggunakan `pluck()` pada relasi many-to-many, Laravel tidak tahu kolom `id` mana yang dimaksud (dari tabel `tenants` atau `tenant_user`).

**Solusi:**
Menambahkan `select()` sebelum `pluck()` untuk specify kolom yang diambil:

```php
// Sebelum (Error)
return $user->tenants()->pluck('name', 'id');

// Sesudah (Fixed)
return $user->tenants()
    ->select('tenants.id', 'tenants.name')
    ->pluck('tenants.name', 'tenants.id');
```

## Files Modified
1. ✅ `app/Filament/Pages/TenantSwitcher.php` - Fixed ambiguous column
2. ✅ `app/Models/Tenant.php` - Added `assignedUsers()` relation

## Testing Checklist

### 1. Login & Basic Access
- [ ] Login sebagai user biasa (operator/owner)
- [ ] Dashboard muncul dengan benar
- [ ] Data sesuai dengan tenant user

### 2. Single Tenant User
- [ ] User dengan 1 tenant dapat akses data
- [ ] Menu "Ganti Tenant" TIDAK muncul (karena hanya 1 tenant)
- [ ] Widget menampilkan data tenant yang benar

### 3. Multi-Tenant User
- [ ] Assign 2+ tenant ke user via UserResource
- [ ] Menu "Ganti Tenant" muncul di navigation
- [ ] Klik "Ganti Tenant" → Form muncul
- [ ] Dropdown menampilkan semua tenant yang bisa diakses
- [ ] Pilih tenant → Klik "Ganti Tenant"
- [ ] Notifikasi sukses muncul
- [ ] Redirect ke dashboard
- [ ] Data berubah sesuai tenant yang dipilih
- [ ] Widget "Tenant Aktif" menampilkan tenant yang benar

### 4. Administrator (Superadmin/Admin)
- [ ] Login sebagai admin
- [ ] Menu "Ganti Tenant" muncul
- [ ] Dropdown menampilkan SEMUA tenant (tidak terbatas)
- [ ] Dapat switch ke tenant manapun
- [ ] Data berubah sesuai tenant yang dipilih

### 5. UserResource Management
- [ ] Buka UserResource
- [ ] Create/Edit user dengan role owner/operator
- [ ] Field "Tenants" (multiple select) muncul
- [ ] Dapat pilih multiple tenants
- [ ] Field "Tenant Aktif" muncul
- [ ] Save berhasil
- [ ] Table menampilkan kolom "Tenants" dengan badge
- [ ] Table menampilkan kolom "Tenant Aktif" dengan badge hijau

### 6. Widgets & Resources
- [ ] YearlyChartWidget menampilkan data tenant aktif
- [ ] IncomeExpenseStatWidget menampilkan data tenant aktif
- [ ] Semua Resources (Items, Vendors, dll) filter by tenant aktif
- [ ] Switch tenant → Data di semua widget/resource berubah

### 7. Edge Cases
- [ ] User tanpa tenant → Tidak bisa akses (atau auto-assign first tenant)
- [ ] User dengan tenant yang di-delete → Fallback ke tenant lain
- [ ] Switch ke tenant yang tidak punya akses → Error message muncul
- [ ] Logout → Login lagi → Tenant aktif masih tersimpan

## Known Issues (If Any)

_Catat di sini jika menemukan bug saat testing_

---

## Quick Test Commands

```bash
# Check database
php artisan tinker
>>> User::find(6)->tenants()->select('tenants.id', 'tenants.name')->get()
>>> User::find(6)->getCurrentTenantId()

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Check logs
tail -f storage/logs/laravel.log
```

## Rollback (If Needed)

```bash
php artisan migrate:rollback --step=3
```

---

**Tested By:** _[Your Name]_  
**Date:** _[Test Date]_  
**Status:** ⏳ Pending / ✅ Passed / ❌ Failed
