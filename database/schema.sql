/*
 Navicat Premium Dump SQL

 Source Server         : database
 Source Server Type    : SQLite
 Source Server Version : 3045000 (3.45.0)
 Source Schema         : main

 Target Server Type    : SQLite
 Target Server Version : 3045000 (3.45.0)
 File Encoding         : 65001

 Date: 16/10/2025 01:06:26
*/

PRAGMA foreign_keys = false;

-- ----------------------------
-- Table structure for activity_log
-- ----------------------------
DROP TABLE IF EXISTS "activity_log";
CREATE TABLE "activity_log" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "log_name" varchar,
  "description" text NOT NULL,
  "subject_type" varchar,
  "subject_id" integer,
  "causer_type" varchar,
  "causer_id" integer,
  "properties" text,
  "created_at" datetime,
  "updated_at" datetime,
  "event" varchar,
  "batch_uuid" varchar
);

-- ----------------------------
-- Table structure for cache
-- ----------------------------
DROP TABLE IF EXISTS "cache";
CREATE TABLE "cache" (
  "key" varchar NOT NULL,
  "value" text NOT NULL,
  "expiration" integer NOT NULL,
  PRIMARY KEY ("key")
);

-- ----------------------------
-- Table structure for cache_locks
-- ----------------------------
DROP TABLE IF EXISTS "cache_locks";
CREATE TABLE "cache_locks" (
  "key" varchar NOT NULL,
  "owner" varchar NOT NULL,
  "expiration" integer NOT NULL,
  PRIMARY KEY ("key")
);

-- ----------------------------
-- Table structure for cash_in_out
-- ----------------------------
DROP TABLE IF EXISTS "cash_in_out";
CREATE TABLE "cash_in_out" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "nama_barang" varchar,
  "type" varchar NOT NULL DEFAULT ('B_BAKU'),
  "type_id" integer,
  "deksripsi" text,
  "tipe_cio" integer NOT NULL DEFAULT ('2'),
  "nilai" integer NOT NULL,
  "waktu" datetime NOT NULL,
  "created_at" datetime,
  "updated_at" datetime,
  "tenant_id" integer,
  "keterangan" text,
  FOREIGN KEY ("type_id") REFERENCES "cash_in_out_types" ("id") ON DELETE SET NULL ON UPDATE NO ACTION,
  FOREIGN KEY ("tenant_id") REFERENCES "tenants" ("id") ON DELETE NO ACTION ON UPDATE NO ACTION
);

-- ----------------------------
-- Table structure for cash_in_out_types
-- ----------------------------
DROP TABLE IF EXISTS "cash_in_out_types";
CREATE TABLE "cash_in_out_types" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "code" varchar NOT NULL,
  "name" varchar NOT NULL,
  "description" text,
  "is_income" tinyint(1) NOT NULL DEFAULT ('0'),
  "is_active" tinyint(1) NOT NULL DEFAULT ('1'),
  "sort_order" integer NOT NULL DEFAULT ('0'),
  "created_at" datetime,
  "updated_at" datetime,
  "tenant_id" integer,
  "slug" varchar,
  "show_akhir" tinyint(1) NOT NULL DEFAULT '0',
  FOREIGN KEY ("tenant_id") REFERENCES "tenants" ("id") ON DELETE SET NULL ON UPDATE NO ACTION
);

-- ----------------------------
-- Table structure for config_tenants
-- ----------------------------
DROP TABLE IF EXISTS "config_tenants";
CREATE TABLE "config_tenants" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "name" varchar NOT NULL,
  "status" tinyint(1) NOT NULL DEFAULT '1',
  "tenant_id" integer NOT NULL,
  "created_at" datetime,
  "updated_at" datetime,
  FOREIGN KEY ("tenant_id") REFERENCES "tenants" ("id") ON DELETE CASCADE ON UPDATE NO ACTION
);

-- ----------------------------
-- Table structure for data_migration_status
-- ----------------------------
DROP TABLE IF EXISTS "data_migration_status";
CREATE TABLE "data_migration_status" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "migration_name" varchar NOT NULL,
  "is_completed" tinyint(1) NOT NULL DEFAULT '0',
  "notes" text,
  "processed_records" integer NOT NULL DEFAULT '0',
  "total_records" integer NOT NULL DEFAULT '0',
  "started_at" datetime,
  "completed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime
);

-- ----------------------------
-- Table structure for failed_jobs
-- ----------------------------
DROP TABLE IF EXISTS "failed_jobs";
CREATE TABLE "failed_jobs" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "uuid" varchar NOT NULL,
  "connection" text NOT NULL,
  "queue" text NOT NULL,
  "payload" text NOT NULL,
  "exception" text NOT NULL,
  "failed_at" datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ----------------------------
-- Table structure for items
-- ----------------------------
DROP TABLE IF EXISTS "items";
CREATE TABLE "items" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "tenant_id" integer NOT NULL,
  "name" varchar NOT NULL,
  "description" text,
  "price" numeric NOT NULL,
  "image" varchar,
  "is_active" tinyint(1) NOT NULL DEFAULT '1',
  "stock" integer NOT NULL DEFAULT '0',
  "sku" varchar,
  "barcode" varchar,
  "category" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  FOREIGN KEY ("tenant_id") REFERENCES "tenants" ("id") ON DELETE CASCADE ON UPDATE NO ACTION
);

-- ----------------------------
-- Table structure for job_batches
-- ----------------------------
DROP TABLE IF EXISTS "job_batches";
CREATE TABLE "job_batches" (
  "id" varchar NOT NULL,
  "name" varchar NOT NULL,
  "total_jobs" integer NOT NULL,
  "pending_jobs" integer NOT NULL,
  "failed_jobs" integer NOT NULL,
  "failed_job_ids" text NOT NULL,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer NOT NULL,
  "finished_at" integer,
  PRIMARY KEY ("id")
);

-- ----------------------------
-- Table structure for jobs
-- ----------------------------
DROP TABLE IF EXISTS "jobs";
CREATE TABLE "jobs" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "queue" varchar NOT NULL,
  "payload" text NOT NULL,
  "attempts" integer NOT NULL,
  "reserved_at" integer,
  "available_at" integer NOT NULL,
  "created_at" integer NOT NULL
);

-- ----------------------------
-- Table structure for migrations
-- ----------------------------
DROP TABLE IF EXISTS "migrations";
CREATE TABLE "migrations" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "migration" varchar NOT NULL,
  "batch" integer NOT NULL
);

-- ----------------------------
-- Table structure for model_has_permissions
-- ----------------------------
DROP TABLE IF EXISTS "model_has_permissions";
CREATE TABLE "model_has_permissions" (
  "permission_id" integer NOT NULL,
  "model_type" varchar NOT NULL,
  "model_id" integer NOT NULL,
  PRIMARY KEY ("permission_id", "model_id", "model_type"),
  FOREIGN KEY ("permission_id") REFERENCES "permissions" ("id") ON DELETE CASCADE ON UPDATE NO ACTION
);

-- ----------------------------
-- Table structure for model_has_roles
-- ----------------------------
DROP TABLE IF EXISTS "model_has_roles";
CREATE TABLE "model_has_roles" (
  "role_id" integer NOT NULL,
  "model_type" varchar NOT NULL,
  "model_id" integer NOT NULL,
  PRIMARY KEY ("role_id", "model_id", "model_type"),
  FOREIGN KEY ("role_id") REFERENCES "roles" ("id") ON DELETE CASCADE ON UPDATE NO ACTION
);

-- ----------------------------
-- Table structure for password_reset_tokens
-- ----------------------------
DROP TABLE IF EXISTS "password_reset_tokens";
CREATE TABLE "password_reset_tokens" (
  "email" varchar NOT NULL,
  "token" varchar NOT NULL,
  "created_at" datetime,
  PRIMARY KEY ("email")
);

-- ----------------------------
-- Table structure for permissions
-- ----------------------------
DROP TABLE IF EXISTS "permissions";
CREATE TABLE "permissions" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "name" varchar NOT NULL,
  "guard_name" varchar NOT NULL,
  "description" text,
  "created_at" datetime,
  "updated_at" datetime
);

-- ----------------------------
-- Table structure for piutangs
-- ----------------------------
DROP TABLE IF EXISTS "piutangs";
CREATE TABLE "piutangs" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "vendor_id" integer NOT NULL,
  "qty" integer NOT NULL,
  "bukti_resi" varchar,
  "waktu" datetime NOT NULL,
  "tenant_id" integer NOT NULL,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "total_utang" numeric NOT NULL,
  "type_id" integer,
  "lunas" tinyint(1) NOT NULL DEFAULT '0',
  "image_pelunasan" varchar,
  FOREIGN KEY ("tenant_id") REFERENCES "tenants" ("id") ON DELETE CASCADE ON UPDATE NO ACTION,
  FOREIGN KEY ("vendor_id") REFERENCES "vendors" ("id") ON DELETE CASCADE ON UPDATE NO ACTION,
  FOREIGN KEY ("type_id") REFERENCES "cash_in_out_types" ("id") ON DELETE SET NULL ON UPDATE NO ACTION
);

-- ----------------------------
-- Table structure for profit_sharings
-- ----------------------------
DROP TABLE IF EXISTS "profit_sharings";
CREATE TABLE "profit_sharings" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "tenant_id" integer NOT NULL,
  "name" varchar NOT NULL,
  "percentage" numeric NOT NULL,
  "is_active" tinyint(1) NOT NULL DEFAULT '1',
  "is_default" tinyint(1) NOT NULL DEFAULT '0',
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  FOREIGN KEY ("tenant_id") REFERENCES "tenants" ("id") ON DELETE CASCADE ON UPDATE NO ACTION
);

-- ----------------------------
-- Table structure for role_has_permissions
-- ----------------------------
DROP TABLE IF EXISTS "role_has_permissions";
CREATE TABLE "role_has_permissions" (
  "permission_id" integer NOT NULL,
  "role_id" integer NOT NULL,
  PRIMARY KEY ("permission_id", "role_id"),
  FOREIGN KEY ("permission_id") REFERENCES "permissions" ("id") ON DELETE CASCADE ON UPDATE NO ACTION,
  FOREIGN KEY ("role_id") REFERENCES "roles" ("id") ON DELETE CASCADE ON UPDATE NO ACTION
);

-- ----------------------------
-- Table structure for roles
-- ----------------------------
DROP TABLE IF EXISTS "roles";
CREATE TABLE "roles" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "name" varchar NOT NULL,
  "guard_name" varchar NOT NULL,
  "created_at" datetime,
  "updated_at" datetime
);

-- ----------------------------
-- Table structure for sessions
-- ----------------------------
DROP TABLE IF EXISTS "sessions";
CREATE TABLE "sessions" (
  "id" varchar NOT NULL,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text NOT NULL,
  "last_activity" integer NOT NULL,
  PRIMARY KEY ("id")
);

-- ----------------------------
-- Table structure for tenants
-- ----------------------------
DROP TABLE IF EXISTS "tenants";
CREATE TABLE "tenants" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "name" varchar NOT NULL,
  "address" varchar,
  "phone" varchar,
  "email" varchar,
  "description" text,
  "is_active" tinyint(1) NOT NULL DEFAULT '1',
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "nota_colour" varchar,
  "invoice_judul" varchar,
  "logo" varchar
);

-- ----------------------------
-- Table structure for transaction_items
-- ----------------------------
DROP TABLE IF EXISTS "transaction_items";
CREATE TABLE "transaction_items" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "transaction_id" integer NOT NULL,
  "transaction_type" varchar NOT NULL,
  "cash_in_out_type_id" integer NOT NULL,
  "item_id" integer NOT NULL,
  "quantity" integer NOT NULL,
  "price" numeric NOT NULL,
  "subtotal" numeric NOT NULL,
  "waktu" datetime NOT NULL,
  "created_at" datetime,
  "updated_at" datetime,
  FOREIGN KEY ("item_id") REFERENCES "items" ("id") ON DELETE CASCADE ON UPDATE NO ACTION
);

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS "users";
CREATE TABLE "users" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "name" varchar NOT NULL,
  "email" varchar NOT NULL,
  "email_verified_at" datetime,
  "password" varchar NOT NULL,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "role" varchar NOT NULL DEFAULT 'operator',
  "tenant_id" integer,
  FOREIGN KEY ("tenant_id") REFERENCES "tenants" ("id") ON DELETE SET NULL ON UPDATE NO ACTION,
   ("role" in ('superadmin', 'admin', 'owner', 'operator'))
);

-- ----------------------------
-- Table structure for vendors
-- ----------------------------
DROP TABLE IF EXISTS "vendors";
CREATE TABLE "vendors" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "nama_vendor" varchar NOT NULL,
  "tenant_id" integer NOT NULL,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  FOREIGN KEY ("tenant_id") REFERENCES "tenants" ("id") ON DELETE CASCADE ON UPDATE NO ACTION
);

-- ----------------------------
-- Auto increment value for activity_log
-- ----------------------------
UPDATE "sqlite_sequence" SET seq = 7250 WHERE name = 'activity_log';

-- ----------------------------
-- Indexes structure for table activity_log
-- ----------------------------
CREATE INDEX "activity_log_log_name_index"
ON "activity_log" (
  "log_name" ASC
);
CREATE INDEX "causer"
ON "activity_log" (
  "causer_type" ASC,
  "causer_id" ASC
);
CREATE INDEX "subject"
ON "activity_log" (
  "subject_type" ASC,
  "subject_id" ASC
);

-- ----------------------------
-- Auto increment value for cash_in_out
-- ----------------------------
UPDATE "sqlite_sequence" SET seq = 5207 WHERE name = 'cash_in_out';

-- ----------------------------
-- Indexes structure for table cash_in_out
-- ----------------------------
CREATE INDEX "cash_in_out_tenant_id_index"
ON "cash_in_out" (
  "tenant_id" ASC
);

-- ----------------------------
-- Auto increment value for cash_in_out_types
-- ----------------------------
UPDATE "sqlite_sequence" SET seq = 18 WHERE name = 'cash_in_out_types';

-- ----------------------------
-- Indexes structure for table cash_in_out_types
-- ----------------------------
CREATE UNIQUE INDEX "cash_in_out_types_code_tenant_id_unique"
ON "cash_in_out_types" (
  "code" ASC,
  "tenant_id" ASC
);
CREATE INDEX "cash_in_out_types_tenant_id_index"
ON "cash_in_out_types" (
  "tenant_id" ASC
);

-- ----------------------------
-- Auto increment value for config_tenants
-- ----------------------------
UPDATE "sqlite_sequence" SET seq = 2 WHERE name = 'config_tenants';

-- ----------------------------
-- Indexes structure for table config_tenants
-- ----------------------------
CREATE UNIQUE INDEX "config_tenants_name_unique"
ON "config_tenants" (
  "name" ASC
);

-- ----------------------------
-- Indexes structure for table data_migration_status
-- ----------------------------
CREATE UNIQUE INDEX "data_migration_status_migration_name_unique"
ON "data_migration_status" (
  "migration_name" ASC
);

-- ----------------------------
-- Indexes structure for table failed_jobs
-- ----------------------------
CREATE UNIQUE INDEX "failed_jobs_uuid_unique"
ON "failed_jobs" (
  "uuid" ASC
);

-- ----------------------------
-- Auto increment value for items
-- ----------------------------
UPDATE "sqlite_sequence" SET seq = 25 WHERE name = 'items';

-- ----------------------------
-- Indexes structure for table jobs
-- ----------------------------
CREATE INDEX "jobs_queue_index"
ON "jobs" (
  "queue" ASC
);

-- ----------------------------
-- Auto increment value for migrations
-- ----------------------------
UPDATE "sqlite_sequence" SET seq = 32 WHERE name = 'migrations';

-- ----------------------------
-- Indexes structure for table model_has_permissions
-- ----------------------------
CREATE INDEX "model_has_permissions_model_id_model_type_index"
ON "model_has_permissions" (
  "model_id" ASC,
  "model_type" ASC
);

-- ----------------------------
-- Indexes structure for table model_has_roles
-- ----------------------------
CREATE INDEX "model_has_roles_model_id_model_type_index"
ON "model_has_roles" (
  "model_id" ASC,
  "model_type" ASC
);

-- ----------------------------
-- Auto increment value for permissions
-- ----------------------------
UPDATE "sqlite_sequence" SET seq = 9 WHERE name = 'permissions';

-- ----------------------------
-- Indexes structure for table permissions
-- ----------------------------
CREATE UNIQUE INDEX "permissions_name_guard_name_unique"
ON "permissions" (
  "name" ASC,
  "guard_name" ASC
);

-- ----------------------------
-- Auto increment value for piutangs
-- ----------------------------
UPDATE "sqlite_sequence" SET seq = 724 WHERE name = 'piutangs';

-- ----------------------------
-- Auto increment value for profit_sharings
-- ----------------------------
UPDATE "sqlite_sequence" SET seq = 8 WHERE name = 'profit_sharings';

-- ----------------------------
-- Auto increment value for roles
-- ----------------------------
UPDATE "sqlite_sequence" SET seq = 3 WHERE name = 'roles';

-- ----------------------------
-- Indexes structure for table roles
-- ----------------------------
CREATE UNIQUE INDEX "roles_name_guard_name_unique"
ON "roles" (
  "name" ASC,
  "guard_name" ASC
);

-- ----------------------------
-- Indexes structure for table sessions
-- ----------------------------
CREATE INDEX "sessions_last_activity_index"
ON "sessions" (
  "last_activity" ASC
);
CREATE INDEX "sessions_user_id_index"
ON "sessions" (
  "user_id" ASC
);

-- ----------------------------
-- Auto increment value for tenants
-- ----------------------------
UPDATE "sqlite_sequence" SET seq = 3 WHERE name = 'tenants';

-- ----------------------------
-- Auto increment value for transaction_items
-- ----------------------------
UPDATE "sqlite_sequence" SET seq = 4488 WHERE name = 'transaction_items';

-- ----------------------------
-- Auto increment value for users
-- ----------------------------
UPDATE "sqlite_sequence" SET seq = 7 WHERE name = 'users';

-- ----------------------------
-- Indexes structure for table users
-- ----------------------------
CREATE UNIQUE INDEX "users_email_unique"
ON "users" (
  "email" ASC
);
CREATE INDEX "users_role_index"
ON "users" (
  "role" ASC
);
CREATE INDEX "users_tenant_id_index"
ON "users" (
  "tenant_id" ASC
);

-- ----------------------------
-- Auto increment value for vendors
-- ----------------------------
UPDATE "sqlite_sequence" SET seq = 16 WHERE name = 'vendors';

PRAGMA foreign_keys = true;
