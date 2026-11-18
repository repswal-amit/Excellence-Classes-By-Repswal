# EXCELLENCE CLASSES BY REPSWAL
### Complete Internship & Certification Management System
**Tagline:** *Learn. Develop. Grow.*

---

## 🏛️ Business & Legal Information
* **Organization Name:** Excellence Classes By Repswal
* **Legal Structure:** Sole Proprietorship
* **Proprietor:** Amit Repswal
* **Business Category:** Educational Services
* **Enterprise Category:** Micro Enterprise
* **Location:** Nawalgarh, Jhunjhunu, Rajasthan, India
* **PIN Code:** 333308

> **Important Note:** This is an authentic proprietorship educational and IT training institute, not a Private Limited Company. All credentials and certificates are officially authorized by the Proprietor, Amit Repswal.

---

## 🚀 Key Features

### 1. Public Website
* Modern Glassmorphism UI with responsive Bootstrap 5 and Bootstrap Icons
* Homepage with Hero Section, About, Active Training Courses, Internship Programs, Why Choose Us, Technologies, FAQ, and Contact form
* Dynamic Course Directory (`courses.php`) with skill level filtering
* Independent Internship Programs Directory (`internships.php`) with Open/Closed badges and detail modals
* Integrated Application Portal (`apply.php`) supporting direct intern registration and pending review
* Contact page (`contact.php`) storing messages directly in MySQL
* Instant Online & QR Certificate Verification (`verify-certificate.php`)

### 2. Intern Portal
* Intern Registration & Email/Password Authentication with rate limiting
* Dashboard with real-time stats: Track, Tasks Completed, Overall Progress Percentage, Certificate Status
* My Applications (`intern/applications.php`) tracking approval decisions and remarks
* My Internship Track (`intern/internship.php`) showing full curriculum and competencies
* My Tasks (`intern/tasks.php`) with milestone submission form (GitHub repo link + notes)
* Submissions & Mentor Feedback history (`intern/submissions.php`)
* Official Documents (`intern/documents.php`) with Offer Letters and Joining Letters printable view
* Certificate of Internship (`intern/certificates.php`) with Landscape A4 print-ready view and live QR verification
* Profile and Password Management (`intern/profile.php`)

### 3. Admin / Proprietor Portal
* Secure Admin Login (`admin/login.php`) with role-based access control
* Dashboard with live metrics, quick actions, and Chart.js analytics
* Internship Applications review & approval with automatic enrollment creation
* Interns Directory (`admin/interns.php`) with status suspension and profile inspection
* Program Management (`admin/programs.php`) with Open/Closed toggle and full CRUD
* Course Management (`admin/courses.php`) with full CRUD
* Task Assignment (`admin/tasks.php`) with individual and bulk program batch assignment
* Task Evaluation (`admin/submissions.php`) with automatic progress recalculation
* Progress Standing & Completion Evaluation (`admin/progress.php`)
* Document Generation (`admin/documents.php`) for Offer & Joining Letters
* Certificate Issuance & Revocation (`admin/certificates.php`) restricted to authorized Proprietor
* Contact Messages management (`admin/messages.php`)
* Website Settings (`admin/settings.php`) for customized branding and content
* Immutable Audit Trail (`admin/audit-logs.php`)

---

## 🛠️ Technology Stack
* **Frontend:** HTML5, CSS3 (Vanilla CSS + Glassmorphism), JavaScript (ES6+), Bootstrap 5.3, Bootstrap Icons 1.11, Chart.js 4.4
* **Backend:** Core PHP (8.0+ recommended), PDO (PHP Data Objects), PHP Secure Sessions
* **Database:** MySQL 5.7+ / MariaDB 10.4+ (utf8mb4_unicode_ci)
* **Libraries:** Composer (dompdf, phpqrcode support)
* **Web Server:** Apache (mod_rewrite enabled via `.htaccess`)

---

## 💻 XAMPP Setup & Installation Guide

### Step 1: Copy Project to XAMPP htdocs
Copy the project folder into your XAMPP `htdocs` directory:
```
C:\xampp\htdocs\excellence-classes\
```
*(or your preferred folder name)*

### Step 2: Start Apache and MySQL
1. Launch the **XAMPP Control Panel**.
2. Click **Start** next to **Apache**.
3. Click **Start** next to **MySQL**.

### Step 3: Run the Automated Setup (Web or CLI)

You have two convenient setup methods:

#### Option A: Web Installation Wizard (Recommended)
1. Navigate in your browser to:
   ```
   http://localhost/excellence-classes/install.php
   ```
2. The wizard checks all server prerequisites, allows you to configure your MySQL host, port, username, and password, automatically creates `excellence_classes`, executes `database.sql`, creates the Proprietor Admin account, generates `.env`, and locks the installation.

#### Option B: Automated CLI Setup
Run from terminal in the project directory:
```bash
# If your MySQL has no password:
php setup.php

# If your MySQL has a password:
php setup.php --pass=YOUR_MYSQL_PASSWORD

# If your MySQL runs on a custom port (e.g. 3307):
php setup.php --port=3307 --pass=YOUR_MYSQL_PASSWORD
```

#### Option C: Manual phpMyAdmin Import
1. Open phpMyAdmin: `http://localhost/phpmyadmin/`
2. Create database `excellence_classes` (Collation: `utf8mb4_unicode_ci`).
3. Import `database.sql`.
4. Copy `.env.example` to `.env` and fill in your DB credentials.

### Step 5: Install Composer Dependencies (Optional)
If you wish to use local PDF and QR libraries via Composer:
```bash
composer install
```

### Step 6: Access the Website
* **Public Website:** `http://localhost/excellence-classes/`
* **Intern Registration:** `http://localhost/excellence-classes/register.php`
* **Intern Login:** `http://localhost/excellence-classes/login.php`
* **Admin Login:** `http://localhost/excellence-classes/admin/login.php`
* **Certificate Verification:** `http://localhost/excellence-classes/verify-certificate.php`

---

## 🔐 Default Credentials

### Default Admin (Proprietor)
* **Email:** `admin@excellenceclasses.com`
* **Default Password:** `Admin@123`
* **Role:** Proprietor (Amit Repswal)
* **Security Notice:** Change this default password immediately after logging in via the Admin Settings.

---

## 🔄 End-to-End Workflow Testing Guide

1. **Student Registration & Application:**
   * Visit `/register.php` or `/apply.php`.
   * Fill out student details (Name, College, Degree, Phone) and choose an open program (e.g. *Web Development Internship*).
   * Submit the form. Account is created and application is saved as `Pending`.

2. **Admin Application Review:**
   * Log into `/admin/login.php` with `admin@excellenceclasses.com` / `Admin@123`.
   * Go to **Applications**, click **Review** on the pending application.
   * Set status to `Approved`, specify Start Date and End Date, and save.
   * An active enrollment, progress record, and draft Offer Letter are automatically initialized!

3. **Task Assignment & Submission:**
   * Go to **Tasks Assigned** in Admin.
   * Click **Assign New Task**, enter milestone title, description, deadline, and assign to the intern.
   * Log into the **Intern Portal** (`/login.php`).
   * View the task under **My Tasks**, click **Submit**, enter GitHub repo link and notes, and submit.

4. **Mentor Review & Progress Update:**
   * In Admin, go to **Submissions**, review the GitHub link, provide mentor feedback, and click `Approved`.
   * Intern's completion progress percentage updates automatically in real-time.

5. **Completion Approval & Certificate Issuance:**
   * In Admin, navigate to **Intern Progress & Evaluation**.
   * Give a performance rating (e.g., `4.8`), check **Approve Official Internship Completion**, and save.
   * Navigate to **Certificates**, click **Issue New Certificate**, select the completed intern, and confirm issuance.

6. **Certificate Verification & QR Check:**
   * Intern views and prints their official landscape certificate under **My Certificates**.
   * Scan the dynamic QR code or visit `/verify-certificate.php?id=CERTIFICATE_ID`.
   * Real-time verification confirms the authentic record, intern name, program, and proprietor seal.

---

## 🛡️ Security Architecture
* **Prepared Statements:** 100% of SQL queries utilize PDO prepared statements with bound parameters to eliminate SQL Injection risks.
* **Password Encryption:** Passwords are hashed with `password_hash()` utilizing strong bcrypt algorithms.
* **CSRF Protection:** Cryptographically secure tokens (`verifyCsrfToken()`) guard every POST request.
* **Session Security:** Strict cookies (`httponly`, `samesite=Strict`, `use_only_cookies`) with automatic periodic session regeneration.
* **Rate Limiting:** Brute force mitigation on login endpoints locking out after 5 consecutive failed attempts.
* **Directory Traversal Protection:** Sensitive folders (`storage/`) are protected by Apache `.htaccess` deny rules.

---

## 📜 License & Copyright
&copy; 2026 **Excellence Classes By Repswal**. All rights reserved.  
Founded and operated as a Sole Proprietorship by **Amit Repswal**, Nawalgarh, Rajasthan, India.
