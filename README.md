# WST Week 13 - Securing a Student Information Management System

Computer Laboratory Activity No. 10 - BSIT Web Systems and Technologies

The app is in this workspace folder: `F:\xampp\htdocs\WST_Laboratory_Activity_10`
(the lab sheet uses `C:\xampp\htdocs\wst_week13`, but XAMPP is installed on `F:`
here). The database is still named `wst_week13` as required.

Open the app at:

```
http://localhost/WST_Laboratory_Activity_10/
```

## Files

| File | Purpose |
|---|---|
| `db.php` | mysqli connection (localhost / root / empty password / `wst_week13`), utf8mb4, generic 500 on failure |
| `helpers.php` | `e()` output-escaping helper used by every page |
| `setup.sql` | Creates the database, `users` and `student_comments` tables |
| `register.php` | Email validation, 12-character minimum, `password_hash()`, prepared INSERT, duplicate-email handling |
| `login.php` | Prepared lookup, `password_verify()`, `session_regenerate_id(true)`, generic error |
| `dashboard.php` | Session-protected landing page with links |
| `admin.php` | Session + role check, HTTP 403 for students, admin dashboard |
| `index.php` | Comment form + comment list, prepared INSERT, escaped output, result/free and close |
| `logout.php` | Clears `$_SESSION`, expires the cookie with the same parameters, destroys the session |
| `style.css` | Basic styling for the pages |
| `tests/smoke-test.ps1` | Automated end-to-end check of all nine checklist items |
| `screenshots/` | Evidence screenshots |
| `REFLECTION.md` | Answers to the ten reflection questions |

## Setup

1. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Import the database:
   - Open <http://localhost/phpmyadmin/>, go to the **SQL** tab, paste
     `setup.sql`, and click **Go**; or run:
     ```powershell
     Get-Content .\setup.sql -Raw | F:\xampp\mysql\bin\mysql.exe -u root
     ```
3. Check the credentials in `db.php` if your MySQL root account has a password.
4. Open <http://localhost/WST_Laboratory_Activity_10/register.php> and create an
   account.
5. To create an administrator, register a second account and run:
   ```sql
   UPDATE wst_week13.users SET role = 'admin' WHERE email = 'admin@example.com';
   ```
6. Log out and log in again so the new role is written into the session.

> The registration form has no role field on purpose. Roles can only be changed
> in the database, never by the public form.

## Demo accounts (local, fictional)

| Role | Email | Password |
|---|---|---|
| Student | `student@example.com` | `StudentPass123!` |
| Administrator | `admin@example.com` | `AdminPass12345!` |

These accounts already exist if the included database was seeded. They are for
local testing only - never reuse these passwords on a real deployment.

## Security controls

- **Password storage** - `password_hash()` / `password_verify()`; no plain-text passwords.
- **SQL injection** - every query that uses input is a prepared statement with bound parameters.
- **Session protection** - `dashboard.php`, `index.php` and `admin.php` redirect visitors with no `user_id`.
- **Session fixation** - `session_regenerate_id(true)` immediately after login.
- **Authorization** - `admin.php` checks `$_SESSION['role'] === 'admin'` on the server and returns HTTP 403 otherwise.
- **XSS** - emails, comments and messages are output through `htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
- **Cookie hardening** - session cookie is `HttpOnly`, `SameSite=Lax`, and `Secure` when HTTPS is used.
- **Logout** - session array cleared, cookie expired, session destroyed, then redirect.

## Automated test

With Apache and MySQL running:

```powershell
powershell -ExecutionPolicy Bypass -File .\tests\smoke-test.ps1
```

Latest run: **21 checks passed, 0 failed** (register, hash inspection,
duplicate/short-password/invalid-email rejection, wrong password, session
protection, student 403, admin access, XSS encoding, logout invalidation).

## Testing checklist

| Test | Expected Result | Actual Result |
|---|---|---|
| Register a valid account | Account is created | **Pass** - success message, row added to `users` (`01-register-page.png`) |
| Inspect stored password | Password is hashed | **Pass** - bcrypt hash beginning `$2y$10$`, not the plain password (`07-users-table-hashes.png`) |
| Log in with correct credentials | Login succeeds | **Pass** - redirect to dashboard, "Welcome, student@example.com!" (`03-student-dashboard.png`) |
| Log in with incorrect credentials | Login is rejected | **Pass** - `Invalid email or password.`, no authenticated session (`02-invalid-login.png`) |
| Open dashboard while logged out | Access denied or redirected | **Pass** - HTTP 302 to `login.php` |
| Open admin page as a student | Access is denied | **Pass** - HTTP 403 and access-denied page (`04-admin-denied-student.png`) |
| Open admin page as an administrator | Access is granted | **Pass** - Administrator Dashboard loads (`05-admin-dashboard.png`) |
| Submit the XSS test string | String displays as text | **Pass** - `&lt;script&gt;alert(&#039;XSS test&#039;)&lt;/script&gt;` appears as text; no alert (`06-xss-comment.png`) |
| Log out and reopen dashboard | Access denied or redirected | **Pass** - HTTP 302 to `login.php`; old cookie cannot reuse the session |

## Evidence screenshots

| Screenshot | Shows |
|---|---|
| `screenshots/01-register-page.png` | Registration form with the 12-character password rule |
| `screenshots/02-invalid-login.png` | Generic invalid-credentials message |
| `screenshots/03-student-dashboard.png` | Successful login / session-protected dashboard |
| `screenshots/04-admin-denied-student.png` | HTTP 403 for a student opening `admin.php` |
| `screenshots/05-admin-dashboard.png` | Admin dashboard for the administrator account |
| `screenshots/06-xss-comment.png` | XSS test string displayed as plain text |
| `screenshots/07-users-table-hashes.png` | phpMyAdmin view of `users` with hashed passwords |
| `screenshots/08-database-structure.png` | `users` table structure |

## Notes

- PHP 8.2 / MariaDB from XAMPP were used for testing.
- `setup.sql` drops and recreates the two activity tables, so do not run it on a
  database that contains data you want to keep.
- For a public deployment: use a dedicated MySQL user with a strong password,
  enable HTTPS, and set `session.cookie_secure` / HSTS.
