<style>
    #page-pending .pending-wrap {
        padding: 16px 24px;
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    #page-pending .pending-card {
        background: #fff;
        border: 1px solid #e8ecf3;
        border-radius: 14px;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
        overflow: hidden;
    }

    #page-pending .pending-head {
        padding: 14px 16px;
        border-bottom: 1px solid #eef2f7;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }

    #page-pending .pending-title {
        font-size: 18px;
        font-weight: 700;
        color: var(--dark);
    }

    #page-pending .pending-subtitle {
        font-size: 12px;
        color: var(--dark-light);
        margin-top: 4px;
    }

    #page-pending .pending-badge {
        border: 1px solid #dbe3ef;
        background: #f8fafc;
        color: #334155;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 12px;
        font-weight: 700;
    }

    #page-pending #pendingList {
        padding: 14px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    #page-pending .pending-empty {
        border: 1px dashed #d9e1ee;
        background: #fbfdff;
        border-radius: 12px;
        padding: 24px 16px;
        text-align: center;
        color: #64748b;
        font-size: 13px;
    }
</style>

<div id="page-pending" class="page h-full flex flex-col" style="background: var(--light);">
    <div class="pending-wrap">
        <div class="pending-card">
            <div class="pending-head">
                <div>
                    <div class="pending-title">Waiting Payment</div>
                    <div class="pending-subtitle">Transaksi dari pelayan yang menunggu pembayaran kasir</div>
                </div>
                <span class="pending-badge">
                    Total: <span id="pendingTotalCount">0</span>
                </span>
            </div>

            <div id="pendingList"></div>
        </div>
    </div>
</div>