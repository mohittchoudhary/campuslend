<?php
/**
 * CampusLend PDO Database Connection Manager
 * Supports MySQL / MariaDB (production & XAMPP/WAMP) and SQLite (portable fallback)
 */

namespace App\Config;

use PDO;
use PDOException;
use Exception;

class Database
{
    private static ?PDO $instance = null;
    private static string $activeDriver = 'mysql';

    public static function getConnection(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $config = require __DIR__ . '/config.php';
        $dbConfig = $config['db'];
        $connectionType = strtolower($dbConfig['connection']);

        if ($connectionType === 'sqlite') {
            self::$instance = self::connectSqlite($dbConfig['sqlite_path']);
            self::$activeDriver = 'sqlite';
            return self::$instance;
        }

        // Attempt MySQL connection
        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                $dbConfig['host'],
                $dbConfig['port'],
                $dbConfig['database'],
                $dbConfig['charset']
            );

            $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ]);

            self::$instance = $pdo;
            self::$activeDriver = 'mysql';
            return self::$instance;
        } catch (PDOException $e) {
            // If MySQL is not running on local machine, smoothly fall back to SQLite for instant local execution
            if (isset($dbConfig['sqlite_path'])) {
                error_log("CampusLend Notice: MySQL connection failed (" . $e->getMessage() . "). Falling back to SQLite database.");
                self::$instance = self::connectSqlite($dbConfig['sqlite_path']);
                self::$activeDriver = 'sqlite';
                return self::$instance;
            }

            throw new Exception("Database Connection Error: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    private static function connectSqlite(string $sqlitePath): PDO
    {
        $dir = dirname($sqlitePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $isNew = !file_exists($sqlitePath) || filesize($sqlitePath) === 0;

        $pdo = new PDO("sqlite:" . $sqlitePath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $pdo->exec("PRAGMA foreign_keys = ON;");
        $pdo->exec("PRAGMA journal_mode = WAL;");

        if ($isNew) {
            self::initSqliteTables($pdo);
        }

        return $pdo;
    }

    public static function getActiveDriver(): string
    {
        return self::$activeDriver;
    }

    /**
     * Initializes SQLite tables and seeds starter dataset if SQLite is used
     */
    public static function initSqliteTables(PDO $pdo): void
    {
        $schemaSql = <<<SQL
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            enrollment_no TEXT NOT NULL UNIQUE,
            department TEXT NOT NULL,
            year_of_study TEXT NOT NULL DEFAULT '1st Year',
            phone TEXT NOT NULL,
            avatar TEXT DEFAULT 'default_avatar.svg',
            role TEXT NOT NULL DEFAULT 'student',
            is_verified INTEGER NOT NULL DEFAULT 0,
            is_banned INTEGER NOT NULL DEFAULT 0,
            trust_score REAL NOT NULL DEFAULT 5.00,
            total_ratings INTEGER NOT NULL DEFAULT 0,
            remember_token TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS otp_codes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            code TEXT NOT NULL,
            purpose TEXT NOT NULL DEFAULT 'registration',
            attempts INTEGER NOT NULL DEFAULT 0,
            expires_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS password_resets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            token TEXT NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            used INTEGER NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            slug TEXT NOT NULL UNIQUE,
            icon TEXT NOT NULL DEFAULT 'box',
            description TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            category_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            description TEXT NULL,
            photo TEXT NULL,
            item_condition TEXT NOT NULL DEFAULT 'Good',
            pickup_location TEXT NOT NULL,
            max_duration_hours INTEGER NOT NULL DEFAULT 24,
            is_available INTEGER NOT NULL DEFAULT 1,
            is_paused INTEGER NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
        );

        CREATE TABLE IF NOT EXISTS requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            category_id INTEGER NOT NULL,
            item_name TEXT NOT NULL,
            urgency TEXT NOT NULL DEFAULT 'Normal',
            campus_block TEXT NOT NULL,
            room_location TEXT NOT NULL,
            needed_by DATETIME NOT NULL,
            note TEXT NULL,
            status TEXT NOT NULL DEFAULT 'open',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
        );

        CREATE TABLE IF NOT EXISTS offers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            request_id INTEGER NOT NULL,
            lender_id INTEGER NOT NULL,
            item_id INTEGER NULL,
            message TEXT NULL,
            status TEXT NOT NULL DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE,
            FOREIGN KEY (lender_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE SET NULL
        );

        CREATE TABLE IF NOT EXISTS borrow_transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            request_id INTEGER NOT NULL,
            offer_id INTEGER NOT NULL,
            borrower_id INTEGER NOT NULL,
            lender_id INTEGER NOT NULL,
            item_id INTEGER NULL,
            status TEXT NOT NULL DEFAULT 'accepted',
            handover_confirmed_borrower INTEGER NOT NULL DEFAULT 0,
            handover_confirmed_lender INTEGER NOT NULL DEFAULT 0,
            return_confirmed_borrower INTEGER NOT NULL DEFAULT 0,
            return_confirmed_lender INTEGER NOT NULL DEFAULT 0,
            handover_time DATETIME NULL,
            due_time DATETIME NOT NULL,
            returned_time DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE,
            FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE CASCADE,
            FOREIGN KEY (borrower_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (lender_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE SET NULL
        );

        CREATE TABLE IF NOT EXISTS messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            transaction_id INTEGER NULL,
            request_id INTEGER NULL,
            sender_id INTEGER NOT NULL,
            receiver_id INTEGER NOT NULL,
            message TEXT NOT NULL,
            is_read INTEGER NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (transaction_id) REFERENCES borrow_transactions(id) ON DELETE CASCADE,
            FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            message TEXT NOT NULL,
            type TEXT NOT NULL,
            link TEXT NULL,
            is_read INTEGER NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS ratings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            transaction_id INTEGER NOT NULL,
            reviewer_id INTEGER NOT NULL,
            reviewed_user_id INTEGER NOT NULL,
            role_in_tx TEXT NOT NULL,
            stars INTEGER NOT NULL CHECK (stars BETWEEN 1 AND 5),
            comment TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (transaction_id, reviewer_id),
            FOREIGN KEY (transaction_id) REFERENCES borrow_transactions(id) ON DELETE CASCADE,
            FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (reviewed_user_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS reports (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            reporter_id INTEGER NOT NULL,
            reported_user_id INTEGER NOT NULL,
            reason TEXT NOT NULL,
            details TEXT NULL,
            status TEXT NOT NULL DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (reported_user_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS user_blocks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            blocker_id INTEGER NOT NULL,
            blocked_id INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (blocker_id, blocked_id),
            FOREIGN KEY (blocker_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (blocked_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS user_badges (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            badge_name TEXT NOT NULL,
            earned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (user_id, badge_name),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS admin_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            admin_id INTEGER NOT NULL,
            action TEXT NOT NULL,
            target_type TEXT NULL,
            target_id INTEGER NULL,
            details TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT NOT NULL UNIQUE,
            setting_value TEXT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
SQL;

        $pdo->exec($schemaSql);
        self::seedSqlite($pdo);
    }

    private static function seedSqlite(PDO $pdo): void
    {
        // Settings
        $pdo->exec("INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES
            ('site_name', 'CampusLend'),
            ('campus_name', 'Uka Tarsadia University'),
            ('email_domain', 'utu.ac.in'),
            ('exam_mode_enabled', '1'),
            ('exam_mode_message', 'Mid-Term Exam Season is ON! Emergency requests for Scientific Calculators, Drafters and Stationery are prioritized. Please return borrowed items on time.'),
            ('allow_registration', '1'),
            ('smart_match_service_url', 'http://127.0.0.1:5000/match');
        ");

        // Categories
        $pdo->exec("INSERT OR IGNORE INTO categories (id, name, slug, icon, description) VALUES
            (1, 'Calculators', 'calculators', 'calculator', 'Scientific calculators (FX-991ES, 991EX), financial and standard calculators'),
            (2, 'Drafting & Geometry', 'drafting-geometry', 'compass', 'Mini drafters, geometry boxes, compasses, scales, T-squares'),
            (3, 'Stationery & Pens', 'stationery-pens', 'pen-tool', 'Pens, markers, staplers, punch machines, sticky notes, correction pens'),
            (4, 'Lab & Workshop', 'lab-workshop', 'flask-conical', 'Lab coats, safety goggles, dissecting kits, workshop aprons'),
            (5, 'Electronics & Kits', 'electronics-kits', 'cpu', 'Arduino, breadboards, jumper wires, multimeters, sensors, raspberry pi'),
            (6, 'Chargers & Power', 'chargers-power', 'battery-charging', 'Phone chargers (Type-C/Lightning), laptop chargers, power banks, adapters'),
            (7, 'Audio & Accessories', 'audio-accessories', 'headphones', 'Earphones, headphones, AUX cables, OTG connectors, microphones'),
            (8, 'Umbrella & Rainwear', 'umbrella-rainwear', 'umbrella', 'Pocket umbrellas, raincoats for sudden campus rain showers'),
            (9, 'ID & Exam Essentials', 'id-exam-essentials', 'badge-check', 'ID card holders, lanyards, transparent clip pads, temp exam pass holders'),
            (10, 'Notes & Textbooks', 'notes-textbooks', 'book-open', 'Handwritten lecture notes, reference textbooks, exam formula guides'),
            (11, 'Storage & Flash Drives', 'storage-flash-drives', 'hard-drive', 'USB flash drives, OTG pendrives, SD cards, hard drive cables'),
            (12, 'Tools & Miscellaneous', 'tools-misc', 'wrench', 'Scissors, tape, glue guns, screwdrivers, mini repair tools');
        ");

        // Admin & Students
        $adminHash = '$2y$10$XZpZjh1UE2yNXYHZEqQEO.DXcFREMOxybJqPu6W30b3oTB0XDTogy'; // Admin@123
        $studentHash = '$2y$10$BAjUF/z11XDgBd9hg5k/nebssfOl21ArJOwFwCx1AFp/Kd6XilICS'; // Student@123

        $pdo->exec("INSERT OR IGNORE INTO users (id, full_name, email, password_hash, enrollment_no, department, year_of_study, phone, avatar, role, is_verified, is_banned, trust_score, total_ratings) VALUES
            (1, 'Prof. Nimesh Patel (Admin)', 'admin@utu.ac.in', '{$adminHash}', 'UTU-ADM-001', 'Computer Engineering', 'Faculty / Admin', '+91 98765 00001', 'admin_avatar.svg', 'admin', 1, 0, 5.00, 15),
            (2, 'Aarav Patel', 'aarav.patel@utu.ac.in', '{$studentHash}', '202303100110001', 'Computer Engineering', '3rd Year', '+91 98251 11001', 'avatar_1.svg', 'student', 1, 0, 4.95, 22),
            (3, 'Diya Shah', 'diya.shah@utu.ac.in', '{$studentHash}', '202303100110002', 'Information Technology', '3rd Year', '+91 98251 11002', 'avatar_2.svg', 'student', 1, 0, 4.88, 18),
            (4, 'Rohan Desai', 'rohan.desai@utu.ac.in', '{$studentHash}', '202203100110003', 'Mechanical Engineering', '4th Year', '+91 98251 11003', 'avatar_3.svg', 'student', 1, 0, 5.00, 29),
            (5, 'Ananya Joshi', 'ananya.joshi@utu.ac.in', '{$studentHash}', '202403100110004', 'Civil Engineering', '2nd Year', '+91 98251 11004', 'avatar_4.svg', 'student', 1, 0, 4.75, 12),
            (6, 'Kavya Mehta', 'kavya.mehta@utu.ac.in', '{$studentHash}', '202303100110005', 'Pharmacy', '3rd Year', '+91 98251 11005', 'avatar_5.svg', 'student', 1, 0, 4.90, 16),
            (7, 'Yash Parmar', 'yash.parmar@utu.ac.in', '{$studentHash}', '202203100110006', 'Electrical Engineering', '4th Year', '+91 98251 11006', 'avatar_6.svg', 'student', 1, 0, 4.80, 14),
            (8, 'Priya Sharma', 'priya.sharma@utu.ac.in', '{$studentHash}', '202403100110007', 'Management Studies', '1st Year', '+91 98251 11007', 'avatar_7.svg', 'student', 1, 0, 4.70, 9),
            (9, 'Manav Gandhi', 'manav.gandhi@utu.ac.in', '{$studentHash}', '202303100110008', 'Computer Engineering', '3rd Year', '+91 98251 11008', 'avatar_8.svg', 'student', 1, 0, 4.92, 21),
            (10, 'Tanvi Modi', 'tanvi.modi@utu.ac.in', '{$studentHash}', '202403100110009', 'Architecture (B.Arch)', '2nd Year', '+91 98251 11009', 'avatar_9.svg', 'student', 1, 0, 5.00, 19),
            (11, 'Harsh Rathod', 'harsh.rathod@utu.ac.in', '{$studentHash}', '202203100110010', 'Mechanical Engineering', '4th Year', '+91 98251 11010', 'avatar_10.svg', 'student', 1, 0, 4.60, 10),
            (12, 'Ishita Vaghela', 'ishita.vaghela@utu.ac.in', '{$studentHash}', '202303100110011', 'Biotechnology', '3rd Year', '+91 98251 11011', 'avatar_1.svg', 'student', 1, 0, 4.85, 15),
            (13, 'Siddharth Dave', 'siddharth.dave@utu.ac.in', '{$studentHash}', '202403100110012', 'Information Technology', '2nd Year', '+91 98251 11012', 'avatar_2.svg', 'student', 1, 0, 4.95, 24),
            (14, 'Khushi Patel', 'khushi.patel@utu.ac.in', '{$studentHash}', '202303100110013', 'Computer Applications (BCA)', '3rd Year', '+91 98251 11013', 'avatar_3.svg', 'student', 1, 0, 4.70, 8),
            (15, 'Vivek Chudasama', 'vivek.chudasama@utu.ac.in', '{$studentHash}', '202203100110014', 'Civil Engineering', '4th Year', '+91 98251 11014', 'avatar_4.svg', 'student', 1, 0, 4.65, 11),
            (16, 'Riya Trivedi', 'riya.trivedi@utu.ac.in', '{$studentHash}', '202403100110015', 'Pharmacy', '1st Year', '+91 98251 11015', 'avatar_5.svg', 'student', 1, 0, 4.80, 13),
            (17, 'Jayraj Gohil', 'jayraj.gohil@utu.ac.in', '{$studentHash}', '202303100110016', 'Mechanical Engineering', '3rd Year', '+91 98251 11016', 'avatar_6.svg', 'student', 1, 0, 4.90, 17),
            (18, 'Bhavya Panchal', 'bhavya.panchal@utu.ac.in', '{$studentHash}', '202403100110017', 'Computer Engineering', '2nd Year', '+91 98251 11017', 'avatar_7.svg', 'student', 1, 0, 5.00, 31),
            (19, 'Drashti Mistri', 'drashti.mistri@utu.ac.in', '{$studentHash}', '202303100110018', 'Interior Design', '3rd Year', '+91 98251 11018', 'avatar_8.svg', 'student', 1, 0, 4.75, 7),
            (20, 'Nirav Solanki', 'nirav.solanki@utu.ac.in', '{$studentHash}', '202203100110019', 'Electrical Engineering', '4th Year', '+91 98251 11019', 'avatar_9.svg', 'student', 1, 0, 4.85, 14),
            (21, 'Simran Ahir', 'simran.ahir@utu.ac.in', '{$studentHash}', '202403100110020', 'Biotechnology', '1st Year', '+91 98251 11020', 'avatar_10.svg', 'student', 1, 0, 4.90, 12);
        ");

        // Items (25 Listings)
        $pdo->exec("INSERT OR IGNORE INTO items (id, user_id, category_id, title, description, photo, item_condition, pickup_location, max_duration_hours, is_available, is_paused) VALUES
            (1, 2, 1, 'Casio FX-991EX ClassWiz Calculator', 'Genuine Casio scientific calculator with 552 functions. Fully working and battery fresh.', 'calc_fx991ex.jpg', 'Like New', 'Block B, Lab 201 (CS Dept)', 48, 1, 0),
            (2, 4, 2, 'Omega Mini Drafter with Canvas Bag', 'Accurate steel rod mini drafter, scale markings completely legible, clamping screw intact.', 'drafter_omega.jpg', 'Good', 'Block D, Workshop Room 3', 72, 1, 0),
            (3, 6, 4, 'White Cotton Lab Coat (Unisex, Size L)', 'Clean and ironed cotton lab coat, required for Chemistry, Pharmacy and Biotech practicals.', 'labcoat_white.jpg', 'Good', 'Pharmacy Block, Ground Floor Lobby', 24, 1, 0),
            (4, 9, 6, 'Fast 65W Type-C Laptop & Mobile Charger', 'Supports PD 3.0. Charges Dell, HP, Lenovo, MacBook and Samsung phones superfast.', 'charger_65w.jpg', 'Like New', 'Central Library, Reading Hall 1', 12, 1, 0),
            (5, 13, 6, 'Mi 20,000 mAh Fast Power Bank (Dual USB + Type-C)', 'Heavy-duty power bank with 18W fast charging and micro USB/Type-C input.', 'powerbank_20k.jpg', 'Good', 'Student Activity Center / Canteen', 24, 1, 0),
            (6, 10, 2, 'Complete Camlin Engineering Geometry Box', 'Includes large bow divider, pencil compass, inking pen attachments, and protractor.', 'camlin_geometry.jpg', 'Like New', 'Block A, 1st Floor Common Area', 48, 1, 0),
            (7, 3, 3, 'Heavy Duty Kangaro Stapler + 2 Pin Boxes', 'Staples up to 30 sheets easily. Great for spiral report binding and exam submissions.', 'stapler_kangaro.jpg', 'Good', 'Block B, Room 304', 24, 1, 0),
            (8, 2, 5, 'Arduino Uno R3 Starter Kit with Jumper Wires', 'Official clone Uno with breadboard, LEDs, resistors, ultrasonic sensor and USB cable.', 'arduino_kit.jpg', 'Good', 'Robotics Club, Block B Basement', 96, 1, 0),
            (9, 7, 5, 'Mastech Digital Multimeter with Sharp Probes', 'Digital multimeter with continuity beeper, resistance, DC/AC voltage testing.', 'multimeter_mastech.jpg', 'Like New', 'Block C, Electrical Lab 2', 48, 1, 0),
            (10, 18, 7, 'Sony WH-CH520 Wireless Over-Ear Headphones', 'Active noise isolation, 50h battery life. Perfect for silent library study sessions.', 'sony_headphones.jpg', 'Good', 'Central Library, 2nd Floor Cubicle 12', 24, 1, 0),
            (11, 5, 8, 'Compact Windproof Foldable Blue Umbrella', '3-fold sturdy umbrella with push button auto open. Very useful in monsoon campus walks.', 'blue_umbrella.jpg', 'Good', 'Block A, Security Reception Desk', 12, 1, 0),
            (12, 8, 9, 'Transparent Hard Plastic Exam Clip Pad', 'University exam approved transparent writing pad, without any markings or formulas.', 'exam_pad.jpg', 'Like New', 'Block A, Exam Hall Lobby', 24, 1, 0),
            (13, 14, 11, 'SanDisk Ultra Dual 64GB USB 3.0 / Type-C Pendrive', 'High speed flash drive for quick project PPT and lab code transfer between systems.', 'sandisk_64gb.jpg', 'Like New', 'Block B, IT Staff Room', 48, 1, 0),
            (14, 12, 4, 'Anti-Fog Chemical Safety Goggles', 'Splash proof and UV resistant clear eyewear for organic chemistry practicals.', 'safety_goggles.jpg', 'Good', 'Pharmacy Block, Lab 302', 12, 1, 0),
            (15, 4, 10, 'Higher Engineering Mathematics - B.S. Grewal (44th Ed.)', 'Comprehensive textbook for Engineering Mathematics 1, 2 and 3. Hardbound copy.', 'bs_grewal_book.jpg', 'Fair', 'Hostel Block H-2, Room 214', 168, 1, 0),
            (16, 9, 6, 'Belkin Multi-Port HDMI + VGA to Type-C Display Adapter', 'Essential for connecting modern thin laptops to campus projectors for presentations.', 'typec_hdmi_adapter.jpg', 'Like New', 'Management Block, Seminar Hall 2', 8, 1, 0),
            (17, 16, 3, 'Set of 4 Fine Liner Drafting Pens (0.2, 0.4, 0.6, 0.8mm)', 'Waterproof black pigment ink pens for architectural drawings and project charts.', 'drafting_pens.jpg', 'Like New', 'Architecture Dept, Studio 1', 24, 1, 0),
            (18, 18, 10, 'Data Structures & Algorithms Handcrafted Exam Notes', 'Detailed notes with colored diagrams, code in C++/Java, and solved past UTU exam papers.', 'dsa_notes.jpg', 'Good', 'Block B, Computer Lab 4', 72, 1, 0),
            (19, 11, 12, 'Stanley 40W Hot Glue Gun with 6 Glue Sticks', 'Fast heating craft gun for robotics chassis assembly, art models, and display charts.', 'glue_gun.jpg', 'Fair', 'Mechanical Workshop, Tool Crib', 24, 1, 0),
            (20, 17, 6, 'Anchor 4-Socket Extension Cord with 3-Meter Heavy Cable', 'Surge protected extension board for hackathons, project displays, and lab demos.', 'extension_cord.jpg', 'Good', 'Block B, Seminar Auditorium', 36, 1, 0),
            (21, 13, 7, 'Logitech M330 Silent Wireless Optical Mouse', 'Zero-click noise, smooth tracking on all surfaces. USB unifying nano receiver included.', 'logitech_mouse.jpg', 'Like New', 'Block B, CAD Lab', 48, 1, 0),
            (22, 21, 4, 'Stainless Steel Zoology / Biotech Dissection Set', 'Forceps, scalpel handle with blades, scissors, probe, bone cutter, magnifying lens.', 'dissection_set.jpg', 'Good', 'Life Sciences Dept, Lab 1', 48, 1, 0),
            (23, 7, 12, 'Mitutoyo Style Vernier Caliper (0-150mm, 0.02mm Accuracy)', 'Precise measurement for manufacturing engineering, workshop jobs, and physics experiments.', 'vernier_caliper.jpg', 'Good', 'Mechanical Metrology Lab', 24, 1, 0),
            (24, 19, 2, 'Rotring 30cm Metric Triangular Scale Ruler', 'Architectural reduction scale 1:1, 1:20, 1:25, 1:50, 1:75, 1:100. Color coded grooves.', 'scale_ruler.jpg', 'Like New', 'Interior Design Studio 2', 48, 1, 0),
            (25, 6, 1, 'Casio FX-82MS Standard Scientific Calculator', 'Battery operated 240-function calculator, approved for diploma and first-year exams.', 'calc_fx82ms.jpg', 'Good', 'Block C, Room 102', 48, 1, 0);
        ");

        // Requests (40 mixed sample requests)
        $pdo->exec("INSERT OR IGNORE INTO requests (id, user_id, category_id, item_name, urgency, campus_block, room_location, needed_by, note, status, created_at) VALUES
            (1, 3, 1, 'Casio FX-991EX Scientific Calculator', 'Critical', 'Block B', 'Exam Hall 101', datetime('now', '+45 minutes'), 'Mid-term maths exam starting at 11 AM! Forgot my calculator in the hostel. Need urgent help!', 'open', datetime('now')),
            (2, 5, 2, 'Mini Drafter with Clamping Knob', 'Critical', 'Block D', 'Drawing Hall 2', datetime('now', '+30 minutes'), 'Engineering Graphics practical test starting. My scale clamp broke just now!', 'open', datetime('now')),
            (3, 8, 3, 'Blue Gel Pen & Transparent Scale', 'Critical', 'Block A', 'Room 204', datetime('now', '+20 minutes'), 'Writing viva test right now, pen leaked. Need a working blue/black pen urgently please.', 'open', datetime('now')),
            (4, 11, 4, 'Lab Coat (Size L or XL)', 'Critical', 'Pharmacy Block', 'Chem Lab 1', datetime('now', '+40 minutes'), 'Lab assistant won''t allow entry without a lab coat. Organic synthesis exam in 30 mins!', 'offered', datetime('now')),
            (5, 15, 6, 'Type-C 65W Fast Laptop Charger', 'Critical', 'Central Library', 'Digital Resource Center', datetime('now', '+50 minutes'), 'Battery at 4% and final capstone project submission is at 12:00 PM today. Very critical!', 'open', datetime('now')),
            (6, 17, 5, 'Male to Female Jumper Wires (10 pcs)', 'Critical', 'Block B', 'Hardware Lab 3', datetime('now', '+60 minutes'), 'Hardware viva demonstration in 45 minutes, need jumper wires to finish breadboard circuit.', 'open', datetime('now')),
            (7, 20, 8, 'Pocket Umbrella', 'High', 'Main Gate', 'Security Post', datetime('now', '+35 minutes'), 'Pouring rain outside and need to walk to Block D for submission. Anyone nearby?', 'open', datetime('now')),
            (8, 7, 1, 'Scientific Calculator (any FX model)', 'Critical', 'Block C', 'Room 302', datetime('now', '+25 minutes'), 'Thermodynamics class test. Please help if anyone is in Block C!', 'offered', datetime('now')),
            (9, 12, 4, 'Safety Goggles with Elastic Band', 'High', 'Pharmacy Block', 'Analytical Chemistry Lab', datetime('now', '+3 hours'), 'Practical session from 2 PM to 5 PM today. Will return immediately afterwards.', 'open', datetime('now')),
            (10, 14, 11, 'USB Flash Drive (at least 8GB)', 'High', 'Block B', 'Server Room', datetime('now', '+2 hours'), 'Need to copy university seminar presentation video for presentation.', 'open', datetime('now')),
            (11, 16, 2, 'Camlin Engineering Geometry Compass', 'High', 'Block D', 'Room 105', datetime('now', '+2 hours'), 'Machine drawing sheet submission today afternoon.', 'open', datetime('now')),
            (12, 19, 3, 'Heavy Duty Stapler with Pins', 'Normal', 'Block A', 'Photocopy Center', datetime('now', '+4 hours'), 'Binding 5 copies of internship project reports before 4 PM.', 'open', datetime('now')),
            (13, 21, 10, 'B.S. Grewal Higher Engineering Maths', 'Normal', 'Central Library', 'Reading Room', datetime('now', '+6 hours'), 'Preparing for remedial exam tomorrow. Need book for 3 hours study.', 'open', datetime('now')),
            (14, 2, 6, 'Power Bank 10,000+ mAh', 'High', 'Student Canteen', 'Outdoor Area', datetime('now', '+2 hours'), 'Phone dying and expecting an urgent interview call from campus placement cell.', 'open', datetime('now')),
            (15, 9, 7, 'Earphones / 3.5mm Headset', 'Normal', 'Central Library', 'Audio-Visual Lab', datetime('now', '+4 hours'), 'Attending online Coursera lab session. Left mine in my room.', 'open', datetime('now')),
            (16, 4, 12, 'Hot Glue Gun & Sticks', 'High', 'Mechanical Workshop', 'Design Lab', datetime('now', '+3 hours'), 'Final assembly of solar vehicle model for tomorrow exhibition.', 'open', datetime('now')),
            (17, 10, 9, 'Transparent Clip Board Pad', 'Normal', 'Block A', 'Exam Room 301', datetime('now', '+5 hours'), 'For external viva tomorrow morning. Clean transparent board needed.', 'open', datetime('now')),
            (18, 13, 11, 'Type-C to USB-A OTG Adapter', 'High', 'Block B', 'Lab 103', datetime('now', '+90 minutes'), 'Need to flash microcontroller firmware from my phone.', 'open', datetime('now')),
            (19, 18, 5, 'Digital Multimeter', 'High', 'Block C', 'Power Electronics Lab', datetime('now', '+2 hours'), 'Testing DC-DC converter output voltage before teacher inspection.', 'open', datetime('now')),
            (20, 6, 4, 'Dissection Box / Kit', 'High', 'Life Sciences Dept', 'Zoology Lab', datetime('now', '+3 hours'), 'Biology practical batch B starting at 2:30 PM.', 'open', datetime('now')),
            (21, 2, 1, 'FX-991ES Plus Scientific Calculator', 'Critical', 'Block B', 'Room 205', datetime('now', '-2 days'), 'Needed for Numerical Methods mid-term.', 'fulfilled', datetime('now', '-3 days')),
            (22, 4, 6, '65W Laptop Charger', 'High', 'Library', 'Reading Hall', datetime('now', '-1 day'), 'Emergency study night battery died.', 'fulfilled', datetime('now', '-2 days')),
            (23, 7, 2, 'Mini Drafter with Canvas Cover', 'Critical', 'Block D', 'Lab 4', datetime('now', '-4 days'), 'Urgent graphics sheet assignment.', 'fulfilled', datetime('now', '-5 days')),
            (24, 9, 3, 'Stapler & Punch Machine', 'Normal', 'Block A', 'Office Lobby', datetime('now', '-3 days'), 'Scholarship form submission deadline.', 'fulfilled', datetime('now', '-4 days')),
            (25, 11, 4, 'Chemistry Lab Coat White', 'High', 'Pharmacy Block', 'Lab 2', datetime('now', '-1 day'), 'Biochemistry titration session.', 'fulfilled', datetime('now', '-2 days')),
            (26, 13, 5, 'Arduino Jumper Cables Male-to-Male', 'Normal', 'Block B', 'Basement Club', datetime('now', '-5 days'), 'Sensor interfacing experiment.', 'fulfilled', datetime('now', '-6 days')),
            (27, 15, 8, 'Raincoat / Umbrella', 'High', 'Hostels', 'Gate 2', datetime('now', '-2 days'), 'Heavy rain during return from labs.', 'fulfilled', datetime('now', '-3 days')),
            (28, 17, 10, 'DBMS Handwritten Notes - 5th Sem', 'Normal', 'Library', 'Desk 45', datetime('now', '-6 days'), 'Quick revision for SQL practical viva.', 'fulfilled', datetime('now', '-7 days')),
            (29, 3, 6, 'Type-C Mobile Fast Charger', 'High', 'Canteen', 'Table 8', datetime('now', '+1 hour'), 'Battery at 3%. Meeting group members.', 'matched', datetime('now', '-30 minutes')),
            (30, 8, 1, 'Casio FX-991EX Calculator', 'Critical', 'Block B', 'Lab 102', datetime('now', '+2 hours'), 'Statistics and probability quiz in 1 hour.', 'matched', datetime('now', '-20 minutes')),
            (31, 10, 3, 'Permanent Whiteboard Marker', 'Normal', 'Block A', 'Room 401', datetime('now', '-1 day'), 'Seminar presentation in class.', 'expired', datetime('now', '-2 days')),
            (32, 12, 11, 'Card Reader / MicroSD Adapter', 'Normal', 'Block B', 'Room 202', datetime('now', '-2 days'), 'Retrieving phone camera images for report.', 'expired', datetime('now', '-3 days')),
            (33, 14, 12, 'Small Flathead Screwdriver', 'Normal', 'Block C', 'Robotics Lab', datetime('now', '-3 days'), 'Tightening loose screw on spectacles frame.', 'expired', datetime('now', '-4 days')),
            (34, 16, 7, 'Auxiliary 3.5mm Audio Cable', 'Normal', 'Seminar Hall', 'Audio Console', datetime('now', '-4 days'), 'Connecting speaker for college club meeting.', 'expired', datetime('now', '-5 days')),
            (35, 18, 9, 'ID Card Lanyard Ribbon', 'Normal', 'Gate 1', 'Security', datetime('now', '-5 days'), 'Lost lanyard at bus stand, needed temp holder.', 'expired', datetime('now', '-6 days')),
            (36, 19, 6, 'iPhone Lightning Cable', 'High', 'Block A', 'Floor 2', datetime('now', '-1 day'), 'Friend arrived with a spare, resolved.', 'cancelled', datetime('now', '-1 day')),
            (37, 20, 1, 'FX-82MS Calculator', 'Normal', 'Block B', 'Room 105', datetime('now', '-2 days'), 'Teacher postponed quiz.', 'cancelled', datetime('now', '-2 days')),
            (38, 21, 2, 'T-Square Ruler', 'Normal', 'Block D', 'Studio 4', datetime('now', '-3 days'), 'Found my old ruler in locker.', 'cancelled', datetime('now', '-3 days')),
            (39, 5, 8, 'Umbrella for 1 Hour', 'Normal', 'Library', 'Main Steps', datetime('now', '-4 days'), 'Rain stopped.', 'cancelled', datetime('now', '-4 days')),
            (40, 6, 4, 'Lab Apron Size M', 'High', 'Pharmacy Block', 'Dispensing Lab', datetime('now', '-5 days'), 'Senior offered their extra apron.', 'cancelled', datetime('now', '-5 days'));
        ");

        // Offers
        $pdo->exec("INSERT OR IGNORE INTO offers (id, request_id, lender_id, item_id, message, status, created_at) VALUES
            (1, 4, 6, 3, 'I have my clean lab coat in Pharmacy Ground Floor! You can take it right away.', 'accepted', datetime('now', '-15 minutes')),
            (2, 8, 2, 1, 'I am in Block B Lab 201 right now, just 2 mins walk from Block C. Come get my FX-991EX.', 'accepted', datetime('now', '-10 minutes')),
            (3, 1, 9, NULL, 'Hey Aarav, I am outside Block B Room 102 with a spare Casio FX-991ES. Call me or reply!', 'pending', datetime('now', '-5 minutes')),
            (4, 2, 4, 2, 'I am in Workshop 3, my Omega drafter is ready to borrow. Clamping screw is solid.', 'pending', datetime('now', '-10 minutes')),
            (5, 5, 13, 4, 'I am in Library Reading Hall 1 with my 65W fast charger. Sitting at table 14.', 'pending', datetime('now', '-8 minutes')),
            (6, 6, 7, NULL, 'Got a whole bundle of jumper wires in my bag. Meet me near the Block B elevator.', 'pending', datetime('now', '-12 minutes')),
            (7, 21, 4, 1, 'Happy to help for your Numerical Methods test!', 'accepted', datetime('now', '-3 days')),
            (8, 22, 13, 4, 'Here you go, charger is ready.', 'accepted', datetime('now', '-2 days')),
            (9, 23, 10, 2, 'Take my drafter, return after your graphics test.', 'accepted', datetime('now', '-5 days')),
            (10, 24, 3, 7, 'Stapler is in my bag, Block B.', 'accepted', datetime('now', '-4 days')),
            (11, 25, 6, 3, 'Lab coat ready at Pharmacy.', 'accepted', datetime('now', '-2 days')),
            (12, 26, 2, 8, 'Take the jumper wires from Robotics club.', 'accepted', datetime('now', '-6 days')),
            (13, 27, 5, 11, 'Take my blue umbrella, keep dry!', 'accepted', datetime('now', '-3 days')),
            (14, 28, 18, 18, 'Take the DSA notes, hope you ace the viva.', 'accepted', datetime('now', '-7 days')),
            (15, 29, 9, 5, 'Power bank handed over at Canteen.', 'accepted', datetime('now', '-30 minutes')),
            (16, 30, 2, 1, 'Handed over calculator at Lab 102.', 'accepted', datetime('now', '-20 minutes'));
        ");

        // Borrow Transactions
        $pdo->exec("INSERT OR IGNORE INTO borrow_transactions (id, request_id, offer_id, borrower_id, lender_id, item_id, status, handover_confirmed_borrower, handover_confirmed_lender, return_confirmed_borrower, return_confirmed_lender, handover_time, due_time, returned_time, created_at) VALUES
            (1, 21, 7, 2, 4, 1, 'returned', 1, 1, 1, 1, datetime('now', '-3 days'), datetime('now', '-2 days'), datetime('now', '-2 days'), datetime('now', '-3 days')),
            (2, 22, 8, 4, 13, 4, 'returned', 1, 1, 1, 1, datetime('now', '-2 days'), datetime('now', '-1 day'), datetime('now', '-1 day'), datetime('now', '-2 days')),
            (3, 23, 9, 7, 10, 2, 'returned', 1, 1, 1, 1, datetime('now', '-5 days'), datetime('now', '-4 days'), datetime('now', '-4 days'), datetime('now', '-5 days')),
            (4, 24, 10, 9, 3, 7, 'returned', 1, 1, 1, 1, datetime('now', '-4 days'), datetime('now', '-3 days'), datetime('now', '-3 days'), datetime('now', '-4 days')),
            (5, 25, 11, 11, 6, 3, 'returned', 1, 1, 1, 1, datetime('now', '-2 days'), datetime('now', '-1 day'), datetime('now', '-1 day'), datetime('now', '-2 days')),
            (6, 26, 12, 13, 2, 8, 'returned', 1, 1, 1, 1, datetime('now', '-6 days'), datetime('now', '-5 days'), datetime('now', '-5 days'), datetime('now', '-6 days')),
            (7, 27, 13, 15, 5, 11, 'returned', 1, 1, 1, 1, datetime('now', '-3 days'), datetime('now', '-2 days'), datetime('now', '-2 days'), datetime('now', '-3 days')),
            (8, 28, 14, 17, 18, 18, 'returned', 1, 1, 1, 1, datetime('now', '-7 days'), datetime('now', '-6 days'), datetime('now', '-6 days'), datetime('now', '-7 days')),
            (9, 29, 15, 3, 9, 5, 'handed_over', 1, 1, 0, 0, datetime('now', '-20 minutes'), datetime('now', '+3 hours'), NULL, datetime('now', '-30 minutes')),
            (10, 30, 16, 8, 2, 1, 'handed_over', 1, 1, 0, 0, datetime('now', '-15 minutes'), datetime('now', '+2 hours'), NULL, datetime('now', '-20 minutes')),
            (11, 4, 1, 11, 6, 3, 'accepted', 0, 0, 0, 0, NULL, datetime('now', '+4 hours'), NULL, datetime('now', '-15 minutes')),
            (12, 8, 2, 7, 2, 1, 'accepted', 0, 0, 0, 0, NULL, datetime('now', '+2 hours'), NULL, datetime('now', '-10 minutes'));
        ");

        // Ratings
        $pdo->exec("INSERT OR IGNORE INTO ratings (transaction_id, reviewer_id, reviewed_user_id, role_in_tx, stars, comment, created_at) VALUES
            (1, 2, 4, 'lender', 5, 'Rohan is a lifesaver! Returned the calculator in pristine condition right after exam.', datetime('now', '-2 days')),
            (1, 4, 2, 'borrower', 5, 'Aarav came to my classroom within 5 minutes. Super polite and reliable lender!', datetime('now', '-2 days')),
            (2, 4, 13, 'lender', 5, 'Siddharth was super quick to respond. 65W charger kept my laptop alive for submissions.', datetime('now', '-1 day')),
            (2, 13, 4, 'borrower', 5, 'Returned right on time without any reminder. 10/10 student!', datetime('now', '-1 day')),
            (3, 7, 10, 'lender', 5, 'Tanvi lent me her pristine geometry box. Saved me from failing the graphics practical!', datetime('now', '-4 days')),
            (3, 10, 7, 'borrower', 5, 'Very courteous and returned everything neatly organized.', datetime('now', '-4 days')),
            (4, 9, 3, 'lender', 5, 'Diya helped with a heavy duty stapler right in Block B. Excellent communication.', datetime('now', '-3 days')),
            (4, 3, 9, 'borrower', 5, 'Returned quickly within an hour. Highly recommended borrower.', datetime('now', '-3 days')),
            (5, 11, 6, 'lender', 5, 'Kavya saved my chemistry lab entry! Clean white lab coat.', datetime('now', '-1 day')),
            (5, 6, 11, 'borrower', 5, 'Gentleman student, returned washed and folded.', datetime('now', '-1 day')),
            (6, 13, 2, 'lender', 5, 'Aarav lent an entire Arduino kit with all sensors working.', datetime('now', '-5 days')),
            (6, 2, 13, 'borrower', 5, 'Careful with electronic components. Perfect borrower.', datetime('now', '-5 days')),
            (7, 15, 5, 'lender', 5, 'Ananya offered her umbrella when it was pouring cats and dogs at Gate 2!', datetime('now', '-2 days')),
            (7, 5, 15, 'borrower', 5, 'Returned dry and intact. Great campus mate.', datetime('now', '-2 days')),
            (8, 17, 18, 'lender', 5, 'Bhavya handwritten notes are gold! Solved questions helped me pass with flying colors.', datetime('now', '-6 days')),
            (8, 18, 17, 'borrower', 5, 'Returned spiral book without any dog-ears or marks. Awesome.', datetime('now', '-6 days'));
        ");

        // Badges
        $pdo->exec("INSERT OR IGNORE INTO user_badges (user_id, badge_name, earned_at) VALUES
            (2, 'Quick Helper', datetime('now', '-10 days')),
            (2, 'Exam Saviour', datetime('now', '-5 days')),
            (2, 'Campus Hero', datetime('now', '-2 days')),
            (4, 'Exam Saviour', datetime('now', '-8 days')),
            (4, 'Reliable Lender', datetime('now', '-4 days')),
            (4, 'Campus Hero', datetime('now', '-1 day')),
            (6, 'Quick Helper', datetime('now', '-7 days')),
            (6, 'Reliable Lender', datetime('now', '-3 days')),
            (9, 'Quick Helper', datetime('now', '-6 days')),
            (10, 'Exam Saviour', datetime('now', '-4 days')),
            (13, 'Reliable Lender', datetime('now', '-5 days')),
            (18, 'Exam Saviour', datetime('now', '-6 days')),
            (18, 'Campus Hero', datetime('now', '-2 days'));
        ");

        // Messages
        $pdo->exec("INSERT OR IGNORE INTO messages (transaction_id, request_id, sender_id, receiver_id, message, is_read, created_at) VALUES
            (9, 29, 9, 3, 'Hey Diya, I just arrived at Canteen Table 8 with the power bank.', 1, datetime('now', '-25 minutes')),
            (9, 29, 3, 9, 'Awesome Manav! I am wearing a yellow jacket near the juice counter.', 1, datetime('now', '-23 minutes')),
            (9, 29, 9, 3, 'Found you! Handing over the power bank now. Click confirm handover in app.', 1, datetime('now', '-21 minutes')),
            (10, 30, 2, 8, 'Priya, I am right outside Lab 102 with the Casio FX-991EX.', 1, datetime('now', '-18 minutes')),
            (10, 30, 8, 2, 'Coming out in 10 seconds! Thank you so much Aarav!', 1, datetime('now', '-17 minutes')),
            (11, 4, 6, 11, 'Harsh, I am in Pharmacy Ground Floor Lobby. Come get the lab coat before your lab starts!', 0, datetime('now', '-10 minutes')),
            (12, 8, 2, 7, 'Yash, I am in Block B Lab 201. Calculator is on desk #4.', 0, datetime('now', '-8 minutes'));
        ");

        // Notifications
        $pdo->exec("INSERT OR IGNORE INTO notifications (user_id, title, message, type, link, is_read, created_at) VALUES
            (3, 'Power Bank Handed Over', 'Manav Gandhi confirmed handover of the power bank. Please confirm from your end.', 'handover', '/transactions?id=9', 1, datetime('now', '-20 minutes')),
            (8, 'Calculator Handed Over', 'Aarav Patel confirmed handover of Casio FX-991EX. Please confirm from your end.', 'handover', '/transactions?id=10', 1, datetime('now', '-15 minutes')),
            (11, 'Offer Accepted for Lab Coat', 'Kavya Mehta accepted your borrow request for Lab Coat.', 'offer_accepted', '/transactions?id=11', 0, datetime('now', '-14 minutes')),
            (7, 'Offer Accepted for Calculator', 'Aarav Patel offered help for your Emergency Request.', 'offer_received', '/requests/view?id=8', 0, datetime('now', '-9 minutes')),
            (2, 'Badge Earned: Campus Hero! 🏆', 'Congratulations! You have completed over 20 verified student loans and emergency assists.', 'system', '/profile', 1, datetime('now', '-2 days')),
            (4, '5-Star Review Received ⭐⭐⭐⭐⭐', 'Aarav Patel gave you a 5-star rating: \"Super polite and reliable lender!\"', 'rating_received', '/profile', 1, datetime('now', '-2 days'));
        ");

        // Admin Logs
        $pdo->exec("INSERT OR IGNORE INTO admin_logs (admin_id, action, target_type, target_id, details, created_at) VALUES
            (1, 'SYSTEM_INIT', 'system', 1, 'CampusLend platform initialized for Uka Tarsadia University (utu.ac.in).', datetime('now', '-30 days')),
            (1, 'EXAM_BANNER_ENABLE', 'setting', 1, 'Enabled exam season alert banner across feed and dashboard.', datetime('now', '-5 days')),
            (1, 'USER_VERIFY', 'user', 2, 'Admin verified faculty endorsement for Aarav Patel.', datetime('now', '-15 days'));
        ");
    }
}
