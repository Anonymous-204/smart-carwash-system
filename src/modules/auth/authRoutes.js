const express = require('express');
const router = express.Router();
// Import chính xác file authControllers.js cùng thư mục
const authController = require('./authControllers'); 

router.post('/register', authController.register);
router.post('/login', authController.login);
router.post('/change-password', authController.changePassword);
router.post('/refresh-token', authController.refresh);
router.post('/logout', authController.logout);
module.exports = router;