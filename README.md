# Roomie — student accommodation finder

A responsive student PG finder focused on practical shared rooms, bedroom photos, and affordable monthly rents. It includes a PHP/MySQL API, account registration and login, live listing filters, property details, and a React-powered saved-stays drawer.

## Requirements

- PHP 8.1+ with `pdo_mysql` enabled
- MySQL 8.0+ (or a compatible MariaDB release)
- A modern browser with internet access for the Google Fonts, React CDN, and property photography

## Run locally

1. Create the database and sample properties:

   ```powershell
   mysql -u root -p < database.sql
   ```

2. Set the connection details for your local MySQL server. Defaults are `127.0.0.1:3306`, database `student_stays`, user `root`, and an empty password.

   ```powershell
   $env:DB_HOST = "127.0.0.1"
   $env:DB_PORT = "3306"
   $env:DB_NAME = "student_stays"
   $env:DB_USER = "root"
   $env:DB_PASSWORD = "your-local-password"
   ```

3. From the project folder, start PHP's development server:

   ```powershell
   php -S 127.0.0.1:8017
   ```

4. Open <http://127.0.0.1:8017>. Create an account to save stays. There are no seeded accounts or shared passwords.

The frontend and `api.php` must be served from the same origin so the PHP session cookie and CSRF protection work. Database connection errors are logged to the PHP error log and shown in the app; configure the database before using the API.

## Features

- Search and filter stays by city, neighborhood, monthly budget, and room gender without reloading the page.
- View property descriptions, ratings, amenities, pricing, and image galleries in a detail dialog.
- Register, sign in, sign out, and save/remove stays from a personal shortlist.
- Passwords use PHP's `password_hash` / `password_verify`; API writes validate the session CSRF token and use prepared SQL statements.
- The shortlist panel is rendered as a React component. PHP and vanilla JavaScript handle API access and the listing/detail experience.
- Responsive layouts support desktop, tablet, and mobile screen widths.

## Database

`database.sql` creates the `users`, `properties`, `amenities`, `property_amenities`, `property_images`, and `interested_users` tables, foreign keys, indexes, and sample budget PG listings. Re-importing it updates the names, prices, descriptions, and room photos for the eight included demo listings.

## Screenshots and deployment

Capture the listing, an opened property detail dialog, and the shortlist after saving a stay while the local site is running. For deployment, upload the files to a PHP 8.1+ host, create a MySQL database, import `database.sql`, and set `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` in the host's environment configuration. Keep HTTPS enabled in production. A hosting account is needed to publish a live URL; no hosting credentials or repository remote were provided.

## API endpoints

| Method | URL | Purpose |
| --- | --- | --- |
| GET | `api.php?action=properties` | Return filtered listings. Optional query parameters: `city`, `budget`, `gender`, `q`, `sort`. |
| GET | `api.php?action=property&id=1` | Return a stay, its amenities, and images. |
| GET | `api.php?action=shortlist` | Return the signed-in user's saved stays. |
| POST | `api.php?action=register` | Create an account. |
| POST | `api.php?action=login` | Sign in. |
| POST | `api.php?action=logout` | End the session. |
| POST | `api.php?action=interest` | Toggle a stay in the signed-in user's shortlist. |

POST requests send JSON and the `X-CSRF-Token` header returned by the listing endpoint or rendered into the page.
