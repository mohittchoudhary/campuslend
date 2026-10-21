# 🤝 CampusLend – Emergency Campus Borrowing Platform

> **Exclusive Peer-to-Peer Emergency Lending & Borrowing Network for Uka Tarsadia University (UTU) Students**  
> *Domain Locked: `@utu.ac.in`*

---

## 📖 1. Project Overview

**CampusLend** is a production-grade emergency borrowing web application built exclusively for university students facing urgent equipment shortages during exams, lab practicals, or daily campus life. 

Instead of panicking when a calculator battery dies in an exam hall or when an apron is forgotten before an organic chemistry lab, students can broadcast an **Emergency Request** or browse nearby peer equipment listings in their exact building block or floor within seconds.

### 🌟 Key Objectives
- **Zero Exam Panics:** Immediate peer discovery for scientific calculators (FX-991EX), engineering mini drafters, lab coats, and 65W chargers.
- **Accountability & Trust:** Two-way handover and return confirmation with automated trust scores and verified campus domain locks (`@utu.ac.in`).
- **AI-Powered Proximity:** Microservice matches nearby lenders based on physical campus block, equipment category, and trust ratings.

---

## ✨ 2. Key Features (Phase Breakdown)

### 🔐 Phase 1 – Foundation & Authentication
- **Campus Domain Restriction:** Strict registration validation allowing only `@utu.ac.in` email addresses.
- **6-Digit Email OTP:** Secure activation OTP (10-minute validity, max 5 failed attempts rate-limited).
- **Session Hardening:** Bcrypt password hashing (`cost=10`), `session_regenerate_id()` on privilege changes, HTTP-only & SameSite cookies.
- **Remember-Me Auto Login:** SHA-256 hashed persistent tokens for seamless mobile experience.
- **Password Reset Flow:** Cryptographic token reset via simulated or SMTP campus email.

### ⚡ Phase 2 – Requests, Listings, Offers & Borrow Flow
- **Emergency SOS Request Mode:** 1-click critical requests with countdown timers.
- **Live Feed (`/feed`):** Real-time filtering by category, campus building block, and urgency level (Critical, High, Normal).
- **Lend Catalog (`/items`):** Students list spare items with photos, condition, and pickup location.
- **Secure File Uploads:** Whitelisted MIME validation (`finfo`), 2MB limit, random filenames, script execution disabled via upload `.htaccess`.
- **Transaction State Machine:** Strict transitions (`requested` → `offered` → `accepted` → `handed_over` → `returned`). Invalid state jumps (e.g. return before handover) and self-transactions are strictly prevented.

### 💬 Phase 3 – Chat, Notifications, Ratings & Trust Score
- **In-App Direct Chat:** Real-time polling chat (5-10s interval) scoped strictly to the two transaction participants (HTTP 403 for third parties).
- **Notification Center:** Live bell counter, unread indicators, mark-as-read actions, and email notifications for major events.
- **Trust Score Formula:** Continuous rating calculation:
  $$\text{Trust Score} = 0.70 \times \text{Avg Stars} + 0.20 \times \text{On-Time Return Rate} + 0.10 \times \text{Volume Bonus}$$
- **Automated Campus Badges:** Automatic awards for *"Quick Helper"*, *"Exam Saviour"*, *"Reliable Lender"*, and *"Campus Hero"*.
- **Safety & Reporting:** Abuse reports with administrative review, user block/unblock enforcement.

### 🎨 Phase 4 – Design System & Responsive PWA
- **Glassmorphism CSS3:** Frosted glass panels (`backdrop-filter`), CSS variables, and light/dark theme toggle saved in `localStorage`.
- **Micro-Animations:** Pulsing glow on Critical requests, button ripple effects, smooth transitions, skeleton loaders, and `prefers-reduced-motion` compliance.
- **Progressive Web App (PWA):** `manifest.json`, service worker (`sw.js`) with cache-first static asset delivery, installable on mobile devices.
- **Responsive Navigation:** Desktop navbar and mobile bottom navigation bar with floating SOS emergency trigger.

### 🛡️ Phase 5 – Admin Command Center & Python Smart Match AI
- **Admin Dashboard:** Real-time metrics with Chart.js (Requests per Day, High-Demand Categories, Success Rate).
- **Student Moderation:** Account suspension (ban/unban) and abuse report adjudication.
- **Category CRUD:** Dynamic creation, modification, and deletion of equipment categories.
- **Exam Season Alert Banner:** Dynamic toggle with custom message displayed university-wide.
- **Python Smart Match Microservice (`python-service/`):** Flask AI service ranking lenders by 4 weighted factors:
  - Equipment Category Alignment (+40 pts)
  - Campus Building Proximity (+30 pts)
  - Peer Trust Score (+20 pts)
  - Keyword Similarity (+10 pts)
- **Resilient Fallback:** PHP automatically falls back to native SQL proximity ranking if the Python microservice is offline.

### 🧪 Phase 6 – Hardening, Master Verification & Documentation
- **Master Test Runner (`tests/run_all.php`):** Executes all test suites and prints unified reports.
- **Hardened Security:** SQL injection protection (PDO prepared statements only), XSS escaping (`e()`), CSRF tokens on all POST/AJAX requests.

---

## 🛠️ 3. Technology Stack

| Layer | Technologies Used |
| :--- | :--- |
| **Backend** | PHP 8.3+ (Object-Oriented, MVC architecture, custom Router, Middleware pipeline) |
| **Database** | MySQL 8.0+ / SQLite 3 (Hybrid PDO abstraction with automatic zero-configuration fallback) |
| **Frontend** | Semantic HTML5, Vanilla CSS3 (Custom Glassmorphism, CSS Tokens), Vanilla JS (ES6+) |
| **Libraries** | Chart.js (via CDN for visual metrics), Lucide Icons, GSAP (animations) |
| **Microservice** | Python 3.10+ with Flask (RESTful Smart Match AI & Overdue Reminder Service) |
| **PWA** | Web App Manifest, Service Worker (`sw.js`) |
| **Mailing** | PHPMailer / Simulated campus development mailer |

---

## 📂 4. Folder Structure Explained

```
campuslend/
├── app/
│   ├── config/              # Environment loader & database connection
│   │   ├── Database.php     # Hybrid MySQL/SQLite PDO manager
│   │   └── Env.php          # .env file parser
│   ├── controllers/         # Application MVC Controllers
│   │   ├── AdminController.php         # Admin Command Center, Moderation, Categories
│   │   ├── AuthController.php          # Register, OTP verify, Login, Password Reset
│   │   ├── ChatController.php          # Per-transaction real-time AJAX chat
│   │   ├── HomeController.php          # Landing page & How It Works
│   │   ├── ItemController.php          # Catalog, listings, pause/unpause
│   │   ├── LeaderboardController.php   # Top Helpers & Hall of Fame
│   │   ├── NotificationController.php  # Bell badge & notification center
│   │   ├── RatingController.php        # 1-5 star reviews & trust recalculation
│   │   ├── RequestController.php       # Live feed, SOS requests, Smart Match
│   │   ├── TransactionController.php   # Handover/Return state machine & activity
│   │   └── UserController.php          # Profile, avatar, password, blocks, reports
│   ├── helpers/             # Global utility functions & custom Router
│   │   ├── functions.php    # e(), csrf_token(), url(), flash(), auth helpers
│   │   ├── Mailer.php       # Email dispatcher (SMTP & dev file logger)
│   │   ├── RateLimiter.php  # Brute force protection for OTP and login
│   │   └── Router.php       # Fast regex router with middleware pipeline
│   ├── middleware/          # Route protection middlewares
│   │   ├── AdminMiddleware.php         # Enforces HTTP 403 on non-admin students
│   │   └── AuthMiddleware.php          # Auth, Guest, Verified, Csrf middlewares
│   ├── models/              # Active-Record style PDO Models
│   │   ├── AdminLog.php, Badge.php, BorrowRequest.php, BorrowTransaction.php,
│   │   ├── Category.php, Item.php, Message.php, Model.php, Notification.php,
│   │   ├── Offer.php, OtpCode.php, Rating.php, Report.php, Setting.php,
│   │   └── User.php, UserBlock.php
│   ├── services/
│   │   └── SmartMatcher.php # Client connecting PHP with Python AI microservice
│   └── views/               # PHP UI Templates
│       ├── admin/           # Dashboard, users, categories, reports, logs
│       ├── layouts/         # main.php (HTML shell, banner, SOS button, modals)
│       ├── pages/           # Landing, Feed, Profile, Chat, Leaderboard, 404
│       └── partials/        # Navbar, Footer, Mobile bottom navigation
├── database/
│   ├── campuslend.sqlite    # Portable zero-config database
│   ├── schema.sql           # Complete MySQL 8 schema (16 tables, constraints, indexes)
│   └── seed.sql             # 20 UTU sample students, items, categories, admin
├── public/
│   ├── assets/              # CSS stylesheets, JS scripts, PWA icons
│   │   ├── css/main.css     # Glassmorphism tokens, dark/light styles, animations
│   │   ├── js/main.js       # Countdowns, live chat polling, toast notifications
│   │   └── img/             # PWA app icons (icon-192.png, icon-512.png)
│   ├── uploads/             # Student item photos with .htaccess security
│   ├── index.php            # Front Controller entry point
│   ├── manifest.json        # PWA web manifest
│   └── sw.js                # Service Worker
├── python-service/
│   ├── app.py               # Flask microservice (POST /match, POST /remind-overdue)
│   └── requirements.txt     # Python dependencies
└── tests/
    ├── run_all.php          # Master automated test runner
    ├── test_phase1.php      # Phase 1 verification suite
    ├── test_phase2.php      # Phase 2 verification suite
    ├── test_phase3.php      # Phase 3 verification suite
    └── test_phase5.php      # Phase 5 verification suite
```

---

## 🚀 5. Step-by-Step Setup Instructions

### Prerequisites
- **PHP 8.2+** with extensions enabled: `pdo_mysql`, `pdo_sqlite`, `curl`, `fileinfo`, `gd`, `mbstring`.
- **MySQL / MariaDB** (via XAMPP, WAMP, or standalone) *OR* built-in **SQLite** (automatic zero-config fallback).
- **Python 3.10+** (for Smart Match microservice).

---

### Step 1: Clone or Open Project
Navigate to the project root directory:
```bash
cd php_project/campuslend
```

---

### Step 2: Environment Configuration (`.env`)
Verify or copy `.env.example` to `.env`:
```ini
APP_NAME=CampusLend
APP_ENV=development
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

# Campus Domain Configuration
CAMPUS_NAME="Uka Tarsadia University"
CAMPUS_EMAIL_DOMAIN=utu.ac.in

# Database Configuration (MySQL / XAMPP)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=campuslend
DB_USERNAME=root
DB_PASSWORD=

# SQLite Fallback Location (Automatic if MySQL is offline)
DB_SQLITE_PATH=database/campuslend.sqlite

# Python Smart Match Microservice
SMART_MATCH_SERVICE_URL=http://127.0.0.1:5000/match
```

---

### Step 3: Database Setup

#### Option A: MySQL (XAMPP / WAMP)
1. Start Apache & MySQL in XAMPP.
2. Open phpMyAdmin (`http://localhost/phpmyadmin`) or MySQL CLI:
   ```sql
   CREATE DATABASE campuslend CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Import the schema and seed data:
   ```bash
   mysql -u root -p campuslend < database/schema.sql
   mysql -u root -p campuslend < database/seed.sql
   ```

#### Option B: Zero-Config SQLite (Default Portable Fallback)
If MySQL is not running, the application automatically boots from `database/campuslend.sqlite`, which is pre-seeded with all categories, sample students, and items!

---

### Step 4: Start Python Smart Match Microservice
Open a terminal and start the Flask microservice:
```bash
cd campuslend/python-service
pip install -r requirements.txt
python app.py
```
*Service will start listening on `http://127.0.0.1:5000`.*

---

### Step 5: Start PHP Web Application
In a second terminal, launch the PHP development server:
```bash
cd campuslend
php -S 127.0.0.1:8000 -t public
```

---

### Step 6: Access CampusLend in Your Browser
Open your browser and navigate to:
👉 **`http://127.0.0.1:8000`**

---

## 🔑 6. Demo Credentials

### 🛡️ Campus Administrator
| Role | Email | Password | Access |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@utu.ac.in` | `Admin@123` | Full Command Center, Student Moderation, Category CRUD, Audit Logs |

### 🎓 Sample Students (Uka Tarsadia University)
| Student Name | Email | Password | Department | Year |
| :--- | :--- | :--- | :--- | :--- |
| **Aarav Patel** | `aarav.patel@utu.ac.in` | `Student@123` | Computer Engineering | 3rd Year |
| **Diya Shah** | `diya.shah@utu.ac.in` | `Student@123` | Information Technology | 3rd Year |
| **Rohan Desai** | `rohan.desai@utu.ac.in` | `Student@123` | Mechanical Engineering | 4th Year |
| **Ananya Joshi** | `ananya.joshi@utu.ac.in` | `Student@123` | Pharmacy | 2nd Year |

---

## 🔄 7. The Borrow Flow in Simple Words

```
[1. NEED ITEM] ──────────► Student posts Emergency Request ("Need FX-991EX in Block B, Exam Hall 101")
      │
[2. MATCH & NOTIFY] ─────► Python Smart Match ranks nearby lenders; peers receive alert
      │
[3. LEND OFFER] ─────────► Peer taps "I Can Lend This" with meeting note
      │
[4. ACCEPT & CHAT] ──────► Borrower accepts offer; two-way private transaction chat opens
      │
[5. HANDOVER] ───────────► Students meet; Borrower taps "Confirm Handover"
      │
[6. SAFE RETURN] ────────► Item returned; Lender taps "Confirm Return"
      │
[7. TRUST & BADGES] ─────► Both submit 1-5 star review; Trust scores & badges recalculate
```

---

## 🧪 8. Automated Acceptance Verification

Run the unified master verification suite at any time:
```bash
php tests/run_all.php
```

### Verified Acceptance Suites:
- `tests/test_phase1.php`: **8/8 PASS** (Domain lock, OTP validation, session security)
- `tests/test_phase2.php`: **9/9 PASS** (Request creation, photo upload, state machine barrier enforcement)
- `tests/test_phase3.php`: **8/8 PASS** (Chat access control, unread notifications, ratings, trust scores, blocks)
- `tests/test_phase5.php`: **6/6 PASS** (Admin RBAC 403, category CRUD, student ban/unban, Python Smart Match & fallback)

---

## 🎓 9. Viva Q&A (15 Examiner Questions & Answers)

### Q1: Why didn't you use a heavy framework like Laravel or CodeIgniter?
**Answer:** A custom lightweight MVC framework demonstrates a deeper understanding of software architecture, the HTTP request lifecycle, Front Controller design pattern, and PSR-4 autoloading. It eliminates framework overhead, boots in under 10 milliseconds, and allows complete control over security headers and session management without hidden blackbox abstractions.

### Q2: How does the application prevent students from non-campus domains from signing up?
**Answer:** In `app/models/User.php`, the `isCampusEmail()` helper inspects the email domain against `CAMPUS_EMAIL_DOMAIN` configured in `.env` (`utu.ac.in`). Any registration attempt with public domains like `@gmail.com` or `@outlook.com` is rejected before database insertion.

### Q3: How is the transaction state machine designed, and how do you prevent invalid state jumps?
**Answer:** In `BorrowTransaction.php`, valid transitions are strictly mapped: `requested` → `offered` → `accepted` → `handed_over` → `returned`. If a user attempts to confirm a return before handover occurs, or attempts to accept an already completed transaction, an exception is raised and the action is blocked with an HTTP error.

### Q4: How does the Python microservice match lenders with borrowers?
**Answer:** The Flask microservice exposes a `POST /match` endpoint that calculates a 100-point affinity score based on 4 weighted parameters: 40 points for exact equipment category match, 30 points for identical campus building block proximity, up to 20 points scaled from the lender's peer trust score, and 10 points for keyword token overlap in the item title.

### Q5: What happens if the Python microservice crashes or is offline?
**Answer:** In `app/services/SmartMatcher.php`, a 2-second timeout is configured with cURL. If the Python microservice fails to respond with HTTP 200, the service silently and gracefully switches to a native pure-PHP/SQL proximity algorithm (`source: php_fallback`), ensuring the student's request page never breaks.

### Q6: How do you prevent Cross-Site Scripting (XSS) attacks?
**Answer:** All dynamic variables rendered in views are sanitized through the global `e()` helper function, which applies `htmlspecialchars($string, ENT_QUOTES, 'UTF-8')`. Additionally, template outputs do not use raw `echo` for user-supplied data.

### Q7: How does the platform protect against Cross-Site Request Forgery (CSRF)?
**Answer:** On session initialization, a cryptographically secure 64-character token is generated via `bin2hex(random_bytes(32))`. Every HTML form includes `<?= csrf_field() ?>`, and all state-changing `POST` requests are validated by `CsrfMiddleware` or `verify_csrf()` using `hash_equals()`.

### Q8: What security measures prevent malicious file uploads when listing items?
**Answer:** In `ItemController.php`, uploaded files are verified using PHP's `finfo` (file information) extension to inspect the file's actual binary signature rather than relying on the client's file extension. Permitted types are strictly limited to `image/jpeg`, `image/png`, and `image/webp`. Files are stored with cryptographically generated random hex names (`bin2hex(random_bytes(16))`), and the `uploads/` folder contains an `.htaccess` file forbidding PHP script execution (`php_flag engine off`).

### Q9: How is the student Trust Score calculated?
**Answer:** The Trust Score uses a weighted continuous formula: 70% is derived from the student's historical 1-to-5 star rating average, 20% from on-time return compliance, and 10% from overall lending volume. It automatically recalculates immediately after each completed return or rating submission.

### Q10: How is chat privacy and access control enforced?
**Answer:** The chat endpoint `ChatController::getMessages` checks that `auth_user('id')` is either the `borrower_id` or `lender_id` associated with that specific transaction record. Any third party attempting to query messages for a transaction they do not belong to is immediately rejected with an HTTP 403 Forbidden response.

### Q11: How do you protect against brute-force attacks on the 6-digit OTP?
**Answer:** In `RateLimiter.php` and `OtpCode.php`, every verification attempt increments an `attempts` counter. If 5 incorrect attempts occur, the OTP is automatically invalidated. Furthermore, OTP codes expire after 10 minutes.

### Q12: How are non-admin students prevented from accessing the Admin Command Center?
**Answer:** In `app/middleware/AdminMiddleware.php`, the session is checked for `is_admin() === true`. If a student attempts to load `/admin/dashboard` or perform administrative moderation, the server halts execution and returns an HTTP 403 Forbidden status code.

### Q13: What makes CampusLend a Progressive Web App (PWA)?
**Answer:** The application includes a `manifest.json` declaring name, theme colors (`#4f46e5`), display mode (`standalone`), and app icons (192x192 and 512x512). The service worker `sw.js` registers lifecycle events to cache essential stylesheets and scripts, allowing the app to be installed onto student mobile home screens.

### Q14: How does CampusLend support zero-configuration portability?
**Answer:** `Database.php` implements a hybrid driver: if MySQL is offline (e.g. during an offline classroom viva or demo), it automatically and seamlessly falls back to `database/campuslend.sqlite`. The database models use portable SQL compatible with both database engines.

### Q15: How are abusive users handled?
**Answer:** Students can file abuse reports with specific categories (e.g., late return, item damage, harassment) from within active transactions. Additionally, students can block peers, preventing them from sending messages or making offers. Administrators can review reports in the Command Center and suspend accounts with one click.

---

## 🔧 10. Troubleshooting

| Issue | Cause | Resolution |
| :--- | :--- | :--- |
| **"Access Denied (403)" on Admin** | Logged in as regular student | Log out and sign in with `admin@utu.ac.in` / `Admin@123`. |
| **MySQL Connection Refused** | MySQL is not started in XAMPP | Start MySQL in XAMPP control panel, or rely on SQLite automatic fallback. |
| **Python Service Warning in UI** | Python microservice not running | Run `python campuslend/python-service/app.py` in a separate terminal. (PHP will still work in fallback mode). |
| **CSS styling looks missing** | Running server with incorrect document root | Start PHP server strictly with `-t public`: `php -S 127.0.0.1:8000 -t public`. |

---

## 📄 License
Academic project built for **Uka Tarsadia University (UTU)**. Distributed for educational and peer collaboration purposes.
