<?php
/**
 * Gloy Skincare - Database schema, migrations & demo seed data
 *
 * !! Yeh file har request par load NAHI hoti. !!
 * Sirf scripts/install.php (command line se, ek baar) isay chalata hai.
 * Pehle yeh sab config/database.php mein tha aur har page load par chalta tha
 * (aur reviews/products ko DELETE bhi kar deta tha). Ab aisa kabhi nahi hota.
 */

/* ---------------------------------------------------------------- helpers */

function gloy_column_exists(PDO $pdo, $table, $column) {
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
        $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
        $st->execute([$table, $column]);
        return (int)$st->fetchColumn() > 0;
    }
    foreach ($pdo->query("PRAGMA table_info(" . preg_replace('/[^a-z_]/i', '', $table) . ")") as $row) {
        if (strcasecmp($row['name'], $column) === 0) return true;
    }
    return false;
}

function gloy_add_column(PDO $pdo, $table, $column, $definition) {
    if (gloy_column_exists($pdo, $table, $column)) return false;
    $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    return true;
}

function gloy_index_exists(PDO $pdo, $table, $index) {
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
        $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?");
        $st->execute([$table, $index]);
        return (int)$st->fetchColumn() > 0;
    }
    foreach ($pdo->query("PRAGMA index_list(" . preg_replace('/[^a-z_]/i', '', $table) . ")") as $row) {
        if (strcasecmp($row['name'], $index) === 0) return true;
    }
    return false;
}

/**
 * Index banata hai. $unique=true par pehle UNIQUE koshish karta hai; agar purana data duplicate hua
 * to plain index bana deta hai. Return: 'exists' | 'unique' | 'plain' | 'failed'
 */
function gloy_ensure_index(PDO $pdo, $table, $index, $column, $unique = false) {
    if (gloy_index_exists($pdo, $table, $index)) return 'exists';
    if ($unique) {
        try {
            $pdo->exec("CREATE UNIQUE INDEX `$index` ON `$table` (`$column`)");
            return 'unique';
        } catch (Exception $e) {
            // purane duplicate rows: neeche plain index
        }
    }
    try {
        $pdo->exec("CREATE INDEX `$index` ON `$table` (`$column`)");
        return 'plain';
    } catch (Exception $e) {
        return 'failed';
    }
}

/* ---------------------------------------------------------------- schema */

function initDatabaseSchema($pdo) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'mysql') {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                phone VARCHAR(30) NULL,
                password_hash VARCHAR(255) NOT NULL,
                role VARCHAR(20) DEFAULT 'user',
                status VARCHAR(20) DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS user_addresses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                address_line VARCHAR(255) NOT NULL,
                city VARCHAR(100) NOT NULL,
                postal_code VARCHAR(30) NOT NULL,
                phone VARCHAR(30) NOT NULL,
                is_default TINYINT(1) DEFAULT 0,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                slug VARCHAR(150) NOT NULL UNIQUE,
                subtitle VARCHAR(200) NULL,
                description TEXT NOT NULL,
                ingredients TEXT NOT NULL,
                how_to_use TEXT NOT NULL,
                category VARCHAR(100) NOT NULL,
                skin_type VARCHAR(50) DEFAULT 'All',
                price DECIMAL(10,2) NOT NULL,
                stock INT DEFAULT 50,
                image VARCHAR(255) NOT NULL,
                is_bestseller TINYINT(1) DEFAULT 0,
                rating_avg DECIMAL(3,2) DEFAULT 5.00,
                review_count INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS product_variants (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                size_label VARCHAR(50) NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                stock INT DEFAULT 30,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS cart (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                session_id VARCHAR(100) NULL,
                product_id INT NOT NULL,
                variant_id INT NULL,
                qty INT DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS reviews (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                user_id INT NOT NULL,
                rating INT NOT NULL,
                comment TEXT NOT NULL,
                before_image VARCHAR(255) NULL,
                after_image VARCHAR(255) NULL,
                approved TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_number VARCHAR(100) NOT NULL UNIQUE,
                user_id INT NULL,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL,
                phone VARCHAR(30) NOT NULL,
                address TEXT NOT NULL,
                city VARCHAR(100) NOT NULL,
                postal_code VARCHAR(30) NOT NULL,
                total_amount DECIMAL(10,2) NOT NULL,
                payment_method VARCHAR(50) NOT NULL,
                payment_status VARCHAR(50) DEFAULT 'Pending',
                order_status VARCHAR(50) DEFAULT 'Pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS order_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                product_id INT NOT NULL,
                variant_id INT NULL,
                qty INT NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS payments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                payment_method VARCHAR(50) NOT NULL,
                transaction_id VARCHAR(100) NULL,
                amount DECIMAL(10,2) NOT NULL,
                status VARCHAR(50) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(150) NOT NULL,
                subject VARCHAR(200) NOT NULL,
                message TEXT NOT NULL,
                is_read TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS newsletter_subscribers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(150) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS wishlist (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                session_id VARCHAR(100) NULL,
                product_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } else {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                phone TEXT,
                password_hash TEXT NOT NULL,
                role TEXT DEFAULT 'user',
                status TEXT DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS user_addresses (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                address_line TEXT NOT NULL,
                city TEXT NOT NULL,
                postal_code TEXT NOT NULL,
                phone TEXT NOT NULL,
                is_default INTEGER DEFAULT 0,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                slug TEXT UNIQUE NOT NULL,
                subtitle TEXT,
                description TEXT NOT NULL,
                ingredients TEXT NOT NULL,
                how_to_use TEXT NOT NULL,
                category TEXT NOT NULL,
                skin_type TEXT DEFAULT 'All',
                price REAL NOT NULL,
                stock INTEGER DEFAULT 50,
                image TEXT NOT NULL,
                is_bestseller INTEGER DEFAULT 0,
                rating_avg REAL DEFAULT 5.00,
                review_count INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS product_variants (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                product_id INTEGER NOT NULL,
                size_label TEXT NOT NULL,
                price REAL NOT NULL,
                stock INTEGER DEFAULT 30,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS cart (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                session_id TEXT,
                product_id INTEGER NOT NULL,
                variant_id INTEGER,
                qty INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS reviews (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                product_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                rating INTEGER NOT NULL,
                comment TEXT NOT NULL,
                before_image TEXT,
                after_image TEXT,
                approved INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_number TEXT UNIQUE NOT NULL,
                user_id INTEGER,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                phone TEXT NOT NULL,
                address TEXT NOT NULL,
                city TEXT NOT NULL,
                postal_code TEXT NOT NULL,
                total_amount REAL NOT NULL,
                payment_method TEXT NOT NULL,
                payment_status TEXT DEFAULT 'Pending',
                order_status TEXT DEFAULT 'Pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            );

            CREATE TABLE IF NOT EXISTS order_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                variant_id INTEGER,
                qty INTEGER NOT NULL,
                price REAL NOT NULL,
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS payments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL,
                payment_method TEXT NOT NULL,
                transaction_id TEXT,
                amount REAL NOT NULL,
                status TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                subject TEXT NOT NULL,
                message TEXT NOT NULL,
                is_read INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS newsletter_subscribers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT UNIQUE NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS wishlist (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                session_id TEXT,
                product_id INTEGER NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            );
        ");
    }

    gloy_run_migrations($pdo);
    return gloy_seed_initial_data($pdo);
}

/* ------------------------------------------------------------ migrations */

function gloy_run_migrations(PDO $pdo) {
    $isMysql = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql');

    // users
    gloy_add_column($pdo, 'users', 'status', "VARCHAR(50) DEFAULT 'active'");
    gloy_add_column($pdo, 'users', 'reset_token', 'VARCHAR(64) NULL');
    gloy_add_column($pdo, 'users', 'reset_expires', 'DATETIME NULL');

    // reviews
    gloy_add_column($pdo, 'reviews', 'before_image', 'VARCHAR(255) NULL');
    gloy_add_column($pdo, 'reviews', 'after_image', 'VARCHAR(255) NULL');

    // orders (Easypaisa proof + fraud checks)
    gloy_add_column($pdo, 'orders', 'payment_screenshot', 'VARCHAR(255) NULL');
    gloy_add_column($pdo, 'orders', 'transaction_id', 'VARCHAR(100) NULL');
    gloy_add_column($pdo, 'orders', 'payment_screenshot_hash', 'VARCHAR(64) NULL');

    if ($isMysql) {
        try {
            $pdo->exec("ALTER TABLE orders MODIFY user_id INT NULL");
            $pdo->exec("ALTER TABLE orders MODIFY payment_method VARCHAR(50) NOT NULL");
            $pdo->exec("ALTER TABLE orders MODIFY payment_status VARCHAR(50) NOT NULL DEFAULT 'Pending'");
            $pdo->exec("ALTER TABLE orders MODIFY order_status VARCHAR(50) NOT NULL DEFAULT 'Pending'");
        } catch (Exception $ignore) {}
    }

    // Brute-force protection table
    if ($isMysql) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS login_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                scope VARCHAR(30) NOT NULL,
                identifier VARCHAR(190) NOT NULL,
                ip VARCHAR(45) NOT NULL,
                attempted_at INT NOT NULL,
                INDEX idx_la_identifier (scope, identifier, attempted_at),
                INDEX idx_la_ip (scope, ip, attempted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    } else {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS login_attempts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                scope TEXT NOT NULL,
                identifier TEXT NOT NULL,
                ip TEXT NOT NULL,
                attempted_at INTEGER NOT NULL
            )
        ");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_la_identifier ON login_attempts (scope, identifier, attempted_at)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_la_ip ON login_attempts (scope, ip, attempted_at)");
    }

}

/**
 * Duplicate TID / screenshot ke liye indexes. UNIQUE tab banta hai jab purane orders mein duplicate na hon,
 * warna plain index (aur app-level check duplicates reject karta hai). Backfill ke BAAD chalayein.
 */
function gloy_ensure_fraud_indexes(PDO $pdo) {
    return [
        'orders.transaction_id'          => gloy_ensure_index($pdo, 'orders', 'uq_orders_transaction_id', 'transaction_id', true),
        'orders.payment_screenshot_hash' => gloy_ensure_index($pdo, 'orders', 'uq_orders_screenshot_hash', 'payment_screenshot_hash', true),
    ];
}

/**
 * Purane orders ki screenshots ka SHA-256 hash nikaal kar save karta hai
 * taake purane duplicates bhi admin ko nazar aayen.
 */
function gloy_backfill_screenshot_hashes(PDO $pdo) {
    $dir = __DIR__ . '/../assets/uploads/payments/';
    $rows = $pdo->query("SELECT id, payment_screenshot FROM orders WHERE payment_screenshot IS NOT NULL AND payment_screenshot != '' AND (payment_screenshot_hash IS NULL OR payment_screenshot_hash = '')")->fetchAll();
    $upd = $pdo->prepare("UPDATE orders SET payment_screenshot_hash = ? WHERE id = ?");
    $done = 0;
    foreach ($rows as $r) {
        $path = $dir . basename($r['payment_screenshot']);
        if (is_file($path)) {
            try {
                $upd->execute([hash_file('sha256', $path), $r['id']]);
                $done++;
            } catch (Exception $e) {
                // UNIQUE index ho aur duplicate ho to skip (index plain hona chahiye, neeche note)
            }
        }
    }
    return $done;
}

/* ------------------------------------------------------------ seed data */

/**
 * Sirf KHALI tables mein data daalta hai. Kabhi DELETE nahi karta.
 * Return: ['admin_password' => ...] agar random admin password banana para.
 */
function gloy_seed_initial_data(PDO $pdo) {
    $result = [];

    // ---- Products (sirf agar table bilkul khali ho)
    if ((int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn() === 0) {
        $stmt = $pdo->prepare("INSERT INTO products (id, name, slug, subtitle, description, ingredients, how_to_use, category, skin_type, price, stock, image, is_bestseller, rating_avg, review_count) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        // 1. Gloy Glass Skin Moisturizer
        $stmt->execute([
            1,
            'Gloy Glass Skin Moisturizer',
            'gloy-glass-skin-moisturizer',
            'Deep Hydration & Barrier Support',
            'Give your skin the hydration it needs with Gloy Glass Skin Moisturizer. Its nourishing formula deeply hydrates, smooths, and softens the skin while helping improve the appearance of an uneven skin tone for a fresh, healthy-looking glow.',
            'Ceramides (Barrier Support & Moisture Lock), Shea Butter (Deeply Nourishes & Softens), Glycerine + Water (Draws Lasting Hydration), Vitamin C, Kojic Acid (Brightens Uneven Tone), Olive Oil (Nourishes & Smooths)',
            'Apply evenly onto clean face and neck. Gently massage in upward motion.',
            'Moisturizers',
            'All',
            2150.00,
            100,
            'gloy_moisturizer_wood.jpg',
            1,
            4.95,
            48
        ]);

        // 2. Glutathione Brightening Serum
        $stmt->execute([
            2,
            'Glutathione Brightening Serum',
            'glutathione-brightening-serum',
            'Lightweight Brightening & Tone Perfecting Elixir',
            'A lightweight brightening serum formulated with Glutathione, Vitamin C, and Niacinamide to help improve the appearance of dull skin and promote a more bright, fresh, and even-looking skin tone. Evens Out Skin Tone, Hydrates Skin, Reduces Dark Wrinkles & Spots.',
            'Glutathione (Tone Perfecting), Vitamin C (Radiance & Brightening), Niacinamide (Refines Complexion & Pore Support), Hyaluronic Acid (Plumping Hydration)',
            'Daily glow in every drop. Dispense 3-4 drops onto clean fingertips. Gently press into face and neck morning and evening.',
            'Serums',
            'All',
            2250.00,
            120,
            'gloy_serum_wood.jpg',
            1,
            4.98,
            64
        ]);

        // 3. Gloy Hydration & Brightening Duo
        $stmt->execute([
            3,
            'Gloy Hydration & Brightening Duo',
            'gloy-hydration-brightening-duo',
            'Complete Radiance Routine (Moisturizer + Serum)',
            'Experience the ultimate 2-step Gloy routine. Includes full-size Gloy Glass Skin Moisturizer (50ml) and Glutathione Brightening Serum (30ml) for maximum hydration and tone brightening.',
            'Ceramides, Shea Butter, Glycerine, Vitamin C, Kojic Acid, Olive Oil, Glutathione, Niacinamide',
            'Step 1: Apply 3-4 drops of Glutathione Serum onto clean skin. Step 2: Lock in moisture with Gloy Glass Skin Moisturizer.',
            'Bundles',
            'All',
            4100.00,
            80,
            'gloy_hero_duo_hd.jpg',
            1,
            5.00,
            32
        ]);

    }

    // ---- Variants
    if ((int)$pdo->query("SELECT COUNT(*) FROM product_variants")->fetchColumn() === 0) {
        $pvStmt = $pdo->prepare("INSERT INTO product_variants (id, product_id, size_label, price, stock) VALUES (?, ?, ?, ?, ?)");
        $pvStmt->execute([1, 1, '50ml Standard Jar', 2150.00, 50]);
        $pvStmt->execute([2, 2, '30ml Dropper Bottle', 2250.00, 60]);
        $pvStmt->execute([3, 3, 'Full Set (50ml + 30ml)', 4100.00, 40]);
    }

    // ---- Users (sirf agar table khali ho). Admin password .env se.
    if ((int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() === 0) {
        $seedEmail = gloy_env('ADMIN_SEED_EMAIL', 'gloyskincare@gmail.com');
        $seedPass  = gloy_env('ADMIN_SEED_PASSWORD');
        if (empty($seedPass)) {
            $seedPass = bin2hex(random_bytes(8));
            $result['admin_password'] = $seedPass;
        }
        $uStmt = $pdo->prepare("INSERT INTO users (id, name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?, ?)");
        $uStmt->execute([1, 'Gloy Admin', $seedEmail, '03168882788', password_hash($seedPass, PASSWORD_BCRYPT), 'admin']);

        // Demo customers: ab 'user123' jaisa maloom password NAHI. Random password, yaani in accounts mein
        // koi login nahi kar sakta (sirf reviews ki attribution ke liye hain).
        $demo = [
            [2, 'Arhama A.', 'customer@gmail.com', '03168882788'],
            [3, 'Fatima Z.',  'fatima@gmail.com',   '03001234567'],
            [4, 'Ayesha N.',  'ayesha@gmail.com',   '03219876543'],
            [5, 'Maham R.',   'maham@gmail.com',    '03335554433'],
        ];
        foreach ($demo as $d) {
            $uStmt->execute([$d[0], $d[1], $d[2], $d[3], password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT), 'user']);
        }
    }

    // ---- Sample reviews (sirf agar reviews table bilkul khali ho; kabhi DELETE nahi)
    if ((int)$pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn() === 0) {
        $getUId = function($email) use ($pdo) {
            $st = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $st->execute([$email]);
            $id = $st->fetchColumn();
            return $id ? (int)$id : 1;
        };

        $revInsert = $pdo->prepare("
            INSERT INTO reviews (product_id, user_id, rating, comment, before_image, after_image, approved, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 1, ?)
        ");

        // Review 1: Ayesha N. - Glutathione Serum (Before & After photo 2)
        $revInsert->execute([
            2,
            $getUId('ayesha@gmail.com'),
            5,
            'Before and after 3 weeks of using Glutathione Brightening Serum daily! The stubborn hyperpigmentation and dark spots around my cheeks faded noticeably, and my skin tone looks so even and naturally glowing.',
            'review_before_after_2.jpg',
            'review_before_after_2.jpg',
            '2026-09-15 14:20:00'
        ]);

        // Review 2: Fatima Z. - Glass Skin Moisturizer (Before & After photo 3)
        $revInsert->execute([
            1,
            $getUId('fatima@gmail.com'),
            5,
            'I suffered from extreme dry skin and compromised barrier. Look at the before and after! The ceramides and shea butter in Gloy Glass Skin Moisturizer repaired my flakiness and gave me soft, plump glass skin.',
            'review_before_after_3.jpg',
            'review_before_after_3.jpg',
            '2026-09-18 10:45:00'
        ]);

        // Review 3: Maham R. - Gloy Duo Bundle (Before & After photo 1)
        $revInsert->execute([
            3,
            $getUId('maham@gmail.com'),
            5,
            'Unbelievable transformation! 4 weeks of the Gloy Duo (Glutathione Serum + Glass Skin Moisturizer). Acne breakouts calmed down, texture smoothed out, and my face looks fresh and dewy every morning!',
            'review_before_after_1.jpg',
            'review_before_after_1.jpg',
            '2026-09-22 16:30:00'
        ]);


        // Review 5: Sara K. - Glutathione Serum
        $revInsert->execute([
            2,
            $getUId('fatima@gmail.com'),
            5,
            'Daily glow in every drop. The Vitamin C and Niacinamide formula absorbs instantly without stickiness. My dullness disappeared within 10 days.',
            null,
            null,
            '2026-09-26 09:10:00'
        ]);

        foreach ([1, 2, 3] as $pId) {
            $calc = $pdo->prepare("SELECT AVG(rating) as avg_r, COUNT(*) as cnt FROM reviews WHERE product_id = ? AND approved = 1");
            $calc->execute([$pId]);
            $res = $calc->fetch();
            if ((int)$res['cnt'] > 0) {
                $upd = $pdo->prepare("UPDATE products SET rating_avg = ?, review_count = ? WHERE id = ?");
                $upd->execute([round((float)$res['avg_r'], 2), (int)$res['cnt'], $pId]);
            }
        }
    }

    return $result;
}
