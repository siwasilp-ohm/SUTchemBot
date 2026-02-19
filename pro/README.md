# Pro Admin Suite (PHP + MySQL + AR + Barcode)

โฟลเดอร์ `pro/` เป็นโมดูลใหม่สำหรับ workflow ระดับ production ที่รองรับบทบาท:
- Admin
- CEO
- Lab Manager
- User

## ความสามารถหลัก
- Login + role-aware dashboard
- สิทธิ์ตามบทบาท (RBAC เบื้องต้น)
- Chemical list พร้อมสถานะคงเหลือ
- Borrow/Return transaction flow (มี validation + row lock)
- Recent transaction timeline
- AR 3D viewer (Google `<model-viewer>` + WebXR) พร้อม overlay ชื่อสาร/เจ้าของ/คงเหลือ
- Barcode label page สำหรับ reference และใช้ทำธุรกรรม (พร้อมปุ่ม print)
- CSRF protection สำหรับฟอร์มสำคัญ

## Quick Start
1. รันสคีม่า `pro/sql/pro_schema.sql`
2. ตั้งค่า DB ผ่าน env (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`) หรือใช้ค่า default ใน `pro/includes/config.php`
3. เปิด `pro/index.php`
4. Login demo ด้วย user ใดก็ได้: `admin`, `ceo`, `labmanager`, `user` และรหัส `Password123!`

## โครงสร้าง
- `includes/` config/auth/layout/db
- `pages/` login/dashboard/chemicals/barcodes/ar-viewer
- `assets/` modern UI style + JsBarcode bootstrap script
- `sql/` schema + seed

> โค้ดถูกออกแบบให้เชื่อมฐานข้อมูล MySQL จริง และสามารถขยายต่อเข้า API เดิมของระบบหลักได้
