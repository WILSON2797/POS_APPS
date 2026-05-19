<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import DefaultLayout from "@/Layouts/DefaultLayout.vue";
import BaseButton from "@/Components/Base/BaseButton.vue";
import BaseSelect from "@/Components/Base/BaseSelect.vue";
import BaseInput from "@/Components/Base/BaseInput.vue";
import BaseModal from "@/Components/Base/BaseModal.vue";
import { usePage } from "@inertiajs/vue3";
import { useCartStore } from "@/stores/cart";
defineOptions({ layout: DefaultLayout });
const props = defineProps({
    products: Array,
    categories: Array,
    customers: Array,
});
const page = usePage();
const cartStore = useCartStore();
const { showConfirm, showSuccess, showError } = window;
// Filter and search products state
const searchQuery = ref("");
const selectedCategoryId = ref("");
// Cart Drawer Slide-Over & Bouncing States (Shopee Style)
const isDrawerOpen = ref(false);
const isCartBouncing = ref(false);
// Active row toggle for inline discount/notes (Shopee Style)
const activeEditRow = ref({}); // productId -> 'discount' | 'note' | null

// FIX #3: Spread object agar mutasi reactive di Vue 3
function toggleEditRow(productId, type) {
    if (activeEditRow.value[productId] === type) {
        activeEditRow.value = { ...activeEditRow.value, [productId]: null };
    } else {
        activeEditRow.value = { ...activeEditRow.value, [productId]: type };
    }
}

const filteredProducts = computed(() => {
    return props.products.filter((p) => {
        const matchesSearch = searchQuery.value
            ? p.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
              (p.barcode &&
                  p.barcode
                      .toLowerCase()
                      .includes(searchQuery.value.toLowerCase()))
            : true;
        // FIX #1: Konversi ke String agar perbandingan int vs string tidak gagal
        const matchesCategory = selectedCategoryId.value
            ? String(p.category_id) === String(selectedCategoryId.value)
            : true;
        return matchesSearch && matchesCategory;
    });
});

// Watch search query for instant exact barcode matches (autofill & add to cart)
watch(searchQuery, (newVal) => {
    const query = newVal.trim().toLowerCase();
    if (!query) return;

    // Find if there is an exact barcode match in products list
    const exactProduct = props.products.find(
        (p) => p.barcode && p.barcode.toLowerCase() === query
    );

    if (exactProduct) {
        // Grab the card element synchronously while it is still filtered in the DOM
        const cardEl = document.querySelector(".product-pos-card");
        
        // Clear search input instantly to prevent double scans/triggers
        searchQuery.value = "";
        
        // Add to cart with the premium Shopee flying animation
        addToCart(exactProduct, cardEl);
    }
});

// Handle enter key on search input (backup/single item autocomplete selector)
function handleSearchEnter() {
    const query = searchQuery.value.trim().toLowerCase();
    if (!query) return;

    const exactProduct = props.products.find(
        (p) => p.barcode && p.barcode.toLowerCase() === query
    );

    if (exactProduct) {
        const cardEl = document.querySelector(".product-pos-card");
        searchQuery.value = "";
        addToCart(exactProduct, cardEl);
        return;
    }

    // Smart UX: If query matches exactly 1 product, select it on Enter press
    if (filteredProducts.value.length === 1) {
        const singleProduct = filteredProducts.value[0];
        const cardEl = document.querySelector(".product-pos-card");
        searchQuery.value = "";
        addToCart(singleProduct, cardEl);
    }
}

// Quick cash buttons
const quickCashAmounts = [10000, 20000, 50000, 100000, 150000, 200000];
// Selected customer selection computed
const selectedCustomer = computed({
    get: () => cartStore.customer?.id || null,
    set: (val) => {
        const cust = props.customers.find((c) => c.id === val);
        cartStore.setCustomer(cust || null);
    },
});
// Pending adds to cart (flying items) to prevent race conditions on stock validation
const pendingCartAdds = ref({});

// Item manipulation
function addToCart(p, eventOrElement) {
    const cartItem = cartStore.items.find((i) => i.product.id === p.id);
    const flyingQty = pendingCartAdds.value[p.id] || 0;
    const currentQty = (cartItem ? cartItem.qty : 0) + flyingQty;
    
    if (p.stock <= currentQty) {
        showError(
            `Stok produk "${p.name}" tidak mencukupi (Tersedia ${p.stock}).`,
        );
        return;
    }
    
    // Trigger Flying Animation ala Shopee
    if (eventOrElement) {
        // Increment pending count immediately to lock the stock during flight
        pendingCartAdds.value[p.id] = (pendingCartAdds.value[p.id] || 0) + 1;
        
        triggerFlyAnimation(eventOrElement, p.image, () => {
            // Decrement pending count
            if (pendingCartAdds.value[p.id] > 0) {
                pendingCartAdds.value[p.id]--;
            }
            
            // Add to cart only when animation completes
            cartStore.addItem(p);
            // Trigger bounce animation on floating button
            isCartBouncing.value = true;
            setTimeout(() => {
                isCartBouncing.value = false;
            }, 600);
        });
    } else {
        // No animation, add instantly
        cartStore.addItem(p);
        isCartBouncing.value = true;
        setTimeout(() => {
            isCartBouncing.value = false;
        }, 600);
    }
}

function triggerFlyAnimation(eventOrElement, imagePath, onComplete) {
    let card = null;
    if (eventOrElement) {
        if (eventOrElement.nodeType === 1) {
            card = eventOrElement; // Already an HTML element
        } else if (eventOrElement.currentTarget || eventOrElement.target) {
            card = eventOrElement.currentTarget?.closest(".product-pos-card") || eventOrElement.target?.closest(".product-pos-card");
        }
    }
    if (!card) return;
    
    const img = card.querySelector("img");
    const cartBtn = document.getElementById("pos-cart-btn");
    if (!cartBtn) return;
    
    const startRect = img ? img.getBoundingClientRect() : card.getBoundingClientRect();
    const targetRect = cartBtn.getBoundingClientRect();
    
    const flyer = document.createElement("div");
    flyer.className = "cart-flyer-item";
    
    if (imagePath) {
        flyer.style.backgroundImage = `url('/storage/${imagePath}')`;
    } else {
        flyer.style.background = "linear-gradient(135deg, #3b82f6, #1d4ed8)";
        flyer.style.display = "flex";
        flyer.style.alignItems = "center";
        flyer.style.justifyContent = "center";
        const miniBox = document.createElement("span");
        miniBox.style.color = "#ffffff";
        miniBox.style.fontSize = "14px";
        miniBox.innerText = "📦";
        flyer.appendChild(miniBox);
    }
    
    const startSize = 54; // Premium larger start size for awesome visibility
    flyer.style.width = `${startSize}px`;
    flyer.style.height = `${startSize}px`;
    flyer.style.position = "fixed";
    flyer.style.zIndex = "99999";
    flyer.style.borderRadius = "50%";
    flyer.style.pointerEvents = "none";
    flyer.style.boxShadow = "0 8px 24px rgba(59, 130, 246, 0.45), 0 0 0 2.5px #ffffff";
    flyer.style.willChange = "transform, opacity, left, top";
    
    document.body.appendChild(flyer);
    
    // Core physics calculations for parabolic bezier trajectory (Shopee Style)
    const startX = startRect.left + startRect.width / 2;
    const startY = startRect.top + startRect.height / 2;
    const targetX = targetRect.left + targetRect.width / 2;
    const targetY = targetRect.top + targetRect.height / 2;
    
    // Control Point pushes the flight path 220px higher into a gorgeous parabola
    const controlX = (startX + targetX) / 2;
    const controlY = Math.min(startY, targetY) - 220; 
    
    const duration = 700; // ms (perfect timing for a luxurious arc)
    const startTime = performance.now();
    
    function animate(currentTime) {
        const elapsed = currentTime - startTime;
        const t = Math.min(elapsed / duration, 1);
        
        // Quadratic Bezier Formula: B(t) = (1-t)^2 * P0 + 2*(1-t)*t * P1 + t^2 * P2
        const x = (1 - t) * (1 - t) * startX + 2 * (1 - t) * t * controlX + t * t * targetX;
        const y = (1 - t) * (1 - t) * startY + 2 * (1 - t) * t * controlY + t * t * targetY;
        
        // Scale down from 1.0 to 0.15, fade out slightly towards the end
        const scale = 1 - t * 0.85;
        const opacity = t > 0.8 ? 1 - (t - 0.8) / 0.2 : 1; 
        
        flyer.style.left = `${x - startSize / 2}px`;
        flyer.style.top = `${y - startSize / 2}px`;
        flyer.style.transform = `scale(${scale}) rotate(${t * 360}deg)`; // Awesome rotation spin!
        flyer.style.opacity = opacity;
        
        if (t < 1) {
            requestAnimationFrame(animate);
        } else {
            flyer.remove();
            
            // Trigger elegant springy bounce on cart
            cartBtn.classList.add("cart-btn-pop");
            setTimeout(() => {
                cartBtn.classList.remove("cart-btn-pop");
            }, 450);
            
            // Call completion callback
            if (onComplete) {
                onComplete();
            }
        }
    }
    
    requestAnimationFrame(animate);
}

// FIX #2: Tambahkan validasi stok dan tipe data
function handleQtyChange(productId, qty) {
    const item = cartStore.items.find((i) => i.product.id === productId);
    if (!item) return;
    const parsed = parseInt(qty, 10);
    if (isNaN(parsed) || parsed < 1) return;
    if (parsed > item.product.stock) {
        showError(
            `Stok "${item.product.name}" tidak mencukupi (maks: ${item.product.stock}).`,
        );
        return;
    }
    cartStore.updateQty(productId, parsed);
}

// Receipt & Transaction Processing
const showReceiptModal = ref(false);
const receiptData = ref(null);
const checkoutForm = useForm({
    items: [],
    customer_id: null,
    discount_type: "flat",
    discount_value: 0,
    tax_rate: 11,
    payment_method: "cash",
    amount_paid: 0,
    note: "",
});
function setQuickCash(amount) {
    if (amount === "pas") {
        cartStore.setAmountPaid(cartStore.grandTotal);
    } else {
        cartStore.setAmountPaid(amount);
    }
}
async function processCheckout() {
    const selectedItems = cartStore.items.filter((item) => item.selected);
    if (selectedItems.length === 0) {
        showError("Tidak ada item terpilih untuk dibayar.");
        return;
    }
    if (!cartStore.paymentMethod) {
        showError("Silakan pilih metode pembayaran terlebih dahulu.");
        return;
    }
    if (
        cartStore.paymentMethod === "cash" &&
        cartStore.amountPaid < cartStore.grandTotal
    ) {
        showError("Jumlah pembayaran kurang dari total belanja.");
        return;
    }
    const result = await showConfirm({
        title: "Konfirmasi Transaksi",
        text: `Proses transaksi sebesar Rp ${fmtNoSymbol(cartStore.grandTotal)}?`,
        confirmText: "Proses Sekarang",
    });
    if (!result.isConfirmed) return;
    // Populate checkout form with selected items only
    checkoutForm.items = selectedItems.map((item) => ({
        product: { id: item.product.id },
        qty: item.qty,
        discount: item.discount,
        note: item.note,
    }));
    checkoutForm.customer_id = cartStore.customer?.id || null;
    // FIX #5: Fallback agar discount_type tidak undefined
    checkoutForm.discount_type = cartStore.discountType ?? "flat";
    checkoutForm.discount_value = cartStore.discountValue;
    checkoutForm.tax_rate = cartStore.taxRate;
    checkoutForm.payment_method = cartStore.paymentMethod;
    checkoutForm.amount_paid = cartStore.amountPaid;
    checkoutForm.note = cartStore.transactionNote;
    checkoutForm.post("/pos", {
        onSuccess: () => {
            const receipt = page.props.flash?.receipt;
            if (receipt) {
                receiptData.value = receipt;
                showReceiptModal.value = true;
            }
            cartStore.removeSelectedItems();
            cartStore.setAmountPaid(0);
            isDrawerOpen.value = false; // Auto close drawer after successful sale
        },
        onError: (err) => {
            showError(Object.values(err)[0] || "Gagal memproses transaksi.");
        },
    });
}
// Draggable/Resizable Footer panel (iOS/Android Bottom Sheet Style)
const footerRef = ref(null);
const footerHeight = ref(null);
const isDraggingFooter = ref(false);

function startDrag(e) {
    isDraggingFooter.value = true;
    document.body.style.userSelect = "none";
    document.body.style.cursor = "ns-resize";

    if (!footerHeight.value && footerRef.value) {
        footerHeight.value = footerRef.value.getBoundingClientRect().height;
    }

    const onMouseMove = (moveEvent) => {
        if (!isDraggingFooter.value) return;
        const clientY = moveEvent.touches
            ? moveEvent.touches[0].clientY
            : moveEvent.clientY;
        let newHeight = window.innerHeight - clientY;

        // Bounds: Min 50px (only drag bar visible), Max 85% of screen height
        const minH = 50;
        const maxH = window.innerHeight * 0.85;
        if (newHeight < minH) newHeight = minH;
        if (newHeight > maxH) newHeight = maxH;

        footerHeight.value = newHeight;
    };

    const onMouseUp = () => {
        isDraggingFooter.value = false;
        document.body.style.userSelect = "";
        document.body.style.cursor = "";
        window.removeEventListener("mousemove", onMouseMove);
        window.removeEventListener("mouseup", onMouseUp);
        window.removeEventListener("touchmove", onMouseMove);
        window.removeEventListener("touchend", onMouseUp);
    };

    window.addEventListener("mousemove", onMouseMove);
    window.addEventListener("mouseup", onMouseUp);
    window.addEventListener("touchmove", onMouseMove, { passive: true });
    window.addEventListener("touchend", onMouseUp);
}

async function confirmRemoveItem(productId) {
    const result = await showConfirm({
        title: "Hapus Item?",
        text: "Anda yakin ingin menghapus item ini dari keranjang?",
        confirmText: "Ya, Hapus",
    });
    if (result.isConfirmed) {
        cartStore.removeItem(productId);
        showSuccess("Item berhasil dihapus.");
    }
}

async function confirmRemoveSelected() {
    const selectedCount = cartStore.items.filter((i) => i.selected).length;
    if (selectedCount === 0) return;

    const result = await showConfirm({
        title: "Hapus Item Terpilih?",
        text: `Anda yakin ingin menghapus ${selectedCount} item terpilih dari keranjang?`,
        confirmText: "Ya, Hapus",
    });
    if (result.isConfirmed) {
        cartStore.removeSelectedItems();
        showSuccess("Item terpilih berhasil dihapus.");
    }
}

async function confirmClearCart() {
    const result = await showConfirm({
        title: "Kosongkan Keranjang?",
        text: "Anda yakin ingin menghapus semua item dari keranjang?",
        confirmText: "Ya, Kosongkan",
    });
    if (result.isConfirmed) {
        cartStore.clearCart();
        showSuccess("Keranjang berhasil dikosongkan.");
    }
}

// Held/suspended transactions
const showHeldModal = ref(false);
function holdTx() {
    if (cartStore.items.length === 0) {
        showError("Keranjang kosong, tidak ada transaksi untuk di-hold.");
        return;
    }
    const success = cartStore.holdCurrentTransaction();
    if (success) showSuccess("Transaksi berhasil di-hold.");
}
function resumeTx(id) {
    cartStore.resumeTransaction(id);
    showHeldModal.value = false;
    showSuccess("Transaksi dilanjutkan.");
}

// FIX #6: Pisahkan penulisan tag <script> agar tidak diparsing parser HTML/JS secara prematur
function printReceipt() {
    const printContent = document.getElementById(
        "thermal-receipt-body",
    ).innerHTML;
    const iframe = document.getElementById("receipt-print-iframe");
    if (!iframe) return;
    const doc = iframe.contentWindow.document || iframe.contentDocument;
    doc.open();
    doc.write(
        `<html>
      <head>
        <title>Cetak Struk</title>
        <style>
          @page { margin: 0; size: auto; }
          body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            line-height: 1.3;
            margin: 8px;
            width: 280px;
            color: #000;
          }
          .text-center { text-align: center; }
          .text-right { text-align: right; }
          .divider { border-top: 1px dashed #000; margin: 6px 0; }
          .item-row { display: flex; justify-content: space-between; }
          .totals-row { display: flex; justify-content: space-between; font-weight: bold; }
          .mb-2 { margin-bottom: 6px; }
          .text-danger { color: #000 !important; }
        </style>
      </head>
      <body>
        ${printContent}
        <scr` +
            `ipt>window.onload=function(){window.focus();window.print()};<\/scr` +
            `ipt>
      </body>
    </html>`,
    );
    doc.close();
}

// Hotkey listeners
function handleHotkeys(e) {
    if (e.key === "F1") {
        e.preventDefault();
        document.getElementById("pos-search-input")?.focus();
    } else if (e.key === "F2") {
        e.preventDefault();
        isDrawerOpen.value = !isDrawerOpen.value; // F2 toggles the beautiful cart drawer
    } else if (e.key === "F4") {
        e.preventDefault();
        processCheckout();
    } else if (e.key === "F7") {
        e.preventDefault();
        cartStore.clearCart();
    }
}
onMounted(() => {
    window.addEventListener("keydown", handleHotkeys);
    // Otomatis fokuskan kursor ke kolom pencarian/scan saat halaman dimuat (Scanner Ready!)
    setTimeout(() => {
        document.getElementById("pos-search-input")?.focus();
    }, 300);
});
onUnmounted(() => {
    window.removeEventListener("keydown", handleHotkeys);
});

// =========================================================================
// FITUR ADVANCED BARCODE SCANNER (HARDWARE & KAMERA DEVICE)
// =========================================================================

// State reaktif untuk mengontrol scanner kamera
const isCameraModalOpen = ref(false); // Buka/tutup modal kamera
const cameraDevices = ref([]);       // Daftar semua kamera yang terdeteksi di device
const activeCameraId = ref("");      // ID kamera aktif saat ini yang sedang digunakan
let html5QrcodeInstance = null;      // Menyimpan instance library Html5Qrcode

// Fungsi untuk memfokuskan kursor ke input pencarian (Sangat penting untuk scanner fisik Kabel/Bluetooth)
function focusSearchInput() {
    const input = document.getElementById("pos-search-input");
    if (input) {
        input.focus();
        showSuccess("Fokus kursor diarahkan ke pencarian. Scanner Fisik Siap!");
    }
}

// Fungsi untuk membunyikan suara beep sukses secara instan memakai Web Audio API browser (Kinerja sangat cepat & offline-friendly)
function playBeepSound() {
    try {
        const context = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = context.createOscillator();
        const gainNode = context.createGain();
        oscillator.connect(gainNode);
        gainNode.connect(context.destination);
        oscillator.type = "sine";
        oscillator.frequency.value = 1000; // Frekuensi nada tinggi (Hz)
        gainNode.gain.setValueAtTime(0, context.currentTime);
        gainNode.gain.linearRampToValueAtTime(0.3, context.currentTime + 0.05); // Nada masuk cepat
        gainNode.gain.linearRampToValueAtTime(0, context.currentTime + 0.15);  // Nada keluar cepat
        oscillator.start();
        oscillator.stop(context.currentTime + 0.15); // Suara mati otomatis dalam 150ms
    } catch (e) {
        console.warn("Umpan balik audio scanner gagal:", e);
    }
}

// Memuat pustaka html5-qrcode secara dinamis (On-Demand) lewat CDN agar load awal POS tetap ringan
function loadHtml5Qrcode() {
    return new Promise((resolve, reject) => {
        if (window.Html5Qrcode) {
            resolve(window.Html5Qrcode);
            return;
        }
        const script = document.createElement("script");
        script.src = "https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js";
        script.onload = () => resolve(window.Html5Qrcode);
        script.onerror = () => reject(new Error("Gagal mengunduh pustaka scanner kamera. Periksa koneksi internet."));
        document.head.appendChild(script);
    });
}

// Membuka modal scanner kamera dan mendeteksi izin & perangkat kamera
async function openCameraScanner() {
    try {
        isCameraModalOpen.value = true;
        
        // Memuat pustaka pendeteksi barcode
        await loadHtml5Qrcode();
        
        // Mengambil daftar kamera yang tersedia di HP/Laptop/Tablet
        const devices = await Html5Qrcode.getCameras();
        if (devices && devices.length > 0) {
            cameraDevices.value = devices;
            
            // Mencoba mendeteksi kamera belakang (back/rear) untuk hasil scan barcode yang optimal
            const rearCamera = devices.find(d => 
                d.label.toLowerCase().includes('back') || 
                d.label.toLowerCase().includes('rear') ||
                d.label.toLowerCase().includes('belakang')
            );
            activeCameraId.value = rearCamera ? rearCamera.id : devices[0].id;
            
            // Menjalankan streaming kamera
            startScanning();
        } else {
            showError("Tidak ada kamera yang terdeteksi di perangkat Anda.");
            isCameraModalOpen.value = false;
        }
    } catch (err) {
        showError("Gagal mengakses kamera: " + err.message);
        isCameraModalOpen.value = false;
    }
}

// Memulai pemindaian barcode dengan konfigurasi FPS dan QRBox optimal
function startScanning() {
    if (html5QrcodeInstance) {
        // Hentikan instance scanner aktif lama sebelum memulai yang baru (Menghindari tabrakan memori)
        html5QrcodeInstance.stop().then(() => {
            initializeScanner();
        }).catch(() => {
            initializeScanner();
        });
    } else {
        initializeScanner();
    }
}

// Melakukan inisialisasi modul scanner dan memetakan kamera ke elemen DOM preview
function initializeScanner() {
    html5QrcodeInstance = new Html5Qrcode("camera-scanner-preview");
    
    const config = {
        fps: 15, // Frame-per-second optimal untuk pemindaian
        qrbox: { width: 280, height: 160 } // Dimensi kotak bidik horizontal yang pas untuk EAN-13 / UPC barcode
    };
    
    html5QrcodeInstance.start(
        activeCameraId.value,
        config,
        (decodedText) => {
            // Callback sukses ketika barcode terbaca
            handleCameraScanSuccess(decodedText);
        },
        () => {
            // Frame gagal diabaikan diam-diam agar proses loop pemindaian tetap lancar & ringan
        }
    ).catch(err => {
        console.error("Gagal menginisialisasi kamera:", err);
    });
}

// Menangani data barcode hasil scan kamera yang berhasil dideteksi
function handleCameraScanSuccess(barcodeText) {
    playBeepSound(); // Bunyikan umpan balik suara scan sukses
    
    // Cari produk di katalog yang barcodenya cocok secara tepat
    const exactProduct = props.products.find(
        (p) => p.barcode && p.barcode.toLowerCase() === barcodeText.trim().toLowerCase()
    );
    
    if (exactProduct) {
        // Hentikan stream kamera dan tutup modal
        closeCameraScanner();
        showSuccess(`Scan Sukses: ${exactProduct.name}`);
        
        // Memicu Shopee flying animation dari area katalog produk menuju keranjang
        setTimeout(() => {
            const cardEl = document.querySelector(".product-pos-card");
            addToCart(exactProduct, cardEl);
        }, 150);
    } else {
        showError(`Produk dengan barcode "${barcodeText}" belum terdaftar.`);
    }
}

// Menghentikan stream kamera secara bersih dan menutup modal (Sangat penting agar baterai & RAM tetap hemat)
function closeCameraScanner() {
    isCameraModalOpen.value = false;
    if (html5QrcodeInstance) {
        html5QrcodeInstance.stop().then(() => {
            html5QrcodeInstance = null;
        }).catch(err => {
            console.error("Gagal menghentikan kamera secara bersih:", err);
            html5QrcodeInstance = null;
        });
    }
}

// Menangani perubahan input dropdown kamera secara dinamis
function handleCameraChange() {
    startScanning();
}

// Currency Helpers
const fmt = (v) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        maximumFractionDigits: 0,
    }).format(v);
const fmtNoSymbol = (v) =>
    new Intl.NumberFormat("id-ID", { maximumFractionDigits: 0 }).format(v);
</script>
<template>
    <div class="pos-page pos-layout p-1">
        <div class="row g-3">
            <!-- ── Katalog Produk (100% Lebar Layar untuk Area Kerja Maksimal) ── -->
            <div class="col-12">
                <CCard class="border-0 shadow-sm">
                    <CCardHeader class="bg-white border-bottom-0 py-3">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-6">
                                <h5 class="m-0 fw-bold text-primary">
                                    Katalog Produk
                                </h5>
                                <small class="text-body-secondary"
                                    >Pilih/Scan produk untuk dimasukkan
                                    keranjang (F1)</small
                                >
                            </div>
                            <div
                                class="col-md-6 d-flex align-items-center gap-2"
                            >
                                <div class="position-relative flex-grow-1">
                                    <!-- Input Pencarian & Scan Barcode terintegrasi secara premium -->
                                    <input
                                        id="pos-search-input"
                                        v-model="searchQuery"
                                        type="text"
                                        class="form-control w-100"
                                        style="height: 40px; font-size: 14px; padding-left: 48px; padding-right: 76px;"
                                        placeholder="Cari nama / scan SKU (F1)..."
                                        @keydown.enter="handleSearchEnter"
                                    />
                                    <!-- Ikon Kaca Pembesar di Sisi Kiri Input (Terpusat Sempurna & Spacing Premium) -->
                                    <!-- Catatan: Class 'start-0' sengaja dihapus agar tidak berbenturan dengan aturan '!important' di CSS Global / Bootstrap -->
                                    <span class="position-absolute d-flex align-items-center justify-content-center text-secondary" style="pointer-events: none; left: 20px; top: 0; bottom: 0; height: 100%;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="feather"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                    </span>
                                    <!-- Tombol Pemicu di Sisi Kanan Input (Terpusat Sempurna) -->
                                    <div class="position-absolute end-0 d-flex align-items-center gap-1 pe-2" style="z-index: 10; top: 0; bottom: 0; height: 100%;">
                                        <!-- Tombol Indikator Status & Fokus untuk Scanner Fisik (Kabel/Bluetooth) -->
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-light p-1 d-flex align-items-center justify-content-center border-0 rounded-circle position-relative"
                                            style="width: 28px; height: 28px;"
                                            title="Status Scanner Fisik Ready. Klik untuk memfokuskan kursor."
                                            @click="focusSearchInput"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather"><path d="M5 18H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h2"></path><path d="M19 6h2a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-2"></path><line x1="12" y1="6" x2="12" y2="18"></line><line x1="8" y1="6" x2="8" y2="18"></line><line x1="16" y1="6" x2="16" y2="18"></line></svg>
                                            <!-- Titik Lampu Hijau Berkedip Menandakan Scanner Aktif & Fokus -->
                                            <span class="position-absolute top-0 end-0 translate-middle badge rounded-circle bg-success border border-white p-1 pulse-animation" style="transform: translate(-10%, 25%) !important;">
                                                <span class="visually-hidden">Ready</span>
                                            </span>
                                        </button>
                                        <!-- Tombol Pemicu Scanner Kamera Device (Webcam/HP) -->
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-light p-1 d-flex align-items-center justify-content-center border-0 rounded-circle text-primary"
                                            style="width: 28px; height: 28px;"
                                            title="Scan Barcode via Kamera Device"
                                            @click="openCameraScanner"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                                        </button>
                                    </div>
                                </div>
                                <button
                                    id="pos-cart-btn"
                                    type="button"
                                    class="btn btn-primary d-flex align-items-center gap-2 px-3 position-relative flex-shrink-0"
                                    style="
                                        height: 40px;
                                        border-radius: 8px;
                                        font-size: 14px;
                                    "
                                    title="Buka Keranjang (F2)"
                                    @click="isDrawerOpen = !isDrawerOpen"
                                >
                                    <!-- Shopping Cart Icon -->
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="18"
                                        height="18"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <circle cx="9" cy="21" r="1"></circle>
                                        <circle cx="20" cy="21" r="1"></circle>
                                        <path
                                            d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"
                                        ></path>
                                    </svg>
                                    <span
                                        v-if="cartStore.allItemsCount > 0"
                                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light"
                                        style="
                                            font-size: 11px;
                                            transform: translate(
                                                -45%,
                                                -45%
                                            ) !important;
                                        "
                                        :class="{
                                            'animate-bounce': isCartBouncing,
                                        }"
                                    >
                                        {{ cartStore.allItemsCount }}
                                    </span>
                                </button>
                            </div>
                        </div>
                        <!-- Kategori Tabs -->
                        <div
                            class="d-flex gap-1 overflow-x-auto mt-3 pb-2 scrollbar-thin"
                        >
                            <button
                                class="btn btn-sm rounded-pill px-3 flex-shrink-0"
                                :class="
                                    selectedCategoryId === ''
                                        ? 'btn-primary'
                                        : 'btn-outline-primary'
                                "
                                @click="selectedCategoryId = ''"
                            >
                                Semua Kategori
                            </button>
                            <button
                                v-for="cat in categories"
                                :key="cat.id"
                                class="btn btn-sm rounded-pill px-3 flex-shrink-0"
                                :class="
                                    selectedCategoryId === String(cat.id)
                                        ? 'btn-primary'
                                        : 'btn-outline-primary'
                                "
                                @click="selectedCategoryId = String(cat.id)"
                            >
                                {{ cat.name }}
                            </button>
                        </div>
                    </CCardHeader>
                    <CCardBody class="bg-light p-3">
                        <!-- Katalog Grid -->
                        <div v-if="filteredProducts.length > 0" class="row g-2">
                            <div
                                v-for="p in filteredProducts"
                                :key="p.id"
                                class="col-6 col-sm-4 col-md-3 col-xl-2"
                            >
                                <div
                                    class="card product-pos-card h-100 border-0 shadow-sm cursor-pointer position-relative overflow-hidden"
                                    @click="addToCart(p, $event)"
                                >
                                    <!-- Stock alert badge -->
                                    <span
                                        class="badge position-absolute top-0 start-0 m-1"
                                        :class="
                                            p.stock <= p.min_stock
                                                ? 'bg-danger'
                                                : 'bg-success'
                                        "
                                        style="z-index: 1"
                                    >
                                        Stok: {{ p.stock }}
                                    </span>
                                    <div
                                        class="ratio ratio-4x3 product-card-img-container"
                                    >
                                        <img
                                            v-if="p.image"
                                            :src="`/storage/${p.image}`"
                                            class="card-img-top object-fit-contain w-100 h-100 p-2"
                                            :alt="p.name"
                                        />
                                        <div 
                                            v-else 
                                            class="w-100 h-100 d-flex flex-column align-items-center justify-content-center premium-no-image-placeholder"
                                        >
                                            <div class="placeholder-icon-wrapper d-flex align-items-center justify-content-center mb-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" class="feather">
                                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                                                </svg>
                                            </div>
                                            <span class="placeholder-text text-secondary fw-semibold font-monospace" style="font-size: 10px; letter-spacing: 0.5px;">NO PHOTO</span>
                                        </div>
                                    </div>
                                    <div
                                        class="card-body p-2 d-flex flex-column justify-content-between"
                                    >
                                        <div
                                            class="product-title-line fw-semibold text-dark small"
                                            :title="p.name"
                                        >
                                            {{ p.name }}
                                        </div>
                                        <div
                                            class="d-flex align-items-center justify-content-between mt-1"
                                        >
                                            <span
                                                class="text-success fw-bold small"
                                                >{{
                                                    fmt(p.selling_price)
                                                }}</span
                                            >
                                            <!-- Shopee-Style Add to Cart Floating Button -->
                                            <button 
                                                class="btn btn-primary btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center add-to-cart-btn"
                                                style="width: 26px; height: 26px;"
                                                @click.stop="addToCart(p, $event)"
                                                title="Tambah ke Keranjang"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" class="feather"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Empty State -->
                        <div
                            v-else
                            class="text-center py-5 text-body-secondary"
                        >
                            <div class="fs-1 opacity-25">📭</div>
                            <div class="mt-2 fw-semibold">
                                Produk tidak ditemukan
                            </div>
                            <small
                                >Coba kata kunci lain atau pilih kategori
                                berbeda.</small
                            >
                        </div>
                    </CCardBody>
                </CCard>
            </div>
        </div>
        <!-- ── Slide-Over Cart Drawer Container (Shopee Style Laci Geser) ── -->
        <div
            class="cart-drawer-overlay"
            :class="{ active: isDrawerOpen }"
            @click.self="isDrawerOpen = false"
        >
            <div
                class="cart-drawer-panel shadow-lg"
                :class="{ open: isDrawerOpen }"
            >
                <!-- Drawer Header -->
                <div
                    class="drawer-header px-3 py-3 border-bottom d-flex align-items-center justify-content-between bg-white"
                >
                    <div class="d-flex align-items-center gap-2">
                        <!-- Feather Icon: Shopping Cart -->
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="20"
                            height="20"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="feather text-primary"
                        >
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path
                                d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"
                            ></path>
                        </svg>
                        <h5 class="m-0 fw-bold text-dark">Keranjang POS</h5>
                        <span
                            v-if="cartStore.allItemsCount > 0"
                            class="badge bg-primary rounded-pill font-monospace"
                            style="font-size: 11px; padding: 3px 7px"
                        >
                            {{ cartStore.allItemsCount }}
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <button
                            class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1 py-1"
                            title="Hold Transaksi"
                            @click="holdTx"
                        >
                            <!-- Feather Icon: Pause (Hold) -->
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="12"
                                height="12"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="feather"
                            >
                                <rect x="6" y="4" width="4" height="16"></rect>
                                <rect x="14" y="4" width="4" height="16"></rect>
                            </svg>
                            Hold
                        </button>
                        <button
                            class="btn btn-outline-primary btn-sm position-relative d-flex align-items-center gap-1 py-1"
                            title="Daftar Transaksi Held"
                            @click="showHeldModal = true"
                        >
                            <!-- Feather Icon: Folder -->
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="12"
                                height="12"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="feather"
                            >
                                <path
                                    d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"
                                ></path>
                            </svg>
                            Held
                            <span
                                v-if="cartStore.heldTransactions.length > 0"
                                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                            >
                                {{ cartStore.heldTransactions.length }}
                            </span>
                        </button>
                        <button
                            type="button"
                            class="btn btn-light rounded-circle p-1 ms-2 d-flex align-items-center justify-content-center"
                            style="width: 32px; height: 32px"
                            @click="isDrawerOpen = false"
                        >
                            <!-- Feather Icon: X (Tutup) -->
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="18"
                                height="18"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                class="feather"
                            >
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                </div>
                <!-- Shopee style "Select All" / "Pilih Semua" bar -->
                <div
                    class="px-3 py-2 bg-white border-bottom d-flex align-items-center justify-content-between"
                >
                    <div class="d-flex align-items-center gap-2">
                        <input
                            id="pos-select-all"
                            type="checkbox"
                            class="form-check-input cursor-pointer"
                            :checked="cartStore.isAllSelected"
                            :disabled="cartStore.items.length === 0"
                            @change="
                                cartStore.selectAllItems($event.target.checked)
                            "
                        />
                        <!-- FIX #4: Label lebih akurat: jumlah baris item dipilih vs total baris -->
                        <label
                            for="pos-select-all"
                            class="small fw-semibold text-secondary cursor-pointer m-0"
                        >
                            Pilih Semua ({{ cartStore.allItemsCount }} item,
                            {{
                                cartStore.selectedItemsCount ??
                                cartStore.items.filter((i) => i.selected).length
                            }}
                            terpilih)
                        </label>
                    </div>
                    <button
                        v-if="cartStore.items.some((item) => item.selected)"
                        type="button"
                        class="btn btn-link btn-sm text-danger p-0 text-decoration-none small fw-semibold d-flex align-items-center gap-1 ms-auto"
                        @click="confirmRemoveSelected"
                    >
                        <!-- Feather Icon: Trash-2 -->
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            width="12"
                            height="12"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="feather"
                        >
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path
                                d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"
                            ></path>
                            <line x1="10" y1="11" x2="10" y2="17"></line>
                            <line x1="14" y1="11" x2="14" y2="17"></line>
                        </svg>
                        Hapus Terpilih
                    </button>
                </div>
                <!-- List Belanja (Shopee style compact) -->
                <div
                    class="drawer-cart-list flex-grow-1 overflow-y-auto scrollbar-thin px-3 py-2"
                >
                    <div
                        v-if="cartStore.items.length > 0"
                        class="d-flex flex-column gap-2"
                    >
                        <div
                            v-for="item in cartStore.items"
                            :key="item.product.id"
                            class="cart-item-row p-3 mb-2 bg-white border border-light rounded shadow-sm"
                        >
                            <div class="d-flex align-items-start gap-2">
                                <!-- Checkbox Selection -->
                                <input
                                    type="checkbox"
                                    class="form-check-input cursor-pointer flex-shrink-0 mt-1"
                                    :checked="item.selected"
                                    @change="
                                        cartStore.toggleItemSelection(
                                            item.product.id,
                                        )
                                    "
                                />
                                <!-- Product Image (Compact but clear) -->
                                <div
                                    class="cart-item-img flex-shrink-0 bg-light rounded d-flex align-items-center justify-content-center overflow-hidden border border-light"
                                    style="width: 48px; height: 48px"
                                >
                                    <img
                                        v-if="item.product.image"
                                        :src="`/storage/${item.product.image}`"
                                        class="w-100 h-100 object-fit-cover"
                                        :alt="item.product.name"
                                    />
                                    <span v-else class="fs-5 opacity-50"
                                        >📦</span
                                    >
                                </div>
                                <!-- Product details & Price -->
                                <div class="flex-grow-1 min-w-0">
                                    <div
                                        class="fw-bold text-dark mb-1 text-wrap"
                                        style="
                                            font-size: 14.5px;
                                            line-height: 1.3;
                                        "
                                        :title="item.product.name"
                                    >
                                        {{ item.product.name }}
                                    </div>
                                    <div
                                        class="d-flex align-items-center gap-2"
                                    >
                                        <span
                                            class="text-body-secondary font-monospace"
                                            style="font-size: 12px"
                                            >{{
                                                fmt(item.product.selling_price)
                                            }}</span
                                        >
                                        <span
                                            v-if="item.product.barcode"
                                            class="badge bg-light text-secondary font-monospace"
                                            style="
                                                font-size: 9px;
                                                padding: 2px 5px;
                                            "
                                            >{{ item.product.barcode }}</span
                                        >
                                    </div>
                                </div>
                                <!-- Remove item button -->
                                <button
                                    type="button"
                                    class="btn btn-light rounded-circle flex-shrink-0 p-1 d-flex align-items-center justify-content-center"
                                    style="
                                        width: 24px;
                                        height: 24px;
                                        border: 0;
                                        background: #fff0f0;
                                        color: #dc3545;
                                    "
                                    title="Hapus dari keranjang"
                                    @click="confirmRemoveItem(item.product.id)"
                                >
                                    <!-- Feather Icon: Trash-2 -->
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="12"
                                        height="12"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        class="feather text-danger"
                                    >
                                        <polyline
                                            points="3 6 5 6 21 6"
                                        ></polyline>
                                        <path
                                            d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"
                                        ></path>
                                    </svg>
                                </button>
                            </div>
                            <!-- Second Row: Controls & Total Price (Indented to line up with product details) -->
                            <div
                                class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top border-light-subtle ps-5"
                            >
                                <!-- Qty input controls -->
                                <div
                                    class="input-group input-group-sm flex-shrink-0"
                                    style="width: 105px"
                                >
                                    <button
                                        class="btn btn-outline-secondary py-0 px-2 d-flex align-items-center justify-content-center"
                                        type="button"
                                        @click="
                                            handleQtyChange(
                                                item.product.id,
                                                item.qty - 1,
                                            )
                                        "
                                    >
                                        <!-- Feather Icon: Minus -->
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="12"
                                            height="12"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather"
                                        >
                                            <line
                                                x1="5"
                                                y1="12"
                                                x2="19"
                                                y2="12"
                                            ></line>
                                        </svg>
                                    </button>
                                    <input
                                        type="number"
                                        class="form-control text-center p-0 font-monospace fw-bold"
                                        style="font-size: 13px"
                                        :value="item.qty"
                                        min="1"
                                        :max="item.product.stock"
                                        @change="
                                            handleQtyChange(
                                                item.product.id,
                                                $event.target.value,
                                            )
                                        "
                                    />
                                    <button
                                        class="btn btn-outline-secondary py-0 px-2 d-flex align-items-center justify-content-center"
                                        type="button"
                                        @click="
                                            handleQtyChange(
                                                item.product.id,
                                                item.qty + 1,
                                            )
                                        "
                                    >
                                        <!-- Feather Icon: Plus -->
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="12"
                                            height="12"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2.5"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather"
                                        >
                                            <line
                                                x1="12"
                                                y1="5"
                                                x2="12"
                                                y2="19"
                                            ></line>
                                            <line
                                                x1="5"
                                                y1="12"
                                                x2="19"
                                                y2="12"
                                            ></line>
                                        </svg>
                                    </button>
                                </div>
                                <!-- Item Total Price -->
                                <div class="text-end">
                                    <span
                                        class="fw-bold text-dark"
                                        style="font-size: 17px"
                                        >{{
                                            fmt(
                                                item.product.selling_price *
                                                    item.qty -
                                                    item.discount,
                                            )
                                        }}</span
                                    >
                                    <div
                                        v-if="item.discount > 0"
                                        class="text-danger font-monospace"
                                        style="font-size: 11px; line-height: 1"
                                    >
                                        -{{ fmt(item.discount) }}
                                    </div>
                                </div>
                            </div>
                            <!-- Expandable Custom Discount & Note per Item Action Strip -->
                            <div
                                class="d-flex gap-3 align-items-center mt-2 pt-1 ps-5 border-top-0"
                            >
                                <button
                                    type="button"
                                    class="btn btn-link btn-xs text-secondary p-0 text-decoration-none d-flex align-items-center gap-1"
                                    style="font-size: 12.5px"
                                    @click="
                                        toggleEditRow(
                                            item.product.id,
                                            'discount',
                                        )
                                    "
                                >
                                    <!-- Feather Icon: Tag -->
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="12"
                                        height="12"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        class="feather"
                                    >
                                        <path
                                            d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"
                                        ></path>
                                        <line
                                            x1="7"
                                            y1="7"
                                            x2="7.01"
                                            y2="7"
                                        ></line>
                                    </svg>
                                    <span
                                        :class="
                                            item.discount > 0
                                                ? 'text-primary fw-semibold'
                                                : ''
                                        "
                                    >
                                        {{
                                            item.discount > 0
                                                ? `Diskon: ${fmt(item.discount)}`
                                                : "Diskon"
                                        }}
                                    </span>
                                </button>
                                <span
                                    class="text-black-50"
                                    style="font-size: 11px"
                                    >|</span
                                >
                                <button
                                    type="button"
                                    class="btn btn-link btn-xs text-secondary p-0 text-decoration-none d-flex align-items-center gap-1"
                                    style="font-size: 12.5px"
                                    @click="
                                        toggleEditRow(item.product.id, 'note')
                                    "
                                >
                                    <!-- Feather Icon: FileText -->
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="12"
                                        height="12"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        class="feather"
                                    >
                                        <path
                                            d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"
                                        ></path>
                                        <polyline
                                            points="14 2 14 8 20 8"
                                        ></polyline>
                                        <line
                                            x1="16"
                                            y1="13"
                                            x2="8"
                                            y2="13"
                                        ></line>
                                        <line
                                            x1="16"
                                            y1="17"
                                            x2="8"
                                            y2="17"
                                        ></line>
                                    </svg>
                                    <span
                                        :class="
                                            item.note
                                                ? 'text-primary fw-semibold'
                                                : ''
                                        "
                                    >
                                        Catatan
                                    </span>
                                </button>
                                <div
                                    v-if="
                                        item.note &&
                                        activeEditRow[item.product.id] !==
                                            'note'
                                    "
                                    class="text-secondary text-truncate ms-1 text-italic"
                                    style="font-size: 12px; max-width: 150px"
                                    :title="item.note"
                                >
                                    "{{ item.note }}"
                                </div>
                            </div>
                            <!-- Expandable Input Fields -->
                            <div
                                v-if="
                                    activeEditRow[item.product.id] ===
                                    'discount'
                                "
                                class="mt-2 ps-5 col-10"
                            >
                                <div class="input-group input-group-sm">
                                    <span
                                        class="input-group-text bg-light text-secondary font-monospace"
                                        style="font-size: 10px"
                                        >Rp</span
                                    >
                                    <input
                                        type="number"
                                        class="form-control form-control-sm font-monospace"
                                        placeholder="Diskon item"
                                        :value="item.discount"
                                        @input="
                                            cartStore.updateItemDiscount(
                                                item.product.id,
                                                $event.target.value,
                                            )
                                        "
                                    />
                                    <button
                                        class="btn btn-primary btn-sm py-0 px-2 d-flex align-items-center justify-content-center"
                                        type="button"
                                        @click="
                                            activeEditRow = {
                                                ...activeEditRow,
                                                [item.product.id]: null,
                                            }
                                        "
                                    >
                                        <!-- Feather Icon: Check -->
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="10"
                                            height="10"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="3"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather"
                                        >
                                            <polyline
                                                points="20 6 9 17 4 12"
                                            ></polyline>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div
                                v-if="activeEditRow[item.product.id] === 'note'"
                                class="mt-2 ps-5 col-12"
                            >
                                <div class="input-group input-group-sm">
                                    <input
                                        type="text"
                                        class="form-control form-control-sm"
                                        placeholder="Catatan belanja..."
                                        :value="item.note"
                                        @input="
                                            cartStore.updateItemNote(
                                                item.product.id,
                                                $event.target.value,
                                            )
                                        "
                                    />
                                    <button
                                        class="btn btn-primary btn-sm py-0 px-2 d-flex align-items-center justify-content-center"
                                        type="button"
                                        @click="
                                            activeEditRow = {
                                                ...activeEditRow,
                                                [item.product.id]: null,
                                            }
                                        "
                                    >
                                        <!-- Feather Icon: Check -->
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="10"
                                            height="10"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="3"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            class="feather"
                                        >
                                            <polyline
                                                points="20 6 9 17 4 12"
                                            ></polyline>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        v-else
                        class="text-center py-5 text-body-secondary bg-white rounded border border-light m-2 shadow-sm"
                    >
                        <div class="fs-1 opacity-25">🛒</div>
                        <small class="d-block mt-2 fw-semibold text-secondary"
                            >Keranjang Belanja Kosong</small
                        >
                        <button
                            type="button"
                            class="btn btn-primary btn-sm mt-3 px-3 rounded-pill"
                            @click="isDrawerOpen = false"
                        >
                            Kembali Belanja
                        </button>
                    </div>
                </div>
                <!-- Drawer Checkout & Kalkulasi Footer -->
                <div
                    ref="footerRef"
                    class="drawer-footer border-top bg-white shadow d-flex flex-column"
                    :style="footerHeight ? { height: `${footerHeight}px` } : {}"
                >
                    <!-- Resizable Drag Handle Bar -->
                    <div
                        class="footer-drag-handle d-flex justify-content-center align-items-center py-2"
                        @mousedown="startDrag"
                        @touchstart="startDrag"
                    >
                        <div class="drag-bar"></div>
                    </div>

                    <!-- Scrollable Content Area -->
                    <div
                        class="drawer-footer-content px-3 pb-3 flex-grow-1 overflow-y-auto"
                    >
                        <div class="row g-2 mb-1 pos-compact-inputs">
                            <div class="col-6">
                                <!-- Customer Selection -->
                                <BaseSelect
                                    v-model="selectedCustomer"
                                    label="Customer / Member"
                                    :options="customers"
                                    placeholder="Pilih Customer Umum"
                                />
                            </div>
                            <div class="col-3">
                                <BaseInput
                                    label="Pajak (%)"
                                    type="number"
                                    size="sm"
                                    :model-value="cartStore.taxRate"
                                    @update:model-value="
                                        cartStore.setTaxRate($event)
                                    "
                                />
                            </div>
                            <div class="col-3">
                                <BaseInput
                                    label="Diskon Transaksi"
                                    type="number"
                                    size="sm"
                                    :model-value="cartStore.discountValue"
                                    @update:model-value="
                                        cartStore.setDiscount(
                                            cartStore.discountType,
                                            $event,
                                        )
                                    "
                                />
                            </div>
                        </div>
                        <!-- Details Block -->
                        <div class="bg-light p-2 px-3 rounded mb-2">
                            <div
                                class="d-flex justify-content-between mb-1 small text-secondary"
                            >
                                <span>Subtotal</span>
                                <span>{{ fmt(cartStore.itemSubtotal) }}</span>
                            </div>
                            <div
                                class="d-flex justify-content-between mb-1 small text-danger"
                            >
                                <span>Diskon Transaksi</span>
                                <span
                                    >-
                                    {{
                                        fmt(cartStore.transactionDiscountAmount)
                                    }}</span
                                >
                            </div>
                            <div
                                class="d-flex justify-content-between mb-1 small text-secondary"
                            >
                                <span>Pajak ({{ cartStore.taxRate }}%)</span>
                                <span>{{ fmt(cartStore.taxAmount) }}</span>
                            </div>
                            <hr class="my-2" />
                            <div
                                class="d-flex justify-content-between align-items-center"
                            >
                                <h5 class="fw-bold text-dark m-0">
                                    Grand Total
                                </h5>
                                <h4 class="fw-bold text-primary m-0">
                                    {{ fmt(cartStore.grandTotal) }}
                                </h4>
                            </div>
                        </div>
                        <!-- Payment Details -->
                        <div class="mb-2">
                            <label class="form-label fw-semibold small m-0 mb-1"
                                >Metode Pembayaran</label
                            >
                            <div class="d-flex gap-1">
                                <button
                                    v-for="method in [
                                        'cash',
                                        'transfer',
                                        'qris',
                                        'e-wallet',
                                    ]"
                                    :key="method"
                                    class="btn btn-sm py-1 flex-fill"
                                    :class="
                                        cartStore.paymentMethod === method
                                            ? 'btn-primary'
                                            : 'btn-outline-primary'
                                    "
                                    @click="cartStore.setPaymentMethod(method)"
                                >
                                    {{ method.toUpperCase() }}
                                </button>
                            </div>
                        </div>
                        <!-- Cash Input Panel -->
                        <div
                            v-if="cartStore.paymentMethod === 'cash'"
                            class="mb-2 pos-compact-inputs"
                        >
                            <div class="row g-2 align-items-end">
                                <div class="col-6">
                                    <BaseInput
                                        label="Jumlah Bayar (Rp)"
                                        type="number"
                                        size="sm"
                                        :model-value="cartStore.amountPaid"
                                        @update:model-value="
                                            cartStore.setAmountPaid($event)
                                        "
                                        class="mb-0"
                                    />
                                </div>
                                <div class="col-6">
                                    <label
                                        class="form-label d-block mb-1"
                                        style="
                                            visibility: hidden;
                                            font-size: 11px;
                                            margin-bottom: 0.15rem !important;
                                        "
                                        >&nbsp;</label
                                    >
                                    <button
                                        class="btn btn-outline-success btn-sm w-100 fw-semibold d-flex align-items-center justify-content-center"
                                        style="
                                            height: 32px;
                                            font-size: 12.5px;
                                            padding: 0;
                                        "
                                        @click="setQuickCash('pas')"
                                    >
                                        Uang Pas
                                    </button>
                                </div>
                            </div>
                            <!-- Quick cash buttons -->
                            <div class="d-flex flex-wrap gap-1 mt-2">
                                <button
                                    v-for="amt in quickCashAmounts"
                                    :key="amt"
                                    class="btn btn-outline-secondary btn-sm flex-fill fw-bold py-1.5 px-2 border-secondary-subtle"
                                    style="font-size: 12.5px; min-width: 75px"
                                    @click="setQuickCash(amt)"
                                >
                                    {{ fmtNoSymbol(amt) }}
                                </button>
                            </div>
                            <!-- Change feedback -->
                            <div
                                class="mt-2 p-2 bg-success bg-opacity-10 border border-success rounded text-success d-flex justify-content-between align-items-center py-1"
                            >
                                <span class="small fw-semibold"
                                    >Kembalian:</span
                                >
                                <span class="fw-bold fs-6">{{
                                    fmt(cartStore.changeAmount)
                                }}</span>
                            </div>
                        </div>
                        <!-- Checkout Action Buttons -->
                        <div class="row g-2 mt-3">
                            <div class="col-4">
                                <BaseButton
                                    variant="outline-danger"
                                    block
                                    title="Kosongkan Keranjang (F7)"
                                    @click="confirmClearCart"
                                >
                                    Reset
                                </BaseButton>
                            </div>
                            <div class="col-8">
                                <BaseButton
                                    variant="success"
                                    block
                                    :loading="checkoutForm.processing"
                                    title="Bayar & Checkout (F4)"
                                    @click="processCheckout"
                                >
                                    💵 Proses Pembayaran (F4)
                                </BaseButton>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal Scan Barcode via Kamera Device -->
        <BaseModal
            :show="isCameraModalOpen"
            title="Scan Barcode via Kamera"
            :show-footer="false"
            @close="closeCameraScanner"
        >
            <div class="p-1">
                <!-- Dropdown pemilih kamera apabila terdeteksi lebih dari 1 kamera pada device -->
                <div v-if="cameraDevices.length > 1" class="mb-3">
                    <label class="form-label fw-semibold small text-secondary">Pilih Kamera</label>
                    <select v-model="activeCameraId" class="form-select form-select-sm" @change="handleCameraChange">
                        <option v-for="device in cameraDevices" :key="device.id" :value="device.id">
                            {{ device.label || `Kamera ${cameraDevices.indexOf(device) + 1}` }}
                        </option>
                    </select>
                </div>
                
                <!-- Frame visual pembidik barcode berdesain futuristik premium -->
                <div class="position-relative bg-dark rounded-4 overflow-hidden border border-secondary border-opacity-25 shadow-lg" style="aspect-ratio: 4/3; max-width: 480px; margin: 0 auto;">
                    <!-- Elemen target yang akan disisipi streaming video kamera oleh library html5-qrcode -->
                    <div id="camera-scanner-preview" class="w-100 h-100"></div>
                    
                    <!-- Garis Laser Sensor Merah Berkedip Naik Turun (Looping Animation) -->
                    <div class="scanner-laser-line"></div>
                    
                    <!-- Sisi Siku Target Kotak Bidik Hijau (Target Corners CSS Overlay) -->
                    <div class="scanner-corner top-left"></div>
                    <div class="scanner-corner top-right"></div>
                    <div class="scanner-corner bottom-left"></div>
                    <div class="scanner-corner bottom-right"></div>
                </div>
                
                <div class="text-center mt-3 bg-light p-2.5 rounded-3 border">
                    <small class="text-secondary d-block fw-semibold" style="font-size: 12px;">
                        Posisikan garis barcode produk agar sejajar lurus tepat di dalam area pemindaian.
                    </small>
                    <small class="text-success fw-bold mt-1.5 d-flex align-items-center justify-content-center gap-1.5" style="font-size: 11px;">
                        <span>🔊</span> Umpan balik bunyi beep audio aktif otomatis jika barcode berhasil ter-scan!
                    </small>
                </div>
            </div>
        </BaseModal>

        <!-- Modal Held Transactions -->
        <BaseModal
            :show="showHeldModal"
            title="Daftar Transaksi Ditangguhkan (Hold)"
            :show-footer="false"
            @close="showHeldModal = false"
        >
            <div
                v-if="cartStore.heldTransactions.length > 0"
                class="list-group"
            >
                <div
                    v-for="tx in cartStore.heldTransactions"
                    :key="tx.id"
                    class="list-group-item d-flex justify-content-between align-items-center"
                >
                    <div>
                        <div class="fw-bold">
                            Pukul {{ tx.time }} ({{
                                tx.items.reduce((s, i) => s + i.qty, 0)
                            }}
                            item)
                        </div>
                        <small class="text-secondary"
                            >Customer: {{ tx.customer?.name ?? "Umum" }}</small
                        >
                    </div>
                    <div class="d-flex gap-1">
                        <button
                            class="btn btn-primary btn-sm"
                            @click="resumeTx(tx.id)"
                        >
                            Lanjutkan
                        </button>
                        <button
                            class="btn btn-outline-danger btn-sm"
                            @click="cartStore.deleteHeldTransaction(tx.id)"
                        >
                            Hapus
                        </button>
                    </div>
                </div>
            </div>
            <div v-else class="text-center py-4 text-body-secondary">
                Tidak ada transaksi yang ditangguhkan.
            </div>
        </BaseModal>
        <!-- Thermal Receipt Modal -->
        <BaseModal
            :show="showReceiptModal"
            title="Struk Penjualan"
            confirm-text="Print Struk"
            confirm-variant="success"
            @close="showReceiptModal = false"
            @confirm="printReceipt"
        >
            <div
                id="thermal-receipt"
                class="d-flex justify-content-center p-3 bg-white border rounded"
            >
                <div
                    id="thermal-receipt-body"
                    style="
                        font-family:
                            &quot;Courier New&quot;, Courier, monospace;
                        width: 300px;
                        font-size: 12px;
                        color: #000;
                    "
                >
                    <div class="text-center">
                        <h4 style="margin: 0 0 5px 0; font-weight: bold">
                            POS KASIR PREMIUM
                        </h4>
                        <div style="font-size: 10px">
                            Jl. Raya Utama No. 88, Jakarta
                        </div>
                        <div style="font-size: 10px">Telp: 0812-3456-7890</div>
                        <div class="divider">
                            --------------------------------
                        </div>
                    </div>
                    <div style="font-size: 11px">
                        <div class="item-row">
                            <span>No Invoice :</span>
                            <span>{{ receiptData?.invoice_no }}</span>
                        </div>
                        <div class="item-row">
                            <span>Tanggal :</span>
                            <span>{{ receiptData?.created_at }}</span>
                        </div>
                        <div class="item-row">
                            <span>Kasir :</span>
                            <span>{{ receiptData?.cashier }}</span>
                        </div>
                        <div class="item-row">
                            <span>Customer :</span>
                            <span>{{ receiptData?.customer }}</span>
                        </div>
                    </div>
                    <div class="divider">--------------------------------</div>
                    <div style="font-size: 11px">
                        <div
                            v-for="(it, idx) in receiptData?.items"
                            :key="idx"
                            class="mb-2"
                        >
                            <div class="fw-bold">{{ it.name }}</div>
                            <div class="item-row">
                                <span
                                    >{{ it.qty }} x
                                    {{ fmtNoSymbol(it.price) }}</span
                                >
                                <span>{{ fmt(it.subtotal) }}</span>
                            </div>
                            <div
                                v-if="it.discount > 0"
                                class="item-row text-danger"
                                style="font-size: 10px"
                            >
                                <span> Diskon Item</span>
                                <span>-{{ fmt(it.discount) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="divider">--------------------------------</div>
                    <div style="font-size: 11px">
                        <div class="item-row">
                            <span>Subtotal:</span>
                            <span>{{ fmt(receiptData?.subtotal) }}</span>
                        </div>
                        <div class="item-row text-danger">
                            <span>Diskon:</span>
                            <span>-{{ fmt(receiptData?.discount) }}</span>
                        </div>
                        <div class="item-row">
                            <span>Pajak:</span>
                            <span>{{ fmt(receiptData?.tax) }}</span>
                        </div>
                        <div class="divider">
                            --------------------------------
                        </div>
                        <div class="totals-row fs-6">
                            <span>Grand Total:</span>
                            <span>{{ fmt(receiptData?.grand_total) }}</span>
                        </div>
                        <div class="item-row">
                            <span>Metode Bayar:</span>
                            <span>{{ receiptData?.payment_method }}</span>
                        </div>
                        <div class="item-row">
                            <span>Bayar:</span>
                            <span>{{ fmt(receiptData?.amount_paid) }}</span>
                        </div>
                        <div class="item-row">
                            <span>Kembali:</span>
                            <span>{{ fmt(receiptData?.change) }}</span>
                        </div>
                    </div>
                    <div class="divider">--------------------------------</div>
                    <div
                        class="text-center"
                        style="font-size: 10px; margin-top: 15px"
                    >
                        <div>TERIMA KASIH ATAS KUNJUNGAN ANDA</div>
                        <div>Barang yang sudah dibeli tidak dapat ditukar</div>
                    </div>
                </div>
            </div>
        </BaseModal>
        <!-- Hidden iframe for silent printing -->
        <iframe
            id="receipt-print-iframe"
            style="position: absolute; width: 0; height: 0; border: 0"
            src="about:blank"
        ></iframe>
    </div>
</template>
<!-- Styling has been moved to global resources/scss/style.scss for full structured layout -->

<style scoped>
/* =========================================================================
   ESTETIKA VISUAL PREMIUM UNTUK SCANNER KAMERA & HARDWARE
   ========================================================================= */

/* Garis sensor laser pemindai kamera (Bergerak naik turun secara halus) */
.scanner-laser-line {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 3.5px;
    background: linear-gradient(to right, transparent, #10b981, transparent);
    box-shadow: 0 0 10px #10b981, 0 0 20px #10b981;
    z-index: 5;
    animation: scanLineAnimation 2.2s infinite ease-in-out;
}

/* Siku-siku bidik target kamera berona hijau futuristik */
.scanner-corner {
    position: absolute;
    width: 24px;
    height: 24px;
    border: 3.5px solid #10b981;
    z-index: 6;
}
.scanner-corner.top-left { top: 16px; left: 16px; border-right: none; border-bottom: none; border-radius: 6px 0 0 0; }
.scanner-corner.top-right { top: 16px; right: 16px; border-left: none; border-bottom: none; border-radius: 0 6px 0 0; }
.scanner-corner.bottom-left { bottom: 16px; left: 16px; border-right: none; border-top: none; border-radius: 0 0 0 6px; }
.scanner-corner.bottom-right { bottom: 16px; right: 16px; border-left: none; border-top: none; border-radius: 0 0 6px 0; }

/* Animasi sensor gerak laser pemindai */
@keyframes scanLineAnimation {
    0% { top: 10%; }
    50% { top: 90%; }
    100% { top: 10%; }
}

/* Animasi berkedip lembut untuk indikator status fisik (Pulsing Dot) */
@keyframes pulse {
    0% { transform: scale(0.85); opacity: 0.75; }
    50% { transform: scale(1.15); opacity: 1; }
    100% { transform: scale(0.85); opacity: 0.75; }
}

.pulse-animation {
    animation: pulse 1.6s infinite ease-in-out;
}

/* Custom premium hover effect untuk input field barcode */
#pos-search-input {
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
#pos-search-input:focus {
    box-shadow: 0 4px 14px rgba(59, 130, 246, 0.15);
    border-color: #3b82f6;
}
</style>
