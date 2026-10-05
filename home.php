<?php
/**
 * MUKIL TECH – Professional Public Website
 */
require_once __DIR__ . '/includes/functions.php';

$businessName = 'MUKIL TECH';
$businessType = 'PC Sales & Repairs';
$address      = 'Vivekanandanagar, Kilinochchi, Sri Lanka';
$phone        = '077 479 5253 / 075 531 6840';
$phone1       = '0774795253';
$email        = 'mukiltech7@gmail.com';
try {
    $businessName = getSetting('business_name', $businessName);
    $businessType = getSetting('business_type', $businessType);
    $address      = getSetting('address', $address);
    $phone        = getSetting('phone',   $phone);
    $email        = getSetting('email',   $email);
} catch (Exception $e) {}

$base     = baseUrl('');
$logoUrl  = baseUrl('assets/images/mukil-tech-logo.png');
$heroBg   = baseUrl('assets/images/hero-bg.jpg');
$servBg   = baseUrl('assets/images/services-bg.jpg');
$aboutImg = baseUrl('assets/images/about-shop.jpg');
$cntBg    = baseUrl('assets/images/counter-bg.jpg');
$loginUrl = baseUrl('login.php');
$year     = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= htmlspecialchars($businessName) ?> – Computer Shop Kilinochchi</title>
<meta name="description" content="<?= htmlspecialchars($businessName) ?> – Expert PC & laptop sales, repair, screen replacement, data recovery and IT services in Kilinochchi, Sri Lanka.">
<meta name="keywords" content="computer repair kilinochchi, laptop repair, PC shop, MUKIL TECH, screen replacement, data recovery">
<link rel="icon" type="image/png" href="<?= $logoUrl ?>">
<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Poppins:wght@700;800;900&display=swap" rel="stylesheet">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script>if(localStorage.getItem('theme')==='dark')document.documentElement.classList.add('dark');</script>
<style>
/* ===================================================
   MUKIL TECH – Professional Website CSS
=================================================== */
:root{
  --primary:#0284c7;--primary-dark:#0369a1;--primary-light:#e0f2fe;
  --cyan:#38bdf8;--cyan-dark:#0ea5e9;
  --navy:#0f172a;--navy-dark:#090e17;--navy-card:#1e293b;
  --green:#10b981;--amber:#f59e0b;--red:#ef4444;--purple:#8b5cf6;
  --bg:#f8fafc;--bg-card:#fff;--border:#e2e8f0;
  --text:#0f172a;--text2:#475569;--text3:#94a3b8;
  --shadow:0 4px 24px rgba(0,0,0,.08);
  --shadow-lg:0 16px 48px rgba(0,0,0,.14);
  --r:14px;--t:all .28s cubic-bezier(.4,0,.2,1);
}
html.dark{
  --bg:#090e17;--bg-card:#1e293b;--border:#334155;
  --text:#f0f6fc;--text2:#cbd5e1;--text3:#8b949e;
  --shadow:0 4px 24px rgba(0,0,0,.4);
  --shadow-lg:0 16px 48px rgba(0,0,0,.6);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:'Inter',system-ui,sans-serif;background:var(--bg);color:var(--text);line-height:1.6;-webkit-font-smoothing:antialiased;overflow-x:hidden;transition:background .3s,color .3s}
h1,h2,h3,h4{font-family:'Poppins',sans-serif}
a{color:var(--primary);text-decoration:none;transition:var(--t)}
img{max-width:100%;display:block}
.container{max-width:1200px;margin:0 auto;padding:0 1.5rem}
.section{padding:90px 0}
.section-sm{padding:60px 0}

/* ── TAG / EYEBROW ── */
.eyebrow{display:inline-flex;align-items:center;gap:.4rem;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:2px;color:var(--primary);background:var(--primary-light);border-radius:999px;padding:.3rem .9rem;margin-bottom:1rem}
html.dark .eyebrow{background:rgba(2,132,199,.15);color:var(--cyan)}
.section-title{font-size:clamp(1.7rem,3.5vw,2.5rem);font-weight:900;color:var(--text);line-height:1.15;margin-bottom:.85rem}
.section-sub{font-size:1rem;color:var(--text2);max-width:560px;line-height:1.75}
.text-center{text-align:center}.text-center .section-sub{margin:0 auto}

/* ══════════════════════════════════
   NAV
══════════════════════════════════ */
.nav{position:sticky;top:0;z-index:1000;background:rgba(9,14,23,.95);backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);border-bottom:1px solid rgba(255,255,255,.06)}
.nav-inner{display:flex;align-items:center;justify-content:space-between;height:70px}
.nav-brand{display:flex;align-items:center;gap:.75rem}
.nav-logo{width:42px;height:42px;border-radius:9px;background:#fff;padding:3px;object-fit:contain;box-shadow:0 0 14px rgba(56,189,248,.3)}
.nav-name strong{display:block;font-size:1.05rem;font-weight:800;color:#fff;letter-spacing:.5px}
.nav-name span{display:block;font-size:.6rem;text-transform:uppercase;letter-spacing:1.2px;color:var(--cyan);font-weight:600}
.nav-links{display:flex;align-items:center;gap:.15rem;list-style:none}
.nav-links a{color:#94a3b8;font-size:.88rem;font-weight:500;padding:.4rem .85rem;border-radius:8px;transition:var(--t)}
.nav-links a:hover,.nav-links a.active{color:#fff;background:rgba(255,255,255,.08)}
.nav-right{display:flex;align-items:center;gap:.6rem}
.btn-theme{width:38px;height:38px;border-radius:9px;border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.06);color:#94a3b8;cursor:pointer;font-size:1rem;transition:var(--t);display:flex;align-items:center;justify-content:center}
.btn-theme:hover{background:rgba(255,255,255,.14);color:#fff}
.btn-nav-cta{background:var(--primary);color:#fff !important;font-size:.85rem;font-weight:700;padding:.45rem 1.15rem;border-radius:9px;transition:var(--t);display:inline-flex;align-items:center;gap:.4rem}
.btn-nav-cta:hover{background:var(--primary-dark);transform:translateY(-1px);box-shadow:0 6px 20px rgba(2,132,199,.4)}
.hamburger{display:none;flex-direction:column;gap:5px;background:none;border:none;cursor:pointer;padding:4px}
.hamburger span{width:24px;height:2px;background:#fff;border-radius:2px;transition:var(--t);display:block}
.mob-menu{display:none;background:#090e17;border-top:1px solid rgba(255,255,255,.06);padding:1rem 1.5rem 1.5rem}
.mob-menu.open{display:block}
.mob-menu a{display:flex;align-items:center;gap:.6rem;color:#94a3b8;padding:.7rem 0;font-size:.93rem;font-weight:500;border-bottom:1px solid rgba(255,255,255,.05)}
.mob-menu a:last-child{border:none}
.mob-menu a:hover{color:#fff}

/* ══════════════════════════════════
   HERO
══════════════════════════════════ */
.hero{position:relative;min-height:100vh;display:flex;align-items:center;background:var(--navy-dark);overflow:hidden}
.hero-bg{position:absolute;inset:0;background-image:url('<?= $heroBg ?>');background-size:cover;background-position:center;opacity:.2}
html.dark .hero-bg{opacity:.15}
.hero-gradient{position:absolute;inset:0;background:linear-gradient(135deg,rgba(9,14,23,.96) 0%,rgba(2,132,199,.12) 50%,rgba(9,14,23,.95) 100%)}
.hero-particles{position:absolute;inset:0;background-image:radial-gradient(circle,rgba(56,189,248,.08) 1px,transparent 1px);background-size:40px 40px}
.hero-content{position:relative;z-index:2;display:grid;grid-template-columns:1fr 1fr;gap:3.5rem;align-items:center;padding:6rem 0 4rem}
.hero-badge{display:inline-flex;align-items:center;gap:.5rem;background:rgba(2,132,199,.15);border:1px solid rgba(56,189,248,.25);color:var(--cyan);font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:2px;padding:.35rem 1rem;border-radius:999px;margin-bottom:1.5rem}
.hero-badge i{font-size:.65rem}
.hero-h1{font-size:clamp(2.2rem,5.5vw,4rem);font-weight:900;color:#fff;line-height:1.12;margin-bottom:1.25rem}
.hero-h1 .hi{color:var(--cyan);position:relative;display:inline-block}
.hero-h1 .hi::after{content:'';position:absolute;bottom:-3px;left:0;width:100%;height:3px;background:linear-gradient(90deg,var(--cyan),transparent);border-radius:2px}
.hero-p{font-size:1.05rem;color:#94a3b8;line-height:1.8;margin-bottom:2rem;max-width:500px}
.hero-btns{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:2.5rem}
.btn-hero-p{display:inline-flex;align-items:center;gap:.5rem;background:var(--primary);color:#fff;font-size:.95rem;font-weight:700;padding:.85rem 1.9rem;border-radius:10px;transition:var(--t);box-shadow:0 6px 24px rgba(2,132,199,.4)}
.btn-hero-p:hover{background:var(--primary-dark);transform:translateY(-2px);color:#fff;box-shadow:0 10px 32px rgba(2,132,199,.5)}
.btn-hero-s{display:inline-flex;align-items:center;gap:.5rem;background:transparent;color:#cbd5e1;font-size:.95rem;font-weight:600;padding:.85rem 1.75rem;border-radius:10px;border:1px solid rgba(255,255,255,.18);transition:var(--t)}
.btn-hero-s:hover{background:rgba(255,255,255,.08);color:#fff;border-color:rgba(255,255,255,.35)}
.hero-stats{display:flex;gap:2.5rem;padding-top:2rem;border-top:1px solid rgba(255,255,255,.08)}
.hstat-num{font-size:1.8rem;font-weight:900;color:#fff;line-height:1}
.hstat-num span{font-size:1.1rem;color:var(--cyan)}
.hstat-lbl{font-size:.72rem;color:#64748b;margin-top:.2rem;font-weight:500;text-transform:uppercase;letter-spacing:.5px}

/* Hero right – floating cards */
.hero-right{display:flex;flex-direction:column;gap:1rem;padding:1rem 0}
.hcard{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:14px;padding:1.25rem 1.5rem;backdrop-filter:blur(10px);display:flex;align-items:center;gap:1rem;animation:float 4s ease-in-out infinite}
.hcard:nth-child(2){animation-delay:1.3s;margin-left:1.5rem}
.hcard:nth-child(3){animation-delay:2.6s}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-10px)}}
.hcard-icon{width:50px;height:50px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0}
.hcard-icon.b{background:rgba(2,132,199,.2)} .hcard-icon.g{background:rgba(16,185,129,.2)} .hcard-icon.a{background:rgba(245,158,11,.2)}
.hcard-title{font-size:.9rem;font-weight:700;color:#fff}
.hcard-sub{font-size:.73rem;color:#64748b;margin-top:.1rem}

/* ══════════════════════════════════
   MARQUEE STRIP
══════════════════════════════════ */
.marquee-strip{background:var(--primary);padding:.65rem 0;overflow:hidden}
.marquee-track{display:flex;gap:2.5rem;animation:marquee 22s linear infinite;width:max-content}
.marquee-track span{white-space:nowrap;font-size:.8rem;font-weight:700;color:#fff;text-transform:uppercase;letter-spacing:1px;display:flex;align-items:center;gap:.5rem}
.marquee-track span::before{content:'★';font-size:.6rem;opacity:.7}
@keyframes marquee{from{transform:translateX(0)}to{transform:translateX(-50%)}}

/* ══════════════════════════════════
   SERVICES
══════════════════════════════════ */
.services-section{background:var(--bg)}
.services-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;margin-top:3rem}
.svc-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--r);padding:2rem 1.75rem;transition:var(--t);position:relative;overflow:hidden;cursor:default}
.svc-card-accent{position:absolute;top:0;left:0;width:100%;height:3px;background:linear-gradient(90deg,var(--primary),var(--cyan));transform:scaleX(0);transform-origin:left;transition:transform .4s ease}
.svc-card:hover{transform:translateY(-6px);box-shadow:var(--shadow-lg);border-color:var(--primary)}
.svc-card:hover .svc-card-accent{transform:scaleX(1)}
.svc-icon{width:60px;height:60px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.6rem;margin-bottom:1.25rem;transition:var(--t)}
.svc-card:hover .svc-icon{transform:scale(1.1) rotate(-5deg)}
.ic-blue{background:#e0f2fe;color:var(--primary)} .ic-green{background:#ecfdf5;color:var(--green)} .ic-purple{background:#ede9fe;color:var(--purple)} .ic-amber{background:#fffbeb;color:var(--amber)} .ic-pink{background:#fdf2f8;color:#db2777} .ic-slate{background:#f1f5f9;color:#475569}
html.dark .ic-blue{background:rgba(2,132,199,.15)} html.dark .ic-green{background:rgba(16,185,129,.15)} html.dark .ic-purple{background:rgba(139,92,246,.15)} html.dark .ic-amber{background:rgba(245,158,11,.15)} html.dark .ic-pink{background:rgba(219,39,119,.15)} html.dark .ic-slate{background:rgba(148,163,184,.1)}
.svc-title{font-size:1.05rem;font-weight:700;color:var(--text);margin-bottom:.6rem}
.svc-desc{font-size:.87rem;color:var(--text2);line-height:1.7}
.svc-tag{display:inline-block;margin-top:.9rem;font-size:.68rem;font-weight:700;color:var(--primary);background:rgba(2,132,199,.1);border-radius:999px;padding:.2rem .65rem;text-transform:uppercase;letter-spacing:.5px}
html.dark .svc-tag{background:rgba(2,132,199,.15)}

/* ══════════════════════════════════
   ABOUT
══════════════════════════════════ */
.about-section{background:var(--bg-card)}
html.dark .about-section{background:rgba(30,41,59,.35)}
.about-grid{display:grid;grid-template-columns:1fr 1fr;gap:5rem;align-items:center}
.about-img-wrap{position:relative}
.about-img-main{border-radius:20px;width:100%;object-fit:cover;max-height:480px;box-shadow:var(--shadow-lg)}
.about-img-badge{position:absolute;bottom:1.5rem;right:-1.5rem;background:var(--primary);color:#fff;padding:1rem 1.25rem;border-radius:14px;text-align:center;box-shadow:0 8px 24px rgba(2,132,199,.5)}
.about-img-badge .num{font-size:1.8rem;font-weight:900;line-height:1}
.about-img-badge .lbl{font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;opacity:.85;margin-top:.15rem}
.about-img-card{position:absolute;top:1.5rem;left:-1.5rem;background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:.85rem 1.1rem;box-shadow:var(--shadow);display:flex;align-items:center;gap:.65rem}
html.dark .about-img-card{background:var(--navy-card);border-color:rgba(255,255,255,.08)}
.about-img-card .dot{width:10px;height:10px;border-radius:50%;background:var(--green);flex-shrink:0;box-shadow:0 0 6px var(--green)}
.about-img-card p{font-size:.8rem;font-weight:600;color:var(--text)}
.about-list{display:flex;flex-direction:column;gap:1.25rem;margin-top:1.75rem}
.about-item{display:flex;gap:1rem;align-items:flex-start}
.about-item-icon{width:46px;height:46px;border-radius:11px;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0}
html.dark .about-item-icon{background:rgba(2,132,199,.15)}
.about-item-title{font-size:.95rem;font-weight:700;color:var(--text);margin-bottom:.2rem}
.about-item-desc{font-size:.84rem;color:var(--text2);line-height:1.65}
.btn-about{display:inline-flex;align-items:center;gap:.5rem;background:var(--primary);color:#fff;font-size:.9rem;font-weight:700;padding:.75rem 1.6rem;border-radius:10px;transition:var(--t);margin-top:2rem;box-shadow:0 4px 16px rgba(2,132,199,.3)}
.btn-about:hover{background:var(--primary-dark);color:#fff;transform:translateY(-2px);box-shadow:0 8px 28px rgba(2,132,199,.45)}

/* ══════════════════════════════════
   COUNTER
══════════════════════════════════ */
.counter-section{position:relative;padding:80px 0;background:var(--navy-dark);overflow:hidden}
.counter-bg{position:absolute;inset:0;background-image:url('<?= $cntBg ?>');background-size:cover;background-position:center;opacity:.18}
.counter-overlay{position:absolute;inset:0;background:rgba(9,14,23,.82)}
.counter-inner{position:relative;z-index:2}
.counter-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:2rem;text-align:center}
.counter-item{}
.counter-num{font-size:clamp(2.2rem,5vw,3.5rem);font-weight:900;color:#fff;line-height:1;font-family:'Poppins',sans-serif}
.counter-num .plus{color:var(--cyan);font-size:.7em}
.counter-divider{width:40px;height:3px;background:var(--primary);border-radius:2px;margin:.6rem auto}
.counter-label{font-size:.82rem;color:#64748b;text-transform:uppercase;letter-spacing:1px;font-weight:600}

/* ══════════════════════════════════
   HOW IT WORKS
══════════════════════════════════ */
.how-section{background:var(--bg)}
.steps-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1.5rem;margin-top:3rem;position:relative}
.steps-grid::before{content:'';position:absolute;top:36px;left:12.5%;right:12.5%;height:2px;background:linear-gradient(90deg,var(--primary),var(--cyan),var(--primary));opacity:.3;z-index:0}
.step-card{text-align:center;position:relative;z-index:1}
.step-circle{width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--cyan));color:#fff;font-size:1.5rem;font-weight:900;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;box-shadow:0 6px 20px rgba(2,132,199,.4);font-family:'Poppins',sans-serif}
.step-title{font-size:.95rem;font-weight:700;color:var(--text);margin-bottom:.4rem}
.step-desc{font-size:.83rem;color:var(--text2);line-height:1.6}

/* ══════════════════════════════════
   PRICING
══════════════════════════════════ */
.pricing-section{background:var(--bg-card)}
html.dark .pricing-section{background:rgba(30,41,59,.3)}
.pricing-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;margin-top:3rem;align-items:start}
.price-card{background:var(--bg);border:1.5px solid var(--border);border-radius:var(--r);padding:2.25rem;transition:var(--t);position:relative}
html.dark .price-card{background:var(--navy-card)}
.price-card.hot{border-color:var(--primary);background:var(--bg-card);box-shadow:0 0 0 4px rgba(2,132,199,.1),var(--shadow-lg)}
html.dark .price-card.hot{background:#1a2844}
.price-hot-badge{position:absolute;top:-1px;left:50%;transform:translateX(-50%);background:linear-gradient(90deg,var(--primary),var(--cyan));color:#fff;font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:1px;padding:.25rem 1.1rem;border-radius:0 0 10px 10px;white-space:nowrap}
.price-icon{font-size:2rem;margin-bottom:1rem}
.price-plan{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:var(--text3);margin-bottom:.4rem}
.price-amount{font-size:2rem;font-weight:900;color:var(--text);font-family:'Poppins',sans-serif}
.price-amount sup{font-size:1rem;vertical-align:top;margin-top:.5rem}
.price-amount small{font-size:.8rem;font-weight:500;color:var(--text2)}
.price-desc{font-size:.84rem;color:var(--text2);margin:.75rem 0 1.5rem;line-height:1.65}
.price-features{list-style:none;display:flex;flex-direction:column;gap:.65rem;margin-bottom:2rem}
.price-features li{display:flex;align-items:center;gap:.6rem;font-size:.87rem;color:var(--text2)}
.price-features li i{color:var(--green);font-size:.85rem;flex-shrink:0}
.btn-price{display:block;text-align:center;padding:.8rem;border-radius:9px;font-weight:700;font-size:.9rem;transition:var(--t)}
.btn-price.outline{border:2px solid var(--border);color:var(--text)}
.btn-price.outline:hover{border-color:var(--primary);color:var(--primary);background:rgba(2,132,199,.05)}
.btn-price.solid{background:var(--primary);color:#fff;box-shadow:0 4px 16px rgba(2,132,199,.35)}
.btn-price.solid:hover{background:var(--primary-dark);color:#fff;transform:translateY(-1px);box-shadow:0 8px 24px rgba(2,132,199,.5)}

/* ══════════════════════════════════
   TESTIMONIALS
══════════════════════════════════ */
.testi-section{background:var(--bg)}
.testi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;margin-top:3rem}
.testi-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--r);padding:1.75rem;transition:var(--t)}
html.dark .testi-card{background:var(--navy-card)}
.testi-card:hover{box-shadow:var(--shadow-lg);transform:translateY(-4px)}
.testi-stars{color:var(--amber);font-size:.85rem;letter-spacing:2px;margin-bottom:.85rem}
.testi-text{font-size:.88rem;color:var(--text2);line-height:1.75;margin-bottom:1.25rem;font-style:italic}
.testi-author{display:flex;align-items:center;gap:.65rem}
.testi-avatar{width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--cyan));color:#fff;font-size:1.1rem;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-family:'Poppins',sans-serif}
.testi-name{font-size:.88rem;font-weight:700;color:var(--text)}
.testi-loc{font-size:.72rem;color:var(--text3)}

/* ══════════════════════════════════
   BRANDS
══════════════════════════════════ */
.brands-section{background:var(--bg-card);padding:50px 0}
html.dark .brands-section{background:rgba(30,41,59,.3)}
.brands-label{text-align:center;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:2px;color:var(--text3);margin-bottom:2rem}
.brands-row{display:flex;justify-content:center;align-items:center;flex-wrap:wrap;gap:1rem 2.5rem}
.brand-pill{display:flex;align-items:center;gap:.5rem;padding:.55rem 1.2rem;border:1px solid var(--border);border-radius:999px;font-size:.82rem;font-weight:700;color:var(--text3);transition:var(--t);cursor:default}
.brand-pill:hover{border-color:var(--primary);color:var(--primary);background:rgba(2,132,199,.06);transform:translateY(-2px)}
.brand-pill i{font-size:1rem}

/* ══════════════════════════════════
   CONTACT CTA
══════════════════════════════════ */
.contact-section{background:var(--navy-dark);padding:90px 0}
.contact-grid{display:grid;grid-template-columns:1fr 1.1fr;gap:5rem;align-items:start}
.contact-tagline{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:2px;color:var(--cyan);margin-bottom:.75rem}
.contact-title{font-size:clamp(1.6rem,3.5vw,2.4rem);font-weight:900;color:#fff;line-height:1.2;margin-bottom:.9rem}
.contact-desc{font-size:.92rem;color:#64748b;line-height:1.75;margin-bottom:2.25rem}
.info-items{display:flex;flex-direction:column;gap:1.1rem}
.info-item{display:flex;align-items:center;gap:.9rem}
.info-icon{width:44px;height:44px;border-radius:11px;background:rgba(2,132,199,.14);border:1px solid rgba(56,189,248,.18);display:flex;align-items:center;justify-content:center;color:var(--cyan);font-size:1rem;flex-shrink:0}
.info-lbl{font-size:.68rem;color:#475569;text-transform:uppercase;letter-spacing:.8px;font-weight:600}
.info-val{font-size:.9rem;color:#cbd5e1;font-weight:500}
.info-val a{color:#cbd5e1}
.info-val a:hover{color:var(--cyan)}

/* Form */
.cf-card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:var(--r);padding:2.25rem}
.cf-title{font-size:1.1rem;font-weight:800;color:#fff;margin-bottom:1.5rem;display:flex;align-items:center;gap:.5rem}
.cf-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.cf-group{margin-bottom:1rem}
.cf-label{display:block;font-size:.75rem;font-weight:600;color:#94a3b8;margin-bottom:.4rem;text-transform:uppercase;letter-spacing:.5px}
.cf-input{width:100%;padding:.7rem 1rem;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:9px;font-size:.9rem;color:#f0f6fc;font-family:'Inter',sans-serif;transition:var(--t)}
.cf-input:focus{outline:none;border-color:var(--cyan);background:rgba(56,189,248,.06);box-shadow:0 0 0 3px rgba(56,189,248,.12)}
.cf-input::placeholder{color:#334155}
textarea.cf-input{resize:vertical;min-height:95px}
.btn-cf{width:100%;padding:.82rem;background:linear-gradient(90deg,var(--primary),var(--cyan-dark));color:#fff;font-size:.95rem;font-weight:700;border:none;border-radius:9px;cursor:pointer;transition:var(--t);margin-top:.5rem;font-family:'Inter',sans-serif}
.btn-cf:hover{opacity:.9;transform:translateY(-2px);box-shadow:0 8px 28px rgba(2,132,199,.5)}
.form-success{display:none;margin-top:1rem;padding:.85rem 1rem;background:rgba(16,185,129,.12);border:1px solid rgba(52,211,153,.3);border-radius:9px;color:#34d399;font-size:.87rem;text-align:center}

/* ══════════════════════════════════
   FOOTER
══════════════════════════════════ */
footer{background:#050a12;border-top:1px solid rgba(255,255,255,.05)}
.footer-main{display:grid;grid-template-columns:2.2fr 1fr 1fr 1fr;gap:3rem;padding:60px 0 40px}
.footer-brand-logo{display:flex;align-items:center;gap:.7rem;margin-bottom:1rem}
.footer-logo{width:38px;height:38px;border-radius:8px;background:#fff;padding:2px;object-fit:contain}
.footer-brand-n{font-size:1rem;font-weight:800;color:#fff}
.footer-desc{font-size:.83rem;color:#334155;line-height:1.7;max-width:265px;margin-bottom:1.25rem}
.footer-socials{display:flex;gap:.5rem}
.footer-social{width:34px;height:34px;border-radius:8px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.06);color:#475569;display:flex;align-items:center;justify-content:center;font-size:.85rem;transition:var(--t)}
.footer-social:hover{background:var(--primary);border-color:var(--primary);color:#fff;transform:translateY(-2px)}
.footer-col-title{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:#334155;margin-bottom:1.1rem}
.footer-links{list-style:none;display:flex;flex-direction:column;gap:.55rem}
.footer-links li a{font-size:.83rem;color:#334155;transition:var(--t);display:flex;align-items:center;gap:.4rem}
.footer-links li a:hover{color:#64748b}
.footer-bottom{border-top:1px solid rgba(255,255,255,.04);padding:1.25rem 0;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem;font-size:.78rem;color:#1e293b}
.footer-bottom a{color:#334155}
.footer-bottom a:hover{color:#64748b}

/* ══════════════════════════════════
   FLOATING BTNS
══════════════════════════════════ */
.fabs{position:fixed;bottom:1.75rem;right:1.75rem;display:flex;flex-direction:column;gap:.6rem;z-index:900}
.fab{width:50px;height:50px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.2rem;box-shadow:0 4px 20px rgba(0,0,0,.3);transition:var(--t);color:#fff;text-decoration:none}
.fab:hover{transform:scale(1.1) translateY(-2px);box-shadow:0 8px 28px rgba(0,0,0,.4);color:#fff}
.fab-wa{background:#25d366}
.fab-call{background:var(--primary)}
.fab-top{background:rgba(15,23,42,.8);border:1px solid rgba(255,255,255,.1);opacity:0;transition:opacity .3s,transform .3s}
.fab-top.show{opacity:1}

/* ══════════════════════════════════
   ANIMATIONS
══════════════════════════════════ */
.reveal{opacity:0;transform:translateY(28px);transition:opacity .65s ease,transform .65s ease}
.reveal.visible{opacity:1;transform:translateY(0)}
.reveal-left{opacity:0;transform:translateX(-32px);transition:opacity .7s ease,transform .7s ease}
.reveal-left.visible{opacity:1;transform:translateX(0)}
.reveal-right{opacity:0;transform:translateX(32px);transition:opacity .7s ease,transform .7s ease}
.reveal-right.visible{opacity:1;transform:translateX(0)}
.d1{transition-delay:.1s}.d2{transition-delay:.18s}.d3{transition-delay:.26s}.d4{transition-delay:.34s}.d5{transition-delay:.42s}.d6{transition-delay:.5s}

/* ══════════════════════════════════
   RESPONSIVE
══════════════════════════════════ */
@media(max-width:1024px){
  .about-grid,.contact-grid{grid-template-columns:1fr;gap:3rem}
  .services-grid,.pricing-grid,.testi-grid{grid-template-columns:repeat(2,1fr)}
  .counter-grid{grid-template-columns:repeat(2,1fr);gap:2.5rem}
  .steps-grid{grid-template-columns:repeat(2,1fr)}
  .steps-grid::before{display:none}
  .footer-main{grid-template-columns:1fr 1fr;gap:2rem}
  .about-img-badge{right:0}
  .about-img-card{left:0}
}
@media(max-width:768px){
  .nav-links{display:none}
  .hamburger{display:flex}
  .hero-content{grid-template-columns:1fr;text-align:center;padding:4.5rem 0 3rem}
  .hero-badge,.hero-p{margin-left:auto;margin-right:auto}
  .hero-btns{justify-content:center}
  .hero-stats{justify-content:center}
  .hero-right{display:none}
  .services-grid,.pricing-grid,.testi-grid{grid-template-columns:1fr}
  .counter-grid{grid-template-columns:repeat(2,1fr)}
  .cf-row{grid-template-columns:1fr}
  .footer-main{grid-template-columns:1fr}
  .footer-bottom{justify-content:center;text-align:center}
  .about-img-badge,.about-img-card{position:static;margin-top:1rem;display:inline-flex}
}
@media(max-width:480px){
  .section{padding:60px 0}
  .counter-grid{grid-template-columns:1fr 1fr}
  .steps-grid{grid-template-columns:1fr}
  .brands-row{gap:.75rem 1.5rem}
}
</style>
</head>
<body>

<!-- ════════════════════════════ NAV ════════════════════════════ -->
<nav class="nav" id="navbar">
  <div class="container">
    <div class="nav-inner">
      <a href="<?= baseUrl('home.php') ?>" class="nav-brand">
        <img src="<?= $logoUrl ?>" alt="Logo" class="nav-logo">
        <div class="nav-name">
          <strong><?= htmlspecialchars($businessName) ?></strong>
          <span>PC Sales &amp; Repairs</span>
        </div>
      </a>

      <ul class="nav-links">
        <li><a href="#services">Services</a></li>
        <li><a href="#about">About</a></li>
        <li><a href="#pricing">Pricing</a></li>
        <li><a href="#testimonials">Reviews</a></li>
        <li><a href="#contact">Contact</a></li>
      </ul>

      <div class="nav-right">
        <button class="btn-theme" onclick="toggleTheme()" title="Toggle theme"><i id="themeIcon" class="fa-solid fa-moon"></i></button>
        <a href="<?= $loginUrl ?>" class="btn-nav-cta"><i class="fa-solid fa-lock"></i> Staff Login</a>
        <button class="hamburger" onclick="toggleNav()" aria-label="Menu">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>
  </div>
  <div class="mob-menu" id="mobMenu">
    <a href="#services"     onclick="closeNav()"><i class="fa-solid fa-screwdriver-wrench"></i> Services</a>
    <a href="#about"        onclick="closeNav()"><i class="fa-solid fa-store"></i> About Us</a>
    <a href="#pricing"      onclick="closeNav()"><i class="fa-solid fa-tag"></i> Pricing</a>
    <a href="#testimonials" onclick="closeNav()"><i class="fa-solid fa-star"></i> Reviews</a>
    <a href="#contact"      onclick="closeNav()"><i class="fa-solid fa-phone"></i> Contact</a>
    <a href="<?= $loginUrl ?>"><i class="fa-solid fa-lock"></i> Staff Login</a>
  </div>
</nav>

<!-- ════════════════════════════ HERO ════════════════════════════ -->
<section class="hero" id="home">
  <div class="hero-bg"></div>
  <div class="hero-gradient"></div>
  <div class="hero-particles"></div>
  <div class="container">
    <div class="hero-content">
      <div>
        <div class="hero-badge"><i class="fa-solid fa-bolt"></i> Kilinochchi's Premier Computer Shop</div>
        <h1 class="hero-h1">
          Your Trusted<br>
          <span class="hi">PC &amp; Laptop</span><br>
          Expert in Town
        </h1>
        <p class="hero-p">Professional computer repair, genuine parts &amp; accessories, expert technicians — all under one roof at <?= htmlspecialchars($businessName) ?>, Kilinochchi.</p>
        <div class="hero-btns">
          <a href="#services" class="btn-hero-p"><i class="fa-solid fa-screwdriver-wrench"></i> Our Services</a>
          <a href="https://wa.me/94<?= $phone1 ?>" class="btn-hero-s" target="_blank"><i class="fa-brands fa-whatsapp"></i> WhatsApp Us</a>
        </div>
        <div class="hero-stats">
          <div><div class="hstat-num">5<span>+</span></div><div class="hstat-lbl">Years Experience</div></div>
          <div><div class="hstat-num">1000<span>+</span></div><div class="hstat-lbl">Repairs Done</div></div>
          <div><div class="hstat-num">500<span>+</span></div><div class="hstat-lbl">Happy Customers</div></div>
        </div>
      </div>
      <div class="hero-right">
        <div class="hcard">
          <div class="hcard-icon b"><i class="fa-solid fa-laptop-medical"></i></div>
          <div><div class="hcard-title">Same-Day Laptop Repair</div><div class="hcard-sub">Most repairs fixed within hours</div></div>
        </div>
        <div class="hcard">
          <div class="hcard-icon g"><i class="fa-solid fa-certificate"></i></div>
          <div><div class="hcard-title">100% Genuine Parts</div><div class="hcard-sub">Warranty on all replacements</div></div>
        </div>
        <div class="hcard">
          <div class="hcard-icon a"><i class="fa-solid fa-shield-halved"></i></div>
          <div><div class="hcard-title">90-Day Service Warranty</div><div class="hcard-sub">Peace of mind guaranteed</div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- MARQUEE -->
<div class="marquee-strip">
  <div class="marquee-track">
    <?php $items=['PC Repair','Laptop Repair','Screen Replacement','Data Recovery','OS Installation','RAM Upgrade','SSD Installation','Networking Setup','Virus Removal','Custom PC Build','Battery Replacement','Keyboard Repair','PC Repair','Laptop Repair','Screen Replacement','Data Recovery','OS Installation','RAM Upgrade','SSD Installation','Networking Setup','Virus Removal','Custom PC Build','Battery Replacement','Keyboard Repair'];
    foreach($items as $i): ?><span><?= $i ?></span><?php endforeach; ?>
  </div>
</div>

<!-- ════════════════════════════ SERVICES ════════════════════════════ -->
<section class="section services-section" id="services">
  <div class="container">
    <div class="text-center">
      <div class="eyebrow reveal"><i class="fa-solid fa-gear"></i> What We Do</div>
      <h2 class="section-title reveal">Complete Computer Solutions<br>Under One Roof</h2>
      <p class="section-sub reveal">Expert technicians, genuine parts, transparent pricing — everything you need to keep your technology running perfectly.</p>
    </div>
    <div class="services-grid">
      <?php
      $svcs=[
        ['icon'=>'fa-laptop-medical','cls'=>'ic-blue','title'=>'PC &amp; Laptop Repair','desc'=>'Full hardware diagnosis and repair for all brands. Motherboard, power jack, fan, keyboard — fixed right the first time with genuine parts.','tag'=>'Hardware'],
        ['icon'=>'fa-display','cls'=>'ic-purple','title'=>'Screen Replacement','desc'=>'Cracked or dim display? We replace laptop screens for all models with high-quality panels — fast and affordable.','tag'=>'Repair'],
        ['icon'=>'fa-floppy-disk','cls'=>'ic-green','title'=>'Data Recovery','desc'=>'Lost important files? Our specialists safely recover data from failed hard drives, SSDs, and corrupted storage media.','tag'=>'Recovery'],
        ['icon'=>'fa-code','cls'=>'ic-amber','title'=>'Software &amp; OS Setup','desc'=>'Windows installation, driver setup, antivirus, performance tuning — get your machine running fast, clean and secure.','tag'=>'Software'],
        ['icon'=>'fa-microchip','cls'=>'ic-pink','title'=>'RAM &amp; SSD Upgrades','desc'=>'Dramatically speed up your computer with a RAM boost or SSD upgrade. We source and install genuine modules at competitive prices.','tag'=>'Upgrade'],
        ['icon'=>'fa-wifi','cls'=>'ic-slate','title'=>'Networking &amp; Wi-Fi','desc'=>'Router setup, LAN configuration, Wi-Fi troubleshooting and full home or office network installation for seamless connectivity.','tag'=>'Networking'],
      ];
      foreach($svcs as $i=>$s): ?>
      <div class="svc-card reveal d<?= $i+1 ?>">
        <div class="svc-card-accent"></div>
        <div class="svc-icon <?= $s['cls'] ?>"><i class="fa-solid <?= $s['icon'] ?>"></i></div>
        <div class="svc-title"><?= $s['title'] ?></div>
        <div class="svc-desc"><?= $s['desc'] ?></div>
        <span class="svc-tag"><?= $s['tag'] ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ════════════════════════════ ABOUT ════════════════════════════ -->
<section class="section about-section" id="about">
  <div class="container">
    <div class="about-grid">
      <div class="reveal-left">
        <div class="about-img-wrap">
          <img src="<?= $aboutImg ?>" alt="Mukil Tech Shop" class="about-img-main">
          <div class="about-img-badge">
            <div class="num">5+</div>
            <div class="lbl">Years in<br>Business</div>
          </div>
          <div class="about-img-card">
            <div class="dot"></div>
            <p>Open Mon – Sat &nbsp;|&nbsp; 9AM – 7PM</p>
          </div>
        </div>
      </div>
      <div class="reveal-right">
        <div class="eyebrow"><i class="fa-solid fa-store"></i> About Us</div>
        <h2 class="section-title">Kilinochchi's Most Trusted<br>Computer Shop</h2>
        <p class="section-sub"><?= htmlspecialchars($businessName) ?> has been serving Kilinochchi and surrounding areas with expert PC &amp; laptop repair services, quality products, and genuine parts for over 5 years. We treat every device like our own.</p>
        <div class="about-list">
          <div class="about-item">
            <div class="about-item-icon"><i class="fa-solid fa-bolt"></i></div>
            <div>
              <div class="about-item-title">Fast &amp; Reliable Turnaround</div>
              <div class="about-item-desc">Most repairs completed same day or within 24 hours. We keep you informed every step of the way.</div>
            </div>
          </div>
          <div class="about-item">
            <div class="about-item-icon"><i class="fa-solid fa-indian-rupee-sign"></i></div>
            <div>
              <div class="about-item-title">No Hidden Charges</div>
              <div class="about-item-desc">We provide a clear quote before any work begins. You only pay if you approve — no surprises, ever.</div>
            </div>
          </div>
          <div class="about-item">
            <div class="about-item-icon"><i class="fa-solid fa-award"></i></div>
            <div>
              <div class="about-item-title">Genuine Parts &amp; Warranty</div>
              <div class="about-item-desc">All replacement parts are authentic with full warranty coverage. Your satisfaction is our top priority.</div>
            </div>
          </div>
        </div>
        <a href="#contact" class="btn-about"><i class="fa-solid fa-phone"></i> Contact Us Today</a>
      </div>
    </div>
  </div>
</section>

<!-- ════════════════════════════ COUNTER ════════════════════════════ -->
<div class="counter-section">
  <div class="counter-bg"></div>
  <div class="counter-overlay"></div>
  <div class="container counter-inner">
    <div class="counter-grid">
      <div class="reveal d1"><div class="counter-num" data-target="1000">0<span class="plus">+</span></div><div class="counter-divider"></div><div class="counter-label">Repairs Completed</div></div>
      <div class="reveal d2"><div class="counter-num" data-target="500">0<span class="plus">+</span></div><div class="counter-divider"></div><div class="counter-label">Happy Customers</div></div>
      <div class="reveal d3"><div class="counter-num" data-target="5">0<span class="plus">+</span></div><div class="counter-divider"></div><div class="counter-label">Years Experience</div></div>
      <div class="reveal d4"><div class="counter-num" data-target="98">0<span class="plus">%</span></div><div class="counter-divider"></div><div class="counter-label">Satisfaction Rate</div></div>
    </div>
  </div>
</div>

<!-- ════════════════════════════ HOW IT WORKS ════════════════════════════ -->
<section class="section how-section">
  <div class="container">
    <div class="text-center">
      <div class="eyebrow reveal"><i class="fa-solid fa-list-check"></i> How It Works</div>
      <h2 class="section-title reveal">Get Your Device Fixed in<br>4 Simple Steps</h2>
    </div>
    <div class="steps-grid">
      <div class="step-card reveal d1">
        <div class="step-circle">1</div>
        <div class="step-title">Bring Your Device</div>
        <div class="step-desc">Walk into our shop or call/WhatsApp us to describe your problem before visiting.</div>
      </div>
      <div class="step-card reveal d2">
        <div class="step-circle">2</div>
        <div class="step-title">Free Diagnosis</div>
        <div class="step-desc">Our technician performs a full hardware &amp; software diagnosis and explains the issue clearly.</div>
      </div>
      <div class="step-card reveal d3">
        <div class="step-circle">3</div>
        <div class="step-title">Approve the Quote</div>
        <div class="step-desc">We give you a transparent quote. No work begins until you fully approve the price.</div>
      </div>
      <div class="step-card reveal d4">
        <div class="step-circle">4</div>
        <div class="step-title">Pick Up &amp; Go</div>
        <div class="step-desc">Collect your fixed device, tested and working. We provide a service warranty on every repair.</div>
      </div>
    </div>
  </div>
</section>

<!-- ════════════════════════════ PRICING ════════════════════════════ -->
<section class="section pricing-section" id="pricing">
  <div class="container">
    <div class="text-center">
      <div class="eyebrow reveal"><i class="fa-solid fa-tag"></i> Pricing</div>
      <h2 class="section-title reveal">Simple, Transparent Pricing</h2>
      <p class="section-sub reveal">Honest prices confirmed before work begins. No hidden fees, no surprises — ever.</p>
    </div>
    <div class="pricing-grid">
      <div class="price-card reveal d1">
        <div class="price-icon">🔍</div>
        <div class="price-plan">Basic</div>
        <div class="price-amount"><sup>Rs.</sup>500 <small>/ diagnosis</small></div>
        <div class="price-desc">Full hardware &amp; software diagnostic to find the root cause of your problem. Free if we do the repair.</div>
        <ul class="price-features">
          <li><i class="fa-solid fa-check"></i> Hardware diagnostics</li>
          <li><i class="fa-solid fa-check"></i> Software scan</li>
          <li><i class="fa-solid fa-check"></i> Written findings report</li>
          <li><i class="fa-solid fa-check"></i> Free when we fix it</li>
        </ul>
        <a href="#contact" class="btn-price outline">Book Diagnosis</a>
      </div>
      <div class="price-card hot reveal d2">
        <div class="price-hot-badge">⭐ Most Popular</div>
        <div class="price-icon">🔧</div>
        <div class="price-plan">Standard Repair</div>
        <div class="price-amount"><sup>Rs.</sup>1,500 <small>/ starting</small></div>
        <div class="price-desc">General repairs including OS reinstall, virus removal, keyboard, fan cleaning &amp; software setup.</div>
        <ul class="price-features">
          <li><i class="fa-solid fa-check"></i> OS installation &amp; drivers</li>
          <li><i class="fa-solid fa-check"></i> Virus &amp; malware removal</li>
          <li><i class="fa-solid fa-check"></i> Fan &amp; thermal cleaning</li>
          <li><i class="fa-solid fa-check"></i> RAM / SSD upgrade fit</li>
          <li><i class="fa-solid fa-check"></i> 90-day service warranty</li>
        </ul>
        <a href="https://wa.me/94<?= $phone1 ?>" class="btn-price solid" target="_blank">Get a Quote</a>
      </div>
      <div class="price-card reveal d3">
        <div class="price-icon">💻</div>
        <div class="price-plan">Advanced / Motherboard</div>
        <div class="price-amount"><sup>Rs.</sup>3,000 <small>/ starting</small></div>
        <div class="price-desc">Complex repairs: motherboard-level work, screen replacement, charging port &amp; data recovery.</div>
        <ul class="price-features">
          <li><i class="fa-solid fa-check"></i> Motherboard repair/replace</li>
          <li><i class="fa-solid fa-check"></i> Screen replacement</li>
          <li><i class="fa-solid fa-check"></i> Charging port repair</li>
          <li><i class="fa-solid fa-check"></i> Data recovery service</li>
          <li><i class="fa-solid fa-check"></i> 120-day service warranty</li>
        </ul>
        <a href="#contact" class="btn-price outline">Get a Quote</a>
      </div>
    </div>
    <p class="reveal" style="text-align:center;margin-top:1.5rem;font-size:.82rem;color:var(--text3);"><i class="fa-solid fa-circle-info"></i> Prices vary by device model &amp; parts. Final cost confirmed before work begins.</p>
  </div>
</section>

<!-- ════════════════════════════ TESTIMONIALS ════════════════════════════ -->
<section class="section testi-section" id="testimonials">
  <div class="container">
    <div class="text-center">
      <div class="eyebrow reveal"><i class="fa-solid fa-star"></i> Customer Reviews</div>
      <h2 class="section-title reveal">What Our Customers Say</h2>
      <p class="section-sub reveal">Trusted by hundreds of satisfied customers across Kilinochchi and surrounding areas.</p>
    </div>
    <div class="testi-grid">
      <?php
      $testis=[
        ['name'=>'Sathiyaraj K.','loc'=>'Kilinochchi','init'=>'S','text'=>'My laptop was completely dead and they brought it back to life in one day! Professional service, fair price and they explained everything clearly. Highly recommended.','stars'=>5],
        ['name'=>'Priya T.','loc'=>'Paranthan','init'=>'P','text'=>'Screen replacement was done quickly with a genuine part. The price was very reasonable and the staff were very helpful. Will definitely come back.','stars'=>5],
        ['name'=>'Arulan M.','loc'=>'Mullaitivu','init'=>'A','text'=>'Data recovery service saved all my important documents from a crashed hard drive. I was so relieved! Great team, great service.','stars'=>5],
        ['name'=>'Thilaga R.','loc'=>'Kilinochchi','init'=>'T','text'=>'OS installation and setup done perfectly. My PC is running so much faster now. The technician was very patient and explained everything.','stars'=>5],
        ['name'=>'Kumaran S.','loc'=>'Cheddikulam','init'=>'K','text'=>'Built a custom gaming PC here at a very good price. They guided me through every component choice. Excellent knowledge and service.','stars'=>5],
        ['name'=>'Niroshan V.','loc'=>'Kilinochchi','init'=>'N','text'=>'WiFi network setup at my shop was done professionally and quickly. No issues at all since. Very reliable service at MUKIL TECH.','stars'=>5],
      ];
      foreach($testis as $i=>$t): ?>
      <div class="testi-card reveal d<?= ($i%3)+1 ?>">
        <div class="testi-stars"><?= str_repeat('★',$t['stars']) ?></div>
        <div class="testi-text">"<?= $t['text'] ?>"</div>
        <div class="testi-author">
          <div class="testi-avatar"><?= $t['init'] ?></div>
          <div><div class="testi-name"><?= $t['name'] ?></div><div class="testi-loc"><i class="fa-solid fa-location-dot fa-xs"></i> <?= $t['loc'] ?></div></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ════════════════════════════ BRANDS ════════════════════════════ -->
<div class="brands-section">
  <div class="container">
    <div class="brands-label reveal">Brands We Service &amp; Sell</div>
    <div class="brands-row reveal">
      <?php foreach(['Dell','HP','Lenovo','Asus','Acer','Samsung','Apple','MSI','Toshiba','Sony'] as $b): ?>
      <div class="brand-pill"><i class="fa-solid fa-laptop"></i> <?= $b ?></div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- ════════════════════════════ CONTACT ════════════════════════════ -->
<section class="contact-section" id="contact">
  <div class="container">
    <div class="contact-grid">
      <div>
        <div class="contact-tagline reveal"><i class="fa-solid fa-phone-volume"></i> &nbsp;Get In Touch</div>
        <h2 class="contact-title reveal">We're Ready to Help.<br>Visit or Call Us Today.</h2>
        <p class="contact-desc reveal"><?= htmlspecialchars($businessName) ?> – your local computer experts in Kilinochchi. Walk in anytime or reach out below.</p>
        <div class="info-items">
          <div class="info-item reveal d1">
            <div class="info-icon"><i class="fa-solid fa-location-dot"></i></div>
            <div><div class="info-lbl">Address</div><div class="info-val"><?= htmlspecialchars($address) ?></div></div>
          </div>
          <div class="info-item reveal d2">
            <div class="info-icon"><i class="fa-solid fa-phone"></i></div>
            <div>
              <div class="info-lbl">Phone &amp; WhatsApp</div>
              <div class="info-val">
                <a href="tel:+94774795253">077 479 5253</a> &nbsp;/&nbsp;
                <a href="tel:+94755316840">075 531 6840</a>
              </div>
            </div>
          </div>
          <div class="info-item reveal d3">
            <div class="info-icon"><i class="fa-solid fa-envelope"></i></div>
            <div><div class="info-lbl">Email</div><div class="info-val"><a href="mailto:<?= htmlspecialchars($email) ?>"><?= htmlspecialchars($email) ?></a></div></div>
          </div>
          <div class="info-item reveal d4">
            <div class="info-icon"><i class="fa-solid fa-clock"></i></div>
            <div><div class="info-lbl">Working Hours</div><div class="info-val">Monday – Saturday &nbsp;|&nbsp; 9:00 AM – 7:00 PM</div></div>
          </div>
        </div>
        <!-- Map placeholder -->
        <div style="margin-top:2rem;border-radius:14px;overflow:hidden;border:1px solid rgba(255,255,255,.08);height:200px;background:rgba(255,255,255,.03);display:flex;align-items:center;justify-content:center;color:#334155;font-size:.85rem;gap:.5rem;" class="reveal">
          <i class="fa-solid fa-map-location-dot" style="color:#0284c7;font-size:1.5rem;"></i>
          Vivekanandanagar, Kilinochchi, Sri Lanka
        </div>
      </div>

      <div class="reveal d2">
        <div class="cf-card">
          <div class="cf-title"><i class="fa-solid fa-paper-plane" style="color:var(--cyan)"></i> Send Us a Message</div>
          <form id="contactForm" onsubmit="sendForm(event)">
            <div class="cf-row">
              <div class="cf-group">
                <label class="cf-label">Your Name</label>
                <input type="text" class="cf-input" placeholder="Your full name" required>
              </div>
              <div class="cf-group">
                <label class="cf-label">Phone Number</label>
                <input type="tel" class="cf-input" placeholder="07X XXX XXXX" required>
              </div>
            </div>
            <div class="cf-group">
              <label class="cf-label">Device &amp; Issue</label>
              <input type="text" class="cf-input" placeholder="e.g. Dell Inspiron – not starting">
            </div>
            <div class="cf-group">
              <label class="cf-label">Message</label>
              <textarea class="cf-input" placeholder="Describe your problem or what you need..."></textarea>
            </div>
            <button type="submit" class="btn-cf"><i class="fa-brands fa-whatsapp"></i> Send via WhatsApp</button>
          </form>
          <div class="form-success" id="formOk"><i class="fa-solid fa-circle-check"></i> &nbsp;Redirecting to WhatsApp...</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ════════════════════════════ FOOTER ════════════════════════════ -->
<footer>
  <div class="container">
    <div class="footer-main">
      <div>
        <div class="footer-brand-logo">
          <img src="<?= $logoUrl ?>" alt="Logo" class="footer-logo">
          <span class="footer-brand-n"><?= htmlspecialchars($businessName) ?></span>
        </div>
        <p class="footer-desc">Your trusted computer shop in Kilinochchi, Sri Lanka. Quality service, genuine parts, transparent pricing since <?= $year - 5 ?>.</p>
        <div class="footer-socials">
          <a href="https://wa.me/94<?= $phone1 ?>" class="footer-social" target="_blank" title="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
          <a href="mailto:<?= htmlspecialchars($email) ?>" class="footer-social" title="Email"><i class="fa-solid fa-envelope"></i></a>
          <a href="tel:+94774795253" class="footer-social" title="Call"><i class="fa-solid fa-phone"></i></a>
        </div>
      </div>
      <div>
        <p class="footer-col-title">Services</p>
        <ul class="footer-links">
          <li><a href="#services"><i class="fa-solid fa-chevron-right fa-xs"></i> PC &amp; Laptop Repair</a></li>
          <li><a href="#services"><i class="fa-solid fa-chevron-right fa-xs"></i> Screen Replacement</a></li>
          <li><a href="#services"><i class="fa-solid fa-chevron-right fa-xs"></i> Data Recovery</a></li>
          <li><a href="#services"><i class="fa-solid fa-chevron-right fa-xs"></i> OS Installation</a></li>
          <li><a href="#services"><i class="fa-solid fa-chevron-right fa-xs"></i> Networking Setup</a></li>
        </ul>
      </div>
      <div>
        <p class="footer-col-title">Quick Links</p>
        <ul class="footer-links">
          <li><a href="#home"><i class="fa-solid fa-chevron-right fa-xs"></i> Home</a></li>
          <li><a href="#about"><i class="fa-solid fa-chevron-right fa-xs"></i> About Us</a></li>
          <li><a href="#pricing"><i class="fa-solid fa-chevron-right fa-xs"></i> Pricing</a></li>
          <li><a href="#testimonials"><i class="fa-solid fa-chevron-right fa-xs"></i> Reviews</a></li>
          <li><a href="<?= $loginUrl ?>"><i class="fa-solid fa-chevron-right fa-xs"></i> Staff Login</a></li>
        </ul>
      </div>
      <div>
        <p class="footer-col-title">Contact</p>
        <ul class="footer-links">
          <li><a href="tel:+94774795253"><i class="fa-solid fa-phone fa-xs"></i> 077 479 5253</a></li>
          <li><a href="tel:+94755316840"><i class="fa-solid fa-phone fa-xs"></i> 075 531 6840</a></li>
          <li><a href="mailto:<?= htmlspecialchars($email) ?>"><i class="fa-solid fa-envelope fa-xs"></i> <?= htmlspecialchars($email) ?></a></li>
          <li><a href="#contact"><i class="fa-solid fa-location-dot fa-xs"></i> Kilinochchi, Sri Lanka</a></li>
          <li><a href="#contact"><i class="fa-solid fa-clock fa-xs"></i> Mon–Sat: 9AM–7PM</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>&copy; <?= $year ?> <?= htmlspecialchars($businessName) ?>. All Rights Reserved.</span>
      <span>Made with <span style="color:#ef4444">♥</span> for Kilinochchi</span>
    </div>
  </div>
</footer>

<!-- ════════════════════════════ FLOATING BTNS ════════════════════════════ -->
<div class="fabs">
  <a href="https://wa.me/94<?= $phone1 ?>" class="fab fab-wa" target="_blank" title="Chat on WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
  <a href="tel:+94774795253" class="fab fab-call" title="Call Us"><i class="fa-solid fa-phone"></i></a>
  <button class="fab fab-top" id="backTop" onclick="window.scrollTo({top:0,behavior:'smooth'})" title="Back to top"><i class="fa-solid fa-chevron-up"></i></button>
</div>

<script>
/* ── Theme ── */
(function(){
  const icon=document.getElementById('themeIcon');
  if(document.documentElement.classList.contains('dark'))icon.className='fa-solid fa-sun';
})();
function toggleTheme(){
  const dark=document.documentElement.classList.toggle('dark');
  localStorage.setItem('theme',dark?'dark':'light');
  document.getElementById('themeIcon').className=dark?'fa-solid fa-sun':'fa-solid fa-moon';
}

/* ── Nav ── */
function toggleNav(){document.getElementById('mobMenu').classList.toggle('open')}
function closeNav(){document.getElementById('mobMenu').classList.remove('open')}

/* ── Smooth scroll with offset ── */
document.querySelectorAll('a[href^="#"]').forEach(a=>{
  a.addEventListener('click',e=>{
    const t=document.querySelector(a.getAttribute('href'));
    if(t){e.preventDefault();window.scrollTo({top:t.offsetTop-74,behavior:'smooth'})}
  });
});

/* ── Active nav links ── */
const navLinks=document.querySelectorAll('.nav-links a[href^="#"]');
const sections=document.querySelectorAll('section[id],div[id="home"]');
const setActive=()=>{
  let cur='';
  sections.forEach(s=>{if(window.scrollY>=s.offsetTop-120)cur=s.id});
  navLinks.forEach(a=>{a.classList.toggle('active',a.getAttribute('href')==='#'+cur)});
};
window.addEventListener('scroll',setActive,{passive:true});

/* ── Back to top ── */
const bt=document.getElementById('backTop');
window.addEventListener('scroll',()=>bt.classList.toggle('show',window.scrollY>400),{passive:true});

/* ── Scroll Reveal ── */
const observer=new IntersectionObserver(entries=>{
  entries.forEach(e=>{if(e.isIntersecting)e.target.classList.add('visible')});
},{threshold:0.1});
document.querySelectorAll('.reveal,.reveal-left,.reveal-right').forEach(el=>observer.observe(el));

/* ── Counter Animation ── */
function animateCounter(el){
  const target=+el.dataset.target;
  const plus=el.querySelector('.plus');
  let start=0;
  const dur=1800;
  const step=timestamp=>{
    if(!start)start=timestamp;
    const progress=Math.min((timestamp-start)/dur,1);
    const ease=progress<.5?2*progress*progress:1-Math.pow(-2*progress+2,2)/2;
    const val=Math.floor(ease*target);
    el.childNodes[0].textContent=val;
    if(progress<1)requestAnimationFrame(step);
    else el.childNodes[0].textContent=target;
  };
  requestAnimationFrame(step);
}
const cObs=new IntersectionObserver(entries=>{
  entries.forEach(e=>{
    if(e.isIntersecting&&!e.target.dataset.counted){
      e.target.dataset.counted='1';
      animateCounter(e.target);
    }
  });
},{threshold:0.5});
document.querySelectorAll('.counter-num[data-target]').forEach(el=>cObs.observe(el));

/* ── WhatsApp Form ── */
function sendForm(e){
  e.preventDefault();
  const f=document.getElementById('contactForm');
  const i=f.querySelectorAll('input,textarea');
  const name=i[0].value.trim(),phone=i[1].value.trim(),device=i[2].value.trim(),msg=i[3].value.trim();
  const text=encodeURIComponent(`Hello MUKIL TECH!\n\nName: ${name}\nPhone: ${phone}\nDevice/Issue: ${device}\nMessage: ${msg}`);
  window.open(`https://wa.me/94<?= $phone1 ?>?text=${text}`,'_blank');
  f.reset();
  const ok=document.getElementById('formOk');
  ok.style.display='block';
  setTimeout(()=>ok.style.display='none',5000);
}
</script>
</body>
</html>
