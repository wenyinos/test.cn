// JumpHost 后台 JS

// ── 主题切换 ──────────────────────────────────────────────
function applyTheme(theme) {
  document.documentElement.setAttribute('data-theme', theme);
  localStorage.setItem('jh_theme', theme);
  var icon = document.getElementById('themeIcon');
  if (icon) icon.textContent = theme === 'light' ? '\u263D' : '\u2600';
}

function toggleTheme() {
  var current = document.documentElement.getAttribute('data-theme') || 'dark';
  applyTheme(current === 'dark' ? 'light' : 'dark');
}

// ── 用户下拉菜单 ──────────────────────────────────────────
function toggleUserMenu() {
  var dd = document.getElementById('userDropdown');
  if (dd) dd.classList.toggle('open');
}

document.addEventListener('click', function(e) {
  var menu = document.getElementById('userMenu');
  if (menu && !menu.contains(e.target)) {
    var dd = document.getElementById('userDropdown');
    if (dd) dd.classList.remove('open');
  }
});

// ── 侧边栏（移动端）────────────────────────────────────────
function toggleSidebar() {
  var sb = document.getElementById('sidebar');
  var mask = document.getElementById('sidebarMask');
  if (sb) sb.classList.toggle('open');
  if (mask) mask.classList.toggle('open');
  // 防止滚动
  document.body.style.overflow = sb.classList.contains('open') ? 'hidden' : '';
}

function closeSidebar() {
  var sb = document.getElementById('sidebar');
  var mask = document.getElementById('sidebarMask');
  if (sb) sb.classList.remove('open');
  if (mask) mask.classList.remove('open');
  document.body.style.overflow = '';
}

// ── 响应式处理 ────────────────────────────────────────────
function handleResize() {
  if (window.innerWidth >= 768) {
    closeSidebar();
  }
}

window.addEventListener('resize', handleResize);

// ── 触摸支持 ──────────────────────────────────────────────
var touchStartX = 0;
var touchEndX = 0;

document.addEventListener('touchstart', function(e) {
  touchStartX = e.changedTouches[0].screenX;
}, false);

document.addEventListener('touchend', function(e) {
  touchEndX = e.changedTouches[0].screenX;
  handleSwipe();
}, false);

function handleSwipe() {
  var diff = touchStartX - touchEndX;
  var sb = document.getElementById('sidebar');
  
  // 从左向右滑动打开侧边栏
  if (diff < -50 && window.innerWidth < 768 && !sb.classList.contains('open')) {
    toggleSidebar();
  }
  // 从右向左滑动关闭侧边栏
  if (diff > 50 && window.innerWidth < 768 && sb.classList.contains('open')) {
    closeSidebar();
  }
}

// ── 初始化 ────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  // 恢复主题
  var saved = localStorage.getItem('jh_theme') || 'dark';
  applyTheme(saved);

  // 移动端：点导航项自动收起侧边栏
  document.querySelectorAll('.nav-item').forEach(function(el) {
    el.addEventListener('click', function() {
      if (window.innerWidth < 768) closeSidebar();
    });
  });
  
  // 防止 iOS 缩放
  document.addEventListener('gesturestart', function(e) {
    e.preventDefault();
  });
  
  // 处理 iOS 安全区域
  if (navigator.userAgent.match(/iPhone|iPad|iPod/)) {
    var viewport = document.querySelector('meta[name="viewport"]');
    if (viewport) {
      viewport.setAttribute('content', 'width=device-width,initial-scale=1,viewport-fit=cover,maximum-scale=5');
    }
  }
});

// ── 工具函数 ──────────────────────────────────────────────
// 检查是否为移动设备
function isMobile() {
  return window.innerWidth < 768;
}

// 检查是否为超小屏
function isSmallMobile() {
  return window.innerWidth < 480;
}

// 显示移动端提示
function showMobileToast(msg, duration) {
  duration = duration || 2000;
  var toast = document.createElement('div');
  toast.style.cssText = 'position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,.8);color:#fff;padding:10px 16px;border-radius:8px;font-size:13px;z-index:9999;max-width:80%;text-align:center;word-break:break-word';
  toast.textContent = msg;
  document.body.appendChild(toast);
  setTimeout(function() {
    toast.remove();
  }, duration);
}
