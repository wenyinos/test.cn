<?php
/**
 * 后台公共头部 + 侧边栏
 * @copyright 2026 wenyinos <ruojiner@hotmail.com>
 * @license MIT License
 */
if (!defined('ROOT_PATH')) exit;
?>
<!DOCTYPE html>
<html lang="zh-CN" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover,maximum-scale=5">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="theme-color" content="#0f1117">
<title><?= e($page_title ?? '后台') ?> · <?= e(get_site_name()) ?></title>
<link rel="stylesheet" href="/assets/css/admin.css">
<script src="/assets/js/admin.js" defer></script>
</head>
<body>
<div class="layout">

<!-- 移动端遮罩 -->
<div class="sidebar-mask" id="sidebarMask" onclick="closeSidebar()"></div>

<!-- 侧边栏 -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">J</div>
    <span class="logo-text"><?= e(get_site_name()) ?></span>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-section">主菜单</div>
    <a href="/admin/index.php" class="nav-item <?= ($active_nav??'')==='dashboard'?'active':'' ?>"><span class="icon">&#9783;</span> 数据统计</a>
    <a href="/admin/domains/index.php" class="nav-item <?= ($active_nav??'')==='domains'?'active':'' ?>"><span class="icon">&#8680;</span> 跳转管理</a>
    <a href="/admin/users/index.php" class="nav-item <?= ($active_nav??'')==='users'?'active':'' ?>"><span class="icon">&#9786;</span> 用户管理</a>
    <a href="/admin/settings/index.php" class="nav-item <?= ($active_nav??'')==='settings'?'active':'' ?>"><span class="icon">&#9881;</span> 网站配置</a>
    <a href="/admin/logs/index.php" class="nav-item <?= ($active_nav??'')==='logs'?'active':'' ?>"><span class="icon">&#9776;</span> 操作日志</a>
    <a href="/admin/media/index.php" class="nav-item <?= ($active_nav??'')==='media'?'active':'' ?>"><span class="icon">&#128444;</span> 资源管理</a>
  </nav>
  <div class="sidebar-footer">
  </div>
</aside>

<!-- 主内容 -->
<div class="main">
  <header class="topbar">
    <div style="display:flex;align-items:center;gap:12px">
      <button class="hamburger" id="hamburger" onclick="toggleSidebar()" aria-label="菜单">
        <span></span><span></span><span></span>
      </button>
      <span class="topbar-title"><?= e($page_title ?? '') ?></span>
    </div>
    <div class="topbar-right">
      <!-- 主题切换 -->
      <button class="theme-toggle" id="themeToggle" onclick="toggleTheme()" title="切换主题">
        <span id="themeIcon">&#9788;</span>
      </button>
      <!-- 用户下拉 -->
      <div class="user-menu" id="userMenu">
        <button class="user-btn" onclick="toggleUserMenu()" id="userBtn">
          <span class="user-avatar"><?= strtoupper(substr($_SESSION['admin_username']??'A',0,1)) ?></span>
          <span class="user-name"><?= e($_SESSION['admin_username']??'') ?></span>
          <span style="font-size:10px;opacity:.6">&#9660;</span>
        </button>
        <div class="user-dropdown" id="userDropdown">
          <div class="user-dropdown-info">
            <div class="user-avatar" style="width:36px;height:36px;font-size:16px"><?= strtoupper(substr($_SESSION['admin_username']??'A',0,1)) ?></div>
            <div>
              <div style="font-weight:600;font-size:13px"><?= e($_SESSION['admin_username']??'') ?></div>
              <div style="font-size:11px;opacity:.6"><?= ucfirst($_SESSION['admin_role']??'') ?></div>
            </div>
          </div>
          <hr style="border:none;border-top:1px solid var(--border);margin:6px 0">
          <a href="/admin/users/index.php" class="user-dropdown-item">&#9881; 账号设置</a>
          <a href="/admin/logout.php" class="user-dropdown-item danger">&#8592; 退出登录</a>
        </div>
      </div>
    </div>
  </header>
  <div class="content">
