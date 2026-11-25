<style>
    #page-history {
        display: none;
        flex-direction: column;
        height: 100%;
    }

    #page-history.active {
        display: flex;
    }

    #page-history .history-header {
        padding: 20px 24px;
        background: white;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
    }

    #page-history .history-title {
        font-size: 18px;
        font-weight: 700;
        color: #111827;
        margin: 0;
    }

    #page-history .history-search {
        display: flex;
        gap: 8px;
        flex: 1;
        max-width: 400px;
    }

    #page-history .history-search input {
        flex: 1;
        padding: 8px 12px;
        border: 1px solid #d5d9e2;
        border-radius: 8px;
        font-size: 14px;
    }

    #page-history .history-search input:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    #page-history .history-date-filter {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    #page-history .history-date-filter label {
        font-size: 13px;
        font-weight: 600;
        color: #374151;
        white-space: nowrap;
    }

    #page-history .history-date-filter input[type="date"] {
        padding: 7px 10px;
        border: 1px solid #d5d9e2;
        border-radius: 8px;
        font-size: 13px;
        color: #111827;
        background: #fff;
        cursor: pointer;
        transition: border-color 0.2s;
    }

    #page-history .history-date-filter input[type="date"]:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    #page-history .history-date-filter .btn-reset-date {
        padding: 7px 14px;
        border-radius: 8px;
        border: 1px solid #d5d9e2;
        background: #f9fafb;
        color: #6b7280;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
    }

    #page-history .history-date-filter .btn-reset-date:hover {
        background: #f3f4f6;
        border-color: #9ca3af;
        color: #374151;
    }

    #page-history .history-content {
        flex: 1;
        overflow-y: auto;
        background: var(--light);
        padding: 16px 24px;
    }

    #page-history .history-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    #page-history .history-item {
        background: white;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        padding: 16px;
        cursor: pointer;
        transition: all 0.2s;
    }

    #page-history .history-item:hover {
        border-color: var(--accent);
        box-shadow: 0 2px 8px rgba(37, 99, 235, 0.1);
    }

    #page-history .history-item-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }

    #page-history .history-item-id {
        font-weight: 600;
        color: #111827;
        font-size: 14px;
    }

    #page-history .history-item-status {
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
    }

    #page-history .history-item-status.completed {
        background: #d1fae5;
        color: #065f46;
    }

    #page-history .history-item-status.pending {
        background: #fef3c7;
        color: #92400e;
    }

    #page-history .history-item-meta {
        display: flex;
        gap: 16px;
        font-size: 13px;
        color: #6b7280;
        margin-bottom: 10px;
    }

    #page-history .history-item-customer {
        font-size: 13px;
        color: #4b5563;
        margin-bottom: 10px;
    }

    #page-history .history-item-customer strong {
        color: #374151;
        font-weight: 600;
    }

    #page-history .history-item-notes {
        font-size: 13px;
        color: #374151;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 10px 12px;
        margin-bottom: 12px;
        line-height: 1.45;
        white-space: pre-wrap;
        word-break: break-word;
    }

    #page-history .history-item-amount {
        font-size: 16px;
        font-weight: 700;
        color: var(--accent);
        margin-bottom: 12px;
    }

    #page-history .history-item-actions {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
    }

    #page-history .history-item-actions button {
        padding: 6px 12px;
        border-radius: 6px;
        border: 1px solid #d5d9e2;
        background: white;
        color: #111827;
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
        transition: all 0.2s;
    }

    #page-history .history-item-actions button:hover {
        background: #f3f4f6;
        border-color: var(--accent);
        color: var(--accent);
    }

    #page-history .history-item-actions .btn-print {
        background: var(--accent);
        color: white;
        border-color: var(--accent);
    }

    #page-history .history-item-actions .btn-print:hover {
        background: #1e40af;
        border-color: #1e40af;
    }

    #page-history .history-empty {
        text-align: center;
        padding: 60px 20px;
        color: #6b7280;
    }

    #page-history .history-empty-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 16px;
        border-radius: 16px;
        background: #f3f4f6;
        border: 2px dashed #d5d9e2;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    #page-history .history-empty-title {
        font-size: 16px;
        font-weight: 600;
        color: #111827;
        margin-bottom: 4px;
    }

    #page-history .history-loading {
        text-align: center;
        padding: 40px 20px;
        color: #6b7280;
    }

    @media (max-width: 768px) {
        #page-history .history-header {
            flex-direction: column;
            align-items: stretch;
            gap: 12px;
        }

        #page-history .history-search {
            max-width: 100%;
        }

        #page-history .history-date-filter {
            flex-wrap: wrap;
        }

        #page-history .history-date-filter input[type="date"] {
            flex: 1;
            min-width: 120px;
        }

        #page-history .history-item-actions {
            flex-direction: column;
        }

        #page-history .history-item-actions button {
            width: 100%;
            text-align: center;
        }
    }
</style>

<div id="page-history" class="page h-full flex flex-col">
    <div class="history-header">
        <h2 class="history-title">Riwayat Transaksi</h2>
        <div class="history-search">
            <input 
                type="text" 
                id="historySearchInput" 
                placeholder="Cari ID transaksi atau customer..."
                onkeyup="applyHistoryFilter()"
            >
        </div>
        <div class="history-date-filter">
            <label for="historyDateFilter">Tanggal:</label>
            <input 
                type="date" 
                id="historyDateFilter" 
                onchange="applyHistoryFilter()"
            >
            <button class="btn-reset-date" onclick="resetHistoryDateFilter()" title="Reset filter tanggal">&#x21BA; Reset</button>
        </div>
    </div>

    <div class="history-content">
        <div id="historyList" class="history-list">
            <div class="history-loading">
                <p>Memuat riwayat transaksi...</p>
            </div>
        </div>
    </div>
</div>
