const authService = require('./authServices');

// Cấu hình các tùy chọn bảo mật cho Cookie (Dùng chung cho cả Access và Refresh Token)
// Chế độ httpOnly: Ngăn chặn JavaScript phía Frontend hack/đọc trộm token (Chống XSS)
const COOKIE_OPTIONS = {
    httpOnly: true,
    secure: process.env.NODE_ENV === 'production', // Chỉ bật qua HTTPS khi deploy lên production
    sameSite: 'strict', // Chống tấn công giả mạo CSRF
    maxAge: 7 * 24 * 60 * 60 * 1000 // Thời gian sống của cookie: 7 ngày (tính bằng mili-giây)
};

/**
 * Xử lý Đăng ký tài khoản
 */
async function register(req, res) {
    try {
        const { email, password } = req.body;

        // Validation cơ bản đầu vào
        if (!email || !password) {
            return res.status(400).json({ message: 'Vui lòng điền đầy đủ email và mật khẩu!' });
        }

        // Gọi Service để thực hiện lệnh INSERT INTO users
        const result = await authService.createUser(email, password);

        // Trả về mã 201 (Created) thành công cho Frontend
        return res.status(201).json(result);
    } catch (error) {
        // Nếu Service ném ra lỗi (Ví dụ: Email đã tồn tại), bắt lấy và trả về FE
        return res.status(400).json({ message: error.message });
    }
}

/**
 * Xử lý Đăng nhập tài khoản
 */
async function login(req, res) {
    try {
        const { email, password } = req.body;

        if (!email || !password) {
            return res.status(400).json({ message: 'Vui lòng điền đầy đủ email và mật khẩu!' });
        }

        // Gọi Service kiểm tra tài khoản, mật khẩu
        const tokens = await authService.authenticateUser(email, password);

        // Đút cả 2 token thẳng vào Cookie của trình duyệt người dùng một cách bảo mật
        res.cookie('accessToken', tokens.accessToken, COOKIE_OPTIONS);
        res.cookie('refreshToken', tokens.refreshToken, COOKIE_OPTIONS);

        // Trả về phản hồi JSON kèm dữ liệu token để FE nếu muốn xài (hoặc cứ thế chuyển trang sang ./)
        return res.status(200).json({
            message: 'Đăng nhập thành công',
            accessToken: tokens.accessToken,
            refreshToken: tokens.refreshToken
        });
    } catch (error) {
        return res.status(400).json({ message: error.message });
    }
}

/**
 * Xử lý Đổi mật khẩu (Yêu cầu phải qua Middleware xác thực trước để có req.user)
 */
async function changePassword(req, res) {
    try {
        const { oldPassword, newPassword } = req.body;
        
        // Thằng Middleware xác thực (Auth Middleware) sau này sẽ nhét thông tin user vào req.user khi accessToken hợp lệ
        const userId = req.user?.id; 

        if (!userId) {
            return res.status(401).json({ message: 'Bạn chưa đăng nhập hoặc phiên làm việc hết hạn!' });
        }

        if (!oldPassword || !newPassword) {
            return res.status(400).json({ message: 'Vui lòng nhập đầy đủ mật khẩu cũ và mới!' });
        }

        // Gọi Service thực thi lệnh UPDATE dữ liệu mật khẩu mới
        await authService.changeUserPassword(userId, oldPassword, newPassword);

        return res.status(200).json({ message: 'Đổi mật khẩu thành công!' });
    } catch (error) {
        return res.status(400).json({ message: error.message });
    }
}

/**
 * Xử lý Đăng xuất (Xóa token trong cookie và xóa session trong SQLite)
 */
async function logout(req, res) {
    try {
        // Đọc mã refreshToken từ Cookie của người dùng gửi lên
        const { refreshToken } = req.cookies;

        // Gọi Service chạy lệnh DELETE xóa bản ghi session tương ứng trong SQLite
        await authService.deleteToken(refreshToken);

        // Lệnh xóa sạch cặp bài trùng Cookie ở phía trình duyệt client
        res.clearCookie('accessToken');
        res.clearCookie('refreshToken');

        return res.status(200).json({ message: 'Đăng xuất thành công!' });
    } catch (error) {
        return res.status(500).json({ message: 'Có lỗi xảy ra khi đăng xuất!' });
    }
}

/**
 * Cấp lại AccessToken mới dựa vào RefreshToken cũ đang lưu trong Cookie
 */
async function refresh(req, res) {
    try {
        const { refreshToken } = req.cookies;

        if (!refreshToken) {
            return res.status(401).json({ message: 'Không tìm thấy Phiên đăng nhập (Refresh Token)!' });
        }

        // Gọi Service thẩm định xem token còn hạn không, user còn tồn tại không
        const result = await authService.refreshAccessToken(refreshToken);

        // Nếu hợp lệ, nhét đè cái AccessToken mới này vào Cookie để gia hạn phiên làm việc
        res.cookie('accessToken', result.accessToken, COOKIE_OPTIONS);

        return res.status(200).json({ message: 'Cấp lại Access Token thành công!', accessToken: result.accessToken });
    } catch (error) {
        // Nếu refresh thất bại (Hết hạn, token giả mạo), tiến hành xóa cookie bắt đăng nhập lại luôn
        res.clearCookie('accessToken');
        res.clearCookie('refreshToken');
        return res.status(401).json({ message: error.message });
    }
}

module.exports = {
    register,
    login,
    changePassword,
    logout,
    refresh
};