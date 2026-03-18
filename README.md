# 🏺 LoveJoy – Antique Evaluation Web Application

A secure, full-stack PHP web application built as part of the **Introduction to Computer Security (G6077)** module at the University of Sussex. LoveJoy allows customers to register, log in, and submit antique evaluation requests — with a strong focus on security best practices throughout.

---

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Screenshots](#screenshots)
- [Security Highlights](#security-highlights)
- [Tech Stack](#tech-stack)
- [Database Schema](#database-schema)
- [Getting Started](#getting-started)
- [Project Structure](#project-structure)
- [Academic Context](#academic-context)

---

## Overview

LoveJoy is a minimum viable product (MVP) for a fictional antique dealer. The application allows:

- **Customers** to register, verify their email, set up two-factor authentication, and submit antique evaluation requests with photo uploads
- **Administrators** to view all submitted evaluation requests via a role-protected listing page

The project was built with security as a first-class concern, implementing defences against SQL injection, XSS, CSRF, brute force attacks, insecure file uploads, and more.

---

## Features

### User-Facing
- **Registration** – Email, password, name, contact telephone number, and security question
- **Email Verification** – Account requires email confirmation before login is permitted
- **Two-Factor Authentication** – Choose between TOTP (Google Authenticator) or email-based PIN; 2FA is mandatory on first login
- **Login** – Secure session-based authentication with account lockout after 3 failed attempts
- **Password Strength Indicator** – Real-time client-side feedback on password quality (`pwordStrength.js`)
- **Password Recovery** – Reset via email + security question answer; successfully resetting also unlocks a locked account
- **Evaluation Request** – Authenticated form with comment box, preferred contact method dropdown (phone/email), and photo upload
- **Show/Hide Password Toggle** – Client-side UX improvement (`showPassword.js`)

### Admin
- **Evaluation Listings** (`adminPage.php`) – Role-protected page displaying all submitted requests; accessible to admin role only

---

## Screenshots

### Login
![Login Page](loginPage.png)

### Register
![Register Page](registerPage.png)

### Reset Password
![Forgot Password Page](forgotPasswordPage.png)

### Request an Evaluation
![Request Evaluation Page](requestEvalPage.png)

### Admin Home
![Admin Home Page](adminHomePage.png)

### Admin – Evaluation Requests
![Admin Evaluation Request Dashboard](adminEvalRequestDashboard.png)

---

## Security Highlights

### 🔐 Authentication & Password Management
- Passwords hashed with **bcrypt** (Blowfish cipher) via `password_hash()` / `password_verify()` — never stored or transmitted in plain text
- Security question answers also bcrypt-hashed before storage
- **Password policy enforced** on both client and server: minimum 8 characters, at least one uppercase, one lowercase, and one special character
- **Account lockout** after 3 incorrect login attempts; lockout is cleared on successful password reset
- **Email verification** required before login is permitted
- **Two-factor authentication** — both TOTP (Google Authenticator via `2faSetup.php`) and email-based PIN (`2faEmailSetup.php`); users choose their method and it is mandatory on first login

### 🛡️ Injection & XSS Prevention
- All database queries use **PDO prepared statements with parameterised inputs** — no raw SQL string interpolation anywhere
- All user-supplied output is escaped with `htmlspecialchars()` — `<script>` tags rendered as `&lt;script&gt;` preventing stored XSS
- The admin evaluation query is a static SQL string with no user input; JOIN conditions use table relationships only

### 🔒 CSRF Protection
- **CSRF tokens** generated per session and validated on all state-changing POST requests

### 📁 File Upload Security
- **MIME type validation** using `finfo` to read magic bytes — checks actual file content, not just extension, preventing extension spoofing
- **Double extension validation** as a secondary check to catch bypass attempts if MIME detection fails
- Only authenticated users can submit uploads — all submissions are associated with a verified user account

### 🧱 Additional Defences
- **`.htaccess`** configuration for server-level security controls
- Input sanitisation (trim, normalisation) applied throughout
- Role-based access control — admin pages verify user role on every page load
- **CAPTCHA** to mitigate bot/botnet registration attacks
- Vendor dependencies managed via **Composer** (PHPMailer, TOTP libraries)

---

## Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP |
| Database | MySQL |
| Styling | HTML5 / CSS3 (`styles.css`) |
| Email | PHPMailer (`vendor/phpmailer`) |
| TOTP / 2FA | `vendor/bacon`, `vendor/pragmarx`, `vendor/paragonie` |
| Password Hashing | bcrypt via `password_hash()` |
| DB Access | PDO with prepared statements |
| Client-side JS | `pwordStrength.js`, `pwordMatch.js`, `showPassword.js` |

---

## Database Schema

The application uses a normalised, four-table relational database:

```
users
 ├── user_id (PK)
 ├── email (UNIQUE)
 ├── password_hash
 ├── role (customer / admin)
 ├── is_locked
 ├── failed_attempts
 ├── email_verified
 └── created_at

user_profiles
 ├── profile_id (PK)
 ├── user_id (FK → users)
 ├── name
 └── phone (UNIQUE)

user_security
 ├── security_id (PK)
 ├── user_id (FK → users)
 ├── security_question
 ├── security_answer_hash
 ├── totp_secret
 └── email_verification_token

evaluation_requests
 ├── request_id (PK)
 ├── user_id (FK → users)
 ├── description
 ├── contact_preference (phone / email)
 ├── photo_path
 ├── status           ← reserved for future expansion
 ├── estimated_value  ← reserved for future expansion
 └── submitted_at
```

**Key relationships:**
- `users` (1) → `user_profiles` (1) — one-to-one
- `users` (1) → `user_security` (1) — one-to-one
- `users` (1) → `evaluation_requests` (many) — one-to-many

Referential integrity is enforced via foreign key constraints with `ON DELETE` / `ON UPDATE` rules. Uniqueness constraints prevent duplicate emails and phone numbers.

---

## Getting Started

### Prerequisites
- PHP 8.x
- MySQL 8.x
- A local server environment (e.g. XAMPP, MAMP, or Docker)
- Composer (dependencies already committed under `vendor/`)

### Installation

```bash
# Clone the repository
git clone https://github.com/gabrielhj17/LoveJoy.git
cd LoveJoy

# Import the database schema
mysql -u root -p < db/schema.sql

# Configure the application
# Edit config.php with your DB credentials
# Edit emailConfig.php with your SMTP / Gmail App Password settings
```

Then visit `http://localhost/LoveJoy` in your browser.

---

## Project Structure

```
LoveJoy/
├── .htaccess                  # Server-level security rules
├── index.html                 # Entry point / redirect
├── config.php                 # DB connection config
├── emailConfig.php            # SMTP / PHPMailer config
│
├── register.html              # Registration form (HTML)
├── register.php               # Registration logic + bcrypt + prepared statements
├── login.html                 # Login form (HTML)
├── login.php                  # Login logic + lockout + email verification check
├── logout.php                 # Session destruction
├── home.php                   # Authenticated home page
│
├── forgotPassword.php         # Password recovery (email + security question)
│
├── choose2faMethod.php        # 2FA method selection page
├── 2faSetup.php               # TOTP setup (Google Authenticator)
├── 2faEmailSetup.php          # Email-based 2FA setup
├── verify2fa.php              # TOTP verification
├── verify2faEmail.php         # Email PIN verification
├── verifyEmail.php            # Email address verification handler
│
├── requestEval.php            # Evaluation request form (auth required)
├── valuation.php              # Evaluation submission handler
├── adminPage.php              # Admin: list all evaluations (admin role only)
│
├── pwordStrength.js           # Real-time password strength indicator
├── pwordMatch.js              # Password confirmation match checker
├── showPassword.js            # Show/hide password toggle
├── styles.css                 # Application stylesheet
│
├── uploads/                   # Uploaded antique photos
├── vendor/                    # Composer dependencies (PHPMailer, TOTP libraries)
├── composer.json
└── composer.lock
```

---

## Academic Context

This project was submitted as **Part B** of the *Introduction to Computer Security (G6077)* coursework at the **University of Sussex**, worth 50% of the module mark. It demonstrates practical implementation of security principles including authentication, access control, vulnerability mitigation, and secure database design.

Part A of the same coursework covered Linux access control (file permissions, user/group management, and umask configuration).

---

> ⚠️ **Disclaimer:** This application was built for academic purposes. Any test credentials used during development are throwaway and not reused elsewhere. All personal data in report screenshots was anonymised prior to submission.
