{{-- Help Modal --}}
<div id="help-modal"
    style="display:none;position:fixed;inset:0;z-index:10000;background:rgba(0,0,0,.6);align-items:flex-start;justify-content:center;padding:24px 16px;overflow-y:auto;">
    <div
        style="background:#fff;border-radius:12px;width:100%;max-width:580px;box-shadow:0 12px 48px rgba(0,0,0,.3);position:relative;margin:auto;overflow-x:hidden;">

        {{-- Header --}}
        <div
            style="background:#1a1f36;border-radius:12px 12px 0 0;padding:18px 24px;display:flex;align-items:center;justify-content:space-between;">
            <div>
                <div style="font-size:15px;font-weight:600;color:#fff;">❓ დახმარება</div>
                <div style="font-size:11px;color:#8892a4;margin-top:2px;">ACTs დაფის სახელმძღვანელო</div>
            </div>
            <button onclick="document.getElementById('help-modal').style.display='none'"
                style="background:none;border:none;font-size:1.4rem;cursor:pointer;color:#8892a4;line-height:1;padding:0;">✕</button>
        </div>

        {{-- Intro --}}
        <div style="padding:16px 24px 0;font-size:12px;color:#4b5563;line-height:1.6;">
            დააჭირე თითოეულ განყოფილებას დეტალების სანახავად ↓
        </div>

        {{-- Accordion --}}
        <div style="padding:16px 24px 24px;display:flex;flex-direction:column;gap:8px;" id="help-accordion">

            {{-- 1. Toolbar --}}
            <div class="help-section">
                <button class="help-toggle" onclick="helpToggle(this)">
                    <span>🟢 განახლდა / დრო &nbsp;·&nbsp; 📊 Airtable სტატისტიკა &nbsp;·&nbsp; 🔄 გადამოწმება</span>
                    <span class="help-chevron">▼</span>
                </button>
                <div class="help-body">
                    <div class="help-row">
                        <span class="help-icon">🟢</span>
                        <div><strong>განახლდა / დრო</strong> — სისტემამ Drive ბოლოს როდის გადაამოწმა.</div>
                    </div>
                    <div class="help-row">
                        <span class="help-icon">📊</span>
                        <div><strong>Airtable სტატისტიკა</strong> — Airtable-ში ACT-ების მიმდინარე მდგომარეობა — სულ
                            რამდენია, რამდენი დასრულდა, რამდენი მიმდინარეობს. აქვე ჩანს ვადაგადაცილებული
                            კორესპონდენციები და ბიზნესები.</div>
                    </div>
                    <div class="help-row">
                        <span class="help-icon">🔄</span>
                        <div><strong>გადამოწმება</strong> — სისტემა Drive-ს ყოველ წუთს ამოწმებს ავტომატურად. თუ ფაილი
                            Drive-ში ატვირთე მაგრამ დაფაზე ✗ წერია — დააჭირე გადამოწმება. სისტემა ყველა ACT-ს თავიდან
                            შეამოწმებს. <strong>30–60 წუთი სჭირდება.</strong> ნუ გამოიყენებ ზედმეტად.</div>
                    </div>
                </div>
            </div>

            {{-- 2. Tools --}}
            <div class="help-section">
                <button class="help-toggle" onclick="helpToggle(this)">
                    <span>🛠️ Tools — გადმოწერა და ატვირთვა</span>
                    <span class="help-chevron">▼</span>
                </button>
                <div class="help-body">
                    <div
                        style="font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;">
                        ⬇️ გადმოწერა</div>
                    <div class="help-sub-row" style="margin-bottom:6px;"><span>📦</span>
                        <div><strong>სკანების გადმოწერა</strong> — ჩამოტვირთე სკანები ZIP ფაილად. შეგიძლია მიუთითო
                            ACT-ების დიაპაზონი (მაგ. 1–100) ან გადმოწერო ყველა.</div>
                    </div>
                    <div class="help-sub-row" style="margin-bottom:12px;"><span>📸</span>
                        <div><strong>ფოტოების გადმოწერა</strong> — ჩამოტვირთე ფოტოები ZIP ფაილად. დიაპაზონი სავალდებულოა
                            (მაგ. 1–100).</div>
                    </div>
                    <div
                        style="font-size:11px;color:#6b7280;font-weight:600;text-transform:uppercase;letter-spacing:.06em;margin-bottom:6px;">
                        ⬆️ ატვირთვა</div>
                    <div class="help-sub-row" style="margin-bottom:6px;"><span>📦</span>
                        <div><strong>სკანების ატვირთვა</strong> — ატვირთე სკანები ZIP ფაილად. სისტემა თავად დაალაგებს
                            ACT-ების მიხედვით. <em>„არსებული ჩაანაცვლე"</em> — მონიშნე თუ გსურს ძველი ფაილი შეიცვალოს
                            ახლით.</div>
                    </div>
                    <div class="help-sub-row" style="margin-bottom:6px;"><span>📸</span>
                        <div><strong>ფოტოების ატვირთვა</strong> — ატვირთე ფოტოები ZIP ფაილად. სისტემა თავად დაალაგებს
                            ACT-ების მიხედვით.</div>
                    </div>
                    <div class="help-sub-row"><span>📐</span>
                        <div><strong>ნახაზების ატვირთვა</strong> — ატვირთე ნახაზები ZIP ფაილად. <em>„არსებული
                                ჩაანაცვლე"</em> — მონიშნე თუ გსურს ძველი ნახაზი შეიცვალოს ახლით.</div>
                    </div>
                </div>
            </div>

            {{-- 3. დაფა --}}
            <div class="help-section">
                <button class="help-toggle" onclick="helpToggle(this)">
                    <span>📋 დაფა — სტატუსი, ფილტრები, სიმბოლოები</span>
                    <span class="help-chevron">▼</span>
                </button>
                <div class="help-body">

                    <div style="font-size:11px;font-weight:600;color:#1a1f36;margin-bottom:6px;">დასრულებული</div>
                    <div class="help-row" style="margin-bottom:4px;">
                        <span>✅</span>
                        <div><strong>დაიხურა</strong> — ACT საბოლოოდ დახურულია, ყველა დოკუმენტი სრულია.</div>
                    </div>
                    <div class="help-row" style="margin-bottom:12px;">
                        <span>⚠️</span>
                        <div><strong>დასრულდა</strong> — ACT-ის ძირითადი დოკუმენტები სრულია, მაგრამ დამატებითი ფაილები
                            ჯერ კიდევ აკლია.</div>
                    </div>

                    <div style="font-size:11px;font-weight:600;color:#1a1f36;margin-bottom:6px;">ფილტრები</div>
                    <div style="display:flex;flex-direction:column;gap:4px;margin-bottom:12px;">
                        <div class="help-sub-row"><span>🔍</span>
                            <div><strong>ACT ძებნა</strong> — ACT ნომრით ძებნა ყველა ACT-ში, არა მხოლოდ მიმდინარე
                                გვერდზე.</div>
                        </div>
                        <div class="help-sub-row"><span>⏳</span>
                            <div><strong>სტატუსი</strong> — გაფილტრე სტატუსის მიხედვით.</div>
                        </div>
                        <div class="help-sub-row"><span>❌</span>
                            <div><strong>აკლია</strong> — აჩვენე მხოლოდ ACT-ები სადაც კონკრეტული დოკუმენტი აკლია.</div>
                        </div>
                        <div class="help-sub-row"><span>✅</span>
                            <div><strong>არის</strong> — აჩვენე მხოლოდ ACT-ები სადაც კონკრეტული დოკუმენტი არის.</div>
                        </div>
                        <div class="help-sub-row"><span>🔬</span>
                            <div><strong>კვლევა</strong> — გაფილტრე კვლევის სტატუსის მიხედვით.</div>
                        </div>
                        <div class="help-sub-row"><span>🏢</span>
                            <div><strong>ბიზნესი</strong> — ბიზნეს საქმიანობასთან დაკავშირებული ACT-ების ფილტრი.</div>
                        </div>
                        <div class="help-sub-row"><span>📊</span>
                            <div><strong>Excel</strong> — ხარჯთაღრიცხვის სტატუსის ფილტრი — გაექსელებულია თუ არა.</div>
                        </div>
                    </div>

                    <div style="font-size:11px;font-weight:600;color:#1a1f36;margin-bottom:6px;">სიმბოლოები</div>
                    <div style="display:flex;flex-direction:column;gap:4px;">
                        <div class="help-sub-row"><span>✅</span>
                            <div><strong>არის</strong> — საჭირო დოკუმენტი Drive-ში ატვირთულია.</div>
                        </div>
                        <div class="help-sub-row"><span>❌</span>
                            <div><strong>აკლია</strong> — საჭირო დოკუმენტი Drive-ში არ არის, ატვირთვა საჭიროა.</div>
                        </div>
                        <div class="help-sub-row"><span style="color:#b3bac5;">·</span>
                            <div><strong>არ საჭიროება</strong> — ეს დოკუმენტი ამ ACT-ისთვის საჭირო არ არის.</div>
                        </div>
                        <div class="help-sub-row"><span>❗</span>
                            <div><strong>მიანახე მაუსი</strong> — დამატებითი ინფორმაცია გამოჩნდება.</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. სვეტები --}}
            <div class="help-section">
                <button class="help-toggle" onclick="helpToggle(this)">
                    <span>📊 სვეტები და პაგინაცია</span>
                    <span class="help-chevron">▼</span>
                </button>
                <div class="help-body">
                    <div style="display:flex;flex-direction:column;gap:4px;margin-bottom:12px;">
                        <div class="help-sub-row"><span>🔢</span>
                            <div><strong>ნომერი</strong> — ACT-ის უნიკალური ნომერი. დააჭირე და Airtable-ში ACT-ის სრული
                                ბარათი გაიხსნება.</div>
                        </div>
                        <div class="help-sub-row"><span>🚦</span>
                            <div><strong>სტატუსი</strong> — ACT-ის მიმდინარე მდგომარეობა.</div>
                        </div>
                        <div class="help-sub-row"><span>💬</span>
                            <div><strong>შენ.</strong> — დამატებითი შენიშვნა ACT-ზე. მიანახე მაუსი და წაიკითხე.</div>
                        </div>
                        <div class="help-sub-row"><span>📄</span>
                            <div><strong>სკანი</strong> — ACT-ის სკანი Drive-შია თუ არა.</div>
                        </div>
                        <div class="help-sub-row"><span>📸</span>
                            <div><strong>ფოტო</strong> — ფოტოები Drive-შია თუ არა.</div>
                        </div>
                        <div class="help-sub-row"><span>✍️</span>
                            <div><strong>ხელმ.</strong> — ხელმოწერები სრულია თუ არა.</div>
                        </div>
                        <div class="help-sub-row"><span>📐</span>
                            <div><strong>ნახაზი</strong> — ნახაზები Drive-შია თუ არა (მხოლოდ იმ ACT-ებისთვის სადაც
                                შენობა/ნაგებობა მონიშნულია).</div>
                        </div>
                        <div class="help-sub-row"><span>⬛</span>
                            <div><strong>გამიჯ.</strong> — გამიჯვნას საჭიროებს თუ არა.</div>
                        </div>
                        <div class="help-sub-row"><span>💰</span>
                            <div><strong>ხარჯ.</strong> — ხარჯთაღრიცხვა Drive-შია თუ არა (იმ შემთხვევაში თუ
                                შესაფასებელია).</div>
                        </div>
                        <div class="help-sub-row"><span>🌳</span>
                            <div><strong>ტოპო</strong> — ტოპო/ნარგავები Drive-შია თუ არა (იმ შემთხვევაში თუ ხე-მცენარები
                                მონიშნულია).</div>
                        </div>
                        <div class="help-sub-row"><span>🏢</span>
                            <div><strong>ბიზნ.</strong> — ბიზნეს საქმიანობასთან დაკავშირებულია თუ არა.</div>
                        </div>
                        <div class="help-sub-row"><span>🔬</span>
                            <div><strong>კვლევა</strong> — კვლევის სტატუსი.</div>
                        </div>
                        <div class="help-sub-row"><span>📁</span>
                            <div><strong>DRIVE</strong> — Drive საქაღალდის გახსნა. ⬆️ აირჩიე საქაღალდე (მაგ: სკანი),
                                ატვირთე ცალკე ფაილი ან მთლიანი საქაღალდე.</div>
                        </div>
                    </div>

                    <div style="font-size:11px;font-weight:600;color:#1a1f36;margin-bottom:6px;">პაგინაცია</div>
                    <div style="font-size:12px;color:#4b5563;line-height:1.6;">დაფა რამდენიმე გვერდადაა დაყოფილი.
                        „შემდეგი →" დააჭირე შემდეგ გვერდზე გადასასვლელად.</div>
                </div>
            </div>

            {{-- 5. პრობლემები --}}
            <div class="help-section">
                <button class="help-toggle" onclick="helpToggle(this)">
                    <span>🚨 პრობლემები და გადაწყვეტა</span>
                    <span class="help-chevron">▼</span>
                </button>
                <div class="help-body">
                    <div class="help-row">
                        <span>📂</span>
                        <div>ფაილი Drive-ში ატვირთე მაგრამ დაფაზე ✗ წერია → <strong>სისტემა თავად განაახლებს ღამის
                                საათებში</strong>, თუმცა შეგიძლია დააჩქარო გადამოწმების ღილაკით.</div>
                    </div>
                    <div class="help-row">
                        <span>⏱️</span>
                        <div>გადამოწმება დიდხანს გრძელდება → <strong>ნორმალურია</strong>, 30–60 წუთი სჭირდება.</div>
                    </div>
                </div>
            </div>

        </div>{{-- /accordion --}}
    </div>
</div>

{{-- Help modal styles --}}
<style>
    .help-section {
        border: 1px solid #e4e8f0;
        border-radius: 8px;
        overflow: hidden;
    }

    .help-toggle {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 13px 16px;
        background: #f8f9fc;
        border: none;
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
        color: #1a1f36;
        font-family: 'IBM Plex Sans', sans-serif;
        text-align: left;
        transition: background .15s;
        line-height: 1.4;
    }

    .help-toggle:hover {
        background: #eef1f8;
    }

    .help-toggle.open {
        background: #1a1f36;
        color: #e2e8f0;
    }

    .help-chevron {
        flex-shrink: 0;
        font-size: 10px;
        transition: transform .2s;
        color: #8892a4;
    }

    .help-toggle.open .help-chevron {
        transform: rotate(180deg);
        color: #7c9df7;
    }

    .help-body {
        display: none;
        padding: 14px 16px;
        font-size: 12px;
        color: #4b5563;
        line-height: 1.6;
        border-top: 1px solid #e4e8f0;
        background: #fff;
    }

    .help-body.open {
        display: block;
    }

    .help-row {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        margin-bottom: 10px;
    }

    .help-row:last-child {
        margin-bottom: 0;
    }

    .help-icon {
        flex-shrink: 0;
        font-size: 15px;
        line-height: 1.5;
    }

    .help-sub-row {
        display: flex;
        gap: 8px;
        align-items: flex-start;
        font-size: 12px;
    }

    .help-sub-row span {
        flex-shrink: 0;
        line-height: 1.6;
        min-width: 16px;
    }

    @media (max-width: 640px) {
        #help-modal {
            padding: 12px 8px;
        }

        .help-toggle {
            font-size: 11px;
            padding: 11px 13px;
        }
    }
</style>

{{-- Help modal script --}}
<script>
    function helpToggle(btn) {
        const body = btn.nextElementSibling;
        const isOpen = body.classList.contains('open');

        // Close all
        document.querySelectorAll('.help-toggle').forEach(b => b.classList.remove('open'));
        document.querySelectorAll('.help-body').forEach(b => b.classList.remove('open'));

        // Open clicked if it was closed
        if (!isOpen) {
            btn.classList.add('open');
            body.classList.add('open');
        }
    }

    document.getElementById('help-modal').addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.getElementById('help-modal').style.display = 'none';
        }
    });

    function openHelpModal() {
        document.getElementById('help-modal').style.display = 'flex';
    }
    document.querySelector('[data-open-help]')?.addEventListener('click', openHelpModal);
</script>
