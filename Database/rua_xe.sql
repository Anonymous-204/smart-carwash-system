-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th6 11, 2026 lúc 07:36 AM
-- Phiên bản máy phục vụ: 10.4.27-MariaDB
-- Phiên bản PHP: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `rua_xe`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `role`) VALUES
(1, 'admin', 'e10adc3949ba59abbe56e057f20f883e', 'admin');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `rua_xe_dat_lich`
--

CREATE TABLE `rua_xe_dat_lich` (
  `id` int(11) NOT NULL,
  `ten_khach_hang` varchar(255) NOT NULL,
  `so_dien_thoai` varchar(20) NOT NULL,
  `bien_so_xe` varchar(20) NOT NULL,
  `loai_xe` varchar(50) NOT NULL,
  `goi_id` int(11) NOT NULL,
  `ngay_dat` date NOT NULL,
  `gio_dat` time NOT NULL,
  `tong_tien` int(11) NOT NULL,
  `diem_nhan_duoc` int(11) DEFAULT 0,
  `trang_thai` varchar(50) DEFAULT 'Chờ duyệt',
  `ngay_tao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `rua_xe_dat_lich`
--

INSERT INTO `rua_xe_dat_lich` (`id`, `ten_khach_hang`, `so_dien_thoai`, `bien_so_xe`, `loai_xe`, `goi_id`, `ngay_dat`, `gio_dat`, `tong_tien`, `diem_nhan_duoc`, `trang_thai`, `ngay_tao`) VALUES
(1, 'Nguyễn Văn Tuấn', '0912345678', '30A-123.45', 'Sedan', 1, '2026-06-06', '09:00:00', 80000, 10, 'Đã hoàn thành', '2026-06-06 08:30:00'),
(2, 'Trần Thị Hương', '0987654321', '29D-987.65', 'SUV', 2, '2026-06-07', '10:30:00', 150000, 20, 'Đã hoàn thành', '2026-06-07 10:00:00'),
(3, 'Phạm Minh Đức', '0901234567', '30H-888.88', 'Sedan', 4, '2026-06-08', '14:00:00', 1200000, 150, 'Đã hoàn thành', '2026-06-08 13:15:00'),
(4, 'Lê Hoàng Nam', '0933334444', '51G-555.55', 'Bán tải', 2, '2026-06-09', '11:00:00', 150000, 20, 'Đã hoàn thành', '2026-06-09 10:30:00'),
(5, 'Vũ Anh Tuấn', '0955556666', '30E-222.33', 'Xe máy', 1, '2026-06-10', '08:00:00', 80000, 10, 'Đã hoàn thành', '2026-06-10 07:45:00'),
(6, 'Nguyễn Thị Mai', '0966667777', '29A-444.55', 'Sedan', 3, '2026-06-11', '09:30:00', 450000, 50, 'Đã duyệt', '2026-06-11 08:15:00'),
(7, 'Bùi Quang Đạt', '0977778888', '30K-777.89', 'SUV', 2, '2026-06-11', '15:00:00', 150000, 20, 'Chờ duyệt', '2026-06-11 11:30:00'),
(8, 'Phạm Quốc Khánh', '0988889999', '30F-999.99', 'Sedan', 1, '2026-06-11', '16:30:00', 80000, 10, 'Chờ duyệt', '2026-06-11 14:00:00');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `rua_xe_dich_vu`
--

CREATE TABLE `rua_xe_dich_vu` (
  `id` int(11) NOT NULL,
  `ten_goi` varchar(255) NOT NULL,
  `gia` int(11) NOT NULL,
  `thoi_gian` int(11) NOT NULL,
  `mo_ta` text DEFAULT NULL,
  `trang_thai` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `rua_xe_dich_vu`
--

INSERT INTO `rua_xe_dich_vu` (`id`, `ten_goi`, `gia`, `thoi_gian`, `mo_ta`, `trang_thai`) VALUES
(1, 'Rửa tiêu chuẩn (Standard Wash)', 80000, 20, 'Rửa ngoài, xịt gầm, hút bụi cơ bản, lau khô.', 1),
(2, 'Rửa chuyên sâu (Premium Wash)', 150000, 35, 'Rửa ngoài, xịt gầm chi tiết, rửa khoang máy cơ bản, hút bụi kỹ, dưỡng lốp bóng.', 1),
(3, 'Vệ sinh nội thất & Khử mùi', 450000, 90, 'Dọn sạch nội thất bằng hơi nước nóng, khử mùi diệt khuẩn chuyên sâu.', 1),
(4, 'Đánh bóng & Phủ Nano Ceramic', 1200000, 180, 'Đánh bóng hiệu chỉnh bề mặt sơn, phủ lớp bảo vệ Nano Ceramic siêu bóng.', 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `rua_xe_thanh_vien`
--

CREATE TABLE `rua_xe_thanh_vien` (
  `id` int(11) NOT NULL,
  `ten_thanh_vien` varchar(255) NOT NULL,
  `so_dien_thoai` varchar(20) NOT NULL,
  `bien_so_xe` varchar(20) DEFAULT NULL,
  `hang_thanh_vien` varchar(50) DEFAULT 'Đồng',
  `diem_tich_luy` int(11) DEFAULT 0,
  `ngay_dang_ky` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `rua_xe_thanh_vien`
--

INSERT INTO `rua_xe_thanh_vien` (`id`, `ten_thanh_vien`, `so_dien_thoai`, `bien_so_xe`, `hang_thanh_vien`, `diem_tich_luy`, `ngay_dang_ky`) VALUES
(1, 'Nguyễn Văn Tuấn', '0912345678', '30A-123.45', 'Vàng', 350, '2026-05-10 10:00:00'),
(2, 'Trần Thị Hương', '0987654321', '29D-987.65', 'Bạc', 180, '2026-05-12 14:30:00'),
(3, 'Phạm Minh Đức', '0901234567', '30H-888.88', 'Kim cương', 1200, '2026-04-20 09:15:00'),
(4, 'Lê Hoàng Nam', '0933334444', '51G-555.55', 'Đồng', 80, '2026-06-01 16:45:00');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Chỉ mục cho bảng `rua_xe_dat_lich`
--
ALTER TABLE `rua_xe_dat_lich`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `rua_xe_dich_vu`
--
ALTER TABLE `rua_xe_dich_vu`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `rua_xe_thanh_vien`
--
ALTER TABLE `rua_xe_thanh_vien`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `so_dien_thoai` (`so_dien_thoai`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `rua_xe_dat_lich`
--
ALTER TABLE `rua_xe_dat_lich`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT cho bảng `rua_xe_dich_vu`
--
ALTER TABLE `rua_xe_dich_vu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `rua_xe_thanh_vien`
--
ALTER TABLE `rua_xe_thanh_vien`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
