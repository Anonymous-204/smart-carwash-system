const express = require('express');
const { engine } = require('express-handlebars');
const path = require('path');
const cookieParser = require('cookie-parser'); // Thêm gói này để đọc Access/Refresh Token từ Cookie
const {initDatabase} = require('./src/db');
require('dotenv').config(); // Để đọc biến môi trường từ file .env

// Import các Routers từ các cụm module
// (Ở đây mình ví dụ cụm Auth và cụm Products, sau này bạn làm thêm cụm nào thì cứ import thêm vào)
const authRouter = require('./src/modules/auth/authRoutes');
// const productRouter = require('./src/modules/products/product.router');

const app = express();
const PORT = process.env.PORT || 3000;

// ==========================================
// 1. CẤU HÌNH VIEW ENGINE (HANDLEBARS)
// ==========================================
app.engine('hbs', engine({
    extname: '.hbs',
    defaultLayout: 'main', // File layouts/main.hbs sẽ là khung xương Bootstrap chung
    layoutsDir: path.join(__dirname, 'src/layouts')
}));
app.set('view engine', 'hbs');



// ==========================================
// 2. MIDDLEWARES CƠ BẢN
// ==========================================
app.use(express.json()); // Để giải mã dữ liệu JSON từ Fetch API gửi lên (Email, Password...)
app.use(express.urlencoded({ extended: true })); // Để giải mã dữ liệu nếu gửi dạng Form thuần
app.use(cookieParser()); // Để Express hiểu và đọc được req.cookies.accessToken
app.use(express.static(path.join(__dirname, 'public'))); // Nơi chứa file CSS, Hình ảnh tĩnh nếu có

// ==========================================
// 3. ĐĂNG KÝ CÁC ROUTERS TỪ CỤM MODULE
// ==========================================
// Tuyến đường xử lý Đăng ký/Đăng nhập/Đổi mật khẩu
app.use('/api/auth', authRouter); 

// Ví dụ các tuyến đường sau này bạn mở rộng:
// app.use('/products', productRouter);
// app.use('/orders', orderRouter);

// Trang chủ tạm thời
app.get('/', (req, res) => {
    res.send('<h1>Chào mừng bạn đến với Hệ thống Quản lý Dịch vụ Xe! 🚀</h1><p> Express + SQLite đang hoạt động .</p>');
});

// ==========================================
// 4. KHỞI CHẠY DATABASE VÀ SERVER
// ==========================================
async function startServer() {
    try {
        // Chạy khởi tạo 9 bảng SQLite từ file riêng biệt
        await initDatabase();
        
        // Sau khi DB sẵn sàng mới bắt đầu mở cổng lắng nghe Server
        app.listen(PORT, () => {
            console.log(`==================================================`);
            console.log(`🚀 Server Express đang chạy tại: http://localhost:${PORT}`);
            console.log(`📦 Database SQLite đã được nhúng trực tiếp và sẵn sàng!`);
            console.log(`==================================================`);
        });
    } catch (error) {
        console.error('❌ Lỗi nghiêm trọng! Không thể khởi động hệ thống:', error);
        process.exit(1); // Dừng chương trình ngay lập tức nếu lỗi DB
    }
}

// Kích hoạt toàn bộ hệ thống
startServer();