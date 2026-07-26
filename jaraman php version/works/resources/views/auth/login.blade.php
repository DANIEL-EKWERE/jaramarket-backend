<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | {{ config('app.name', 'JaraMarket') }}</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; min-height: 100vh; display: flex; }

        /* ── Left panel ──────────────────────────────────────────────── */
        .left-panel {
            width: 52%;
            background: linear-gradient(145deg, #052e16 0%, #064e3b 35%, #065f46 65%, #047857 100%);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px;
            position: relative;
            overflow: hidden;
        }
        .left-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        /* Glowing orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            pointer-events: none;
        }
        .orb-1 { width: 320px; height: 320px; background: rgba(52,211,153,0.18); top: -80px; right: -80px; }
        .orb-2 { width: 240px; height: 240px; background: rgba(16,185,129,0.14); bottom: 60px; left: -60px; }
        .orb-3 { width: 180px; height: 180px; background: rgba(110,231,183,0.10); top: 45%; left: 40%; }

        /* ── Right panel ─────────────────────────────────────────────── */
        .right-panel {
            width: 48%;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 56px 52px;
            position: relative;
        }

        /* ── Form inputs ─────────────────────────────────────────────── */
        .input-wrap { position: relative; }
        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
        }
        .field {
            width: 100%;
            padding: 12px 44px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            background: #f8fafc;
            color: #0f172a;
            transition: all 0.2s;
            outline: none;
        }
        .field:focus {
            border-color: #059669;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(5,150,105,0.12);
        }
        .field::placeholder { color: #94a3b8; }
        .field-right { padding-right: 44px; }

        /* ── Submit button ───────────────────────────────────────────── */
        .btn-submit {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            color: #fff;
            font-weight: 600;
            font-size: 15px;
            font-family: 'Inter', sans-serif;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
            letter-spacing: 0.01em;
        }
        .btn-submit:hover {
            background: linear-gradient(135deg, #047857 0%, #065f46 100%);
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(5,150,105,0.35);
        }
        .btn-submit:active { transform: none; }
        .btn-submit:disabled { opacity: 0.75; cursor: not-allowed; transform: none; }

        /* ── Stat cards on left ──────────────────────────────────────── */
        .stat-card {
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 14px;
            padding: 18px 20px;
        }

        /* ── Animations ──────────────────────────────────────────────── */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .anim-1 { animation: fadeUp 0.5s 0.05s ease both; }
        .anim-2 { animation: fadeUp 0.5s 0.15s ease both; }
        .anim-3 { animation: fadeUp 0.5s 0.25s ease both; }
        .anim-4 { animation: fadeUp 0.5s 0.35s ease both; }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: 0.4; transform: scale(0.8); }
        }
        .dot-1 { animation: pulse-dot 1.4s 0.0s infinite; }
        .dot-2 { animation: pulse-dot 1.4s 0.2s infinite; }
        .dot-3 { animation: pulse-dot 1.4s 0.4s infinite; }

        /* ── Responsive: collapse to single column on small screens ─── */
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .left-panel { width: 100%; padding: 36px 28px; min-height: auto; }
            .left-panel .features-section { display: none; }
            .right-panel { width: 100%; padding: 36px 28px; }
        }
    </style>
</head>
<body>

    {{-- ═══════════════════════════════════════════════════
         LEFT PANEL — Branding & features
    ═══════════════════════════════════════════════════ --}}
    <div class="left-panel">
        <!-- Orbs -->
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>

        <!-- Logo -->
        <div class="relative z-10 anim-1">
            <div style="display:inline-flex;align-items:center;gap:14px;margin-bottom:8px;">
                <div style="width:48px;height:48px;background:rgba(255,255,255,0.15);border-radius:14px;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(8px);">
                    <svg style="width:26px;height:26px;color:white;fill:currentColor;" viewBox="0 0 20 20">
                        <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3z"/>
                        <path d="M16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/>
                    </svg>
                </div>
                <div>
                    <h1 style="font-size:24px;font-weight:800;color:white;letter-spacing:-0.02em;line-height:1;">
                        Jara<span style="color:#6ee7b7;">Market</span>
                    </h1>
                    <p style="font-size:12px;color:rgba(167,243,208,0.8);font-weight:500;margin-top:2px;">Admin Dashboard</p>
                </div>
            </div>
        </div>

        <!-- Hero text -->
        <div class="relative z-10 features-section">
            <div class="anim-2" style="margin-bottom:40px;">
                <h2 style="font-size:34px;font-weight:800;color:white;line-height:1.2;letter-spacing:-0.02em;margin-bottom:14px;">
                    Manage your<br>marketplace<br><span style="color:#6ee7b7;">with confidence.</span>
                </h2>
                <p style="color:rgba(167,243,208,0.75);font-size:15px;line-height:1.65;max-width:340px;">
                    Full control over vendors, orders, customers, finances and logistics — all from one place.
                </p>
            </div>

            <!-- Feature list -->
            <div class="anim-3" style="margin-bottom:44px;display:flex;flex-direction:column;gap:14px;">
                @php
                $features = [
                    ['icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'text' => 'Real-time order tracking & management'],
                    ['icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'text' => 'Multi-role admin & vendor management'],
                    ['icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'text' => 'Full financial audit & wallet oversight'],
                ];
                @endphp
                @foreach($features as $f)
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:34px;height:34px;background:rgba(255,255,255,0.1);border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg style="width:16px;height:16px;color:#6ee7b7;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $f['icon'] }}"/>
                        </svg>
                    </div>
                    <span style="color:rgba(209,250,229,0.85);font-size:13.5px;font-weight:400;">{{ $f['text'] }}</span>
                </div>
                @endforeach
            </div>

            <!-- Stats row -->
            <div class="anim-4" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
                @php
                $stats = [['label'=>'Vendors', 'value'=>'500+'],['label'=>'Orders/day', 'value'=>'2.4k'],['label'=>'Uptime', 'value'=>'99.9%']];
                @endphp
                @foreach($stats as $s)
                <div class="stat-card">
                    <p style="font-size:22px;font-weight:800;color:white;letter-spacing:-0.02em;">{{ $s['value'] }}</p>
                    <p style="font-size:11px;color:rgba(167,243,208,0.7);font-weight:500;margin-top:3px;text-transform:uppercase;letter-spacing:0.06em;">{{ $s['label'] }}</p>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Bottom copyright -->
        <div class="relative z-10" style="margin-top:24px;">
            <p style="color:rgba(167,243,208,0.45);font-size:12px;">&copy; {{ date('Y') }} JaraMarket. All rights reserved.</p>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════
         RIGHT PANEL — Login form
    ═══════════════════════════════════════════════════ --}}
    <div class="right-panel">

        <!-- Top-right decorative dot pattern -->
        <div style="position:absolute;top:0;right:0;width:160px;height:160px;background-image:radial-gradient(#e2e8f0 1.5px, transparent 1.5px);background-size:20px 20px;opacity:0.6;pointer-events:none;"></div>
        <div style="position:absolute;bottom:0;left:0;width:120px;height:120px;background-image:radial-gradient(#e2e8f0 1.5px, transparent 1.5px);background-size:20px 20px;opacity:0.5;pointer-events:none;"></div>

        <div style="max-width:380px;width:100%;margin:0 auto;position:relative;z-index:1;">

            <!-- Heading -->
            <div style="margin-bottom:32px;" class="anim-1">
                <h2 style="font-size:28px;font-weight:800;color:#0f172a;letter-spacing:-0.02em;line-height:1.2;margin-bottom:8px;">
                    Welcome back 👋
                </h2>
                <p style="color:#64748b;font-size:14px;line-height:1.5;">
                    Enter your credentials to access the admin portal.
                </p>
            </div>

            {{-- Flash messages --}}
            @if(session('success'))
            <div style="display:flex;align-items:center;gap:10px;padding:12px 14px;background:#ecfdf5;border:1.5px solid #a7f3d0;border-radius:10px;margin-bottom:20px;" class="anim-2">
                <svg style="width:16px;height:16px;color:#059669;flex-shrink:0;" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span style="font-size:13.5px;color:#065f46;font-weight:500;">{{ session('success') }}</span>
            </div>
            @endif

            @if($errors->any())
            <div style="display:flex;align-items:flex-start;gap:10px;padding:12px 14px;background:#fef2f2;border:1.5px solid #fecaca;border-radius:10px;margin-bottom:20px;" class="anim-2">
                <svg style="width:16px;height:16px;color:#ef4444;flex-shrink:0;margin-top:1px;" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <div>
                    @foreach($errors->all() as $error)
                    <p style="font-size:13.5px;color:#b91c1c;font-weight:500;">{{ $error }}</p>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Form --}}
            <form method="POST" action="{{ route('login') }}" id="loginForm" class="anim-3">
                @csrf

                {{-- Email --}}
                <div style="margin-bottom:18px;">
                    <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:7px;">Email address</label>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </span>
                        <input type="email"
                               name="email"
                               value="{{ old('email') }}"
                               class="field"
                               placeholder="admin@jaramarket.com"
                               required
                               autocomplete="email">
                    </div>
                </div>

                {{-- Password --}}
                <div style="margin-bottom:18px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:7px;">
                        <label style="font-size:13px;font-weight:600;color:#374151;">Password</label>
                        <a href="{{ route('password.request') }}"
                           style="font-size:12px;color:#059669;font-weight:500;text-decoration:none;"
                           onmouseover="this.style.textDecoration='underline'"
                           onmouseout="this.style.textDecoration='none'">
                            Forgot password?
                        </a>
                    </div>
                    <div class="input-wrap">
                        <span class="input-icon">
                            <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </span>
                        <input type="password"
                               name="password"
                               id="passwordInput"
                               class="field field-right"
                               placeholder="Enter your password"
                               required
                               autocomplete="current-password">
                        <button type="button" id="togglePassword"
                                style="position:absolute;right:0;top:0;bottom:0;padding:0 14px;display:flex;align-items:center;color:#94a3b8;background:transparent;border:none;cursor:pointer;transition:color 0.15s;">
                            <svg id="eyeOpen" style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg id="eyeClosed" style="width:16px;height:16px;display:none;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Remember me --}}
                <div style="display:flex;align-items:center;margin-bottom:24px;">
                    <input type="checkbox" name="remember" id="remember"
                           style="width:16px;height:16px;border-radius:4px;border:1.5px solid #d1d5db;cursor:pointer;accent-color:#059669;">
                    <label for="remember" style="margin-left:10px;font-size:13.5px;color:#4b5563;cursor:pointer;user-select:none;">
                        Keep me signed in for 30 days
                    </label>
                </div>

                {{-- Submit --}}
                <button type="submit" class="btn-submit" id="submitBtn">
                    <span id="btnText">Sign in to Dashboard</span>
                    <span id="btnLoading" style="display:none;align-items:center;justify-content:center;gap:6px;">
                        <span class="dot-1" style="width:6px;height:6px;border-radius:50%;background:white;display:inline-block;"></span>
                        <span class="dot-2" style="width:6px;height:6px;border-radius:50%;background:white;display:inline-block;"></span>
                        <span class="dot-3" style="width:6px;height:6px;border-radius:50%;background:white;display:inline-block;"></span>
                    </span>
                </button>
            </form>

            {{-- Divider + role note --}}
            <div class="anim-4" style="margin-top:28px;padding-top:24px;border-top:1px solid #f1f5f9;">
                <div style="display:flex;align-items:center;gap:8px;justify-content:center;">
                    <svg style="width:13px;height:13px;color:#94a3b8;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <p style="font-size:12px;color:#94a3b8;text-align:center;">
                        Restricted to authorised administrators only
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const input = document.getElementById('passwordInput');
            const open  = document.getElementById('eyeOpen');
            const closed = document.getElementById('eyeClosed');
            if (input.type === 'password') {
                input.type = 'text';
                open.style.display   = 'none';
                closed.style.display = 'block';
            } else {
                input.type = 'password';
                open.style.display   = 'block';
                closed.style.display = 'none';
            }
        });

        // Hover colour for toggle
        document.getElementById('togglePassword').addEventListener('mouseenter', function() {
            this.style.color = '#475569';
        });
        document.getElementById('togglePassword').addEventListener('mouseleave', function() {
            this.style.color = '#94a3b8';
        });

        // Loading state on submit
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn     = document.getElementById('submitBtn');
            const text    = document.getElementById('btnText');
            const loading = document.getElementById('btnLoading');
            btn.disabled = true;
            text.style.display    = 'none';
            loading.style.display = 'flex';
        });
    </script>
</body>
</html>
