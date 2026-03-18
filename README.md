# 🏺 LoveJoy – Antique Evaluation Web Application

A secure, full-stack PHP web application built as part of the **Introduction to Computer Security (G6077)** module at the University of Sussex. LoveJoy allows customers to register, log in, and submit antique evaluation requests — with a strong focus on security best practices throughout.

---

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Security Highlights](#security-highlights)
- [Tech Stack](#tech-stack)
- [Database Schema](#database-schema)
- [Getting Started](#getting-started)
- [Project Structure](#project-structure)
- [Academic Context](#academic-context)

---

## Overview

LoveJoy is a minimum viable product (MVP) for a fictional antique dealer. The application allows:

- **Customers** to register, log in, and submit evaluation requests with photo uploads
- **Administrators** to view all submitted evaluation requests via a protected listing page

The project was built with security as a first-class concern, implementing defences against common web vulnerabilities including SQL injection, XSS, CSRF, brute force attacks, and insecure file uploads.

---

## Features

### User-Facing
- **Registration** – Email, password, name, and contact telephone number
- **Login** – Secure session-based authentication
- **Password Recovery** – Forgot password flow with secure reset
- **Evaluation Request** – Authenticated form with comment box, preferred contact dropdown, and photo upload
- **Password Strength Indicator** – Real-time feedback on password quality

### Admin
- **Evaluation Listings** – Role-protected page displaying all submitted requests (admin only)

---

## Security Highlights

### 🔐 Authentication & Password Management
- Passwords hashed using **bcrypt** with salting via `password_hash()` / `password_verify()`
- **Password entropy enforcement** – minimum length, uppercase, lowercase, numbers, and special characters required
- **Account lockout** after repeated failed login attempts (brute force mitigation)
- **Two-factor authentication (2FA)** via time-based one-time password (Google Authenticator / TOTP)
- **Email-based password recovery** using PHPMailer with Gmail SMTP
- Security questions as an additional recovery layer

### 🛡️ Injection & XSS Prevention
- All database queries use **prepared statements with parameterised inputs** (PDO) — no raw SQL string interpolation
- All user-supplied output is sanitised with `htmlspecialchars()` to prevent **Cross-Site Scripting (XSS)**
- Input validation performed on both client and server sides

### 🔒 CSRF Protection
- **CSRF tokens** generated per session and validated on all state-changing POST requests

### 📁 File Upload Security
- Uploaded files validated by **MIME type** (not just extension) using `finfo`
- Only whitelisted image types accepted (JPEG, PNG, GIF, WebP)
- Files stored outside the web root with randomised filenames
- File size limits enforced

### 🧱 Additional Defences
- **Content Security Policy (CSP)** headers set to restrict resource loading
- **HTTP security headers** including `X-Frame-Options`, `X-Content-Type-Options`, `Strict-Transport-Security`
- Session tokens regenerated on login to prevent **session fixation**
- **CAPTCHA** integration to mitigate bot/botnet registration attacks
- Database credentials stored in environment configuration outside the web root
- Normalised relational database design with least-privilege DB user

---

## Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP |
| Database | MySQL |
| Styling | HTML5 / CSS3 |
| Email | PHPMailer + Gmail SMTP |
| 2FA | Google Authenticator (TOTP) |
| Password Hashing | bcrypt (`password_hash`) |
| DB Access | PDO with prepared statements |

---

## Database Schema

The application uses a normalised, four-table relational database:

```
users
 ├── id (PK)
 ├── name
 ├── email (UNIQUE)
 ├── password_hash
 ├── phone
 ├── role (customer / admin)
 ├── is_locked
 ├── failed_attempts
 ├── totp_secret
 └── created_at

evaluation_requests
 ├── id (PK)
 ├── user_id (FK → users)
 ├── description
 ├── contact_preference (phone / email)
 ├── photo_path
 └── submitted_at

password_resets
 ├── id (PK)
 ├── user_id (FK → users)
 ├── token_hash
 └── expires_at

security_questions
 ├── id (PK)
 ├── user_id (FK → users)
 ├── question
 └── answer_hash
```

---

## Getting Started

### Prerequisites
- PHP 8.x
- MySQL 8.x
- A local server environment (e.g. XAMPP, MAMP, or Docker)
- Composer (for PHPMailer)

### Installation

```bash
# Clone the repository
git clone https://github.com/gabrielhj17/LoveJoy.git
cd LoveJoy

# Install dependencies
composer install

# Import the database schema
mysql -u root -p < db/schema.sql

# Copy and configure environment settings
cp config/config.example.php config/config.php
# Edit config.php with your DB credentials, SMTP details, and app secret
```

### Configuration

In `config/config.php`, set:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'lovejoy');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'your_email@gmail.com');
define('SMTP_PASS', 'your_app_password');
```

Then visit `http://localhost/LoveJoy` in your browser.

---

## Project Structure

```
LoveJoy/
├── config/
│   └── config.php          # DB and SMTP credentials (not committed)
├── db/
│   └── schema.sql           # Database schema
├── includes/
│   ├── db.php               # PDO connection
│   ├── auth.php             # Session & auth helpers
│   └── csrf.php             # CSRF token generation & validation
├── uploads/                 # Stored outside web root (symlinked or redirected)
├── register.php
├── login.php
├── forgot_password.php
├── reset_password.php
├── evaluate.php             # Evaluation request form (auth required)
├── admin_listings.php       # Admin evaluation list (admin role required)
├── logout.php
└── index.php
```

---

## Academic Context

This project was submitted as Part B of the **Introduction to Computer Security (G6077)** coursework at the **University of Sussex**, worth 50% of the module mark. It demonstrates practical implementation of security principles covered in the module including access control, authentication, vulnerability mitigation, and secure database design.

Part A of the same coursework covered Linux access control (file permissions, user/group management, and umask configuration) — documented separately in the report submission.

---

> ⚠️ **Disclaimer:** This application was built for academic purposes. Credentials used in testing are not reused elsewhere and any personal data in screenshots has been anonymised per submission guidelines.
