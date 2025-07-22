# Chloé Paddington Microsite

A simple, zero-framework PHP microsite that lets customers join a “waiting list” for the Chloé Paddington bag.  
Features:

- AJAX-powered sign-up form at `/`  
- Stores submissions in a MySQL `waitlist` table  
- Dynamic in-page thank-you message  
- Admin UI at `/admin.php` to browse submissions  
- CSV export of all entries at `/export_csv.php`  
- Configuration via `.env` (using `vlucas/phpdotenv`)  
- Vanilla PHP + Composer + Bootstrap 5 + custom “Ten Futura Boo Cy” font  

---

## 🔧 Prerequisites

- **PHP 8.0+** (with PDO MySQL extension)  
- **Composer** (for dependency management)  
- **MySQL 5.7+** (or MariaDB)  
- **Git** (to clone the repo)  
- Optionally: **Homebrew** (macOS) and **Sequel Ace** or **phpMyAdmin** for database GUI

---

## 🚀 Quick Local Setup

```bash
# 1) Clone the repo
git clone https://github.com/your-username/chloe-paddington.git
cd chloe-paddington

# 2) Install PHP dependencies (dotenv)
composer require vlucas/phpdotenv --no-interaction

# 3) Copy example env and edit
cp .env.example .env
# Edit `.env` to match your local MySQL creds:
# DB_HOST=127.0.0.1
# DB_NAME=paddington
# DB_USER=root
# DB_PASS=

# 4) Create the database & table
#    You can use the SQL in `db/schema.sql` (see next section)
mysql -u root -p < db/schema.sql

# 5) Start the PHP built-in server
php -S 127.0.0.1:8000

# 6) Browse to
#    http://127.0.0.1:8000/
#    http://127.0.0.1:8000/admin.php
#    http://127.0.0.1:8000/export_csv.php

🗄 Database Schema
## The file db/schema.sql defines your table:

CREATE DATABASE IF NOT EXISTS paddington
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE paddington;

CREATE TABLE IF NOT EXISTS waitlist (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title            ENUM('Mrs.','Mr.','Miss') NOT NULL DEFAULT '',
  first_name       VARCHAR(100)    NOT NULL,
  last_name        VARCHAR(100)    NOT NULL,
  phone            VARCHAR(50)     NOT NULL,
  email            VARCHAR(100)    NOT NULL,
  preferred_colors VARCHAR(255)    NOT NULL,
  created_at       TIMESTAMP       DEFAULT CURRENT_TIMESTAMP
);

📁 File Structure:
.
├── .env.example        # Example env file
├── db/
│   └── schema.sql      # SQL to create the waitlist table
├── export_csv.php      # Streams all entries as CSV download
├── index.php           # AJAX form page (sign-up)
├── admin.php           # Admin UI: HTML table of submissions
├── submit.php          # AJAX endpoint: validate & insert
├── thankyou.php        # Fallback thank you page (if JS disabled)
├── style.css           # Custom CSS + @font-face for “Ten Futura Boo Cy”
├── fonts/
│   └── TenFuturaBooCy-Regular.*  # Font files (ttf, woff, woff2)
├── vendor/             # Composer dependencies (dotenv)
├── composer.json       # Composer manifest
└── README.md           # This file

⚙️ Environment Variables:
require __DIR__ . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__)->load();

$host = getenv('DB_HOST');
$name = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');

🛠 Endpoints & Usage
URL	Purpose
- / ->	Sign-up form (AJAX)
- /submit.php ->	AJAX POST endpoint → returns JSON
- /thankyou.php ->	Static fallback thank-you page
- /admin.php ->	View all submissions in an HTML table
- /export_csv.php ->	Download all submissions as waitlist.csv

📦 Production Deployment:
- Move code to your web-root (e.g. /var/www/paddington-site).
- Install Composer deps (composer install --no-dev).
- Create a real MySQL database & user, import db/schema.sql.
- Copy .env (with prod creds) and secure it.
- Configure Nginx / Apache VirtualHost.
- Enable HTTPS (Let’s Encrypt).
- Disable display_errors in php.ini.
- Set proper file permissions:
- Directories: 755
- Files: 644

🐞 Troubleshooting
- Blank JSON / JS errors → check browser console & Network tab for submit.php response.
- Database connection issues → verify .env values and MySQL user privileges.
- CSVs include HTML warnings → ensure display_errors = Off or use ini_set() at top of export_csv.php.


