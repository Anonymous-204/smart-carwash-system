# smart-carwash-system
API Design - Smart Automated Car Wash Management System

Hệ thống: Smart Automated Car Wash Management System with Advance Booking & Loyalty Program

Mục tiêu:
- Đủ chi tiết để nhóm 5 sinh viên phát triển trong khoảng 10 tuần.
- Kiến trúc REST API.
- Có JWT Authentication + Refresh Token.
- Có booking, loyalty, payment, machine management, staff dashboard.
- Scope vừa phải nhưng vẫn đủ “đồ án lớn”.

Tech gợi ý:
- Backend: Node.js + Express/NestJS hoặc Spring Boot
- Database: PostgreSQL/MySQL
- Auth: JWT + Refresh Token
- Storage: Cloudinary/S3 (optional)

Method	Endpoint	Chức năng	Role	Request chính	Response
POST	/api/auth/register	Đăng ký tài khoản	Khách hàng	email, password, fullName, phone	JWT access token + refresh token
POST	/api/auth/login	Đăng nhập	Khách hàng/Nhân viên/Admin	email, password	Access token + refresh token
POST	/api/auth/refresh-token	Làm mới access token	Mọi user	refreshToken	Access token mới
POST	/api/auth/logout	Đăng xuất	Mọi user	refreshToken	Invalidate refresh token
POST	/api/auth/forgot-password	Gửi OTP reset password	Mọi user	email	OTP reset
POST	/api/auth/reset-password	Đổi mật khẩu bằng OTP	Mọi user	otp, newPassword	Success message
GET	/api/users/me	Lấy profile hiện tại	User	JWT	Thông tin user
PUT	/api/users/me	Cập nhật profile	User	fullName, phone, vehicleInfo	Profile updated
GET	/api/carwash/services	Danh sách gói rửa xe	Public	-	Danh sách package
POST	/api/carwash/services	Tạo gói rửa	Admin	name, price, duration	Service created
PUT	/api/carwash/services/{id}	Cập nhật gói rửa	Admin	name, price	Updated
DELETE	/api/carwash/services/{id}	Xóa gói rửa	Admin	serviceId	Deleted
GET	/api/branches	Danh sách chi nhánh	Public	city	Danh sách branch
POST	/api/branches	Tạo chi nhánh	Admin	name, address	Created
GET	/api/machines	Danh sách máy rửa	Admin/Staff	branchId	Machine list
POST	/api/machines	Thêm máy rửa	Admin	machineCode, status	Created
PUT	/api/machines/{id}/status	Cập nhật trạng thái máy	Staff/Admin	active/maintenance	Updated
GET	/api/slots	Lấy slot trống	Khách hàng	date, branchId	Available slots
POST	/api/bookings	Đặt lịch rửa xe	Khách hàng	serviceId, slotId, vehicleType	Booking created
GET	/api/bookings	Danh sách booking	Khách hàng/Admin	status	Booking list
GET	/api/bookings/{id}	Chi tiết booking	Khách hàng/Admin	bookingId	Booking detail
PUT	/api/bookings/{id}/cancel	Hủy lịch	Khách hàng	reason	Canceled
PUT	/api/bookings/{id}/reschedule	Đổi lịch	Khách hàng	newSlotId	Rescheduled
PUT	/api/bookings/{id}/checkin	Check-in khi tới	Staff	bookingId	Checked-in
PUT	/api/bookings/{id}/start	Bắt đầu rửa	Staff/System	bookingId	Processing
PUT	/api/bookings/{id}/complete	Hoàn thành rửa xe	Staff/System	bookingId	Completed
GET	/api/payments/methods	Danh sách phương thức thanh toán	Khách hàng	-	Cash/VNPay/Momo
POST	/api/payments/create	Tạo payment	Khách hàng	bookingId, method	Payment url/status
POST	/api/payments/webhook	Webhook từ cổng thanh toán	System	gateway payload	Payment updated
GET	/api/payments/history	Lịch sử thanh toán	Khách hàng	JWT	Payment history
GET	/api/loyalty/me	Thông tin thành viên	Khách hàng	JWT	Tier + points
POST	/api/loyalty/earn	Cộng điểm	System/Admin	bookingId	Points updated
POST	/api/loyalty/redeem	Đổi điểm	Khách hàng	rewardId	Reward redeemed
GET	/api/loyalty/rewards	Danh sách quà	Khách hàng	-	Reward list
POST	/api/reviews	Đánh giá dịch vụ	Khách hàng	rating, comment	Review created
GET	/api/reviews/service/{id}	Review theo dịch vụ	Public	serviceId	Review list
GET	/api/dashboard/admin	Dashboard admin	Admin	JWT	Revenue/stats
GET	/api/dashboard/staff	Dashboard nhân viên	Staff	JWT	Today's bookings
GET	/api/reports/revenue	Báo cáo doanh thu	Admin	fromDate,toDate	Revenue data
GET	/api/reports/top-services	Top dịch vụ	Admin	month	Service ranking
GET	/api/notifications	Danh sách thông báo	User	JWT	Notification list
POST	/api/notifications/send	Gửi thông báo	Admin/System	message	Sent
GET	/api/coupons	Danh sách coupon	Khách hàng	-	Coupon list
POST	/api/coupons	Tạo coupon	Admin	code, discount	Coupon created
POST	/api/coupons/apply	Áp coupon	Khách hàng	bookingId, code	Discount result
Flow đề xuất cho nhóm 5 người
1. Member A: Authentication + User + JWT + Refresh Token
2. Member B: Booking + Slot + Scheduling
3. Member C: Loyalty + Coupon + Review
4. Member D: Payment + Notification
5. Member E: Admin Dashboard + Reports + Machine Management
Database Tables gợi ý
- users
- refresh_tokens
- roles
- services
- branches
- machines
- slots
- bookings
- payments
- loyalty_points
- rewards
- coupons
- reviews
- notifications
Scope thực tế cho 10 tuần

NÊN LÀM:
- JWT Auth
- Booking
- Slot thời gian
- Loyalty point
- Coupon
- Payment giả lập hoặc sandbox
- Dashboard cơ bản
- Notification email đơn giản

KHÔNG NÊN LÀM:
- AI camera recognition thật
- IoT hardware thật
- Realtime microservice phức tạp
- Kubernetes
- Multi-tenant architecture

