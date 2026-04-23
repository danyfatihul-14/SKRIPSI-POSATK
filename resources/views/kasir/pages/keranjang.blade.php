@php
$isWaiter = auth()->user()?->role === 'pelayan';
@endphp

<style>
    #page-keranjang .hold-wrap {
        padding: 16px 24px;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    #page-keranjang .hold-card {
        background: #fff;
        border: 1px solid #e7eaf1;
        border-radius: 14px;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
    }

    #page-keranjang .hold-header {
        padding: 14px 16px;
        border-bottom: 1px solid #eef1f6;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    #page-keranjang .hold-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 18px;
        font-weight: 700;
        color: var(--dark);
    }

    #page-keranjang .hold-subtitle {
        margin-top: 4px;
        font-size: 12px;
        color: var(--dark-light);
    }

    #page-keranjang .hold-back-btn {
        background: #fff;
        color: var(--accent);
        border: 1px solid var(--accent);
        border-radius: 10px;
        padding: 8px 14px;
        font-size: 13px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }

    #page-keranjang .queue-summary {
        padding: 12px 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    #page-keranjang .queue-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 1px solid #e7eaf1;
        background: #f8fafc;
        color: #334155;
        border-radius: 999px;
        padding: 6px 12px;
        font-size: 12px;
        font-weight: 700;
    }

    #page-keranjang #queueList {
        padding: 0 16px 14px;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    @media (max-width: 768px) {
        #page-keranjang .hold-wrap {
            padding: 12px;
        }

        #page-keranjang .hold-header {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<div id="page-keranjang" class="page h-full flex-col" style="background: var(--light);">
    <div class="hold-wrap">
        <div class="hold-card">
            <div class="hold-header">
                <div>
                    <div class="hold-title">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width:22px; height:22px; color:var(--accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        Hold Order
                    </div>
                    <div class="hold-subtitle">Daftar pesanan yang ditahan sementara</div>
                </div>

                <button onclick="showPage('kasir')" class="hold-back-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:15px; height:15px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Tambah Produk
                </button>
            </div>

            <div class="queue-summary">
                <span class="queue-badge">
                    Antrian aktif:
                    <strong id="queueCount">0</strong>
                </span>
            </div>

            <div id="queueList"></div>
        </div>
    </div>
</div>