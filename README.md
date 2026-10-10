# Branded Email Notification Service

A PHP/MySQL web app where an admin sends institution-branded emails (Welcome, Exam Result, Exam Disqualification Notice) to students. Each email uses the student's college name, logo, brand colour and contact details. Every send is recorded in a log with status and automatic retry.

Author:Sheik Muksena N

## Features
- Admin login and logout
- Colleges: add and edit name, sender name, brand colour, contact details and logo (with live preview)
- Students linked to a college
- Email types: Welcome, Exam Result, Exam Disqualification Notice
- Preview page that shows the branded email before sending
- Send via SMTP (Mailtrap sandbox)
- Email log with status (pending / sent / failed), attempts and error message
- Automatic retry (up to 3 attempts per send) plus a manual Retry button for failed emails
- Neutral default branding when a student has no college

## Tech stack
PHP 8.2, MySQL/MariaDB, XAMPP (Apache), PHPMailer (bundled in `includes/lib/PHPMailer`), Mailtrap sandbox SMTP.

## Setup
1. Copy the project folder to `C:\xampp\htdocs\branded-email`.
2. Start **Apache** and **MySQL** in XAMPP.
3. In phpMyAdmin, create a database named `branded_email` and import `schema.sql`.
4. Check the database settings in `config/db.php`.
5. Copy `config/mail.example.php` to `config/mail.php` and enter your Mailtrap SMTP username and password
   (Mailtrap > Email Testing > My Sandbox > SMTP credentials).
6. Open `http://localhost/branded-email/admin/login.php`.

Admin login: **admin** / **Admin@123**

## Design decisions
- **Single send function**: `sendMail()` in `includes/lib/mailer.php` wraps PHPMailer; `sendAndLog()` writes every attempt to the `email_log` table.
- **Log first, then send**: a row is saved as `pending` before sending, so failures are never lost and can be retried from the saved email body.
- **Template separation**: `includes/lib/template.php` builds the header/body/footer layout once; each email type only supplies its own content.
- **Neutral fallback**: if a college is missing, a plain default design is used.
- **Bundled PHPMailer**: copied into the project so no Composer is needed.

## Security
- CSRF token on every form (`includes/csrf.php`)
- Prepared statements (PDO) for all database queries
- Output escaped with `htmlspecialchars` in pages and email templates
- Admin pages protected by a login check
- Admin password stored as a hash
- Mail credentials kept out of Git (`config/mail.php` is in `.gitignore`; use `config/mail.example.php`)

## AI tools disclosure
I used Claude (Anthropic) as a learning and debugging assistant. It explained the approach and gave me code snippets for the email-sending and retry features, which I integrated into my own project.

**My work:** I set up XAMPP and the database, configured Mailtrap SMTP, added PHPMailer, wired sending and logging into `send_email.php`, created `email_log.php`, and tested every flow (preview, send, a deliberate SMTP failure, and manual Retry). I also debugged the errors that came up.

**AI help:** explaining PHPMailer setup and the retry/log design, suggesting fixes for errors, and drafting this README. I reviewed and tested all code before submitting.

## Project structure

```
admin/       login, colleges, students, send_email, email_log
config/      db.php, mail.example.php
includes/    auth, csrf, helpers, header, footer, lib (template, mailer, PHPMailer)
assets/      css, uploaded logos
schema.sql   database schema and sample data
```