<?php
session_start();
require_once __DIR__ . '/../database/db.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>TalentScout AI — Find Your Next Opportunity</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
      *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

      :root {
        --sand:        #f5f0e8;
        --sand-dark:   #ece5d5;
        --sage:        #6b8f71;
        --sage-light:  #9ab89f;
        --sage-pale:   #d4e6d6;
        --sage-deep:   #4a6b50;
        --stone:       #8a8070;
        --stone-light: #c4b9a8;
        --cream:       #faf8f3;
        --charcoal:    #2a2a22;
        --warm-mid:    #5a5448;
        --warm-light:  #9a9288;
        --gold:        #c8a96e;
        --gold-pale:   #f0e4c8;
        --white-t:     rgba(255,255,255,0.92);
        --radius-xl:   24px;
        --radius-lg:   16px;
        --radius-md:   10px;
        --radius-pill: 999px;
        --ease-out:    cubic-bezier(0.22, 1, 0.36, 1);
      }

      html { scroll-behavior: smooth; }

      body {
        font-family: 'DM Sans', sans-serif;
        background: var(--cream);
        color: var(--charcoal);
        min-height: 100vh;
        overflow-x: hidden;
      }

      a { text-decoration: none; color: inherit; }

      /* ── GRAIN OVERLAY ── */
      body::before {
        content: '';
        position: fixed;
        inset: 0;
        pointer-events: none;
        z-index: 9999;
        opacity: 0.03;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E");
      }

      /* ── NAVBAR ── */
      .navbar {
        position: fixed;
        top: 0; left: 0; right: 0;
        z-index: 100;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 3rem;
        height: 64px;
        background: rgba(250, 248, 243, 0.88);
        backdrop-filter: blur(20px);
        border-bottom: 1px solid rgba(139, 128, 112, 0.12);
        animation: slideDown 0.6s var(--ease-out) both;
      }

      @keyframes slideDown {
        from { transform: translateY(-100%); opacity: 0; }
        to   { transform: translateY(0); opacity: 1; }
      }

      .nav-logo {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        font-family: 'Playfair Display', serif;
        font-weight: 700;
        font-size: 1.15rem;
        color: var(--charcoal);
        letter-spacing: -0.01em;
      }

      .nav-logo-mark {
        width: 34px; height: 34px;
        background: var(--sage-deep);
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.72rem;
        font-weight: 600;
        color: #fff;
        letter-spacing: 0.04em;
      }

      .nav-logo em { font-style: italic; color: var(--sage); }

      .nav-links {
        display: flex;
        list-style: none;
        gap: 0.15rem;
      }

      .nav-links a {
        padding: 0.38rem 0.85rem;
        border-radius: var(--radius-pill);
        font-size: 0.84rem;
        font-weight: 400;
        color: var(--warm-mid);
        transition: background 0.2s, color 0.2s;
        letter-spacing: 0.01em;
      }

      .nav-links a:hover, .nav-links a.active {
        background: var(--sage-pale);
        color: var(--sage-deep);
      }

      .nav-right {
        display: flex; align-items: center; gap: 0.7rem;
      }

      .nav-user {
        font-size: 0.83rem;
        color: var(--warm-mid);
      }

      .btn-nav-ghost {
        padding: 0.4rem 1rem;
        border-radius: var(--radius-pill);
        border: 1px solid var(--stone-light);
        color: var(--warm-mid);
        font-family: 'DM Sans', sans-serif;
        font-size: 0.83rem;
        font-weight: 500;
        background: transparent;
        cursor: pointer;
        transition: border-color 0.2s, background 0.2s;
      }

      .btn-nav-ghost:hover { background: var(--sand); border-color: var(--stone); }

      .btn-nav-solid {
        padding: 0.44rem 1.2rem;
        border-radius: var(--radius-pill);
        background: var(--sage-deep);
        color: #fff;
        font-family: 'DM Sans', sans-serif;
        font-size: 0.83rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: background 0.2s, transform 0.15s;
        display: flex; align-items: center; gap: 0.35rem;
      }

      .btn-nav-solid:hover { background: var(--sage); transform: translateY(-1px); }

      /* ── HERO ── */
      .hero {
        position: relative;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        padding-top: 64px;
      }

      .hero-bg {
        position: absolute;
        inset: 0;
        background: url('hero-bg.jpg') center/cover no-repeat;
        transform: scale(1.05);
        animation: heroZoom 12s ease-in-out infinite alternate;
      }

      @keyframes heroZoom {
        from { transform: scale(1.05); }
        to   { transform: scale(1.12); }
      }

      .hero-bg::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(
          to bottom,
          rgba(42, 42, 34, 0.45) 0%,
          rgba(42, 42, 34, 0.28) 50%,
          rgba(42, 42, 34, 0.55) 100%
        );
      }

      .hero-center {
        position: relative;
        z-index: 2;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 3rem 2rem;
        animation: heroFadeIn 1s 0.3s var(--ease-out) both;
      }

      @keyframes heroFadeIn {
        from { opacity: 0; transform: translateY(30px); }
        to   { opacity: 1; transform: translateY(0); }
      }

      /* Glass card — like image 2 */
      .hero-card {
        background: rgba(250, 248, 243, 0.82);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border: 1px solid rgba(255,255,255,0.6);
        border-radius: var(--radius-xl);
        padding: 3.2rem 3.8rem 2.8rem;
        max-width: 600px;
        width: 100%;
        box-shadow: 0 24px 72px rgba(42,42,34,0.22), 0 4px 16px rgba(42,42,34,0.12);
      }

      .hero-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.13em;
        text-transform: uppercase;
        color: var(--sage);
        margin-bottom: 1.2rem;
        background: var(--sage-pale);
        padding: 0.28rem 0.85rem;
        border-radius: var(--radius-pill);
      }

      .hero-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(1.9rem, 4vw, 2.8rem);
        font-weight: 900;
        color: var(--charcoal);
        line-height: 1.15;
        letter-spacing: -0.025em;
        margin-bottom: 1rem;
      }

      .hero-title em {
        font-style: italic;
        color: var(--sage);
      }

      .hero-desc {
        font-size: 0.92rem;
        color: var(--warm-mid);
        line-height: 1.7;
        margin-bottom: 2rem;
        font-weight: 400;
        max-width: 420px;
        margin-left: auto;
        margin-right: auto;
      }

      .hero-actions {
        display: flex;
        gap: 0.75rem;
        justify-content: center;
        flex-wrap: wrap;
      }

      .btn-hero-primary {
        padding: 0.82rem 2rem;
        background: var(--sage-deep);
        color: #fff;
        border-radius: var(--radius-pill);
        font-family: 'DM Sans', sans-serif;
        font-size: 0.9rem;
        font-weight: 700;
        border: none;
        cursor: pointer;
        display: inline-flex; align-items: center; gap: 0.4rem;
        transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
        box-shadow: 0 6px 20px rgba(74,107,80,0.35);
        letter-spacing: 0.01em;
      }

      .btn-hero-primary:hover {
        background: var(--sage);
        transform: translateY(-2px);
        box-shadow: 0 10px 28px rgba(74,107,80,0.42);
      }

      .btn-hero-ghost {
        padding: 0.82rem 1.8rem;
        background: transparent;
        color: var(--charcoal);
        border-radius: var(--radius-pill);
        font-family: 'DM Sans', sans-serif;
        font-size: 0.9rem;
        font-weight: 600;
        border: 1.5px solid rgba(42,42,34,0.22);
        cursor: pointer;
        display: inline-block;
        transition: background 0.2s, border-color 0.2s, transform 0.15s;
      }

      .btn-hero-ghost:hover {
        background: var(--sand);
        border-color: var(--stone);
        transform: translateY(-2px);
      }

      /* Floating scroll indicator */
      .hero-scroll {
        position: absolute;
        bottom: 2.5rem;
        left: 50%;
        transform: translateX(-50%);
        z-index: 3;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.4rem;
        color: rgba(255,255,255,0.7);
        font-size: 0.7rem;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        animation: bounce 2s ease-in-out infinite;
      }

      @keyframes bounce {
        0%, 100% { transform: translateX(-50%) translateY(0); }
        50% { transform: translateX(-50%) translateY(6px); }
      }

      .hero-scroll svg { opacity: 0.8; }

      /* ── STAT BAR ── */
      .stat-bar {
        background: var(--charcoal);
        padding: 1.4rem 2rem;
      }

      .stat-bar-inner {
        max-width: 1000px;
        margin: 0 auto;
        display: flex;
        justify-content: space-around;
        gap: 1rem;
        flex-wrap: wrap;
      }

      .stat-item {
        text-align: center;
        animation: fadeUp 0.8s var(--ease-out) both;
      }

      .stat-item:nth-child(2) { animation-delay: 0.1s; }
      .stat-item:nth-child(3) { animation-delay: 0.2s; }
      .stat-item:nth-child(4) { animation-delay: 0.3s; }

      @keyframes fadeUp {
        from { opacity: 0; transform: translateY(20px); }
        to   { opacity: 1; transform: translateY(0); }
      }

      .stat-num {
        font-family: 'Playfair Display', serif;
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--gold);
        line-height: 1;
        display: block;
      }

      .stat-label {
        font-size: 0.72rem;
        color: rgba(255,255,255,0.5);
        text-transform: uppercase;
        letter-spacing: 0.09em;
        margin-top: 0.2rem;
        display: block;
      }

      /* ── WELCOME SECTION ── */
      .welcome {
        padding: 6rem 2rem;
        max-width: 1080px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 1.1fr 1fr;
        gap: 5rem;
        align-items: center;
      }

      .welcome-visual {
        position: relative;
      }

      .welcome-img-wrap {
        width: 100%;
        height: 420px;
        border-radius: var(--radius-xl);
        overflow: hidden;
        position: relative;
      }

      .welcome-img-wrap img {
        width: 100%; height: 100%;
        object-fit: cover;
        transition: transform 0.6s var(--ease-out);
      }

      .welcome-img-wrap:hover img { transform: scale(1.04); }

      .welcome-img-wrap::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(107,143,113,0.2), transparent 60%);
        border-radius: var(--radius-xl);
      }

      .badge-float {
        position: absolute;
        background: #fff;
        border-radius: var(--radius-lg);
        box-shadow: 0 8px 28px rgba(42,42,34,0.14);
        display: flex; align-items: center; gap: 0.6rem;
        padding: 0.7rem 1.1rem;
        animation: float 4s ease-in-out infinite;
      }

      @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-7px); }
      }

      .badge-float.b1 {
        top: -18px; right: -20px;
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--charcoal);
      }

      .badge-float.b2 {
        bottom: -18px; left: -20px;
        animation-delay: 2s;
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--charcoal);
      }

      .badge-icon {
        width: 34px; height: 34px;
        background: var(--sage-pale);
        border-radius: var(--radius-md);
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem;
      }

      .welcome-text .eyebrow {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--sage);
        margin-bottom: 0.8rem;
      }

      .welcome-text h2 {
        font-family: 'Playfair Display', serif;
        font-size: clamp(1.6rem, 2.8vw, 2.2rem);
        font-weight: 900;
        color: var(--charcoal);
        letter-spacing: -0.025em;
        line-height: 1.2;
        margin-bottom: 1.1rem;
      }

      .welcome-text h2 em {
        font-style: italic;
        color: var(--sage);
      }

      .welcome-text p {
        font-size: 0.89rem;
        color: var(--warm-mid);
        line-height: 1.78;
        margin-bottom: 1.8rem;
      }

      .welcome-btns {
        display: flex; gap: 0.75rem; flex-wrap: wrap;
      }

      .btn-sage {
        padding: 0.68rem 1.5rem;
        background: var(--sage-deep);
        color: #fff;
        border-radius: var(--radius-pill);
        font-family: 'DM Sans', sans-serif;
        font-size: 0.86rem;
        font-weight: 700;
        border: none;
        cursor: pointer;
        display: inline-flex; align-items: center; gap: 0.35rem;
        transition: background 0.2s, transform 0.15s;
        box-shadow: 0 4px 14px rgba(74,107,80,0.28);
      }

      .btn-sage:hover { background: var(--sage); transform: translateY(-1px); }

      .btn-outline {
        padding: 0.68rem 1.4rem;
        background: transparent;
        color: var(--warm-mid);
        border-radius: var(--radius-pill);
        font-family: 'DM Sans', sans-serif;
        font-size: 0.86rem;
        font-weight: 500;
        border: 1.5px solid var(--stone-light);
        cursor: pointer;
        transition: background 0.2s, border-color 0.2s;
      }

      .btn-outline:hover { background: var(--sand); border-color: var(--stone); }

      /* ── FEATURES ── */
      .features {
        background: var(--sand);
        padding: 6rem 2rem;
        position: relative;
        overflow: hidden;
      }

      .features::before {
        content: '';
        position: absolute;
        top: -80px; right: -80px;
        width: 320px; height: 320px;
        border-radius: 50%;
        background: radial-gradient(circle, var(--sage-pale) 0%, transparent 70%);
        pointer-events: none;
      }

      .features::after {
        content: '';
        position: absolute;
        bottom: -60px; left: -60px;
        width: 260px; height: 260px;
        border-radius: 50%;
        background: radial-gradient(circle, var(--gold-pale) 0%, transparent 70%);
        pointer-events: none;
      }

      .section-head {
        text-align: center;
        margin-bottom: 3.5rem;
        position: relative; z-index: 1;
      }

      .section-head .eyebrow {
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: var(--sage);
        margin-bottom: 0.7rem;
      }

      .section-head h2 {
        font-family: 'Playfair Display', serif;
        font-size: clamp(1.6rem, 3vw, 2.4rem);
        font-weight: 900;
        color: var(--charcoal);
        letter-spacing: -0.025em;
        margin-bottom: 0.7rem;
      }

      .section-head p {
        font-size: 0.88rem;
        color: var(--warm-mid);
        max-width: 480px;
        margin: 0 auto;
        line-height: 1.7;
      }

      .features-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.4rem;
        max-width: 1080px;
        margin: 0 auto;
        position: relative; z-index: 1;
      }

      .feature-card {
        background: var(--cream);
        border-radius: var(--radius-xl);
        padding: 0;
        overflow: hidden;
        border: 1px solid rgba(139,128,112,0.12);
        box-shadow: 0 4px 24px rgba(42,42,34,0.07);
        transition: transform 0.3s var(--ease-out), box-shadow 0.3s;
        display: flex; flex-direction: column;
        cursor: pointer;
      }

      .feature-card:hover {
        transform: translateY(-7px);
        box-shadow: 0 18px 48px rgba(42,42,34,0.14);
      }

      .feature-thumb {
        width: 100%;
        height: 160px;
        background: var(--sage-pale);
        display: flex; align-items: center; justify-content: center;
        font-size: 2.8rem;
        overflow: hidden;
        position: relative;
      }

      .feature-thumb img {
        width: 100%; height: 100%;
        object-fit: cover;
        transition: transform 0.4s var(--ease-out);
      }

      .feature-card:hover .feature-thumb img { transform: scale(1.06); }

      .feature-thumb .overlay-tag {
        position: absolute;
        top: 0.75rem; left: 0.75rem;
        background: rgba(250,248,243,0.9);
        backdrop-filter: blur(8px);
        border-radius: var(--radius-pill);
        padding: 0.22rem 0.7rem;
        font-size: 0.7rem;
        font-weight: 600;
        color: var(--sage-deep);
        letter-spacing: 0.05em;
      }

      .feature-body {
        padding: 1.5rem 1.4rem 1.4rem;
        flex: 1;
        display: flex; flex-direction: column;
      }

      .feature-body h3 {
        font-family: 'Playfair Display', serif;
        font-size: 1.02rem;
        font-weight: 700;
        color: var(--charcoal);
        margin-bottom: 0.5rem;
        letter-spacing: -0.01em;
      }

      .feature-body p {
        font-size: 0.81rem;
        color: var(--warm-mid);
        line-height: 1.66;
        flex: 1;
        margin-bottom: 1.1rem;
      }

      .feature-link {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--sage-deep);
        transition: gap 0.2s, color 0.2s;
      }

      .feature-link:hover { gap: 0.55rem; color: var(--sage); }

      /* ── BENEFITS ── */
      .benefits {
        padding: 6rem 2rem;
        max-width: 1080px;
        margin: 0 auto;
      }

      .benefits-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.2rem;
        margin-top: 3.5rem;
      }

      .benefit-card {
        background: #fff;
        border: 1px solid var(--sand-dark);
        border-radius: var(--radius-xl);
        padding: 2rem 1.5rem;
        transition: box-shadow 0.25s, transform 0.25s, border-color 0.25s;
        position: relative;
        overflow: hidden;
      }

      .benefit-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--sage-pale), var(--sage));
        transform: scaleX(0);
        transform-origin: left;
        transition: transform 0.3s var(--ease-out);
      }

      .benefit-card:hover {
        box-shadow: 0 12px 40px rgba(42,42,34,0.1);
        transform: translateY(-4px);
        border-color: var(--sage-pale);
      }

      .benefit-card:hover::before { transform: scaleX(1); }

      .benefit-icon {
        width: 44px; height: 44px;
        background: var(--sage-pale);
        border-radius: var(--radius-md);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem;
        margin-bottom: 1rem;
      }

      .benefit-card h3 {
        font-family: 'Playfair Display', serif;
        font-size: 0.98rem;
        font-weight: 700;
        color: var(--charcoal);
        margin-bottom: 0.5rem;
        letter-spacing: -0.01em;
      }

      .benefit-card p {
        font-size: 0.81rem;
        color: var(--warm-mid);
        line-height: 1.65;
      }

      /* ── DIVIDER ── */
      .divider-wave {
        width: 100%;
        overflow: hidden;
        line-height: 0;
      }

      /* ── CTA ── */
      .cta {
        background: var(--charcoal);
        padding: 6rem 2rem;
        text-align: center;
        position: relative;
        overflow: hidden;
      }

      .cta-orb {
        position: absolute;
        border-radius: 50%;
        pointer-events: none;
      }

      .cta-orb.o1 {
        width: 400px; height: 400px;
        background: radial-gradient(circle, rgba(107,143,113,0.18) 0%, transparent 70%);
        top: -100px; left: -80px;
      }

      .cta-orb.o2 {
        width: 320px; height: 320px;
        background: radial-gradient(circle, rgba(200,169,110,0.12) 0%, transparent 70%);
        bottom: -80px; right: -60px;
      }

      .cta-inner { position: relative; z-index: 1; }

      .cta .eyebrow {
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: var(--sage-light);
        margin-bottom: 0.8rem;
      }

      .cta h2 {
        font-family: 'Playfair Display', serif;
        font-size: clamp(1.8rem, 3.5vw, 2.8rem);
        font-weight: 900;
        color: #fff;
        letter-spacing: -0.025em;
        margin-bottom: 1rem;
        line-height: 1.18;
      }

      .cta h2 em { color: var(--gold); font-style: italic; }

      .cta p {
        font-size: 0.9rem;
        color: rgba(255,255,255,0.6);
        max-width: 440px;
        margin: 0 auto 2.2rem;
        line-height: 1.72;
      }

      .cta-actions {
        display: flex;
        justify-content: center;
        gap: 0.9rem;
        flex-wrap: wrap;
      }

      .btn-cta-light {
        padding: 0.82rem 2rem;
        background: #fff;
        color: var(--charcoal);
        border-radius: var(--radius-pill);
        font-family: 'DM Sans', sans-serif;
        font-size: 0.9rem;
        font-weight: 700;
        border: none;
        cursor: pointer;
        transition: background 0.2s, transform 0.15s;
      }

      .btn-cta-light:hover { background: var(--sand); transform: translateY(-2px); }

      .btn-cta-outline {
        padding: 0.82rem 1.8rem;
        background: transparent;
        color: rgba(255,255,255,0.85);
        border-radius: var(--radius-pill);
        font-family: 'DM Sans', sans-serif;
        font-size: 0.9rem;
        font-weight: 500;
        border: 1.5px solid rgba(255,255,255,0.25);
        cursor: pointer;
        transition: background 0.2s, border-color 0.2s;
      }

      .btn-cta-outline:hover { background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.5); }

      /* ── FOOTER ── */
      .footer {
        background: #1e1e18;
        color: rgba(255,255,255,0.5);
        padding: 4rem 2rem 2rem;
      }

      .footer-inner { max-width: 1080px; margin: 0 auto; }

      .footer-top {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr;
        gap: 2.5rem;
        padding-bottom: 2.5rem;
        border-bottom: 1px solid rgba(255,255,255,0.07);
        margin-bottom: 1.8rem;
      }

      .footer-brand h3 {
        font-family: 'Playfair Display', serif;
        font-size: 1.1rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 0.7rem;
      }

      .footer-brand p {
        font-size: 0.81rem;
        line-height: 1.68;
        color: rgba(255,255,255,0.42);
      }

      .footer-col h4 {
        font-size: 0.72rem;
        font-weight: 700;
        color: rgba(255,255,255,0.7);
        text-transform: uppercase;
        letter-spacing: 0.1em;
        margin-bottom: 1rem;
      }

      .footer-col ul { list-style: none; display: flex; flex-direction: column; gap: 0.5rem; }

      .footer-col ul a {
        font-size: 0.82rem;
        color: rgba(255,255,255,0.4);
        transition: color 0.15s;
      }

      .footer-col ul a:hover { color: var(--sage-light); }

      .footer-bottom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.77rem;
        flex-wrap: wrap;
        gap: 0.5rem;
      }

      /* ── SCROLL ANIMATIONS ── */
      .reveal {
        opacity: 0;
        transform: translateY(28px);
        transition: opacity 0.7s var(--ease-out), transform 0.7s var(--ease-out);
      }

      .reveal.visible {
        opacity: 1;
        transform: translateY(0);
      }

      .reveal-delay-1 { transition-delay: 0.1s; }
      .reveal-delay-2 { transition-delay: 0.2s; }
      .reveal-delay-3 { transition-delay: 0.3s; }
      .reveal-delay-4 { transition-delay: 0.4s; }
      .reveal-delay-5 { transition-delay: 0.5s; }

      /* ── RESPONSIVE ── */
      @media (max-width: 960px) {
        .welcome { grid-template-columns: 1fr; gap: 2.5rem; }
        .features-grid { grid-template-columns: repeat(2, 1fr); }
        .benefits-grid { grid-template-columns: repeat(2, 1fr); }
        .footer-top { grid-template-columns: 1fr 1fr; }
        .nav-links { display: none; }
      }

      @media (max-width: 600px) {
        .navbar { padding: 0 1.2rem; }
        .features-grid { grid-template-columns: 1fr; }
        .benefits-grid { grid-template-columns: 1fr; }
        .footer-top { grid-template-columns: 1fr; }
        .footer-bottom { flex-direction: column; text-align: center; }
        .hero-card { padding: 2.2rem 1.8rem 2rem; }
        .welcome { padding: 4rem 1.5rem; }
        .badge-float { display: none; }
      }
    </style>
  </head>
  <body>

    <!-- ── NAVBAR ── -->
    <nav class="navbar">
      <a href="./index.php" class="nav-logo">
        <div class="nav-logo-mark">TS</div>
        <span>Talent<em>Scout</em> AI</span>
      </a>
      <ul class="nav-links">
        <li><a href="./index.php" class="active">Home</a></li>
        <li><a href="./modules/job-postings/index.php">Browse Jobs</a></li>
        <li><a href="./modules/ai-matching/index.php">AI Matching</a></li>
        <li><a href="./modules/resume-builder/index.php">Resume Builder</a></li>
        <li><a href="./modules/skill-gap-analysis/index.php">Skills</a></li>
        <li><a href="./modules/applicant-tracking/index.php">Applications</a></li>
      </ul>
      <div class="nav-right">
        <?php if (isset($_SESSION['employee_id'])): ?>
          <span class="nav-user">Welcome, <?php echo htmlspecialchars($_SESSION['employee_name'] ?? 'User'); ?></span>
          <a href="./logout.php" class="btn-nav-ghost">Logout</a>
        <?php else: ?>
          <a href="./login.php" class="btn-nav-ghost">Login</a>
          <a href="./signup.php" class="btn-nav-solid">Get Started →</a>
        <?php endif; ?>
      </div>
    </nav>

    <!-- ── HERO ── -->
    <section class="hero">
      <div class="hero-bg"></div>

      <div class="hero-center">
        <div class="hero-card">
          <div class="hero-eyebrow">🌿 PESO Nasugbu, Batangas</div>
          <h1 class="hero-title">
            Land Your Next <em>Opportunity</em><br>in Nasugbu
          </h1>
          <p class="hero-desc">
            Get AI-powered recommendations, build your resume, and track your applications — all in one place.
          </p>
          <div class="hero-actions">
            <a href="./modules/job-postings/" class="btn-hero-primary">Browse Jobs →</a>
            <a href="./modules/applicant-tracking/" class="btn-hero-ghost">Track Applications</a>
          </div>
        </div>
      </div>

      <div class="hero-scroll">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
        Scroll
      </div>
    </section>

    <!-- ── STAT BAR ── -->
    <div class="stat-bar">
      <div class="stat-bar-inner">
        <div class="stat-item">
          <span class="stat-num">500+</span>
          <span class="stat-label">Active Job Seekers</span>
        </div>
        <div class="stat-item">
          <span class="stat-num">120+</span>
          <span class="stat-label">Local Employers</span>
        </div>
        <div class="stat-item">
          <span class="stat-num">340+</span>
          <span class="stat-label">Job Placements</span>
        </div>
        <div class="stat-item">
          <span class="stat-num">AI</span>
          <span class="stat-label">Powered Matching</span>
        </div>
      </div>
    </div>

    <!-- ── WELCOME ── -->
    <section class="welcome">
      <div class="welcome-visual reveal">
        <div class="welcome-img-wrap">
          <img
            src="https://images.unsplash.com/photo-1521737711867-e3b97375f902?w=800&q=80"
            alt="People collaborating at work"
            onerror="this.style.display='none'"
          />
        </div>
        <div class="badge-float b1">
          <div class="badge-icon">🤖</div>
          <div>
            <div style="font-size:0.78rem;font-weight:700;color:#2a2a22;">AI Matching</div>
            <div style="font-size:0.68rem;color:#9a9288;">Skills-based hiring</div>
          </div>
        </div>
        <div class="badge-float b2">
          <div class="badge-icon">📊</div>
          <div>
            <div style="font-size:0.78rem;font-weight:700;color:#2a2a22;">Real-time Tracking</div>
            <div style="font-size:0.68rem;color:#9a9288;">Application status</div>
          </div>
        </div>
      </div>

      <div class="welcome-text">
        <div class="eyebrow reveal">Welcome to TalentScout AI</div>
        <h2 class="reveal reveal-delay-1">Grow Your Career with <em>Smart</em> AI Matching</h2>
        <p class="reveal reveal-delay-2">
          TalentScout AI is Nasugbu's dedicated job platform, powered by artificial intelligence. We connect local talent with local employers through smart skill-based matching, blind hiring, and personalized upskilling recommendations — so every opportunity is fair and accessible.
        </p>
        <div class="welcome-btns reveal reveal-delay-3">
          <a href="./modules/ai-matching/index.php" class="btn-sage">🤖 Try AI Matching</a>
          <a href="./modules/index.php" class="btn-outline">View All Tools</a>
        </div>
      </div>
    </section>

    <!-- ── FEATURES ── -->
    <section class="features">
      <div class="section-head reveal">
        <div class="eyebrow">Your Toolkit</div>
        <h2>Everything You Need to Succeed</h2>
        <p>Smart tools designed to help you find the right job, build your skills, and advance your career.</p>
      </div>

      <div class="features-grid">

        <a href="./modules/job-postings/index.php" class="feature-card reveal">
          <div class="feature-thumb">
            <img src="https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=500&q=70" alt="Browse Jobs" onerror="this.parentElement.innerHTML='💼'" />
            <span class="overlay-tag">Open Roles</span>
          </div>
          <div class="feature-body">
            <h3>Browse Job Opportunities</h3>
            <p>Explore active job postings across all barangays in Nasugbu. Filter by skills, location, salary, and type.</p>
            <span class="feature-link">Explore Jobs <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
          </div>
        </a>

        <a href="./modules/ai-matching/index.php" class="feature-card reveal reveal-delay-1">
          <div class="feature-thumb">
            <img src="https://images.unsplash.com/photo-1677442135703-1787eea5ce01?w=500&q=70" alt="AI Matching" onerror="this.parentElement.innerHTML='🤖'" />
            <span class="overlay-tag">AI-Powered</span>
          </div>
          <div class="feature-body">
            <h3>AI Job Matching</h3>
            <p>Get personalized job recommendations powered by AI that analyzes your skills and matches you to the best roles.</p>
            <span class="feature-link">See Your Matches <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
          </div>
        </a>

        <a href="./modules/resume-builder/index.php" class="feature-card reveal reveal-delay-2">
          <div class="feature-thumb">
            <img src="https://images.unsplash.com/photo-1586281380349-632531db7ed4?w=500&q=70" alt="Resume Builder" onerror="this.parentElement.innerHTML='📝'" />
            <span class="overlay-tag">Templates</span>
          </div>
          <div class="feature-body">
            <h3>Resume Builder</h3>
            <p>Create a professional resume with guided templates. Highlight your skills and experience to stand out.</p>
            <span class="feature-link">Build Resume <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
          </div>
        </a>

        <a href="./modules/skill-gap-analysis/index.php" class="feature-card reveal reveal-delay-1">
          <div class="feature-thumb">
            <img src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=500&q=70" alt="Skill Gap Analysis" onerror="this.parentElement.innerHTML='💡'" />
            <span class="overlay-tag">Analysis</span>
          </div>
          <div class="feature-body">
            <h3>Skill Gap Analysis</h3>
            <p>Get a detailed analysis of your skills and discover what training you need to unlock more opportunities.</p>
            <span class="feature-link">Analyze Skills <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
          </div>
        </a>

        <a href="#" class="feature-card reveal reveal-delay-2">
          <div class="feature-thumb" style="background: linear-gradient(135deg, #d4e6d6, #f0e4c8);">
            <span style="font-size:2.8rem;">📚</span>
            <span class="overlay-tag">Courses</span>
          </div>
          <div class="feature-body">
            <h3>Upskilling Courses</h3>
            <p>Access personalized course recommendations to close your skill gaps and level up your career prospects.</p>
            <span class="feature-link">Find Courses <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
          </div>
        </a>

        <a href="./modules/applicant-tracking/index.php" class="feature-card reveal reveal-delay-3">
          <div class="feature-thumb">
            <img src="https://images.unsplash.com/photo-1611532736597-de2d4265fba3?w=500&q=70" alt="Application Tracker" onerror="this.parentElement.innerHTML='📊'" />
            <span class="overlay-tag">Live Status</span>
          </div>
          <div class="feature-body">
            <h3>Application Tracker</h3>
            <p>Monitor every application in real-time. Track status from Pending through Interview to Successfully Hired.</p>
            <span class="feature-link">Track Applications <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg></span>
          </div>
        </a>

      </div>
    </section>

    <!-- ── BENEFITS ── -->
    <section class="benefits">
      <div class="section-head reveal">
        <div class="eyebrow">Why Choose TalentScout</div>
        <h2>Built For Your Success</h2>
        <p>Six reasons why Nasugbu's job seekers trust TalentScout AI.</p>
      </div>

      <div class="benefits-grid">
        <div class="benefit-card reveal">
          <div class="benefit-icon">⚡</div>
          <h3>Faster Matching</h3>
          <p>AI-powered matching finds your perfect job in seconds. Get matched to roles that truly fit your profile.</p>
        </div>
        <div class="benefit-card reveal reveal-delay-1">
          <div class="benefit-icon">🤝</div>
          <h3>Fair &amp; Unbiased</h3>
          <p>Blind hiring evaluates you on skills alone — no discrimination, no bias, pure talent recognition.</p>
        </div>
        <div class="benefit-card reveal reveal-delay-2">
          <div class="benefit-icon">📈</div>
          <h3>Skill Development</h3>
          <p>Get upskilling recommendations tailored to what employers in Nasugbu are actively seeking.</p>
        </div>
        <div class="benefit-card reveal reveal-delay-1">
          <div class="benefit-icon">🏘️</div>
          <h3>Local Opportunities</h3>
          <p>Support local employment. Find jobs in your community or work remotely for Nasugbu-based companies.</p>
        </div>
        <div class="benefit-card reveal reveal-delay-2">
          <div class="benefit-icon">🔗</div>
          <h3>Always Connected</h3>
          <p>Chat and SMS support keeps you updated on applications and connected with employers instantly.</p>
        </div>
        <div class="benefit-card reveal reveal-delay-3">
          <div class="benefit-icon">🗂️</div>
          <h3>One Dashboard</h3>
          <p>Manage your profile, resume, job applications, and skill development all in a single place.</p>
        </div>
      </div>
    </section>

    <!-- ── CTA ── -->
    <section class="cta">
      <div class="cta-orb o1"></div>
      <div class="cta-orb o2"></div>
      <div class="cta-inner reveal">
        <div class="eyebrow">Ready to Get Started?</div>
        <h2>Start Your Job Search <em>Today</em></h2>
        <p>Join hundreds of job seekers who have found their dream jobs through TalentScout AI. Create your profile and get matched to opportunities in minutes.</p>
        <div class="cta-actions">
          <a href="./modules/job-postings/index.php" class="btn-cta-light">Browse Job Postings</a>
          <a href="./modules/resume-builder/index.php" class="btn-cta-outline">Build Resume</a>
        </div>
      </div>
    </section>

    <!-- ── FOOTER ── -->
    <footer class="footer">
      <div class="footer-inner">
        <div class="footer-top">
          <div class="footer-brand">
            <h3>🌿 TalentScout AI</h3>
            <p>Smart AI-powered recruitment platform for PESO Nasugbu, Batangas. Connecting local talent with local opportunities.</p>
          </div>
          <div class="footer-col">
            <h4>For Job Seekers</h4>
            <ul>
              <li><a href="./modules/job-postings/index.php">Browse Jobs</a></li>
              <li><a href="./modules/ai-matching/index.php">AI Matching</a></li>
              <li><a href="./modules/skill-gap-analysis/index.php">Skill Gap Analysis</a></li>
              <li><a href="./modules/applicant-tracking/index.php">Track Applications</a></li>
            </ul>
          </div>
          <div class="footer-col">
            <h4>For Employers</h4>
            <ul>
              <li><a href="../employers/index.php">Post Jobs</a></li>
              <li><a href="../employers/modules/blind-hiring/index.php">Blind Hiring</a></li>
              <li><a href="../employers/index.php">Find Talent</a></li>
            </ul>
          </div>
          <div class="footer-col">
            <h4>PESO Nasugbu</h4>
            <ul>
              <li><a href="#">Nasugbu, Batangas</a></li>
              <li><a href="#">About PESO</a></li>
              <li><a href="#">Contact Us</a></li>
              <li><a href="#">Privacy Policy</a></li>
            </ul>
          </div>
        </div>
        <div class="footer-bottom">
          <span>&copy; 2026 TalentScout AI — PESO Nasugbu, Batangas</span>
          <span>Built for Local Employment &amp; Community Growth</span>
        </div>
      </div>
    </footer>

    <script>
      // ── Scroll reveal
      const reveals = document.querySelectorAll('.reveal');
      const io = new IntersectionObserver((entries) => {
        entries.forEach(e => {
          if (e.isIntersecting) {
            e.target.classList.add('visible');
            io.unobserve(e.target);
          }
        });
      }, { threshold: 0.12 });
      reveals.forEach(el => io.observe(el));

      // ── Stat counter animation
      const stats = document.querySelectorAll('.stat-num');
      const statObs = new IntersectionObserver((entries) => {
        entries.forEach(e => {
          if (e.isIntersecting) {
            const el = e.target;
            const raw = el.textContent;
            const num = parseInt(raw);
            if (!isNaN(num)) {
              let start = 0;
              const step = Math.ceil(num / 40);
              const timer = setInterval(() => {
                start = Math.min(start + step, num);
                el.textContent = start + (raw.includes('+') ? '+' : '');
                if (start >= num) clearInterval(timer);
              }, 30);
            }
            statObs.unobserve(el);
          }
        });
      }, { threshold: 0.5 });
      stats.forEach(s => statObs.observe(s));
    </script>

    <script src="./employee-auth.js"></script>
  </body>
</html>