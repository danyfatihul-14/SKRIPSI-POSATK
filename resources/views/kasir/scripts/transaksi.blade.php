<script>
    var currentQrisTransactionId = null;

    function getActivePaymentSource() {
        if (window.selectedPendingTransaction) {
            const rawSubtotal = Number(window.selectedPendingTransaction.subtotal || 0);
            const rawDiscount = Number(window.selectedPendingTransaction.discount || 0);
            const rawTotal = Number(window.selectedPendingTransaction.total_amount || 0);

            const items = window.selectedPendingTransaction.items || [];
            let grossSubtotal = items.reduce(function(sum, item) {
                return sum + (Number(item.qty || 0) * Number(item.price || 0));
            }, 0);

            if (grossSubtotal <= 0) {
                grossSubtotal = rawSubtotal > rawTotal ? rawSubtotal : (rawTotal + rawDiscount);
            }

            return {
                type: "pending",
                subtotal: grossSubtotal,
                discount: rawDiscount,
                total: rawTotal,
                items: items,
                code: window.selectedPendingTransaction.transaction_code || "-",
                served_by: window.selectedPendingTransaction.served_by || "-",
                created_at: window.selectedPendingTransaction.created_at || "-"
            };
        }
        const items = (window.cart || []).map(function(item) {
            return {
                product_name: item.name,
                qty: Number(item.qty || 0),
                price: Number(item.price || 0),
                subtotal: Number(item.qty || 0) * Number(item.price || 0)
            };
        });
        const grossSubtotal = items.reduce(function(sum, item) {
            return sum + item.subtotal;
        }, 0);
        return {
            type: "direct",
            subtotal: grossSubtotal,
            discount: 0,
            total: grossSubtotal,
            items: items,
            code: "-",
            served_by: "-",
            created_at: "-"
        };
    }

    function clearSelectedPendingTransaction() {
        window.selectedPendingTransaction = null;
        const paymentEl = document.getElementById("payment");
        if (paymentEl) paymentEl.value = "";
        renderOrderSummary();
    }

    function updatePaymentContextUI(source) {
        const box = document.getElementById("paymentContextBox");
        const title = document.getElementById("paymentContextTitle");
        const meta = document.getElementById("paymentContextMeta");
        const clearBtn = document.getElementById("btnClearPending");
        const submitLabel = document.getElementById("paymentSubmitLabel");
        if (!box || !title || !meta || !clearBtn || !submitLabel) return;
        box.style.display = "block";
        if (source.type === "pending") {
            title.innerText = "Mode: Pembayaran transaksi pending";
            meta.innerText = "Kode: " + source.code + " | Pelayan: " + source.served_by + " | Waktu: " + source.created_at;
            clearBtn.style.display = "inline-block";
            submitLabel.innerText = "Bayar Transaksi Pending";
        } else {
            title.innerText = "Mode: Pembayaran langsung dari keranjang";
            meta.innerText = "Transaksi baru kasir (belum ada kode transaksi).";
            clearBtn.style.display = "none";
            submitLabel.innerText = "Proses Transaksi";
        }
    }

    function renderOrderSummary() {
        const summary = document.getElementById("orderSummary");
        const cartInput = document.getElementById("cartInput");
        const pendingInput = document.getElementById("pendingTransactionId");
        const source = getActivePaymentSource();

        updatePaymentContextUI(source);

        if (!source.items.length) {
            if (summary) {
                summary.innerHTML = "<div style='text-align:center; padding:24px 0; color:var(--dark-light); font-size:13px;'>Belum ada transaksi yang dipilih</div>";
            }
            updateBreakdownUI({
                subtotal: 0,
                discount: 0,
                tax: 0,
                qrisFee: 0,
                total: 0
            });
            if (cartInput) cartInput.value = "[]";
            if (pendingInput) pendingInput.value = "";
            renderQuickPay(0);
            updateChange();
            return;
        }

        let html = "";
        source.items.forEach(function(item) {
            const itemGross = Number(item.qty || 0) * Number(item.price || 0);
            html += "<div style='display:flex; justify-content:space-between; align-items:center; padding:12px 0; border-bottom:1px solid var(--light);'>" + "<div style='flex:1;'>" + "<div style='font-weight:600; color:var(--dark); font-size:14px;'>" + item.product_name + "</div>" + "<div style='font-size:12px; color:var(--dark-light); margin-top:2px;'>x" + item.qty + " = Rp" + itemGross.toLocaleString("id-ID") + "</div>" + "</div>" + "<div style='font-weight:700; color:var(--accent); font-size:14px; min-width:120px; text-align:right;'>Rp" + itemGross.toLocaleString("id-ID") + "</div>" + "</div>";
        });
        if (summary) summary.innerHTML = html;

        const breakdown = getBreakdown(source);
        updateBreakdownUI(breakdown);

        if (window.selectedPendingTransaction) {
            if (cartInput) cartInput.value = getActiveCartPayload();
            if (pendingInput) pendingInput.value = window.selectedPendingTransaction.transaction_id;
        } else {
            if (cartInput) cartInput.value = JSON.stringify(window.cart || []);
            if (pendingInput) pendingInput.value = "";
        }
        renderQuickPay(breakdown.total);
        updateChange();
    }

    function renderQuickPay(total) {
        const nominals = [Math.ceil(total / 1000) * 1000, Math.ceil(total / 5000) * 5000, Math.ceil(total / 10000) * 10000, 50000, 100000, 200000];
        const uniqueNominals = Array.from(new Set(nominals)).filter(function(n) {
            return n >= total;
        }).sort(function(a, b) {
            return a - b;
        }).slice(0, 6);
        const quickPayEl = document.getElementById("quickPay");
        if (!quickPayEl) return;
        quickPayEl.innerHTML = uniqueNominals.map(function(n) {
            return "<button type='button' onclick='setPayment(" + n + ")' style='background:white; color:var(--accent); border:2px solid var(--accent); padding:10px 12px; border-radius:10px; font-size:12px; font-weight:700; cursor:pointer; transition:all .2s; font-family:inherit;'>Rp" + n.toLocaleString("id-ID") + "</button>";
        }).join("");
    }

    function setPayment(val) {
        const paymentEl = document.getElementById("payment");
        if (!paymentEl) return;
        paymentEl.value = val;
        updateChange();
    }

    function updateChange() {
        const method = (document.getElementById("paymentMethod") || {}).value || "cash";
        const total = Number(getBreakdown(getActivePaymentSource()).total || 0);
        const paymentInputEl = document.getElementById("payment");
        // QRIS selalu dibayar pas sesuai Total Belanja (setelah diskon)
        if (method === "qris" && paymentInputEl) {
            paymentInputEl.value = total;
            const qrisAmountEl = document.getElementById("qrisAmount");
            if (qrisAmountEl) qrisAmountEl.innerText = formatRupiah(total);
        }
        const bayar = parseInt((paymentInputEl || {}).value || 0, 10);
        const kembali = bayar - total;
        const changeEl = document.getElementById("change");
        const warningEl = document.getElementById("paymentWarning");
        const submitBtn = document.getElementById("paymentSubmitBtn");

        if (!changeEl) return;

        const insufficient = method !== "qris" && kembali < 0;

        if (!insufficient) {
            changeEl.innerHTML = toRupiah(Math.max(kembali, 0));
            changeEl.style.color = "#5C4C42";
            if (warningEl) warningEl.style.display = "none";
            if (submitBtn) submitBtn.disabled = false;
        } else {
            changeEl.innerHTML = "Kurang: " + toRupiah(Math.abs(kembali));
            changeEl.style.color = "#ef4444";
            if (warningEl) warningEl.style.display = "block";
            if (submitBtn) submitBtn.disabled = true;
        }
    }

    function toRupiah(n) {
        return "Rp" + Number(n || 0).toLocaleString("id-ID");
    }

    function getBreakdown(source) {
        const method = (document.getElementById("paymentMethod") || {}).value || "cash";
        const grossSubtotal = Number(source.subtotal || 0);

        const discountTypeEl = document.getElementById("checkoutDiscountType");
        const discountValueEl = document.getElementById("checkoutDiscountValue");
        const discountSymbolEl = document.getElementById("checkoutDiscountSymbol");
        const discountNoteEl = document.getElementById("checkoutDiscountNote");

        const discountType = discountTypeEl ? discountTypeEl.value : "rp";
        const discountVal = discountValueEl ? parseFloat(discountValueEl.value || 0) : 0;

        if (discountSymbolEl) {
            discountSymbolEl.innerText = discountType === "percent" ? "%" : "Rp";
        }
        if (discountNoteEl) {
            discountNoteEl.style.display = discountType === "percent" ? "block" : "none";
        }

        let discountAmount = 0;
        let discountedSubtotal = grossSubtotal;

        if (grossSubtotal > 0 && discountVal > 0) {
            if (discountType === "percent") {
                const rawAfter = grossSubtotal * (1 - (Math.min(100, Math.max(0, discountVal)) / 100));
                // Round UP to nearest 500
                discountedSubtotal = Math.ceil(rawAfter / 500) * 500;
                if (discountedSubtotal > grossSubtotal) discountedSubtotal = grossSubtotal;
                discountAmount = Math.max(0, grossSubtotal - discountedSubtotal);
            } else {
                discountAmount = Math.min(grossSubtotal, Math.max(0, discountVal));
                discountedSubtotal = Math.max(0, grossSubtotal - discountAmount);
            }
        }

        const tax = 0;
        const qrisFee = 0;
        const total = discountedSubtotal + tax;

        return {
            subtotal: grossSubtotal,
            discount: discountAmount,
            tax: tax,
            qrisFee: qrisFee,
            total: total
        };
    }


    function updateBreakdownUI(breakdown) {
        const subtotalEl = document.getElementById("breakdownSubtotal");
        const discountEl = document.getElementById("breakdownDiscount");
        const taxEl = document.getElementById("breakdownTax");
        const qrisFeeEl = document.getElementById("breakdownQrisFee");
        const totalEl = document.getElementById("orderTotal");
        if (subtotalEl) subtotalEl.innerText = toRupiah(breakdown.subtotal);
        if (discountEl) discountEl.innerText = toRupiah(breakdown.discount);
        if (taxEl) taxEl.innerText = toRupiah(breakdown.tax);
        if (qrisFeeEl) qrisFeeEl.innerText = toRupiah(breakdown.qrisFee || 0);
        if (totalEl) totalEl.innerText = toRupiah(breakdown.total);
    }

    function setSubmitLoading(isLoading) {
        const btn = document.getElementById("paymentSubmitBtn");
        const label = document.getElementById("paymentSubmitLabel");
        if (!btn || !label) return;
        btn.disabled = isLoading;
        btn.style.opacity = isLoading ? "0.7" : "1";
        btn.style.cursor = isLoading ? "not-allowed" : "pointer";
        if (isLoading) {
            label.innerText = "Memproses...";
        } else {
            const source = getActivePaymentSource();
            label.innerText = source.type === "pending" ? "Bayar Transaksi Pending" : "Proses Transaksi";
        }
    }
    var qrisPaid = false;
    var isGeneratingQris = false;

    function safeParseJson(raw) {
        try {
            return JSON.parse(raw);
        } catch (e) {
            return null;
        }
    }

    function getActiveCartPayload() {
        if (window.selectedPendingTransaction && Array.isArray(window.selectedPendingTransaction.items)) {
            const mapped = window.selectedPendingTransaction.items.map(function(item) {
                return {
                    id: item.product_id || item.id,
                    price: Number(item.price || item.unit_price || 0),
                    qty: Number(item.qty || item.quantity || 0)
                };
            }).filter(function(row) {
                return row.id && row.qty > 0;
            });
            return JSON.stringify(mapped);
        }
        return JSON.stringify(window.cart || []);
    }

    function updateWaitingPaymentBadge(delta) {
        const badge = document.getElementById('waitingPaymentBadge');
        if (!badge) return;

        const current = parseInt(badge.innerText || '0', 10) || 0;
        badge.innerText = Math.max(current + delta, 0);
    }

    function finalizePendingPayment(paymentMethod, payment) {
        if (!window.selectedPendingTransaction) return Promise.resolve();

        const customerName = document.getElementById('customerName')?.value || 'Pelanggan Umum';
        const discountTypeInput = document.getElementById("checkoutDiscountType");
        const discountValueInput = document.getElementById("checkoutDiscountValue");

        return fetch("/kasir/pending/" + window.selectedPendingTransaction.transaction_id + "/pay", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                payment_method: paymentMethod,
                payment: Number(payment || 0),
                customer_name: customerName,
                discount_type: discountTypeInput ? discountTypeInput.value : "rp",
                discount_value: discountValueInput ? discountValueInput.value : 0
            })
        }).then(async function(r) {
            const raw = await r.text();
            const data = safeParseJson(raw) || {};
            if (!r.ok) {
                throw new Error(data.message || "Gagal memproses pembayaran pending.");
            }

            window.showAlert(data.message || "Pembayaran berhasil.");
            askPrintReceipt(data.receipt_url);

            removePendingTransactionById(window.selectedPendingTransaction?.transaction_id);

            window.selectedPendingTransaction = null;
            const paymentEl = document.getElementById("payment");
            if (paymentEl) paymentEl.value = "";
            renderOrderSummary();
            if (typeof renderPendingList === "function") renderPendingList();
            if (typeof fetchProducts === "function") {
                const searchInput = document.getElementById('productSearch');
                fetchProducts(searchInput ? searchInput.value : '', true);
            }
        });
        setWaitingPaymentBadge(pendingList.length);
    }

    function loadMidtransScript() {
        return new Promise(function(resolve, reject) {
            if (window.snap) return resolve();

            const snapUrl = "{{ config('services.midtrans.is_production') ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' }}";

            const existing = document.querySelector("script[src='" + snapUrl + "']");
            if (existing) {
                existing.addEventListener("load", function() {
                    resolve();
                });
                existing.addEventListener("error", function() {
                    reject(new Error("Gagal load Midtrans Snap."));
                });
                return;
            }

            const script = document.createElement("script");
            script.src = snapUrl;
            script.setAttribute("data-client-key", "{{ config('services.midtrans.client_key') }}");
            script.onload = function() {
                resolve();
            };
            script.onerror = function() {
                reject(new Error("Gagal load Midtrans Snap."));
            };
            document.head.appendChild(script);
        });
    }

    var snapResizeObserver = null;

    function removeSnapEmbedOverlay() {
        const overlay = document.getElementById('snap-embed-overlay');
        if (overlay) overlay.remove();
    }

    function injectSnapOverlayStyles() {
        let style = document.getElementById('snap-embed-custom-style');
        if (style) return;

        style = document.createElement('style');
        style.id = 'snap-embed-custom-style';

        style.innerHTML = `
        /* Overlay */
        #snap-embed-overlay{
            position:fixed;
            inset:0;
            z-index:999999;
            background:rgba(0,0,0,.75);

            display:flex;
            justify-content:center;
            align-items:center;

            padding:20px;
            backdrop-filter: blur(4px);
        }

        /* Panel utama */
        #snap-embed-panel{
            position:relative;

            width:min(860px, 94vw);
            height:min(900px, 92vh);

            background:#fff;
            border-radius:24px;

            overflow:hidden;
            box-shadow:0 25px 80px rgba(0,0,0,.35);
        }

        /* Container Snap */
        #snap-container{
            width:100%;
            height:100%;

            overflow:auto;
            overflow-x:hidden;

            background:#fff;
            padding:0;
        }

        /* iframe Midtrans */
        #snap-container iframe{
            width:100% !important;
            height:920px !important;
            border:none !important;
            display:block;
        }

        /* Tombol close */
        #snap-close-btn{
            position:absolute;
            top:14px;
            right:14px;

            width:42px;
            height:42px;

            border:none;
            border-radius:50%;

            background:#111827;
            color:white;

            font-size:22px;
            cursor:pointer;

            z-index:20;
        }

        /* Desktop besar */
        @media (min-width: 1400px){
            #snap-embed-panel{
                width:min(960px, 92vw);
            }
        }

        /* Mobile */
        @media(max-width:768px){
            #snap-embed-panel{
                width:100vw;
                height:100vh;
                border-radius:0;
            }

            #snap-container iframe{
                height:100vh !important;
            }
        }

        /* Animasi */
        @keyframes snapFade{
            from{
                opacity:0;
                transform:scale(.96);
            }
            to{
                opacity:1;
                transform:scale(1);
            }
        }
        `;
        document.head.appendChild(style);
    }

    function openSnapEmbed(token) {
        // Using snap.pay() instead of embed() for native Midtrans modal
        window.snap.pay(token, {
            onSuccess: function(result) {
                qrisPaid = true;
                handleQrisSuccess(currentQrisTransactionId);
            },

            onPending: function(result) {
                setSubmitLoading(false);
                window.showAlert(
                    'Menunggu pembayaran QRIS.',
                    'info'
                );
            },

            onError: function(result) {
                setSubmitLoading(false);
                qrisPaid = false;

                window.showAlert(
                    'Pembayaran QRIS gagal.',
                    'error'
                );
            },

            onClose: function() {
                setSubmitLoading(false);
                qrisPaid = false;
            }
        });
    }

    async function finalizeDirectPayment(paymentMethod, payment) {
        const form = document.getElementById("paymentForm");
        if (!form) throw new Error("Form pembayaran tidak ditemukan.");

        const discountTypeInput = document.getElementById("checkoutDiscountType");
        const discountValueInput = document.getElementById("checkoutDiscountValue");

        const payload = new URLSearchParams();
        payload.append("_token", document.querySelector("[name='_token']").value);
        payload.append("cart", document.getElementById("cartInput")?.value || "[]");
        payload.append("payment_method", paymentMethod);
        payload.append("payment", String(Number(payment || 0)));
        payload.append("customer_name", document.getElementById("customerName")?.value || "Pelanggan Umum");
        payload.append("discount_type", discountTypeInput ? discountTypeInput.value : "rp");
        payload.append("discount_value", discountValueInput ? discountValueInput.value : "0");

        const res = await fetch(form.action, {
            method: "POST",
            headers: {
                "Accept": "application/json",
                "X-Requested-With": "XMLHttpRequest"
            },
            body: payload
        });

        const raw = await res.text();
        const data = safeParseJson(raw) || {};

        if (!res.ok) {
            throw new Error(
                data.message ||
                (data.errors && Object.values(data.errors)[0]?.[0]) ||
                "Gagal menyimpan transaksi."
            );
        }

        if (Array.isArray(window.cart)) window.cart = [];
        qrisPaid = false;

        const paymentEl = document.getElementById("payment");
        if (paymentEl) {
            paymentEl.disabled = false;
            paymentEl.value = "";
        }

        renderOrderSummary();
        if (typeof renderCart === "function") renderCart();
        if (typeof renderPendingList === "function") renderPendingList();
        if (typeof fetchProducts === "function") {
            const searchInput = document.getElementById('productSearch');
            fetchProducts(searchInput ? searchInput.value : '', true);
        }
        if (typeof showPage === "function") showPage("kasir");

        window.showAlert(data.message || "Transaksi berhasil.");
        askPrintReceipt(data.receipt_url);
    }

    // QRIS tidak generate saat ganti metode, generate saat klik submit
    document.getElementById("paymentMethod")?.addEventListener("change", function() {
        const qrisSection = document.getElementById("qrisSection");
        const paymentInput = document.getElementById("payment");
        const qrisLoading = document.getElementById("qrisLoading");
        const qrisAmount = document.getElementById("qrisAmount");
        const quickPayWrap = document.getElementById("quickPay")?.parentElement;

        if (!paymentInput) return;

        qrisPaid = false;
        isGeneratingQris = false;

        if (this.value === "qris") {
            if (quickPayWrap) quickPayWrap.style.display = "none";
            if (qrisSection) qrisSection.style.display = "block";
            paymentInput.disabled = true;
            paymentInput.style.opacity = "0.6";

            const totalWithFee = getPayableTotal(getActivePaymentSource(), "qris");
            paymentInput.value = totalWithFee;

            if (qrisAmount) qrisAmount.innerText = formatRupiah(totalWithFee);
            if (qrisLoading) {
                qrisLoading.style.display = "block";
                qrisLoading.innerHTML = "<p style='color:#475569; font-size:12px; margin-top:8px;'>Klik Proses Transaksi untuk menampilkan QRIS.</p>";
            }

            updateChange();
            return;
        }

        if (quickPayWrap) quickPayWrap.style.display = "block";
        if (qrisSection) qrisSection.style.display = "none";
        paymentInput.disabled = false;
        paymentInput.style.opacity = "1";
        paymentInput.value = "";
        updateChange();
    });

    var isSubmittingPayment = false;

    document.getElementById("paymentForm")?.addEventListener("submit", function(e) {
        if (isSubmittingPayment) {
            e.preventDefault();
            return;
        }

        const paymentMethod = (document.getElementById("paymentMethod") || {}).value || "cash";
        const paymentEl = document.getElementById("payment");
        const totalNow = Number(getOrderTotal() || 0);

        isSubmittingPayment = true;

        // QRIS: buka modal dulu
        if (paymentMethod === "qris" && !qrisPaid) {
            e.preventDefault();
            setSubmitLoading(true);
            generateQrisCode().finally(() => {
                isSubmittingPayment = false;
            });
            return;
        }

        // QRIS sukses -> lanjut simpan transaksi
        if (paymentMethod === "qris" && qrisPaid && paymentEl) {
            paymentEl.disabled = false;
            paymentEl.value = totalNow;
        }

        // pending payment pakai endpoint khusus
        if (window.selectedPendingTransaction) {
            e.preventDefault();
            setSubmitLoading(true);

            const payment = Number((document.getElementById("payment") || {}).value || 0);
            finalizePendingPayment(paymentMethod, payment)
                .catch(function(err) {
                    window.showAlert(err.message || "Gagal memproses pembayaran.", "error");
                })
                .finally(function() {
                    setSubmitLoading(false);
                    isSubmittingPayment = false;
                    qrisPaid = false;
                });

            return;
        }

        if (paymentMethod === "qris") {
            e.preventDefault();
            setSubmitLoading(true);
            finalizeDirectPayment("qris", totalNow)
                .catch(function(err) {
                    window.showAlert(err.message || "Gagal menyimpan transaksi.", "error");
                })
                .finally(function() {
                    setSubmitLoading(false);
                    isSubmittingPayment = false;
                    qrisPaid = false;
                });
        } else {
            // Cash direct payment via AJAX to keep UX fluid & show the global print modal
            e.preventDefault();
            setSubmitLoading(true);
            const paymentVal = Number((document.getElementById("payment") || {}).value || 0);
            finalizeDirectPayment("cash", paymentVal)
                .catch(function(err) {
                    window.showAlert(err.message || "Gagal menyimpan transaksi.", "error");
                })
                .finally(function() {
                    setSubmitLoading(false);
                    isSubmittingPayment = false;
                    qrisPaid = false;
                });
        }
    });

    async function generateQrisCode() {
        await loadMidtransScript();
        if (isGeneratingQris) return;

        const total = Number(getOrderTotal() || 0);
        if (total < 1000) {
            setSubmitLoading(false);
            window.showAlert("Minimal pembayaran QRIS Rp 1.000", "error");
            return;
        }

        const qrisLoading = document.getElementById("qrisLoading");
        const qrisAmount = document.getElementById("qrisAmount");
        const paymentEl = document.getElementById("payment");

        if (qrisAmount) qrisAmount.innerText = formatRupiah(total);
        if (qrisLoading) {
            qrisLoading.style.display = "block";
            qrisLoading.innerHTML =
                "<div style='border:3px solid #e5e7eb; border-top:3px solid #0284c7; border-radius:50%; width:40px; height:40px; animation: spin 1s linear infinite; margin:0 auto;'></div>" +
                "<p style='color:#475569; font-size:12px; margin-top:8px;'>Sedang membuat QR Code...</p>";
        }

        isGeneratingQris = true;

        currentQrisTransactionId = window.selectedPendingTransaction ?
            window.selectedPendingTransaction.transaction_id :
            Date.now();

        try {
            const response = await fetch("{{ route('kasir.generate-qris') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": document.querySelector("[name='_token']").value
                },
                body: JSON.stringify({
                    cart: getActiveCartPayload(),
                    total_amount: total,
                    transaction_id: currentQrisTransactionId,
                    discount_type: (document.getElementById("checkoutDiscountType") || {}).value || "rp",
                    discount_value: (document.getElementById("checkoutDiscountValue") || {}).value || 0
                })
            });

            const raw = await response.text();
            const data = safeParseJson(raw);

            if (!data) throw new Error("Response server tidak valid.");
            if (!response.ok) throw new Error(data.message || "Gagal generate QRIS.");
            if (data.status !== "success" || !data.snap_token) throw new Error(data.message || "Gagal membuat token QRIS.");

            // gunakan embedded mode agar QRIS tampil dalam panel besar
            try {
                openSnapEmbed(data.snap_token);
            } catch (e) {
                throw e;
            }
        } catch (error) {
            qrisPaid = false;
            setSubmitLoading(false);
            if (qrisLoading) {
                qrisLoading.innerHTML = "<p style='color:#ef4444; font-weight:600;'>Error: " + (error.message || "Gagal generate QRIS") + "</p>";
            }
            console.error(error);
        } finally {
            isGeneratingQris = false;
        }
    }

    loadMidtransScript();

    function getOrderTotal() {
        const el = document.getElementById("orderTotal");
        const total = el ? el.innerText.replace(/[^0-9]/g, "") : "0";
        return total || "0";
    }

    function formatRupiah(amount) {
        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            minimumFractionDigits: 0
        }).format(Number(amount || 0));
    }

    function showAlert(message, type) {
        if (window.showAlert) return window.showAlert(message, type);
    }

    function getPayableTotal(source, method) {
        return Number(getBreakdown(source).total || 0);
    }



    // optional, buat animasi spinner jika belum ada di css global
    if (!document.getElementById("qris-spin-style")) {
        const style = document.createElement("style");
        style.id = "qris-spin-style";
        style.innerHTML = "@keyframes spin { 0% { transform: rotate(0deg);} 100% { transform: rotate(360deg);} }";
        document.head.appendChild(style);
    }

    function closeAppModal() {
        const el = document.getElementById('appModal');
        if (!el) return;
        el.classList.add('is-hidden');
        el.setAttribute('aria-hidden', 'true');
    }

    function openAppModal(opts = {}) {
        const config = Object.assign({
            size: 'modal-sm', // modal-sm | modal-xl
            title: 'Informasi',
            message: '',
            okText: 'OK',
            cancelText: 'Batal',
            showCancel: false,
            onOk: null,
            onCancel: null
        }, opts);

        const modal = document.getElementById('appModal');
        const dialog = document.getElementById('appModalDialog');
        const title = document.getElementById('appModalTitle');
        const body = document.getElementById('appModalBody');
        const ok = document.getElementById('appModalOk');
        const cancel = document.getElementById('appModalCancel');
        if (!modal || !dialog || !title || !body || !ok || !cancel) return;

        dialog.classList.remove('modal-sm', 'modal-xl');
        dialog.classList.add(config.size === 'modal-xl' ? 'modal-xl' : 'modal-sm');

        title.textContent = config.title;
        body.innerHTML = config.message;
        ok.textContent = config.okText;
        cancel.textContent = config.cancelText;
        cancel.style.display = config.showCancel ? 'inline-block' : 'none';

        ok.onclick = () => {
            closeAppModal();
            if (typeof config.onOk === 'function') config.onOk();
        };
        cancel.onclick = () => {
            closeAppModal();
            if (typeof config.onCancel === 'function') config.onCancel();
        };

        modal.classList.remove('is-hidden');
        modal.setAttribute('aria-hidden', 'false');
    }

    window.showAlert = function(message, type = 'info') {
        const titleMap = {
            success: 'Berhasil',
            error: 'Error',
            warning: 'Peringatan',
            info: 'Informasi'
        };
        openAppModal({
            size: 'modal-sm',
            title: titleMap[type] || 'Informasi',
            message: message || '-',
            okText: 'OK',
            showCancel: false
        });
    };

    function handleQrisSuccess(transactionId) {
        if (!transactionId) {
            window.showAlert('ID transaksi tidak ditemukan.', 'error');
            return;
        }

        const total = Number(getBreakdown(getActivePaymentSource()).total || 0);

        // simpan dulu ke server seperti cash/pending
        finalizePendingPayment('qris', total)
            .then(() => {
                askPrintReceipt(`/kasir/receipt/${transactionId}`);
            })
            .catch((err) => {
                window.showAlert(err.message || 'Gagal memperbarui pending payment.', 'error');
            });
    }

    function setWaitingPaymentBadge(count) {
        const badge = document.getElementById('waitingPaymentBadge');
        if (!badge) return;
        badge.innerText = Math.max(Number(count || 0), 0);
    }

    window.showConfirm = function(message, onOk, onCancel = null, size = 'modal-sm') {
        openAppModal({
            size,
            title: 'Konfirmasi',
            message,
            okText: 'Ya',
            cancelText: 'Batal',
            showCancel: true,
            onOk,
            onCancel
        });
    };

    function refreshWaitingPaymentBadge() {
        const badge = document.getElementById('waitingPaymentBadge');
        if (!badge) return;

        if (Array.isArray(window.pendingTransactions)) {
            badge.innerText = window.pendingTransactions.length;
        }
    }

    function removePendingTransactionById(transactionId) {
        if (!transactionId || !Array.isArray(window.pendingTransactions)) return;

        window.pendingTransactions = window.pendingTransactions.filter(
            item => String(item.transaction_id) !== String(transactionId)
        );

        refreshWaitingPaymentBadge();

        if (typeof renderPendingList === 'function') {
            renderPendingList();
        }
    }
</script>