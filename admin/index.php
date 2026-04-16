<?php
/**
 * 后台首页 — 数据统计面板
 * @copyright 2026 wenyinos <ruojiner@hotmail.com>
 * @license MIT License
 */
require_once dirname(__DIR__) . '/config/config.php';
admin_auth();

$db = get_db();

// 清空统计数据处理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clear_stats') {
    csrf_verify();
    role_guard('super');
    try {
        $db->exec("TRUNCATE TABLE `access_logs`");
        write_admin_log('清空了所有访问统计数据');
        header('Location: index.php');
        exit;
    } catch (Throwable $e) {
        error_log('Clear stats error: ' . $e->getMessage());
    }
}

// 时间范围
$range = $_GET['range'] ?? '7';
$range = in_array($range, ['0','7','30']) ? $range : '7';
if ($range === '0') {
    $where_time  = "DATE(l.`created_at`)=CURDATE()";
    $interval    = 0;
    $range_label = '今天';
} elseif ($range === '30') {
    $where_time  = "l.`created_at`>=CURDATE()-INTERVAL 29 DAY";
    $interval    = 29;
    $range_label = '近30天';
} else {
    $where_time  = "l.`created_at`>=CURDATE()-INTERVAL 6 DAY";
    $interval    = 6;
    $range_label = '近7天';
}

// 按角色过滤域名范围
try {
    [$dow,$dop] = domain_owner_where('d');
    [$aow,$aop] = domain_owner_where();
} catch (Throwable $e) {
    $dow = '1=1'; $dop = [];
    $aow = '1=1'; $aop = [];
}

// 转发规则数
try {
    $ds = $db->prepare("SELECT COUNT(*) FROM `domains` WHERE {$aow}"); $ds->execute($aop);
    $total_domains = (int)$ds->fetchColumn();
    $ds2 = $db->prepare("SELECT COUNT(*) FROM `domains` WHERE `status`='active' AND {$aow}"); $ds2->execute($aop);
    $active_domains = (int)$ds2->fetchColumn();
} catch (Throwable $e) {
    $total_domains  = (int)$db->query("SELECT COUNT(*) FROM `domains`")->fetchColumn();
    $active_domains = (int)$db->query("SELECT COUNT(*) FROM `domains` WHERE `status`='active'")->fetchColumn();
}
$paused_domains = $total_domains - $active_domains;

// access_logs 按域名 owner 过滤
$log_join    = "FROM `access_logs` l JOIN `domains` d ON d.`id`=l.`domain_id` WHERE {$dow}";
$log_params  = $dop;
$log_join_t  = "FROM `access_logs` l JOIN `domains` d ON d.`id`=l.`domain_id` WHERE {$dow} AND {$where_time}";
$log_params_t = $dop;

try {
    // 今日独立IP
    $th = $db->prepare("SELECT COUNT(DISTINCT l.`ip`) {$log_join} AND DATE(l.`created_at`)=CURDATE()");
    $th->execute($log_params);
    $today_ip = (int)$th->fetchColumn();
    // 昨日独立IP
    $yh = $db->prepare("SELECT COUNT(DISTINCT l.`ip`) {$log_join} AND DATE(l.`created_at`)=CURDATE()-INTERVAL 1 DAY");
    $yh->execute($log_params);
    $yesterday_ip = (int)$yh->fetchColumn();
    // 今日访问次数(PV)
    $thpv = $db->prepare("SELECT COUNT(*) {$log_join} AND DATE(l.`created_at`)=CURDATE()");
    $thpv->execute($log_params);
    $today_pv = (int)$thpv->fetchColumn();
    // 昨日访问次数
    $yhpv = $db->prepare("SELECT COUNT(*) {$log_join} AND DATE(l.`created_at`)=CURDATE()-INTERVAL 1 DAY");
    $yhpv->execute($log_params);
    $yesterday_pv = (int)$yhpv->fetchColumn();
} catch (Throwable $e) {
    $today_ip = $yesterday_ip = $today_pv = $yesterday_pv = 0;
}

// 趋势
$chart_labels = [];
$chart_map    = [];
try {
    if ($range === '0') {
        for ($h = 0; $h < 24; $h++) {
            $chart_labels[] = sprintf('%02d:00', $h);
            $chart_map[$h]  = 0;
        }
        $rs = $db->prepare("SELECT HOUR(l.`created_at`) AS h, COUNT(*) AS cnt {$log_join} AND DATE(l.`created_at`)=CURDATE() GROUP BY h");
        $rs->execute($log_params);
        foreach ($rs->fetchAll() as $r) $chart_map[(int)$r['h']] = (int)$r['cnt'];
    } else {
        for ($i = $interval; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} day"));
            $chart_labels[] = date('m/d', strtotime($day));
            $chart_map[$day] = 0;
        }
        $rs = $db->prepare("SELECT DATE(l.`created_at`) AS day, COUNT(*) AS cnt {$log_join_t} GROUP BY day");
        $rs->execute($log_params_t);
        foreach ($rs->fetchAll() as $r) $chart_map[$r['day']] = (int)$r['cnt'];
    }
} catch (Throwable $e) { error_log('chart error:'.$e->getMessage()); }
$chart_data = array_values($chart_map);

// 设备分布
$device_labels = ['手机','平板','桌面']; $device_data = [0,0,0];
try {
    $ua_st = $db->prepare(
        "SELECT
          SUM(CASE WHEN l.`user_agent` LIKE '%Mobile%' OR l.`user_agent` LIKE '%Android%' OR l.`user_agent` LIKE '%iPhone%' THEN 1 ELSE 0 END) AS mobile,
          SUM(CASE WHEN l.`user_agent` LIKE '%iPad%' OR l.`user_agent` LIKE '%Tablet%' THEN 1 ELSE 0 END) AS tablet,
          SUM(CASE WHEN l.`user_agent` NOT LIKE '%Mobile%' AND l.`user_agent` NOT LIKE '%Android%' AND l.`user_agent` NOT LIKE '%iPhone%' AND l.`user_agent` NOT LIKE '%iPad%' THEN 1 ELSE 0 END) AS desktop
         {$log_join_t}"
    );
    $ua_st->execute($log_params_t);
    $ua_row = $ua_st->fetch();
    $device_data = [(int)($ua_row['mobile']??0),(int)($ua_row['tablet']??0),(int)($ua_row['desktop']??0)];
} catch (Throwable $e) {}

// 归属地分布
$location_labels = []; $location_data = [];
try {
    // 检查是否有 location 列
    $cols = $db->query("SHOW COLUMNS FROM `access_logs`")->fetchAll(PDO::FETCH_COLUMN);
    $has_location = in_array('location', $cols);
    
    if ($has_location) {
        $loc_stmt = $db->prepare("SELECT l.`location`, COUNT(*) AS cnt {$log_join_t} AND l.`location` IS NOT NULL AND l.`location` != '' GROUP BY l.`location` ORDER BY cnt DESC LIMIT 8");
        $loc_stmt->execute($log_params_t);
        $loc_rows = $loc_stmt->fetchAll();
        $location_labels = array_column($loc_rows, 'location');
        $location_data   = array_map('intval', array_column($loc_rows, 'cnt'));
    }
} catch (Throwable $e) {}

// IP Top10
$ip_top = []; $ip_max = 1;
try {
    $ip_stmt = $db->prepare("SELECT l.`ip`, COUNT(*) AS cnt {$log_join_t} GROUP BY l.`ip` ORDER BY cnt DESC LIMIT 10");
    $ip_stmt->execute($log_params_t);
    $ip_top = $ip_stmt->fetchAll();
    $ip_max = $ip_top ? (int)$ip_top[0]['cnt'] : 1;
} catch (Throwable $e) {}

// 来源域名 Top10
$src_top = []; $src_max = 1;
try {
    $src_stmt = $db->prepare("SELECT d.`domain`, COUNT(*) AS cnt {$log_join_t} GROUP BY d.`domain` ORDER BY cnt DESC LIMIT 10");
    $src_stmt->execute($log_params_t);
    $src_top = $src_stmt->fetchAll();
    $src_max = $src_top ? (int)$src_top[0]['cnt'] : 1;
} catch (Throwable $e) {}

$page_title = '数据统计';
$active_nav = 'dashboard';
require __DIR__ . '/_layout_header.php';
?>

<!-- 顶部三个统计块 -->
<div class="stats-grid stats-grid-3" style="margin-bottom:24px">
  <div class="stat-card accent">
    <span class="stat-label">今日独立IP</span>
    <span class="stat-value"><?=number_format($today_ip)?></span>
    <span class="stat-sub">昨日 <?=number_format($yesterday_ip)?> 个</span>
  </div>
  <div class="stat-card">
    <span class="stat-label">今日访问次数</span>
    <span class="stat-value"><?=number_format($today_pv)?></span>
    <span class="stat-sub">昨日 <?=number_format($yesterday_pv)?> 次</span>
  </div>
  <div class="stat-card">
    <span class="stat-label">转发规则</span>
    <span class="stat-value"><?=$total_domains?></span>
    <span class="stat-sub">活跃 <?=$active_domains?> / 暂停 <?=$paused_domains?></span>
  </div>
</div>

<!-- 时间范围切换 -->
<div style="display:flex;gap:8px;margin-bottom:16px;align-items:center;justify-content:space-between">
  <div style="display:flex;gap:8px;align-items:center">
    <span class="text-muted text-sm">时间范围：</span>
    <a href="?range=0"  class="btn <?=$range==='0' ?'btn-primary':'btn-ghost'?> btn-sm">今天</a>
    <a href="?range=7"  class="btn <?=$range==='7' ?'btn-primary':'btn-ghost'?> btn-sm">近7天</a>
    <a href="?range=30" class="btn <?=$range==='30'?'btn-primary':'btn-ghost'?> btn-sm">近30天</a>
  </div>
  <?php if(is_super()): ?>
  <form method="POST" onsubmit="return confirm('确定要清空所有访问统计数据吗？此操作不可逆！');">
    <input type="hidden" name="csrf_token" value="<?=csrf_token()?>">
    <input type="hidden" name="action" value="clear_stats">
    <button type="submit" class="btn btn-ghost btn-sm" style="color:#f76a6a;border-color:#f76a6a">清空统计数据</button>
  </form>
  <?php endif; ?>
</div>

<!-- 访问趋势 -->
<div class="card" style="margin-bottom:20px">
  <div class="card-header"><span class="card-title">访问趋势（<?=e($range_label)?>）</span></div>
  <canvas id="chartLine" height="75"></canvas>
</div>

<!-- 归属地分布 + 设备分布 -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
  <div class="card">
    <div class="card-header"><span class="card-title">归属地分布</span></div>
    <div style="max-width:260px;margin:0 auto"><canvas id="chartLocation"></canvas></div>
  </div>
  <div class="card">
    <div class="card-header"><span class="card-title">设备分布</span></div>
    <div style="max-width:260px;margin:0 auto"><canvas id="chartDevice"></canvas></div>
  </div>
</div>

<!-- IP Top10 + 来源 Top10 -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
  <div class="card">
    <div class="card-header"><span class="card-title">IP Top10</span></div>
    <?php if(empty($ip_top)): ?><p class="text-muted text-sm" style="padding:16px">暂无数据</p><?php else: ?>
    <div style="padding:4px 0">
    <?php foreach($ip_top as $r): $pct=round($r['cnt']/$ip_max*100); ?>
      <div style="margin-bottom:10px">
        <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:3px">
          <span class="text-muted"><?=e($r['ip'])?></span><span><?=$r['cnt']?></span>
        </div>
        <div style="background:var(--border);border-radius:3px;height:5px"><div style="background:var(--accent);height:5px;border-radius:3px;width:<?=$pct?>%"></div></div>
      </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
  <div class="card">
    <div class="card-header"><span class="card-title">来源 Top10</span></div>
    <?php if(empty($src_top)): ?><p class="text-muted text-sm" style="padding:16px">暂无数据</p><?php else: ?>
    <div style="padding:4px 0">
    <?php foreach($src_top as $r): $pct=round($r['cnt']/$src_max*100); ?>
      <div style="margin-bottom:10px">
        <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:3px">
          <span class="text-muted"><?=e($r['domain'])?></span><span><?=$r['cnt']?></span>
        </div>
        <div style="background:var(--border);border-radius:3px;height:5px"><div style="background:#22d3a5;height:5px;border-radius:3px;width:<?=$pct?>%"></div></div>
      </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
(function(){
  var COLORS=['#7c6af7','#22d3a5','#f7a94a','#f76a6a','#6ab0f7','#c96af7','#6af7a9','#f7e06a'];
  var gridColor='rgba(255,255,255,.06)', tickColor='#8b8fa8';

  // 趋势折线图
  new Chart(document.getElementById('chartLine'),{
    type:'line',
    data:{
      labels:<?=json_encode($chart_labels)?>,
      datasets:[{label:'访问次数',data:<?=json_encode($chart_data)?>,
        borderColor:'#7c6af7',backgroundColor:'rgba(124,106,247,.13)',
        borderWidth:2,pointBackgroundColor:'#7c6af7',pointRadius:3,fill:true,tension:.4}]
    },
    options:{responsive:true,plugins:{legend:{display:false}},
      scales:{x:{grid:{color:gridColor},ticks:{color:tickColor}},
              y:{grid:{color:gridColor},ticks:{color:tickColor,precision:0},beginAtZero:true}}}
  });

  // 归属地饼图
  var dLoc=<?=json_encode($location_data)?>;
  var lLoc=<?=json_encode($location_labels)?>;
  if(dLoc.length===0){dLoc=[1];lLoc=['暂无数据'];}
  new Chart(document.getElementById('chartLocation'),{
    type:'doughnut',
    data:{labels:lLoc,datasets:[{data:dLoc,backgroundColor:COLORS,borderWidth:0}]},
    options:{responsive:true,plugins:{legend:{position:'bottom',labels:{color:tickColor,boxWidth:12,font:{size:11}}}}}
  });

  // 设备饼图
  var dDev=<?=json_encode($device_data)?>;
  if(dDev.reduce(function(a,b){return a+b;},0)===0) dDev=[0,0,1];
  new Chart(document.getElementById('chartDevice'),{
    type:'doughnut',
    data:{labels:<?=json_encode($device_labels)?>,datasets:[{data:dDev,backgroundColor:COLORS,borderWidth:0}]},
    options:{responsive:true,plugins:{legend:{position:'bottom',labels:{color:tickColor,boxWidth:12}}}}
  });
})();
</script>
<?php require __DIR__ . '/_layout_footer.php'; ?>
