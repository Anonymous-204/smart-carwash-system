const getDatabaseConnection = require('../../db'); // Lùi 3 cấp ra ngoài src/db.js chuẩn cây thư mục của bạn
const bcrypt = require('bcrypt');
const jwt = require('jsonwebtoken');
const crypto = require('crypto');

const JWT_SECRET = 'your_jwt_secret_key';

// ... Giữ nguyên các hàm createUser, authenticateUser, deleteToken, refreshAccessToken phía dưới ...

const createUser = async (email, password) => {
    const db = await getDatabaseConnection();
    
    // Sửa lại dùng db.get (trả về object trực tiếp hoặc undefined)
    const existingUser = await db.get('SELECT id FROM users WHERE email = ?', [email]);
    if (existingUser) {
        throw new Error('Email đã tồn tại');
    }
    
    const hashedPassword = await bcrypt.hash(password, 10);
    const defaultName = email.split('@')[0]; // Lấy tạm phần trước @ làm tên công dân
    
    // Sửa lại: Thêm name và role vì database cấu hình NOT NULL
    await db.run(
        'INSERT INTO users (email, hashed_password, name, role) VALUES (?, ?, ?, ?)', 
        [email, hashedPassword, defaultName, 'customer']
    );
    
    return { message: 'Đăng ký thành công' };
};

const authenticateUser = async (email, password) => {
    const db = await getDatabaseConnection();
    
    // Sửa lại dùng db.get
    const user = await db.get('SELECT * FROM users WHERE email = ?', [email]);
    if (!user) {
        throw new Error('Sai email hoặc mật khẩu');
    }
    
    const validPassword = await bcrypt.compare(password, user.hashed_password);
    if (!validPassword) {
        throw new Error('Sai email hoặc mật khẩu');
    }
    
    const accessToken = jwt.sign({ id: user.id, email: user.email }, JWT_SECRET, { expiresIn: '1h' });
    const refreshToken = crypto.randomBytes(64).toString('hex');
    
    // SỬA: Đổi sang trường refresh_token và cú pháp thời gian datetime() của SQLite
    await db.run(
        `INSERT INTO sessions (user_id, refresh_token, expires_at) 
         VALUES (?, ?, datetime('now', '+7 days'))`, 
        [user.id, refreshToken]
    );
    
    return { accessToken, refreshToken };
};

const deleteToken = async (refreshToken) => {
    if (!refreshToken) return;
    const db = await getDatabaseConnection();
    // SỬA: Đổi sang trường refresh_token và dùng db.run
    await db.run('DELETE FROM sessions WHERE refresh_token = ?', [refreshToken]);
};

const refreshAccessToken = async (oldToken) => {
    const db = await getDatabaseConnection();
    
    // SỬA: Đổi sang trường refresh_token và dùng db.get
    const session = await db.get('SELECT * FROM sessions WHERE refresh_token = ?', [oldToken]);
    if (!session) {
        throw new Error('Token không hợp lệ');
    }
    
    // Xử lý so sánh thời gian trong SQLite: Ép về dạng Date của JS để check cho chuẩn
    // (SQLite lưu chuỗi dạng YYYY-MM-DD HH:MM:SS, cần thêm chữ 'Z' hoặc đổi khoảng trắng thành 'T' để JS hiểu là UTC)
    const expiresTime = new Date(session.expires_at.replace(' ', 'T') + 'Z');
    if (expiresTime < new Date()) {
        await deleteToken(oldToken);
        throw new Error('Token đã hết hạn');
    }
    
    // SỬA: Dùng db.get để tìm user
    const user = await db.get('SELECT * FROM users WHERE id = ?', [session.user_id]);
    if (!user) {
        await deleteToken(oldToken);
        throw new Error('Người dùng không tồn tại');
    }
    
    const newAccessToken = jwt.sign({ id: user.id, email: user.email }, JWT_SECRET, { expiresIn: '1h' });
    return { accessToken: newAccessToken };
};

module.exports = {
    createUser,
    authenticateUser,
    deleteToken,
    refreshAccessToken
};