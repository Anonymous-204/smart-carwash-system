const sqlite3 = require('sqlite3');
const { open } = require('sqlite');
const path = require('path');

/**
 * 1. Hàm kết nối Database SQLite
 * Hàm này sẽ được export ra cho authServices.js và các module khác gọi đến
 */
async function getDatabaseConnection() {
    return open({
        filename: path.join(__dirname, '..', 'database.sqlite'), // Tạo file database.sqlite ở thư mục gốc project
        driver: sqlite3.Database
    });
}

/**
 * 2. Hàm khởi tạo 10 bảng dữ liệu hệ thống
 */
async function initDatabase() {
    // Gọi trực tiếp hàm ở ngay phía trên, KHÔNG dùng require('./db') nữa!
    const db = await getDatabaseConnection();

    // Bật khóa ngoại (Foreign Keys) cho chắc chắn
    await db.get('PRAGMA foreign_keys = ON');

    // 1. Bảng RANKS
    await db.exec(`
        CREATE TABLE IF NOT EXISTS ranks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            discount INTEGER NOT NULL,
            min_point INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    `);

    // 2. Bảng BRANCHES
    await db.exec(`
        CREATE TABLE IF NOT EXISTS branches (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            address TEXT NOT NULL
        )
    `);

    // 3. Bảng USERS
    await db.exec(`
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT UNIQUE NOT NULL,
            hashed_password TEXT NOT NULL,
            name TEXT NOT NULL,
            branch_id INTEGER,
            role TEXT NOT NULL CHECK(role IN ('customer', 'admin', 'staff')),
            phone TEXT,
            address TEXT,
            point INTEGER DEFAULT 0,
            rank_id INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL,
            FOREIGN KEY (rank_id) REFERENCES ranks(id) ON DELETE SET NULL
        )
    `);

    // 4. BẢNG SESSIONS
    await db.exec(`
        CREATE TABLE IF NOT EXISTS sessions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            refresh_token TEXT UNIQUE NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    `);

    // 5. Bảng VEHICLES
    await db.exec(`
        CREATE TABLE IF NOT EXISTS vehicles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            brand TEXT NOT NULL,
            vehicle_type TEXT NOT NULL,
            license_plate TEXT NOT NULL,
            color TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    `);

    // 6. Bảng SERVICES
    await db.exec(`
        CREATE TABLE IF NOT EXISTS services (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            duration INTEGER NOT NULL,
            description TEXT,
            price INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    `);

    // 7. Bảng ORDERS
    await db.exec(`
        CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            customer_id INTEGER NOT NULL,
            staff_id INTEGER,
            vehicle_id INTEGER NOT NULL,
            pickup_by_staff INTEGER DEFAULT 0 CHECK(pickup_by_staff IN (0, 1)),
            return_by_staff INTEGER DEFAULT 0 CHECK(return_by_staff IN (0, 1)),
            branch_id INTEGER NOT NULL,
            booking_time DATETIME NOT NULL,
            total INTEGER NOT NULL,
            status TEXT NOT NULL CHECK(status IN ('PENDING', 'CONFIRMED', 'PROCESSING', 'COMPLETED', 'CANCELLED')),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES users(id),
            FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
            FOREIGN KEY (branch_id) REFERENCES branches(id)
        )
    `);

    // 8. Bảng ORDER_DETAILS
    await db.exec(`
        CREATE TABLE IF NOT EXISTS order_details (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            service_id INTEGER NOT NULL,
            price INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (service_id) REFERENCES services(id)
        )
    `);

    // 9. Bảng FEEDBACKS
    await db.exec(`
        CREATE TABLE IF NOT EXISTS feedbacks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            rating INTEGER NOT NULL,
            content TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        )
    `);

    // 10. Bảng ANNOUNCEMENTS
    await db.exec(`
        CREATE TABLE IF NOT EXISTS announcements (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            content TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    `);

    console.log('🚀 Toàn bộ 10 bảng (đã bao gồm sessions) đã được khởi tạo thành công trong SQLite!');
}

// Export cả 2 hàm dưới dạng một Object để các file khác bốc tách ra xài
module.exports = {
    getDatabaseConnection,
    initDatabase
};