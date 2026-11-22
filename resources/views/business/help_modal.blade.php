{{-- Business Help Modal --}}
<div id="help-modal"
    style="display:none;position:fixed;inset:0;z-index:10000;background:rgba(0,0,0,.6);align-items:flex-start;justify-content:center;padding:16px 12px;overflow-y:auto;overflow-x:hidden;box-sizing:border-box;">
    <div
        style="background:#fff;border-radius:12px;width:100%;max-width:580px;box-shadow:0 12px 48px rgba(0,0,0,.3);position:relative;margin:auto;overflow-x:hidden;box-sizing:border-box;">

        {{-- Header --}}
        <div
            style="background:#1a2332;border-radius:12px 12px 0 0;padding:18px 24px;display:flex;align-items:center;justify-content:space-between;">
            <div>
                <div style="font-size:15px;font-weight:600;color:#fff;">❓ დახმარება</div>
                <div style="font-size:11px;color:#7a92a8;margin-top:2px;">ბიზნეს მოდულის სახელმძღვანელო</div>
            </div>
            <button onclick="closeHelpModal()"
                style="background:none;border:none;font-size:1.4rem;cursor:pointer;color:#7a92a8;line-height:1;padding:0;">✕</button>
        </div>

        {{-- Intro --}}
        <div style="padding:16px 24px 0;font-size:12px;color:#4b5563;line-height:1.6;">
            დააჭირე თითოეულ განყოფილებას დეტალების სანახავად ↓
        </div>

        {{-- Accordion --}}
        <div style="padding:16px 24px 24px;display:flex;flex-direction:column;gap:8px;" id="help-accordion">

            {{-- 1. დაფა --}}
            <div class="bhelp-section">
                <button class="bhelp-toggle" onclick="bhelpToggle(this)">
                    <span>📋 დაფა — სტატუსები, ფილტრები, სიმბოლოები</span>
                    <span class="bhelp-chevron">▼</span>
                </button>
                <div class="bhelp-body">
                    <div style="font-size:11px;font-weight:600;color:#1a2332;margin-bottom:6px;">სტატუსები</div>
                    <div style="display:flex;flex-direction:column;gap:4px;margin-bottom:12px;">
                        <div class="bhelp-sub-row"><span>🔵</span>
                            <div><strong>მიმდინარე</strong> — ბიზნესის შეფასება მიმდინარეობს.</div>
                        </div>
                        <div class="bhelp-sub-row"><span>🟡</span>
                            <div><strong>გასაგზავნი</strong> — წერილი ჯერ არ გაგზავნილა.</div>
                        </div>
                        <div class="bhelp-sub-row"><span>🟢</span>
                            <div><strong>დასრულებული</strong> — პროცესი დასრულებულია.</div>
                        </div>
                        <div class="bhelp-sub-row"><span>⚠️</span>
                            <div><strong>ვადაგადაცილებული</strong> — პასუხის ვადა გავიდა.</div>
                        </div>
                    </div>

                    <div style="font-size:11px;font-weight:600;color:#1a2332;margin-bottom:6px;">სიმბოლოები სიაში</div>
                    <div style="display:flex;flex-direction:column;gap:4px;margin-bottom:12px;">
                        <div class="bhelp-sub-row"><span>📥</span>
                            <div><strong>შემოსული</strong> — ბიზნესმა გამოგზავნა პასუხი.
                            </div>
                        </div>
                        <div class="bhelp-sub-row"><span>⚠️</span>
                            <div><strong>ელფოსტა არ არის</strong> — ბიზნესს ელფოსტა არ აქვს მითითებული.</div>
                        </div>
                    </div>

                    <div style="font-size:11px;font-weight:600;color:#1a2332;margin-bottom:6px;">ფილტრები</div>
                    <div style="display:flex;flex-direction:column;gap:4px;">
                        <div class="bhelp-sub-row"><span>🔍</span>
                            <div><strong>ძებნა</strong> — სახელით, საიდ. კოდით ან ACT ნომრით.</div>
                        </div>
                        <div class="bhelp-sub-row"><span>⏳</span>
                            <div><strong>სტატუსი</strong> — გაფილტრე სტატუსის მიხედვით, მათ შორის ⚠️ ვადაგადაცილებული და
                                📥 გვიპასუხა.</div>
                        </div>
                        <div class="bhelp-sub-row"><span>🏷️</span>
                            <div><strong>ტიპი</strong> — მეპატრონე, მოიჯარე, მეიჯარე, სხვა.</div>
                        </div>
                        <div class="bhelp-sub-row"><span>🔢</span>
                            <div><strong>ლოტი</strong> — გაფილტრე ლოტის ნომრის მიხედვით.</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. ბიზნეს ბარათი --}}
            <div class="bhelp-section">
                <button class="bhelp-toggle" onclick="bhelpToggle(this)">
                    <span>💼 ბიზნეს ბარათი — ველები და მოქმედებები</span>
                    <span class="bhelp-chevron">▼</span>
                </button>
                <div class="bhelp-body">
                    <div style="font-size:11px;font-weight:600;color:#1a2332;margin-bottom:6px;">ძირითადი ინფო</div>
                    <div style="display:flex;flex-direction:column;gap:4px;margin-bottom:12px;">
                        <div class="bhelp-sub-row"><span>🏢</span>
                            <div><strong>სახელი</strong> — ბიზნესის სრული სახელი.</div>
                        </div>
                        <div class="bhelp-sub-row"><span>🔢</span>
                            <div><strong>საიდ. კოდი</strong> — საიდენტიფიკაციო კოდი.</div>
                        </div>
                        <div class="bhelp-sub-row"><span>📋</span>
                            <div><strong>ACT</strong> — დაკავშირებული ACT ნომერი.</div>
                        </div>
                        <div class="bhelp-sub-row"><span>🔢</span>
                            <div><strong>ლოტი</strong> — ლოტის ნომერი.</div>
                        </div>
                        <div class="bhelp-sub-row"><span>📍</span>
                            <div><strong>საკადასტრო</strong> — საკადასტრო კოდი.</div>
                        </div>
                        <div class="bhelp-sub-row"><span>🏷️</span>
                            <div><strong>ტიპი</strong> — მეპატრონე / მოიჯარე / მეიჯარე / სხვა.</div>
                        </div>
                        <div class="bhelp-sub-row"><span>📁</span>
                            <div><strong>Drive</strong> — ბიზნესის Drive საქაღალდის ბმული. ⬆️ ღილაკით შეგიძლია ფაილი
                                პირდაპირ ატვირთო.</div>
                        </div>
                    </div>

                    <div style="font-size:11px;font-weight:600;color:#1a2332;margin-bottom:6px;">კონტაქტები</div>
                    <div style="display:flex;flex-direction:column;gap:4px;margin-bottom:12px;">
                        <div class="bhelp-sub-row"><span>👤</span>
                            <div><strong>მეპატრონე / ბუღალტერი</strong> — სახელი, ტელეფონი, ელფოსტა. სისტემა წერილს ამ
                                ელფოსტაზე აგზავნის.</div>
                        </div>
                    </div>

                    <div style="font-size:11px;font-weight:600;color:#1a2332;margin-bottom:6px;">მოქმედებები</div>
                    <div style="display:flex;flex-direction:column;gap:4px;">
                        <div class="bhelp-sub-row"><span>✏️</span>
                            <div><strong>რედაქტირება</strong> — შეცვალე ბიზნესის ნებისმიერი ველი.</div>
                        </div>
                        <div class="bhelp-sub-row"><span>🗑️</span>
                            <div><strong>წაშლა</strong> — ბიზნესი სისტემიდან წაიშლება. <strong>Drive საქაღალდე
                                    რჩება</strong> — ფაილები არ იკარგება.</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. მიმოწერა --}}
            <div class="bhelp-section">
                <button class="bhelp-toggle" onclick="bhelpToggle(this)">
                    <span>📨 მიმოწერა — წერილები და შემოსული პასუხები</span>
                    <span class="bhelp-chevron">▼</span>
                </button>
                <div class="bhelp-body">
                    <div style="font-size:11px;font-weight:600;color:#1a2332;margin-bottom:6px;">წერილის პროექტი</div>
                    <div class="bhelp-row" style="margin-bottom:12px;">
                        <span>📝</span>
                        <div>ბიზნესის შექმნისას სისტემა ავტომატურად ამზადებს წერილის პროექტს. გახსენი, შეამოწმე და
                            გაგზავნე ბიზნესის ელფოსტაზე.</div>
                    </div>

                    <div style="font-size:11px;font-weight:600;color:#1a2332;margin-bottom:6px;">შემოსული პასუხები</div>
                    <div class="bhelp-row" style="margin-bottom:4px;">
                        <span>📥</span>
                        <div>ბიზნესი პასუხობს — სისტემა ავტომატურად ამოიცნობს ელფოსტას და ამატებს შემოსულში. თითოეულ
                            პასუხს აქვს საკუთარი Drive ბმული და ნომერი (#1, #2…).</div>
                    </div>
                    <div class="bhelp-row">
                        <span>🔄</span>
                        <div>თუ პასუხი მოვიდა მაგრამ არ ჩანს — დააჭირე <strong>განახლება</strong>. სისტემა ხელახლა
                            შეამოწმებს.</div>
                    </div>
                </div>
            </div>

            {{-- 4. Drive საქაღალდეები --}}
            <div class="bhelp-section">
                <button class="bhelp-toggle" onclick="bhelpToggle(this)">
                    <span>🗂️ Drive საქაღალდეები და ფაილების გადატანა</span>
                    <span class="bhelp-chevron">▼</span>
                </button>
                <div class="bhelp-body">
                    <div class="bhelp-row" style="margin-bottom:12px;">
                        <span>📁</span>
                        <div>ბიზნესის შექმნისას Drive-ში ავტომატურად იქმნება საქაღალდე ქვესაქაღალდეებით:
                            <strong>ფინანსური ანგარიშგება, ხელფასები, შემოსავლის ამონაწერი, თანამშრომლები, საბანკო
                                ამონაწერი, იჯარის ხელშეკრულება, გამოთვლები, სხვა, მიმოწერა.</strong>
                        </div>
                    </div>

                    <div style="font-size:11px;font-weight:600;color:#1a2332;margin-bottom:6px;">შემომავალი ფაილები →
                        გადატანა</div>
                    <div class="bhelp-row" style="margin-bottom:4px;">
                        <span>1️⃣</span>
                        <div>პასუხის შემოსვლის შემდეგ <strong>შემომავალი ფაილები</strong> განყოფილებაში ჩანს ყველა
                            მიღებული ფაილი.</div>
                    </div>
                    <div class="bhelp-row" style="margin-bottom:4px;">
                        <span>2️⃣</span>
                        <div>თითოეული ფაილის გვერდით აირჩიე სწორი საქაღალდე dropdown-იდან (მაგ. ფინანსური ანგარიშგება).
                        </div>
                    </div>
                    <div class="bhelp-row" style="margin-bottom:12px;">
                        <span>3️⃣</span>
                        <div>დააჭირე <strong>გადატანა</strong> — ფაილი Drive-ის შესაბამის ქვესაქაღალდეში გადავა.</div>
                    </div>

                    <div style="font-size:11px;font-weight:600;color:#1a2332;margin-bottom:6px;">საქაღალდეების სტატუსი
                    </div>
                    <div class="bhelp-row">
                        <span>✅</span>
                        <div>ფაილის გადატანის შემდეგ შესაბამისი საქაღალდე სტატუსში მწვანედ მოინიშნება. ეს გვაჩვენებს
                            რომელი დოკუმენტებია უკვე მიღებული.</div>
                    </div>
                </div>
            </div>

            {{-- 5. პრობლემები --}}
            <div class="bhelp-section">
                <button class="bhelp-toggle" onclick="bhelpToggle(this)">
                    <span>🚨 პრობლემები და გადაწყვეტა</span>
                    <span class="bhelp-chevron">▼</span>
                </button>
                <div class="bhelp-body">
                    <div class="bhelp-row">
                        <span>🕐</span>
                        <div>ფაილი Drive-ში გადაიტანე მაგრამ სტატუსი არ განახლდა → <strong>დაელოდე</strong>. სისტემა
                            ავტომატურად ამოწმებს — ეს ჩანს <strong>განახლდა 14:33</strong> ველში.</div>
                    </div>
                </div>
            </div>
        </div>

    </div>{{-- /card --}}
</div>{{-- /overlay --}}

{{-- Business Help modal styles --}}
<style>
    .bhelp-section {
        border: 1px solid #e4e7ec;
        border-radius: 8px;
        overflow: hidden;
    }

    .bhelp-toggle {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 13px 16px;
        background: #f8f9fb;
        border: none;
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
        color: #1a2332;
        font-family: 'Inter', system-ui, sans-serif;
        text-align: left;
        transition: background .15s;
        line-height: 1.4;
    }

    .bhelp-toggle:hover {
        background: #eef1f8;
    }

    .bhelp-toggle.open {
        background: #1a2332;
        color: #e2e8f0;
    }

    .bhelp-chevron {
        flex-shrink: 0;
        font-size: 10px;
        transition: transform .2s;
        color: #7a92a8;
    }

    .bhelp-toggle.open .bhelp-chevron {
        transform: rotate(180deg);
        color: #7c9df7;
    }

    .bhelp-body {
        display: none;
        padding: 14px 16px;
        font-size: 12px;
        color: #4b5563;
        line-height: 1.6;
        border-top: 1px solid #e4e7ec;
        background: #fff;
    }

    .bhelp-body.open {
        display: block;
    }

    .bhelp-row {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        margin-bottom: 10px;
    }

    .bhelp-row:last-child {
        margin-bottom: 0;
    }

    .bhelp-sub-row {
        display: flex;
        gap: 8px;
        align-items: flex-start;
        font-size: 12px;
    }

    .bhelp-sub-row span {
        flex-shrink: 0;
        line-height: 1.6;
        min-width: 16px;
    }

    @media (max-width: 640px) {
        #help-modal {
            padding: 12px 8px;
        }

        .bhelp-toggle {
            font-size: 11px;
            padding: 11px 13px;
        }
    }
</style>

{{-- Business Help modal script --}}
<script>
    function bhelpToggle(btn) {
        const body = btn.nextElementSibling;
        const isOpen = body.classList.contains('open');

        document.querySelectorAll('.bhelp-toggle').forEach(b => b.classList.remove('open'));
        document.querySelectorAll('.bhelp-body').forEach(b => b.classList.remove('open'));

        if (!isOpen) {
            btn.classList.add('open');
            body.classList.add('open');
        }
    }

    function closeHelpModal() {
        document.getElementById('help-modal').style.display = 'none';
        document.body.style.overflow = '';
    }
    document.getElementById('help-modal').addEventListener('click', function(e) {
        if (e.target === this) closeHelpModal();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeHelpModal();
    });

    function openHelpModal() {
        document.getElementById('help-modal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
</script>
