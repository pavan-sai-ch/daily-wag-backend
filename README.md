# Daily Wag — Backend

## What this contains
- PHP API sources in `src/` and `api/`
- Public assets (if any) in `public/`
- Database initialization SQL in `sql/init/schema.sql`
- App config in `app/config/`

cd "C:\Users\Admin\Desktop\daily-wag\daily-wag-backend"
```
mysql -u root -p -e "CREATE DATABASE daily_wag;"
mysql -u root -p daily_wag < sql/init/schema.sql
```powershell
php -S localhost:8080 -t public
```powershell
php -S localhost:8080
```
## Database
- The schema and initial table definitions are in `sql/init/schema.sql`.
## API endpoints
- `api/auth.php` — authentication: register, login, logout (uses `UserModels` / `AuthController`)
- `api/users.php` — user profile actions
- `api/pets.php` — pet CRUD (uses `PetModels` / `PetController`)
- `api/products.php` — store items (uses `ProductModel` / `ProductController`)
- `api/visits.php` — appointments and visits (ses `VisitController`)

- Ensure these ports are free:
  - 8000 → PHP backend  http://localhost:8000/index.php
  
  - 8082 → phpMyAdmin
  - 5173 → Frontend (Vite)
