<?php
$pageTitle = "ตะกร้าสินค้าและการสั่งจอง - CMTC Shop";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/core/Csrf.php';

$cart = $_SESSION['cart'] ?? [];
$cartSubtotal = 0;
$totalItems = 0;

foreach ($cart as $item) {
    $cartSubtotal += ((float)$item['price'] * (int)$item['quantity']);
    $totalItems += (int)$item['quantity'];
}

$shippingBaseFee = (float)get_setting('shipping_base_fee', 50.00);
$shippingExtraFee = (float)get_setting('shipping_extra_fee', 10.00);
$baseShipping = $shippingBaseFee;
$extraShipping = $shippingExtraFee;
$calculatedDeliveryFee = OrderManager::calculateShippingCost('delivery', $totalItems);
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">
    <!-- Stepper Header -->
    <div class="max-w-3xl mx-auto mb-10">
        <div class="flex items-center justify-between">
            <div class="flex flex-col items-center flex-1 text-center">
                <div class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-sm shadow-md mb-2">
                    1
                </div>
                <span class="text-xs font-bold text-blue-600">ตรวจสอบตะกร้า</span>
            </div>
            <div class="h-1 bg-slate-200 flex-1 mx-2"></div>
            <div class="flex flex-col items-center flex-1 text-center">
                <div class="w-10 h-10 rounded-full bg-slate-200 text-slate-500 flex items-center justify-center font-bold text-sm mb-2">
                    2
                </div>
                <span class="text-xs font-medium text-slate-400">ชำระเงินและสลิป</span>
            </div>
            <div class="h-1 bg-slate-200 flex-1 mx-2"></div>
            <div class="flex flex-col items-center flex-1 text-center">
                <div class="w-10 h-10 rounded-full bg-slate-200 text-slate-500 flex items-center justify-center font-bold text-sm mb-2">
                    3
                </div>
                <span class="text-xs font-medium text-slate-400">รับใบยืนยัน</span>
            </div>
        </div>
    </div>

    <?php if (empty($cart)): ?>
        <?php if ($activePendingOrder): ?>
            <!-- Active Pending Order Notice in Cart -->
            <div class="bg-white rounded-3xl border border-amber-200/90 p-8 sm:p-10 text-center max-w-xl mx-auto shadow-md mb-8">
                <div class="w-16 h-16 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center mx-auto mb-4 text-2xl font-bold">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <span class="inline-block text-xs font-bold text-amber-800 bg-amber-100/80 px-3 py-1 rounded-full uppercase tracking-wider mb-2">
                    มีรายการสั่งจองรอการชำระเงิน
                </span>
                <h2 class="text-xl font-black text-slate-900 mb-2">
                    หมายเลขคำสั่งจอง: <?= htmlspecialchars($activePendingOrder['order_code']) ?>
                </h2>
                <p class="text-xs text-slate-600 mb-6 leading-relaxed">
                    คุณได้ทำรายการสั่งจองไว้เรียบร้อยแล้ว ยอดชำระเงิน <strong class="text-blue-600 text-sm">฿<?= Helper::money($activePendingOrder['total_amount']) ?></strong><br>
                    กรุณาชำระเงินและแนบหลักฐานสลิปก่อนหมดเวลา
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a href="<?= base_url('payment.php?code=' . urlencode($activePendingOrder['order_code'])) ?>" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md hover:shadow-lg transition">
                        <i class="fas fa-receipt mr-2"></i> ชำระเงินและแนบสลิปตอนนี้
                    </a>
                    <button type="button" onclick="cancelPendingOrderGlobal('<?= htmlspecialchars($activePendingOrder['order_code']) ?>')" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-3 rounded-xl border border-rose-200 bg-white hover:bg-rose-50 text-rose-600 font-bold text-xs transition">
                        <i class="fas fa-times mr-1.5"></i> ยกเลิกคำสั่งจองนี้
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Empty Cart -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-12 text-center max-w-xl mx-auto shadow-sm">
            <div class="w-20 h-20 rounded-full bg-slate-50 text-slate-300 flex items-center justify-center mx-auto mb-4 text-3xl">
                <i class="fas fa-shopping-basket"></i>
            </div>
            <h2 class="text-xl font-bold text-slate-900 mb-2">ตะกร้าสินค้าว่างเปล่า</h2>
            <p class="text-xs text-slate-500 mb-6">คุณยังไม่ได้เลือกสินค้าใดๆ เข้ามาในตะกร้า</p>
            <a href="<?= base_url() ?>" class="inline-flex items-center px-6 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md transition">
                <i class="fas fa-arrow-left mr-2"></i> เลือกดูเสื้อและเริ่มสั่งจอง
            </a>
        </div>
    <?php else: ?>
        <form action="<?= base_url('checkout_process.php') ?>" method="POST" id="checkoutForm">
            <?= Csrf::field() ?>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- Left Column: Cart Items & Customer Form -->
                <div class="lg:col-span-8 space-y-6">
                    
                    <!-- 1. Cart Items Box -->
                    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-100 mb-6 gap-2">
                            <div>
                                <h2 class="text-lg font-black text-slate-900 flex items-center">
                                    <i class="fas fa-shopping-bag text-blue-600 mr-2.5"></i> รายการสินค้าที่เลือกจอง
                                </h2>
                                <span class="text-xs font-semibold text-slate-500">
                                    รวม <span id="cartTotalItemsCount"><?= $totalItems ?></span> ชิ้น/ตัว
                                </span>
                            </div>
                            <div class="text-left sm:text-right bg-blue-50/60 sm:bg-transparent px-3 py-1.5 sm:p-0 rounded-xl">
                                <span class="text-[11px] text-slate-500 block">ยอดรวมราคาสินค้า</span>
                                <span class="text-base sm:text-lg font-black text-blue-600">
                                    ฿<span id="cartHeaderSubtotal"><?= Helper::money($cartSubtotal) ?></span>
                                </span>
                            </div>
                        </div>

                        <div class="divide-y divide-slate-100" id="cartItemsList">
                            <?php foreach ($cart as $sId => $item): ?>
                                <div class="py-4 first:pt-0 last:pb-0 flex flex-col sm:flex-row sm:items-center justify-between gap-4" id="cartItemRow_<?= $sId ?>">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-16 h-16 rounded-xl bg-gradient-to-b from-slate-50 to-slate-100/70 border border-slate-200/80 overflow-hidden flex-shrink-0 p-1 flex items-center justify-center">
                                            <img src="<?= base_url($item['main_image'] ?: 'assets/uploads/products/polo_87_main.jpg') ?>" class="w-full h-full object-contain object-center">
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-sm text-slate-900 leading-snug">
                                                <?= htmlspecialchars($item['product_name']) ?>
                                            </h4>
                                            <div class="text-xs text-slate-500 mt-0.5">
                                                ขนาด / ตัวเลือก: <span class="font-bold text-blue-600"><?= htmlspecialchars($item['size_name']) ?></span>
                                                <?php if (!empty($item['chest_inch']) && $item['chest_inch'] > 0): ?>
                                                    (อก <?= $item['chest_inch'] ?>" ยาว <?= $item['length_inch'] ?>")
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-xs font-semibold text-slate-700 mt-1">
                                                ฿<?= Helper::money($item['price']) ?> / <?= (!empty($item['chest_inch']) && $item['chest_inch'] > 0) ? 'ตัว' : 'ชิ้น' ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Stepper & Actions -->
                                    <div class="flex items-center justify-between sm:justify-end space-x-4">
                                        <div class="inline-flex items-center border border-slate-200 rounded-xl bg-slate-50 p-1">
                                            <button type="button" onclick="updateItemQty(<?= $sId ?>, -1)" class="w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-100 transition">
                                                <i class="fas fa-minus text-[10px]"></i>
                                            </button>
                                            <span id="qtyDisplay_<?= $sId ?>" class="w-10 text-center font-bold text-xs text-slate-900">
                                                <?= (int)$item['quantity'] ?>
                                            </span>
                                            <button type="button" onclick="updateItemQty(<?= $sId ?>, 1)" class="w-7 h-7 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-100 transition">
                                                <i class="fas fa-plus text-[10px]"></i>
                                            </button>
                                        </div>

                                        <div class="text-right min-w-[80px]">
                                            <span class="font-black text-sm text-blue-600">
                                                ฿<span id="subtotalDisplay_<?= $sId ?>"><?= Helper::money((float)$item['price'] * (int)$item['quantity']) ?></span>
                                            </span>
                                        </div>

                                        <button type="button" onclick="removeItem(<?= $sId ?>)" class="text-slate-300 hover:text-rose-500 w-8 h-8 rounded-lg flex items-center justify-center hover:bg-rose-50 transition" title="ลบรายการ">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Bottom Total Summary of Cart Items -->
                        <div class="mt-6 pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-slate-50/80 -mx-6 sm:-mx-8 -mb-6 sm:-mb-8 px-6 sm:px-8 py-4 rounded-b-3xl">
                            <div class="flex items-center space-x-2 text-xs font-bold text-slate-700">
                                <i class="fas fa-calculator text-blue-600"></i>
                                <span>ยอดรวมราคาสินค้าทั้งหมด (<span id="cartBottomQty"><?= $totalItems ?></span> ชิ้น/ตัว)</span>
                            </div>
                            <div class="flex items-center justify-between sm:justify-end space-x-3">
                                <span class="text-xs text-slate-500 sm:hidden">ยอดรวมค่าสินค้า:</span>
                                <span class="text-lg sm:text-xl font-black text-blue-600">
                                    ฿<span id="cartItemsSubtotal"><?= Helper::money($cartSubtotal) ?></span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Customer Information Form -->
                    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
                        <div class="pb-4 border-b border-slate-100 mb-6">
                            <h2 class="text-lg font-black text-slate-900 flex items-center">
                                <i class="fas fa-user text-blue-600 mr-2.5"></i> ข้อมูลผู้สั่งจอง
                            </h2>
                            <p class="text-xs text-slate-500 mt-1">ใช้สำหรับอ้างอิงการรับสินค้าและการติดต่อ</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    ชื่อ - นามสกุล <span class="text-rose-500">*</span>
                                </label>
                                <input type="text" name="customer_name" required placeholder="เช่น นายสมชาย ช่างทอง" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 text-sm text-slate-900 transition">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    ชื่อสถานศึกษา / หน่วยงาน / แผนกวิชา
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <i class="fas fa-university text-xs"></i>
                                    </span>
                                    <input type="text" name="customer_organization" placeholder="เช่น วิทยาลัยเทคนิคเชียงใหม่, แผนกช่างไฟฟ้า หรือ บุคคลทั่วไป" class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 text-sm text-slate-900 transition">
                                </div>
                                <p class="text-[11px] text-slate-400 mt-1">ระบุชื่อสถานศึกษา หน่วยงาน หรือแผนกวิชาของผู้สั่งจอง</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    เบอร์โทรศัพท์ (10 หลัก) <span class="text-rose-500">*</span>
                                </label>
                                <input type="tel" name="customer_phone" required placeholder="เช่น 0812345678" pattern="[0-9]{9,10}" maxlength="10" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 text-sm text-slate-900 transition">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    อีเมล (Email) <span class="text-rose-500">*</span>
                                </label>
                                <input type="email" name="customer_email" required placeholder="เช่น somchai@email.com" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 text-sm text-slate-900 transition">
                            </div>
                        </div>

                        <!-- 3. Delivery Method Selection -->
                        <div class="mt-8 pt-6 border-t border-slate-100">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-3">
                                วิธีการรับสินค้า <span class="text-rose-500">*</span>
                            </label>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                                <label class="relative flex flex-col p-4 rounded-2xl border-2 cursor-pointer transition select-none border-slate-200 hover:border-blue-300 bg-white has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50/40">
                                    <input type="radio" name="delivery_method" value="pickup" checked onchange="toggleDeliveryMethod(this)" class="sr-only">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm font-black text-slate-900 flex items-center">
                                            <i class="fas fa-hand-holding-box text-blue-600 mr-2"></i> รับด้วยตนเอง
                                        </span>
                                        <span class="text-xs font-bold text-emerald-600">ฟรี (0 บาท)</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500">
                                        มารับสินค้าด้วยตนเอง ณ วิทยาลัยเทคนิคเชียงใหม่
                                    </p>
                                </label>

                                <label class="relative flex flex-col p-4 rounded-2xl border-2 cursor-pointer transition select-none border-slate-200 hover:border-blue-300 bg-white has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50/40">
                                    <input type="radio" name="delivery_method" value="delivery" onchange="toggleDeliveryMethod(this)" class="sr-only">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm font-black text-slate-900 flex items-center">
                                            <i class="fas fa-shipping-fast text-indigo-600 mr-2"></i> จัดส่งพัสดุทางไปรษณีย์
                                        </span>
                                        <span class="text-xs font-bold text-blue-600">
                                            +฿<span id="deliveryCostBadge"><?= Helper::money($calculatedDeliveryFee) ?></span>
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-slate-500">
                                        เริ่มต้น <?= (int)$shippingBaseFee ?> บาท (บวกเพิ่มชิ้น/ตัวละ <?= (int)$shippingExtraFee ?> บาท)
                                    </p>
                                </label>
                            </div>

                            <!-- Self Pickup Info Box -->
                            <div id="pickupInfoBox" class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs text-slate-600">
                                <div class="font-bold text-slate-800 mb-1 flex items-center">
                                    <i class="fas fa-map-marker-alt text-rose-500 mr-1.5"></i> สถานที่รับสินค้าหน้างาน:
                                </div>
                                <p class="leading-relaxed">
                                    <?= nl2br(htmlspecialchars(get_setting('pickup_location', 'ตึกอำนวยการ แผนกวิชาช่างเทคนิคคอมพิวเตอร์ ชั้น 2'))) ?>
                                </p>
                            </div>

                            <!-- Delivery Address Input Box -->
                            <div id="deliveryAddressBox" class="hidden mt-4 space-y-2">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                    ที่อยู่สำหรับจัดส่งพัสดุอย่างละเอียด <span class="text-rose-500">*</span>
                                </label>
                                <textarea name="shipping_address" id="shippingAddressInput" rows="3" placeholder="บ้านเลขที่, ถนน, ซอย, ตำบล/แขวง, อำเภอ/เขต, จังหวัด, รหัสไปรษณีย์" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 text-sm text-slate-900 transition"></textarea>
                            </div>

                            <!-- Optional Notes -->
                            <div class="mt-4">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                    หมายเหตุเพิ่มเติม (ถ้ามี)
                                </label>
                                <input type="text" name="notes" placeholder="เช่น แผนกช่างยนต์ ปวส.2, นัดรับแทน ฯลฯ" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 text-sm text-slate-900 transition">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Order Summary Card -->
                <div class="lg:col-span-4 sticky top-24">
                    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
                        <h3 class="text-base font-black text-slate-900 pb-4 border-b border-slate-100 mb-4">
                            สรุปยอดคำสั่งซื้อ
                        </h3>

                        <div class="space-y-3 text-xs mb-6">
                            <div class="flex justify-between text-slate-600">
                                <span>ยอดรวมค่าสินค้า</span>
                                <span class="font-bold text-slate-900">฿<span id="summarySubtotal"><?= Helper::money($cartSubtotal) ?></span></span>
                            </div>

                            <div class="flex justify-between text-slate-600">
                                <span>ค่าจัดส่ง</span>
                                <span class="font-bold text-slate-900" id="summaryShipping">฿0.00</span>
                            </div>

                            <div class="pt-3 border-t border-slate-100 flex justify-between items-baseline">
                                <span class="text-sm font-bold text-slate-800">ยอดชำระสุทธิ</span>
                                <span class="text-2xl font-black text-blue-600">
                                    ฿<span id="summaryTotal"><?= Helper::money($cartSubtotal) ?></span>
                                </span>
                            </div>
                        </div>

                        <!-- Stock Hold Alert -->
                        <div class="bg-amber-50 rounded-2xl p-3.5 border border-amber-200/60 text-[11px] text-amber-800 mb-6 flex items-start space-x-2.5">
                            <i class="fas fa-clock text-amber-600 mt-0.5 text-xs flex-shrink-0"></i>
                            <div class="leading-relaxed">
                                เมื่อกดยืนยันสั่งจอง ระบบจะ<strong>ล็อกสต็อกชั่วคราว <?= (int)get_setting('cart_timeout_minutes', 30) ?> นาที</strong> เพื่อรอการแนบสลิปโอนเงินผ่าน PromptPay
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" id="btnSubmitOrder" class="w-full py-4 px-6 rounded-2xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm shadow-md hover:shadow-lg transition flex items-center justify-center active:scale-[0.98]">
                            <span>ยืนยันการจอง & ชำระเงิน</span>
                            <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
let currentSubtotal = <?= (float)$cartSubtotal ?>;
let totalItemQty = <?= (int)$totalItems ?>;
let isDelivery = false;
const baseShippingFee = <?= $baseShipping ?>;
const extraShippingFee = <?= $extraShipping ?>;

function calculateShipping() {
    if (!isDelivery || totalItemQty <= 0) return 0;
    if (totalItemQty <= 1) return baseShippingFee;
    return baseShippingFee + ((totalItemQty - 1) * extraShippingFee);
}

function updateSummary() {
    const shipping = calculateShipping();
    const grandTotal = currentSubtotal + shipping;

    document.getElementById('summarySubtotal').textContent = currentSubtotal.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('summaryShipping').textContent = '฿' + shipping.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('summaryTotal').textContent = grandTotal.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    
    const headerSubtotal = document.getElementById('cartHeaderSubtotal');
    if (headerSubtotal) headerSubtotal.textContent = currentSubtotal.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const itemsSubtotal = document.getElementById('cartItemsSubtotal');
    if (itemsSubtotal) itemsSubtotal.textContent = currentSubtotal.toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const bottomQty = document.getElementById('cartBottomQty');
    if (bottomQty) bottomQty.textContent = totalItemQty;
    const countSpan = document.getElementById('cartTotalItemsCount');
    if (countSpan) countSpan.textContent = totalItemQty;

    const badge = document.getElementById('deliveryCostBadge');
    if (badge) {
        badge.textContent = calculateShipping().toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
}

function toggleDeliveryMethod(radio) {
    isDelivery = (radio.value === 'delivery');
    const pickupBox = document.getElementById('pickupInfoBox');
    const deliveryBox = document.getElementById('deliveryAddressBox');
    const addrInput = document.getElementById('shippingAddressInput');

    if (isDelivery) {
        pickupBox.classList.add('hidden');
        deliveryBox.classList.remove('hidden');
        addrInput.required = true;
    } else {
        pickupBox.classList.remove('hidden');
        deliveryBox.classList.add('hidden');
        addrInput.required = false;
    }
    updateSummary();
}

function updateItemQty(sizeId, change) {
    const qtySpan = document.getElementById('qtyDisplay_' + sizeId);
    let currentQty = parseInt(qtySpan.textContent) || 1;
    let newQty = currentQty + change;
    if (newQty < 1) {
        removeItem(sizeId);
        return;
    }

    const csrfToken = '<?= Csrf::getToken() ?>';
    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('action', 'update_cart');
    formData.append('size_id', sizeId);
    formData.append('quantity', newQty);

    fetch('<?= base_url("api.php") ?>', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'ไม่สามารถปรับจำนวนได้',
                text: data.error,
                confirmButtonColor: '#2563eb'
            });
        }
    });
}

function removeItem(sizeId) {
    Swal.fire({
        title: 'ยืนยันลบสินค้านี้?',
        text: 'ต้องการนำรายการนี้ออกจากตะกร้าหรือไม่',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ลบรายการ',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b'
    }).then(res => {
        if (res.isConfirmed) {
            const formData = new FormData();
            formData.append('csrf_token', '<?= Csrf::getToken() ?>');
            formData.append('action', 'remove_from_cart');
            formData.append('size_id', sizeId);

            fetch('<?= base_url("api.php") ?>', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                location.reload();
            });
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
