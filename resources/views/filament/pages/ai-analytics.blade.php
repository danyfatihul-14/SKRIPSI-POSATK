<x-filament-panels::page>
    <div class="ai-analytics-wrapper" style="display: flex; flex-direction: column; gap: 16px; max-width: 1100px; margin: 0 auto; width: 100%;">
        
        {{-- Top Bar Card --}}
        <div style="background: white; dark:bg-gray-900; border-radius: 14px; border: 1px solid #e2e8f0; padding: 18px 24px; box-shadow: 0 2px 6px rgba(0,0,0,0.03); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); display: flex; align-items: center; justify-content: center; color: white; box-shadow: 0 4px 10px rgba(245, 158, 11, 0.3);">
                    <svg style="width: 24px; height: 24px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/>
                    </svg>
                </div>
                <div>
                    <h2 style="font-size: 17px; font-weight: 700; color: #1e293b; margin: 0; line-height: 1.2;">Asisten AI Analitik Toko</h2>
                    <p style="font-size: 12px; color: #64748b; margin: 2px 0 0 0;">Konsultasi & analisis performa penjualan, tren omset, dan perbandingan waktu berbasis database live.</p>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 12px;">
                @if(count($stores) > 1)
                <div style="display: flex; align-items: center; gap: 6px;">
                    <span style="font-size: 12px; font-weight: 600; color: #64748b;">Toko:</span>
                    <select id="aiStoreSelect" style="padding: 7px 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px; font-weight: 600; color: #334155; background: white; outline: none; cursor: pointer;">
                        @foreach($stores as $st)
                            <option value="{{ $st['id'] }}" {{ $st['id'] == $defaultStoreId ? 'selected' : '' }}>{{ $st['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                @else
                    <input type="hidden" id="aiStoreSelect" value="{{ $defaultStoreId }}" />
                @endif

                <button type="button" onclick="clearAiChat()" style="background: #f1f5f9; hover:bg-slate-200; border: none; padding: 7px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; color: #475569; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; transition: all 0.15s;">
                    <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Bersihkan Chat
                </button>
            </div>
        </div>

        {{-- Preset Chips --}}
        <div style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 2px;">
            <button type="button" class="ai-chip" onclick="askPreset('Bandingkan penjualan hari Kamis bulan lalu dengan Kamis sekarang, mana yang lebih ramai?')">
                📊 Kamis bulan lalu vs sekarang
            </button>
            <button type="button" class="ai-chip" onclick="askPreset('Apa 5 produk paling laris dan berapa total penjualannya bulan ini?')">
                🔥 Top 5 produk terlaris
            </button>
            <button type="button" class="ai-chip" onclick="askPreset('Berapa total omset dan total transaksi selama 7 hari terakhir?')">
                💰 Omset 7 hari terakhir
            </button>
            <button type="button" class="ai-chip" onclick="askPreset('Hari apa yang paling ramai penjualannya dalam 30 hari terakhir?')">
                📅 Hari tersibuk
            </button>
            <button type="button" class="ai-chip" onclick="askPreset('Produk apa saja yang stoknya paling menipis saat ini?')">
                ⚠️ Stok paling menipis
            </button>
        </div>

        {{-- Chat History Container --}}
        <div id="aiChatBox" style="background: white; border-radius: 14px; border: 1px solid #e2e8f0; height: 520px; overflow-y: auto; padding: 24px; display: flex; flex-direction: column; gap: 18px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
            
            {{-- Initial Bot Welcome --}}
            <div class="ai-msg ai-bot">
                <div class="ai-avatar" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                    🤖
                </div>
                <div class="ai-bubble" style="background: #f8fafc; border: 1px solid #e2e8f0; color: #1e293b;">
                    <div style="font-weight: 700; font-size: 13px; margin-bottom: 4px; color: #d97706; display: flex; align-items: center; gap: 6px;">
                        <span>Asisten Pintar Toko</span>
                        <span style="font-size: 10px; font-weight: 600; background: #fef3c7; color: #b45309; padding: 2px 6px; border-radius: 6px;">Live Data</span>
                    </div>
                    <div style="font-size: 13.5px; line-height: 1.6;">
                        Halo! Saya asisten AI analitik toko Anda. Anda bisa menanyakan apa saja seputar data transaksi, performa penjualan, stok produk, atau perbandingan periode waktu.
                        <br><br>
                        <em>Contoh: "Bandingkan hari Kamis bulan kemarin sama bulan ini lebih ramai yang mana?", "Berapa omset kemarin?", atau pilih salah satu tombol saran di atas.</em>
                    </div>
                </div>
            </div>

        </div>

        {{-- Input Section --}}
        <div style="background: white; border-radius: 14px; border: 1px solid #e2e8f0; padding: 14px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
            <form id="aiChatForm" onsubmit="handleSendAiMessage(event)" style="display: flex; gap: 10px; align-items: flex-end;">
                <input type="hidden" id="aiCsrfToken" value="{{ csrf_token() }}" />
                <div style="flex: 1; position: relative;">
                    <textarea 
                        id="aiQuestionInput" 
                        rows="2" 
                        placeholder="Ketik pertanyaan analitik toko Anda di sini (misal: 'Hari apa paling ramai minggu ini?')..."
                        onkeydown="if(event.key === 'Enter' && !event.shiftKey){ event.preventDefault(); handleSendAiMessage(event); }"
                        style="width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 14px; font-size: 13.5px; line-height: 1.4; outline: none; resize: none; color: #1e293b; font-family: inherit; transition: border-color 0.15s;"
                    ></textarea>
                </div>
                <button 
                    type="submit" 
                    id="aiSendBtn"
                    style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border: none; padding: 12px 22px; border-radius: 10px; font-size: 13.5px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25); height: 52px; transition: transform 0.1s, opacity 0.2s;"
                >
                    <span>Kirim</span>
                    <svg id="aiSendIcon" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                    <svg id="aiSendLoading" style="width: 16px; height: 16px; display: none; animation: spin 1s linear infinite;" fill="none" viewBox="0 0 24 24">
                        <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                </button>
            </form>
            <div style="margin-top: 8px; display: flex; justify-content: space-between; align-items: center; font-size: 11px; color: #94a3b8; padding: 0 4px;">
                <span>💡 Tekan <b>Enter</b> untuk kirim, <b>Shift + Enter</b> untuk baris baru.</span>
                <span>Didukung oleh Groq LLaMA 3.1 & MySQL Realtime</span>
            </div>
        </div>

        <style>
        .ai-chip {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 999px;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.15s ease;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        }
        .ai-chip:hover {
            background: #fef3c7;
            border-color: #f59e0b;
            color: #b45309;
            transform: translateY(-1px);
        }
        .ai-msg {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            max-width: 88%;
        }
        .ai-msg.ai-user {
            align-self: flex-end;
            flex-direction: row-reverse;
        }
        .ai-msg.ai-bot {
            align-self: flex-start;
        }
        .ai-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            flex-shrink: 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }
        .ai-bubble {
            border-radius: 14px;
            padding: 14px 18px;
            font-size: 13.5px;
            line-height: 1.6;
            word-break: break-word;
        }
        .ai-user .ai-bubble {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            border-bottom-right-radius: 4px;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.2);
        }
        .ai-bot .ai-bubble {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #1e293b;
            border-bottom-left-radius: 4px;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .ai-data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 12px;
        }
        .ai-data-table th, .ai-data-table td {
            border: 1px solid #e2e8f0;
            padding: 6px 10px;
            text-align: left;
        }
        .ai-data-table th {
            background: #f1f5f9;
            font-weight: 700;
            color: #334155;
        }
    </style>
    </div>

    <script src="{{ asset('js/ai-analytics.js') }}"></script>
</x-filament-panels::page>
