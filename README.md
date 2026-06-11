# smart-carwash-system
API Design - Smart Automated Car Wash Management System

Hệ thống: Smart Automated Car Wash Management System with Advance Booking & Loyalty Program

Mục tiêu:
- Đủ chi tiết để nhóm 5 sinh viên phát triển trong khoảng 10 tuần.
- Kiến trúc REST API.
- Có JWT Authentication + Refresh Token.
- Có booking, loyalty, payment, machine management, staff dashboard.
- Scope vừa phải nhưng vẫn đủ “đồ án lớn”.


-- Kích hoạt kiểm tra khóa ngoại (Foreign Keys)
PRAGMA foreign_keys = ON;

-- 1. Bảng RANKS (Hạng thành viên)
CREATE TABLE IF NOT EXISTS ranks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    discount INTEGER NOT NULL,
    min_point INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 2. Bảng BRANCHES (Chi nhánh tiệm rửa xe)
CREATE TABLE IF NOT EXISTS branches (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    address TEXT NOT NULL
);

-- 3. Bảng USERS (Tài khoản: Khách hàng, Admin, Nhân viên)
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
);

-- 4. Bảng SESSIONS (Quản lý Refresh Token cho Express Auth)
CREATE TABLE IF NOT EXISTS sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    refresh_token TEXT UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 5. Bảng VEHICLES (Thông tin xe của khách)
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
);

-- 6. Bảng SERVICES (Danh sách gói dịch vụ rửa xe/chăm sóc)
CREATE TABLE IF NOT EXISTS services (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    duration INTEGER NOT NULL,
    description TEXT,
    price INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 7. Bảng ORDERS (Đơn đặt lịch rửa xe)
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
);

-- 8. Bảng ORDER_DETAILS (Chi tiết các gói dịch vụ trong đơn hàng)
CREATE TABLE IF NOT EXISTS order_details (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    service_id INTEGER NOT NULL,
    price INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id)
);

-- 9. Bảng FEEDBACKS (Khách hàng đánh giá đơn hàng)
CREATE TABLE IF NOT EXISTS feedbacks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    rating INTEGER NOT NULL,
    content TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- 10. Bảng ANNOUNCEMENTS (Thông báo hệ thống)
CREATE TABLE IF NOT EXISTS announcements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    content TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
