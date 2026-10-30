# KYC Laravel application

This Laravel 12 application replaces the legacy PHP KYC web entry points while continuing to use the existing PostgreSQL student and KYC tables. Runtime connection settings are read from `.env`; keep that file private and do not commit it.

## Requirements

- PHP 8.2 or newer with `pdo_pgsql`, `pgsql`, and `fileinfo`
- Composer dependencies from `composer.json`
- Access to the existing KYC PostgreSQL database
- HTTPS for browser camera access outside localhost

## Local development

From this directory:

```powershell
php artisan serve
```

Configure `.env` with the existing database connection. The included environment already selects the private `kyc` filesystem disk; captured images are kept in `storage/app/kyc`, not in a public directory.

## Existing database

The application uses the existing tables `tblusers`, `tblaudittrail`, `tblsession`, `tblstudent`, `tblstudentregistration`, `tblstudentid`, and `tblcbz_student_ids`.

**No database migrations are required or should be run for this application.** Laravel reads and writes only the existing system tables for staff authentication, audit entries, and KYC status. Do not run `php artisan migrate` or `php artisan migrate:fresh` against the existing database.

Staff passwords are verified against the existing `tblusers.userpassword` field. Authentication does not create, hash, or update staff credentials or add any tables.

## Existing captured images

The legacy image folders are left untouched. After backing up the files, copy compatible JPEG images into Laravel's private storage with:

```powershell
php artisan kyc:import-legacy-images "C:\path\to\legacy\upload"
```

The importer validates file type, size, and registration-number format, skips files already present, and reports aggregate imported/skipped counts. Student-facing images can then be read only through authenticated Laravel routes.

## Deployment

Point the web server document root at this project's `public` directory and configure Apache URL rewriting. Set the `.env` `APP_URL` to the deployed application URL, keep `APP_DEBUG=false`, and ensure the web-server account can write to `storage` and `bootstrap/cache`. Do not expose `.env`, `storage/app/kyc`, or the project root through the web server.

## Tests and checks

The test suite uses an in-memory SQLite database and does not connect to or migrate the configured database:

```powershell
php artisan test
```

To check the routes:

```powershell
php artisan route:list
```
