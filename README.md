# เว็บแอปพลิเคชันระบบจองสินค้า (เสื้อ) ออนไลน์ (CMTC Shirt Pre-Order System)

ระบบสั่งจองเสื้อกิจกรรมและเสื้อรุ่นออนไลน์ พัฒนาด้วยภาษา **PHP 7.4 (Native Clean Architecture)** ร่วมกับฐานข้อมูล **MariaDB / MySQL** ออกแบบ Front-end สไตล์ **Minimalist Modern, Clean UI, Mobile-First Responsive**

---

## 🌟 จุดเด่นและฟังก์ชันสำคัญของระบบ

1. **ระบบสินค้า & ไซส์ยืดหยุ่น (Products & Flexible Variants)**
   - จัดการเสื้อได้หลายแบบ พร้อมแกลเลอรีรูปภาพ
   - แยกราคาและสต็อกอิสระตามขนาดไซต์ (S, M, L, XL, 2XL, 3XL)
   - แสดงตารางขนาดรอบอก-ความยาวมาตรฐาน (Size Chart)
2. **ระบบรอบการสั่งจอง (Booking Batches/Rounds)**
   - กำหนดช่วงเวลาเปิด-ปิดรับจอง (Start Date - End Date) หรือเลือกแบบ **"ไม่จำกัด"** (เปิดรับจองต่อเนื่องตลอดเวลา ไม่มีกำหนดปิด)
   - ดูรายงานและสถิติตัดสต็อกแยกเป็นรายรอบได้
3. **การล็อกสต็อกชั่วคราว & คืนสต็อกอัตโนมัติ (Cart Timeout & Stock Hold)**
   - ใช้ **ACID Database Transactions** ร่วมกับ **Row Locking (`SELECT ... FOR UPDATE`)** ป้องกันปัญหา Race Condition
   - เมื่อจองสำเร็จ ระบบล็อกสต็อกชั่วคราว (เช่น 30 นาที)
   - สคริปต์ Auto-cleanup ยกเลิกคำสั่งซื้อที่ค้างชำระและคืนสต็อกอัตโนมัติ (รองรับ Cronjob)
4. **PromptPay Dynamic QR Code (EMVCo Standard)**
   - สร้าง QR Code พร้อมเพย์แบบระบุยอดเงินสุทธิอัตโนมัติ ตามมาตรฐานธนาคารแห่งประเทศไทย (BOT)
   - แสดงตัวเลขนับถอยหลัง (Live Countdown Timer)
   - แนบสลิปพร้อมระบุวัน-เวลาโอนเงิน และพรีวิวรูปภาพก่อนส่ง
5. **ระบบตรวจสอบสลิป (Admin Slip Verification)**
   - เจ้าหน้าที่ตรวจสอบภาพสลิป เทียบยอดเงินและเวลาโอน
   - ระบบตัดสต็อกจริงเมื่อกดยืนยัน (Approve) หรือคืนสต็อกเมื่อปฏิเสธ (Reject)
6. **ใบรับสินค้าและพิมพ์เอกสาร (Printable Voucher / Receipt)**
   - ลูกค้าที่ยืนยันแล้ว สามารถพิมพ์ใบรับของขนาด A4/A5 เพื่อนำมารับเสื้อหน้างาน
   - มาพร้อม QR Code และช่องลงชื่อรับ-จ่ายของ
7. **รายงานสรุปยอดตัดสต็อกส่งโรงงานผลิต (Factory Production Summary)**
   - แสดงตาราง Matrix สรุปยอดจองแยกตามแบบเสื้อและขนาดไซส์ (S, M, L, XL, ...)
   - พร้อมพิมพ์ใบสั่งผลิตส่งโรงงานตัดเย็บได้ทันที

---

## 🛠️ Tech Stack & Requirements

- **PHP:** 7.3 หรือ 7.4+ (เปิดใช้งาน extension `pdo_mysql`, `fileinfo`)
- **Database:** MariaDB 10.4+ หรือ MySQL 5.7+ (`utf8mb4_unicode_ci`)
- **Web Server:** Apache (รองรับ mod_rewrite) หรือ Nginx
- **Front-end:** HTML5, Tailwind CSS, Font Awesome 6, SweetAlert2, QRCode.js

---

## 📁 โครงสร้างโฟลเดอร์ของโปรเจกต์ (Project Structure)

```
shop.cmtc.ac.th/
├── config/
│   ├── database.php          # การเชื่อมต่อฐานข้อมูล PDO (Singleton Pattern)
│   └── settings.php          # จัดการการตั้งค่าระบบ และฟังก์ชัน base_url()
├── core/
│   ├── Auth.php              # ระบบตรวจสอบสิทธิ์ผู้ใช้ (RBAC: Superadmin / Admin)
│   ├── Csrf.php              # ระบบป้องกัน CSRF Token แบบสมบูรณ์
│   ├── PromptPayQR.php       # คลาสสร้าง EMVCo Payload สำหรับ PromptPay QR Code
│   ├── OrderManager.php      # จัดการคำสั่งซื้อ ตะกร้า สต็อก ล็อกสต็อก และคืนสต็อก
│   └── Helper.php            # ฟังก์ชันช่วยจัดรูปแบบเงิน วันที่ไทย อัปโหลดไฟล์ และ Badge
├── includes/
│   ├── header.php            # ส่วนหัวและแถบเมนูนำทาง (ลูกค้าระดับผู้ใช้)
│   ├── footer.php            # ส่วนท้ายเว็บไซต์และสคริปต์
│   ├── admin_header.php      # แถบเมนูนำทางสำหรับเจ้าหน้าที่ (Admin Layout)
│   └── admin_footer.php
├── admin/
│   ├── index.php             # แดชบอร์ดสรุปสถิติภาพรวม & รายการรอตรวจสลิปด่วน
│   ├── login.php             # หน้าเข้าสู่ระบบสำหรับเจ้าหน้าที่
│   ├── logout.php            # ออกจากระบบ
│   ├── orders.php            # รายการสั่งจอง ตรวจสอบสลิป อนุมัติ/ปฏิเสธ
│   ├── verify_payment.php    # API ปลายทางสำหรับ อนุมัติ/ปฏิเสธสลิป
│   ├── summary.php           # รายงานสรุปยอดตัดสต็อกแยกตามไซส์ส่งโรงงานผลิต
│   ├── products.php          # จัดการข้อมูลแบบเสื้อ รูปภาพ ราคา ไซส์ และสต็อก
│   ├── rounds.php            # จัดการรอบการจอง (เปิด/ปิด/กำหนดเวลา)
│   ├── admins.php            # จัดการบัญชีผู้ดูแลระบบ (Superadmin Only)
│   └── settings.php          # กำหนดค่าระบบ (พร้อมเพย์, เวลาล็อกสต็อก, ค่าจัดส่ง)
├── assets/
│   ├── css/
│   │   └── custom.css        # ฟอนต์ Prompt/Inter, สไตล์การจัดวาง, @media print
│   ├── js/
│   │   ├── app.js            # ตะกร้าสินค้า, ตัวนับถอยหลัง, SweetAlert toast, พรีวิวภาพ
│   │   └── qrcode.min.js     # ไลบรารีเรนเดอร์ QR Code ออฟไลน์ความคมชัดสูง
│   └── uploads/
│       ├── products/         # รูปภาพสินค้าเสื้อ
│       └── slips/            # ภาพสลิปการโอนเงิน (Secure Hashed Filename)
├── cron/
│   └── cron_cleanup.php      # สคริปต์ Auto-cleanup คำสั่งซื้อหมดเวลาชำระเงิน
├── database.sql              # สคริปต์ฐานข้อมูล MariaDB พร้อม Seed Data
├── index.php                 # หน้าร้านหลัก รายการเสื้อ และรอบการจองปัจจุบัน
├── product_detail.php        # หน้ารายละเอียดสินค้า เลือกไซส์ ดูสต็อก และเพิ่มลงตะกร้า
├── cart.php                  # หน้าตะกร้าสินค้า และฟอร์มกรอกข้อมูลสั่งจอง (รับเอง/จัดส่ง)
├── checkout_process.php      # ประมวลผลคำสั่งจองพร้อมล็อกสต็อก (Transaction)
├── payment.php               # แสดง Dynamic PromptPay QR Code, ตัวนับเวลา, แนบสลิป
├── upload_slip_process.php   # บันทึกสลิปและปรับสถานะเป็น "รอตรวจสอบสลิป"
├── track.php                 # ตรวจสอบสถานะการจองด้วยรหัสคำสั่งซื้อ หรือ เบอร์โทรศัพท์
├── voucher.php               # ใบรับสินค้าขนาด A4/A5 สำหรับพิมพ์นำมารับเสื้อหน้างาน
├── api.php                   # AJAX API สำหรับปรับจำนวนตะกร้าสินค้า
└── test_workflow.php         # สคริปต์ทดสอบระบบอัตโนมัติ End-to-End
```

---

## 🚀 ขั้นตอนการติดตั้งและเริ่มต้นใช้งาน (Installation)

### 1. ติดตั้งฐานข้อมูล (Database Setup)
นำเข้าไฟล์ `database.sql` ไปยัง MariaDB / MySQL:
```bash
mysql -u root -p db_shirt_booking < database.sql
```
หรือเปิดผ่าน phpMyAdmin แล้วสร้างฐานข้อมูลชื่อ `db_shirt_booking` จากนั้น Import ไฟล์ `database.sql`

### 2. ตั้งค่าการเชื่อมต่อฐานข้อมูล
ตรวจสอบการตั้งค่าในไฟล์ [config/database.php](file:///Applications/XAMPP/xamppfiles/htdocs/antigravity/shop.cmtc.ac.th/config/database.php):
```php
private $host = '127.0.0.1';
private $db_name = 'db_shirt_booking';
private $username = 'root';
private $password = '';
```

### 3. ข้อมูลบัญชีผู้ดูแลระบบเริ่มต้น (Default Accounts)
| บทบาท (Role) | บัญชีผู้ใช้ (Username) | รหัสผ่าน (Password) |
|---|---|---|
| **Superadmin** | `superadmin` | `admin1234` |
| **Admin** | `admin` | `admin1234` |

---

## ⏰ การตั้งค่าระบบ Auto-cleanup คำสั่งซื้อหมดเวลา (Cron Job)

ระบบมีกลไก Passive Cleanup ทำงานทุกครั้งที่มีผู้เข้าชมเว็บ แต่หากต้องการให้ตัดรอบสต็อกแม่นยำระดับนาที สามารถตั้ง Cron Job ได้ดังนี้:

### รันผ่านระบบ Crontab ของเซิร์ฟเวอร์ (ทุก 5 นาที):
```bash
*/5 * * * * /usr/bin/php /Applications/XAMPP/xamppfiles/htdocs/antigravity/shop.cmtc.ac.th/cron/cron_cleanup.php > /dev/null 2>&1
```

หรือเรียกผ่าน URL Web Cron:
```
http://localhost/antigravity/shop.cmtc.ac.th/cron/cron_cleanup.php?key=cmtc_cron_safe_key_2026
```

---

## 🛡️ มาตรการด้านความปลอดภัย (Security Implementations)

1. **SQL Injection:** ใช้ PDO Prepared Statements 100% ในทุกคำสั่งคิวรี
2. **Race Conditions:** ใช้ `SELECT ... FOR UPDATE` ภายใต้ Database Transaction ป้องกันการแย่งซื้อสินค้าชิ้นสุดท้ายพร้อมกัน
3. **CSRF Protection:** ตรวจสอบ CSRF Token ทุกฟอร์มที่ส่งด้วยเมธอด POST
4. **Secure Slip Upload:** ตรวจสอบ Real MIME-Type (อนุญาตเฉพาะ JPG, PNG, WEBP), จำกัดขนาดไฟล์ไม่เกิน 5MB, เปลี่ยนชื่อไฟล์เป็นสุ่มแฮชแบบไม่ซ้ำกัน
5. **Password Security:** ใช้ `password_hash()` แบบ BCRYPT อัลกอริทึมมาตรฐาน
