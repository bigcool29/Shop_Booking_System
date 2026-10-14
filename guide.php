<?php
$pageTitle = "คู่มือและขั้นตอนการสั่งจอง - CMTC Official Store";
$currentPage = 'guide';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Guide Hero Banner -->
<div class="relative bg-gradient-to-br from-blue-900 via-indigo-900 to-slate-900 text-white overflow-hidden py-12 md:py-16">
    <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#38bdf8_1px,transparent_1px)] [background-size:16px_16px]"></div>
    
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumbs -->
        <nav class="flex items-center space-x-2 text-xs text-slate-300 mb-4 font-medium">
            <a href="<?= base_url() ?>" class="hover:text-white transition flex items-center">
                <i class="fas fa-home mr-1"></i> หน้าหลัก
            </a>
            <span class="text-slate-500">/</span>
            <span class="text-amber-300 font-bold">คู่มือและขั้นตอนการสั่งจอง</span>
        </nav>

        <div class="max-w-3xl">
            <div class="inline-flex items-center space-x-2 px-3.5 py-1 rounded-full bg-blue-500/20 border border-blue-400/30 text-blue-200 text-xs font-bold mb-4 backdrop-blur-sm">
                <i class="fas fa-book-reader text-amber-300"></i>
                <span>คู่มือการใช้งาน • CMTC BOOKING GUIDE</span>
            </div>
            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight leading-tight mb-4">
                คู่มือและขั้นตอนการสั่งจองสินค้า
            </h1>
            <p class="text-slate-300 text-sm sm:text-base leading-relaxed mb-6">
                ระบบสั่งจองเสื้อกิจกรรม เสื้อรุ่น และของที่ระลึกออนไลน์ วิทยาลัยเทคนิคเชียงใหม่ เพื่อความสะดวกรวดเร็ว เป็นระบบ และปลอดภัยใน 4 ขั้นตอนง่ายๆ
            </p>
            <div class="flex flex-wrap items-center gap-3">
                <a href="<?= base_url() ?>" class="inline-flex items-center px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs sm:text-sm shadow-md transition active:scale-95">
                    <i class="fas fa-store mr-2"></i> ไปยังหน้ารายการสั่งจอง
                </a>
                <a href="<?= base_url('track.php') ?>" class="inline-flex items-center px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs sm:text-sm backdrop-blur-sm border border-white/20 transition">
                    <i class="fas fa-search mr-2 text-slate-300"></i> ตรวจสอบสถานะคำสั่งจอง
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Workflow Stepper Quick Bar -->
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-6 relative z-10">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-5 md:p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="flex items-center space-x-3.5">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-base flex-shrink-0 shadow-2xs">
                1
            </div>
            <div>
                <h4 class="text-xs font-bold text-slate-800">เลือกแบบและขนาดเสื้อ</h4>
                <p class="text-[11px] text-slate-500">เลือกแบบ ไซส์ และจำนวนที่ต้องการ</p>
            </div>
        </div>

        <div class="flex items-center space-x-3.5">
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-base flex-shrink-0 shadow-2xs">
                2
            </div>
            <div>
                <h4 class="text-xs font-bold text-slate-800">กรอกข้อมูลผู้สั่งจอง</h4>
                <p class="text-[11px] text-slate-500">เบอร์โทรศัพท์ และเลือกวิธีรับสินค้า</p>
            </div>
        </div>

        <div class="flex items-center space-x-3.5">
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-base flex-shrink-0 shadow-2xs">
                3
            </div>
            <div>
                <h4 class="text-xs font-bold text-slate-800">สแกนจ่าย PromptPay</h4>
                <p class="text-[11px] text-slate-500">QR Code พร้อมยอดเงิน + แนบสลิป</p>
            </div>
        </div>

        <div class="flex items-center space-x-3.5">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-base flex-shrink-0 shadow-2xs">
                4
            </div>
            <div>
                <h4 class="text-xs font-bold text-slate-800">รับใบยืนยันการจอง</h4>
                <p class="text-[11px] text-slate-500">พิมพ์หรือแคปหน้าจอเพื่อรับเสื้อ</p>
            </div>
        </div>
    </div>
</div>

<!-- Main Guide Content -->
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

    <!-- 4 Detailed Steps Cards -->
    <div class="mb-14">
        <div class="text-center max-w-2xl mx-auto mb-10">
            <span class="inline-flex items-center space-x-1.5 px-3 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200/80 text-xs font-bold mb-3 shadow-2xs">
                <i class="fas fa-layer-group text-blue-600"></i>
                <span>4 ขั้นตอนการสั่งจองอย่างละเอียด</span>
            </span>
            <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                ขั้นตอนการสั่งจองสินค้าแบบทีละสเต็ป
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 mt-2">
                เข้าใจง่าย ชัดเจน พร้อมคำแนะนำในการสั่งจองสินค้าได้อย่างถูกต้อง
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            
            <!-- Step 1 Card -->
            <div class="bg-white rounded-3xl border border-slate-200/90 shadow-sm hover:shadow-md transition duration-300 p-6 flex flex-col justify-between group hover:border-blue-400">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center font-bold text-xl shadow-md shadow-blue-500/20 group-hover:scale-105 transition">
                            <i class="fas fa-tshirt"></i>
                        </div>
                        <span class="text-[11px] font-black px-2.5 py-1 rounded-xl bg-blue-50 text-blue-700 border border-blue-200/60 font-mono">
                            STEP 01
                        </span>
                    </div>
                    <h3 class="text-base font-black text-slate-900 mb-1.5 group-hover:text-blue-600 transition">
                        1. เลือกสินค้า & ระบุไซส์
                    </h3>
                    <p class="text-xs text-slate-500 mb-4 leading-relaxed">
                        เลือกแบบเสื้อหรือของที่ระลึกที่ต้องการ ตรวจสอบขนาดรอบอกและความยาวเสื้อ แล้วกดเพิ่มลงในตะกร้า
                    </p>

                    <ul class="space-y-2.5 text-xs text-slate-600 mb-5">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-blue-600 mt-0.5 mr-2 flex-shrink-0 text-[11px]"></i>
                            <span>ดูภาพสินค้าจริงและรายละเอียดเนื้อผ้า</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-blue-600 mt-0.5 mr-2 flex-shrink-0 text-[11px]"></i>
                            <span>ตรวจสอบ <strong>ตารางไซส์ (Size Chart)</strong> เพื่อเทียบขนาดอกจริง</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-blue-600 mt-0.5 mr-2 flex-shrink-0 text-[11px]"></i>
                            <span>เลือกไซส์ ระบุจำนวน แล้วกด <strong>เพิ่มลงตะกร้า</strong></span>
                        </li>
                    </ul>
                </div>

                <div class="pt-3 border-t border-slate-100">
                    <button type="button" onclick="openSizeModal()" class="w-full text-center py-2.5 px-3 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-bold transition flex items-center justify-center">
                        <i class="fas fa-ruler-combined mr-1.5"></i> ดูตารางไซส์เสื้อ
                    </button>
                </div>
            </div>

            <!-- Step 2 Card -->
            <div class="bg-white rounded-3xl border border-slate-200/90 shadow-sm hover:shadow-md transition duration-300 p-6 flex flex-col justify-between group hover:border-indigo-400">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-600 text-white flex items-center justify-center font-bold text-xl shadow-md shadow-indigo-500/20 group-hover:scale-105 transition">
                            <i class="fas fa-user-edit"></i>
                        </div>
                        <span class="text-[11px] font-black px-2.5 py-1 rounded-xl bg-indigo-50 text-indigo-700 border border-indigo-200/60 font-mono">
                            STEP 02
                        </span>
                    </div>
                    <h3 class="text-base font-black text-slate-900 mb-1.5 group-hover:text-indigo-600 transition">
                        2. กรอกข้อมูล & วิธีรับสินค้า
                    </h3>
                    <p class="text-xs text-slate-500 mb-4 leading-relaxed">
                        เข้าสู่ตะกร้าสินค้า ระบุข้อมูลผู้สั่งจอง และเลือกวิธีการรับเสื้อที่สะดวก
                    </p>

                    <ul class="space-y-2.5 text-xs text-slate-600 mb-5">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-indigo-600 mt-0.5 mr-2 flex-shrink-0 text-[11px]"></i>
                            <span>กรอกชื่อ-นามสกุล, เบอร์โทร และแผนก/สถานศึกษา</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-indigo-600 mt-0.5 mr-2 flex-shrink-0 text-[11px]"></i>
                            <span><strong>รับด้วยตนเอง:</strong> ณ วิทยาลัยเทคนิคเชียงใหม่ (ไม่มีค่าส่ง)</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-indigo-600 mt-0.5 mr-2 flex-shrink-0 text-[11px]"></i>
                            <span><strong>จัดส่งพัสดุ:</strong> ระบุที่อยู่จัดส่ง ระบบจะคำนวณค่าส่งอัตโนมัติ</span>
                        </li>
                    </ul>
                </div>

                <div class="pt-3 border-t border-slate-100">
                    <a href="<?= base_url('cart.php') ?>" class="w-full text-center py-2.5 px-3 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold transition flex items-center justify-center">
                        <i class="fas fa-shopping-bag mr-1.5"></i> ไปที่ตะกร้าสินค้า
                    </a>
                </div>
            </div>

            <!-- Step 3 Card -->
            <div class="bg-white rounded-3xl border border-slate-200/90 shadow-sm hover:shadow-md transition duration-300 p-6 flex flex-col justify-between group hover:border-amber-400">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-amber-500 to-orange-600 text-white flex items-center justify-center font-bold text-xl shadow-md shadow-amber-500/20 group-hover:scale-105 transition">
                            <i class="fas fa-qrcode"></i>
                        </div>
                        <span class="text-[11px] font-black px-2.5 py-1 rounded-xl bg-amber-50 text-amber-700 border border-amber-200/60 font-mono">
                            STEP 03
                        </span>
                    </div>
                    <h3 class="text-base font-black text-slate-900 mb-1.5 group-hover:text-amber-600 transition">
                        3. สแกนจ่าย & แนบสลิป
                    </h3>
                    <p class="text-xs text-slate-500 mb-4 leading-relaxed">
                        สแกนชำระเงินผ่าน PromptPay Dynamic QR ตามยอดจริง และอัปโหลดรูปสลิปหลักฐาน
                    </p>

                    <ul class="space-y-2.5 text-xs text-slate-600 mb-5">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-amber-600 mt-0.5 mr-2 flex-shrink-0 text-[11px]"></i>
                            <span>สแกน <strong>PromptPay Dynamic QR</strong> ด้วยแอปธนาคารใดก็ได้</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-amber-600 mt-0.5 mr-2 flex-shrink-0 text-[11px]"></i>
                            <span>ระบบมี <strong>เวลานับถอยหลังล็อกสต็อก 30 นาที</strong></span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-amber-600 mt-0.5 mr-2 flex-shrink-0 text-[11px]"></i>
                            <span>แนบไฟล์สลิป (JPG, PNG, WEBP) และกดยืนยันส่งข้อมูล</span>
                        </li>
                    </ul>
                </div>

                <div class="pt-3 border-t border-slate-100">
                    <div class="py-2.5 px-3 rounded-xl bg-amber-50 text-amber-800 text-[11px] font-bold text-center">
                        <i class="fas fa-shield-alt mr-1"></i> จ่ายตรงบัญชีวิทยาลัยฯ ปลอดภัย 100%
                    </div>
                </div>
            </div>

            <!-- Step 4 Card -->
            <div class="bg-white rounded-3xl border border-slate-200/90 shadow-sm hover:shadow-md transition duration-300 p-6 flex flex-col justify-between group hover:border-emerald-400">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-600 text-white flex items-center justify-center font-bold text-xl shadow-md shadow-emerald-500/20 group-hover:scale-105 transition">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <span class="text-[11px] font-black px-2.5 py-1 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200/60 font-mono">
                            STEP 04
                        </span>
                    </div>
                    <h3 class="text-base font-black text-slate-900 mb-1.5 group-hover:text-emerald-600 transition">
                        4. รับใบจอง & รอรับสินค้า
                    </h3>
                    <p class="text-xs text-slate-500 mb-4 leading-relaxed">
                        บันทึกหลักฐานคำสั่งจอง และตรวจสอบสถานะความคืบหน้าได้ตลอด 24 ชั่วโมง
                    </p>

                    <ul class="space-y-2.5 text-xs text-slate-600 mb-5">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-emerald-600 mt-0.5 mr-2 flex-shrink-0 text-[11px]"></i>
                            <span>รับรหัสคำสั่งจอง เช่น <strong>CMTC-261010-XXXX</strong></span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-emerald-600 mt-0.5 mr-2 flex-shrink-0 text-[11px]"></i>
                            <span>สามารถพิมพ์ใบเสร็จ หรือแคปภาพหน้าจอไว้เป็นหลักฐาน</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-emerald-600 mt-0.5 mr-2 flex-shrink-0 text-[11px]"></i>
                            <span>ติดตามสถานะได้ทันทีที่เมนู <strong>"ตรวจสอบสถานะคำสั่งจอง"</strong></span>
                        </li>
                    </ul>
                </div>

                <div class="pt-3 border-t border-slate-100">
                    <a href="<?= base_url('track.php') ?>" class="w-full text-center py-2.5 px-3 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-bold transition flex items-center justify-center">
                        <i class="fas fa-search mr-1.5"></i> ตรวจสอบสถานะคำสั่งจอง
                    </a>
                </div>
            </div>

        </div>
    </div>

    <!-- Size Chart Table Card on Page -->
    <div class="bg-white rounded-3xl border border-slate-200/90 shadow-sm p-6 sm:p-8 mb-12">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-100 mb-6 gap-3">
            <div class="flex items-center space-x-3.5">
                <div class="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-lg flex-shrink-0">
                    <i class="fas fa-ruler-combined"></i>
                </div>
                <div>
                    <h3 class="font-black text-slate-900 text-base sm:text-lg tracking-tight">ตารางขนาดเสื้อมาตรฐาน (Size Chart Reference)</h3>
                    <p class="text-xs text-slate-500">วัดขนาดรอบอกและความยาวเสื้อเป็นหน่วยนิ้ว (Inches) แนะนำวัดจากเสื้อที่ใส่พอดีตัว</p>
                </div>
            </div>
            <span class="text-xs bg-slate-100 text-slate-600 font-bold px-3 py-1 rounded-xl self-start sm:self-auto">
                หน่วย: นิ้ว (Inches)
            </span>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-200/80 mb-4">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-3">ขนาด (Size)</th>
                        <th class="px-5 py-3">รอบอก (Chest)</th>
                        <th class="px-5 py-3">ความยาว (Length)</th>
                        <th class="px-5 py-3">คำแนะนำการสวมใส่</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-600 font-medium">
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="px-5 py-3 font-black text-slate-900 text-sm">S</td>
                        <td class="px-5 py-3 font-semibold text-blue-700">34 - 36 นิ้ว</td>
                        <td class="px-5 py-3">25 - 26 นิ้ว</td>
                        <td class="px-5 py-3 text-slate-500">เหมาะสำหรับผู้ที่มีรูปร่างเล็ก</td>
                    </tr>
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="px-5 py-3 font-black text-slate-900 text-sm">M</td>
                        <td class="px-5 py-3 font-semibold text-blue-700">38 นิ้ว</td>
                        <td class="px-5 py-3">27 นิ้ว</td>
                        <td class="px-5 py-3 text-slate-500">ขนาดมาตรฐานยอดนิยม</td>
                    </tr>
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="px-5 py-3 font-black text-slate-900 text-sm">L</td>
                        <td class="px-5 py-3 font-semibold text-blue-700">40 - 42 นิ้ว</td>
                        <td class="px-5 py-3">28 - 29 นิ้ว</td>
                        <td class="px-5 py-3 text-slate-500">สวมใส่สบาย พอดีตัว</td>
                    </tr>
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="px-5 py-3 font-black text-slate-900 text-sm">XL</td>
                        <td class="px-5 py-3 font-semibold text-blue-700">42 - 44 นิ้ว</td>
                        <td class="px-5 py-3">29 - 30 นิ้ว</td>
                        <td class="px-5 py-3 text-slate-500">ขนาดใหญ่สำหรับคนรูปร่างท้วมหรือชอบหลวม</td>
                    </tr>
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="px-5 py-3 font-black text-slate-900 text-sm">2XL</td>
                        <td class="px-5 py-3 font-semibold text-blue-700">44 - 46 นิ้ว</td>
                        <td class="px-5 py-3">30 - 31 นิ้ว</td>
                        <td class="px-5 py-3 text-slate-500">ขนาดพิเศษ Oversize</td>
                    </tr>
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="px-5 py-3 font-black text-slate-900 text-sm">3XL</td>
                        <td class="px-5 py-3 font-semibold text-blue-700">46 - 48 นิ้ว</td>
                        <td class="px-5 py-3">31 - 32 นิ้ว</td>
                        <td class="px-5 py-3 text-slate-500">ขนาดใหญ่พิเศษ</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="text-[11px] text-slate-400 italic">
            * หมายเหตุ: ขนาดจริงอาจมีความคลาดเคลื่อนประมาณ +/- 0.5 นิ้ว เนื่องจากการตัดเย็บ
        </p>
    </div>

    <!-- Highlights & Feature Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
        <!-- Feature 1: Cart Timeout Protection -->
        <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-blue-950 rounded-3xl p-6 sm:p-7 text-white shadow-md flex flex-col justify-between relative overflow-hidden">
            <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-blue-600/10 rounded-full blur-2xl pointer-events-none"></div>
            <div>
                <div class="w-11 h-11 rounded-2xl bg-white/10 text-amber-300 flex items-center justify-center font-bold text-lg mb-4 backdrop-blur-sm border border-white/10">
                    <i class="fas fa-stopwatch"></i>
                </div>
                <h4 class="font-extrabold text-base text-white tracking-tight mb-2">
                    ระบบล็อกสต็อก 30 นาที (Cart Timeout)
                </h4>
                <p class="text-xs text-slate-300 leading-relaxed">
                    เมื่อท่านกดยืนยันคำสั่งจอง ระบบจะล็อกสต็อกสินค้าไว้ให้ท่านทันทีเป็นเวลา 30 นาที เพื่อให้ท่านมีเวลาสแกนจ่ายและแนบสลิปอย่างสบายใจ โดยไม่ถูกผู้อื่นแย่งสต็อก หากเกินกำหนดระบบจะคืนสต็อกให้ผู้อื่นอัตโนมัติ
                </p>
            </div>
            <div class="mt-5 pt-3.5 border-t border-white/10 text-xs text-amber-300 font-bold flex items-center">
                <i class="fas fa-shield-alt mr-2 text-sm"></i> ยุติธรรม คืนสต็อกโปร่งใส
            </div>
        </div>

        <!-- Feature 2: Return to Payment Support -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/90 shadow-sm flex flex-col justify-between hover:shadow-md transition">
            <div>
                <div class="w-11 h-11 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-lg mb-4 border border-amber-200/50">
                    <i class="fas fa-undo-alt"></i>
                </div>
                <h4 class="font-extrabold text-base text-slate-900 tracking-tight mb-2">
                    เผลอปิดหน้าจอ? กลับมาจ่ายได้ทันที
                </h4>
                <p class="text-xs text-slate-500 leading-relaxed">
                    ไม่ต้องกังวลหากอินเทอร์เน็ตหลุดหรือปิดหน้าจอไประหว่างชำระเงิน ระบบจะแสดงแถบเตือนสีส้มด้านบนพร้อมเวลานับถอยหลังที่เหลืออยู่ และปุ่มลัดนำทางกลับไปยังหน้ารายการสั่งจองเดิมเพื่อแนบสลิปได้จนกว่าจะหมดเวลา
                </p>
            </div>
            <div class="mt-5 pt-3.5 border-t border-slate-100 text-xs text-blue-600 font-bold flex items-center">
                <i class="fas fa-check-circle mr-2 text-sm"></i> ไม่ต้องทำรายการใหม่ตั้งแต่ต้น
            </div>
        </div>

        <!-- Feature 3: Official Verification & Contact -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/90 shadow-sm flex flex-col justify-between hover:shadow-md transition">
            <div>
                <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-lg mb-4 border border-emerald-200/50">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <h4 class="font-extrabold text-base text-slate-900 tracking-tight mb-2">
                    จุดรับสินค้าและการติดต่อเจ้าหน้าที่
                </h4>
                <p class="text-xs text-slate-500 leading-relaxed mb-2">
                    <strong>จุดรับเสื้อ:</strong> <?= nl2br(htmlspecialchars(get_setting('pickup_location', 'ตึกอำนวยการ แผนกวิชาช่างเทคนิคคอมพิวเตอร์ ชั้น 2'))) ?>
                </p>
                <p class="text-xs text-slate-600">
                    <i class="fas fa-phone-alt text-emerald-600 mr-1.5"></i> <strong>โทรศัพท์:</strong> <?= htmlspecialchars(get_setting('contact_phone', '053-217-708')) ?>
                </p>
            </div>
            <div class="mt-5 pt-3.5 border-t border-slate-100 text-xs text-emerald-700 font-bold flex items-center">
                <i class="fas fa-clock mr-2 text-sm"></i> ติดต่อได้ในเวลาราชการ (08:30 - 16:30 น.)
            </div>
        </div>
    </div>

    <!-- FAQ Accordion Block -->
    <div class="bg-slate-50/80 rounded-3xl border border-slate-200/90 p-6 sm:p-8 mb-12">
        <div class="flex items-center space-x-3 mb-6">
            <div class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center text-sm font-bold shadow-xs">
                <i class="fas fa-question-circle"></i>
            </div>
            <div>
                <h3 class="font-black text-slate-900 text-base sm:text-lg tracking-tight">คำถามที่พบบ่อยในการสั่งจอง (Frequently Asked Questions)</h3>
                <p class="text-xs text-slate-500">ข้อสงสัยที่พบบ่อยเกี่ยวกับการโอนเงิน การจัดส่ง และการรับสินค้า</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 text-xs">
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
                <div class="font-bold text-slate-900 mb-2 flex items-start">
                    <span class="text-blue-600 mr-2 font-black">Q1:</span>
                    <span>สั่งจองเสื้อหลายตัว/หลายไซส์ คิดค่าส่งอย่างไร?</span>
                </div>
                <p class="text-slate-500 text-[11px] leading-relaxed pl-5">
                    หากเลือกจัดส่งพัสดุ คิดค่าส่งเริ่มต้นชิ้นแรก <?= number_format((float)get_setting('shipping_base_fee', 50), 0) ?> บาท และชิ้นถัดไปบวกเพิ่มเพียง <?= number_format((float)get_setting('shipping_extra_fee', 10), 0) ?> บาท/ตัว แต่หากเลือก <strong>"รับด้วยตนเอง"</strong> ฟรี ไม่มีค่าใช้จ่าย
                </p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
                <div class="font-bold text-slate-900 mb-2 flex items-start">
                    <span class="text-amber-600 mr-2 font-black">Q2:</span>
                    <span>หากแนบสลิปผิด หรือสลิปไม่ชัดเจนต้องทำอย่างไร?</span>
                </div>
                <p class="text-slate-500 text-[11px] leading-relaxed pl-5">
                    หากเจ้าหน้าที่ตรวจสอบพบข้อผิดพลาด จะเปลี่ยนสถานะเป็น <strong>"สลิปไม่ถูกต้อง"</strong> โดยผู้สั่งจองสามารถเข้าไปที่หน้าชำระเงินเดิมเพื่อแนบสลิปใหม่ที่ถูกต้องได้ทันที
                </p>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-2xs">
                <div class="font-bold text-slate-900 mb-2 flex items-start">
                    <span class="text-emerald-600 mr-2 font-black">Q3:</span>
                    <span>สามารถให้เพื่อนหรือผู้แทนมารับเสื้อแทนได้ไหม?</span>
                </div>
                <p class="text-slate-500 text-[11px] leading-relaxed pl-5">
                    ได้ โดยผู้มารับแทนต้องแสดง <strong>รหัสคำสั่งจอง (Order Code)</strong> หรือภาพหน้าจอใบยืนยันการจอง พร้อมระบุชื่อ-นามสกุล และเบอร์โทรศัพท์ของผู้สั่งจองให้เจ้าหน้าที่ตรวจสอบ
                </p>
            </div>
        </div>
    </div>

    <!-- Bottom CTA Banner -->
    <div class="rounded-3xl bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-700 p-8 sm:p-10 text-white text-center shadow-lg relative overflow-hidden">
        <div class="max-w-2xl mx-auto relative z-10">
            <h3 class="text-2xl sm:text-3xl font-black mb-3 tracking-tight">
                พร้อมสั่งจองเสื้อกิจกรรมและของที่ระลึกแล้วหรือยัง?
            </h3>
            <p class="text-blue-100 text-xs sm:text-sm mb-6 leading-relaxed">
                เข้าชมแบบเสื้อรอบล่าสุด เลือกรหัสสินค้า และสั่งจองล่วงหน้าได้ทันทีก่อนหมดรอบ
            </p>
            <div class="flex flex-wrap justify-center gap-3">
                <a href="<?= base_url() ?>" class="px-6 py-3 rounded-2xl bg-white text-blue-700 font-extrabold text-xs sm:text-sm shadow-md hover:bg-blue-50 transition active:scale-95">
                    <i class="fas fa-shopping-bag mr-2"></i> สั่งจองสินค้าทันที
                </a>
                <a href="<?= base_url('track.php') ?>" class="px-5 py-3 rounded-2xl bg-blue-800/80 hover:bg-blue-800 text-white font-bold text-xs sm:text-sm transition border border-white/20">
                    <i class="fas fa-search mr-2"></i> ตรวจสอบสถานะคำสั่งจอง
                </a>
            </div>
        </div>
    </div>

</main>

<!-- Modal: Size Chart -->
<div id="sizeModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm hidden p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl relative">
        <button onclick="closeSizeModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 w-8 h-8 rounded-full flex items-center justify-center hover:bg-slate-100 transition">
            <i class="fas fa-times"></i>
        </button>
        <div class="flex items-center space-x-3 mb-4">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                <i class="fas fa-ruler-combined"></i>
            </div>
            <div>
                <h3 class="font-bold text-slate-900 text-base">ตารางขนาดเสื้อมาตรฐาน (Size Chart)</h3>
                <p class="text-xs text-slate-500">วัดขนาดรอบอกและความยาวเป็นหน่วยนิ้ว (Inches)</p>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-200 mb-4">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-50 text-slate-700 font-bold border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-2.5">ขนาด (Size)</th>
                        <th class="px-4 py-2.5">รอบอก (Chest)</th>
                        <th class="px-4 py-2.5">ความยาว (Length)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-600 font-medium">
                    <tr>
                        <td class="px-4 py-2.5 font-bold text-slate-900">S</td>
                        <td class="px-4 py-2.5">34 - 36 นิ้ว</td>
                        <td class="px-4 py-2.5">25 - 26 นิ้ว</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2.5 font-bold text-slate-900">M</td>
                        <td class="px-4 py-2.5">38 นิ้ว</td>
                        <td class="px-4 py-2.5">27 นิ้ว</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2.5 font-bold text-slate-900">L</td>
                        <td class="px-4 py-2.5">40 - 42 นิ้ว</td>
                        <td class="px-4 py-2.5">28 - 29 นิ้ว</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2.5 font-bold text-slate-900">XL</td>
                        <td class="px-4 py-2.5">42 - 44 นิ้ว</td>
                        <td class="px-4 py-2.5">29 - 30 นิ้ว</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2.5 font-bold text-slate-900">2XL</td>
                        <td class="px-4 py-2.5">44 - 46 นิ้ว</td>
                        <td class="px-4 py-2.5">30 - 31 นิ้ว</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2.5 font-bold text-slate-900">3XL</td>
                        <td class="px-4 py-2.5">46 - 48 นิ้ว</td>
                        <td class="px-4 py-2.5">31 - 32 นิ้ว</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="text-[11px] text-slate-400 italic">
            * หมายเหตุ: ขนาดจริงอาจมีความคลาดเคลื่อนประมาณ +/- 0.5 นิ้ว เนื่องจากการตัดเย็บ
        </p>

        <div class="mt-5 text-right">
            <button onclick="closeSizeModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition">
                ปิดหน้าต่าง
            </button>
        </div>
    </div>
</div>

<script>
function openSizeModal() {
    document.getElementById('sizeModal').classList.remove('hidden');
}
function closeSizeModal() {
    document.getElementById('sizeModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
