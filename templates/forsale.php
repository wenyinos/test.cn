<?php
/**
 * 域名转让模板
 * @label 域名转让出售
 * @fields url,title,desc
 */
require_once __DIR__ . '/_helpers.php';

$domain_name = e($_SERVER['HTTP_HOST'] ?? '');
$contact_url = template_href($target_url ?? '', '');
$page_title  = template_value($site_title ?? '', ($domain_name ?: '当前域名') . ' — 域名转让');
$page_desc   = template_value($site_description ?? '', '该域名正在出售，欢迎洽谈收购。');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($page_title) ?></title>
<meta name="description" content="<?= e($page_desc) ?>">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Segoe UI', 'PingFang SC', 'Microsoft YaHei', sans-serif;
  background: #0a0c12;
  color: #e8eaf0;
  overflow: hidden;
}

/* 背景粒子网格 */
body::before {
  content: '';
  position: fixed;
  inset: 0;
  background:
    radial-gradient(ellipse 80% 60% at 50% 0%, rgba(99,70,255,.18) 0%, transparent 70%),
    radial-gradient(ellipse 60% 40% at 80% 80%, rgba(34,211,165,.1) 0%, transparent 60%),
    repeating-linear-gradient(0deg, transparent, transparent 39px, rgba(255,255,255,.025) 39px, rgba(255,255,255,.025) 40px),
    repeating-linear-gradient(90deg, transparent, transparent 39px, rgba(255,255,255,.025) 39px, rgba(255,255,255,.025) 40px);
  pointer-events: none;
  z-index: 0;
}

.wrap {
  position: relative;
  z-index: 1;
  text-align: center;
  padding: 20px;
  max-width: 560px;
  width: 100%;
  animation: fadeUp .7s cubic-bezier(.22,1,.36,1) both;
}

@keyframes fadeUp {
  from { opacity:0; transform:translateY(28px); }
  to   { opacity:1; transform:translateY(0); }
}

.badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 14px;
  background: rgba(99,70,255,.15);
  border: 1px solid rgba(99,70,255,.4);
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  letter-spacing: .08em;
  color: #a78bfa;
  text-transform: uppercase;
  margin-bottom: 28px;
}

.badge::before {
  content: '';
  width: 7px; height: 7px;
  background: #a78bfa;
  border-radius: 50%;
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%,100% { opacity:1; transform:scale(1); }
  50%      { opacity:.4; transform:scale(.7); }
}

.domain {
  font-size: clamp(28px, 6vw, 52px);
  font-weight: 800;
  letter-spacing: -.02em;
  line-height: 1.1;
  margin-bottom: 16px;
  background: linear-gradient(135deg, #fff 30%, #a78bfa 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  word-break: break-all;
}

.desc {
  font-size: 16px;
  color: #8b8fa8;
  line-height: 1.7;
  margin-bottom: 40px;
  max-width: 420px;
  margin-left: auto;
  margin-right: auto;
}

.features {
  display: flex;
  justify-content: center;
  gap: 20px;
  flex-wrap: wrap;
  margin-bottom: 40px;
}

.feature {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 16px 20px;
  background: rgba(255,255,255,.04);
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 12px;
  min-width: 100px;
  animation: fadeUp .7s cubic-bezier(.22,1,.36,1) both;
}

.feature:nth-child(1) { animation-delay:.1s; }
.feature:nth-child(2) { animation-delay:.18s; }
.feature:nth-child(3) { animation-delay:.26s; }

.feature-icon {
  font-size: 22px;
  line-height: 1;
}

.feature-label {
  font-size: 12px;
  color: #8b8fa8;
}

.feature-val {
  font-size: 13px;
  font-weight: 600;
  color: #e8eaf0;
}

.cta {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 14px 36px;
  background: linear-gradient(135deg, #6346ff, #a259ff);
  color: #fff;
  border-radius: 50px;
  font-size: 16px;
  font-weight: 700;
  text-decoration: none;
  letter-spacing: .02em;
  box-shadow: 0 8px 32px rgba(99,70,255,.4);
  transition: transform .2s, box-shadow .2s;
  animation: fadeUp .7s .35s cubic-bezier(.22,1,.36,1) both;
}

.cta:hover {
  transform: translateY(-2px) scale(1.03);
  box-shadow: 0 12px 40px rgba(99,70,255,.55);
  color: #fff;
  opacity: 1;
}

.cta svg {
  width: 18px; height: 18px;
  fill: none;
  stroke: currentColor;
  stroke-width: 2;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.footer-note {
  margin-top: 28px;
  font-size: 12px;
  color: rgba(139,143,168,.5);
  animation: fadeUp .7s .45s cubic-bezier(.22,1,.36,1) both;
}

/* 漂浮光晕 */
.glow {
  position: fixed;
  border-radius: 50%;
  pointer-events: none;
  filter: blur(80px);
  opacity: .25;
  z-index: 0;
}
.glow-1 {
  width: 400px; height: 400px;
  background: #6346ff;
  top: -100px; left: -100px;
  animation: drift 12s ease-in-out infinite alternate;
}
.glow-2 {
  width: 300px; height: 300px;
  background: #22d3a5;
  bottom: -80px; right: -60px;
  animation: drift 15s ease-in-out infinite alternate-reverse;
}

@keyframes drift {
  from { transform: translate(0,0); }
  to   { transform: translate(40px,30px); }
}
</style>
</head>
<body>
<div class="glow glow-1"></div>
<div class="glow glow-2"></div>

<div class="wrap">
  <div class="badge">域名出售</div>

  <div class="domain"><?= $domain_name ?></div>

  <p class="desc"><?= e($page_desc) ?></p>

  <div class="features">
    <div class="feature">
      <span class="feature-icon">&#128279;</span>
      <span class="feature-label">域名类型</span>
      <span class="feature-val"><?= strtoupper(ltrim(strrchr($domain_name, '.'), '.') ?: 'COM') ?></span>
    </div>
    <div class="feature">
      <span class="feature-icon">&#9889;</span>
      <span class="feature-label">转让方式</span>
      <span class="feature-val">即时过户</span>
    </div>
    <div class="feature">
      <span class="feature-icon">&#128274;</span>
      <span class="feature-label">交易保障</span>
      <span class="feature-val">担保交易</span>
    </div>
  </div>

  <?php if (!empty($contact_url)): ?>
  <a href="<?= e($contact_url) ?>" class="cta" target="_blank" rel="noopener noreferrer">
    <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    立即洽谈收购
  </a>
  <?php endif; ?>

  <p class="footer-note">点击按钮将跳转至联系方式页面，请放心访问</p>
</div>
</body>
</html>
