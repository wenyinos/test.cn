<?php
/**
 * 单域名数据统计看板
 */
require_once dirname(dirname(__DIR__)) . '/config/config.php';
admin_auth();

$db  = get_db();
$id  = (int)($_GET['id'] ?? 0);

// 权限校验
[$dw,$dp] = domain_owner_where();
$stmt = $db->prepare("SELECT d.*,a.username AS owner_name FROM `domains` d LEFT JOIN `admins` a ON a.id=d.owner_id WHERE d.id=? AND {$dw}");
$stmt->execute(array_merge([$id],$dp));
$domain = $stmt->fetch();
if (!$domain) { http_response_code(404); exit('域名不存在或无权限'); }

// 回填归属地
$backfill_count = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'backfill_location') {
    csrf_verify();
    try {
        $empty_ips = $db->prepare("SELECT DISTINCT `ip` FROM `access_logs` WHERE `domain_id`=? AND (`location` IS NULL OR `location`='') LIMIT 100");
        $empty_ips->execute([$id]);
        foreach ($empty_ips->fetchAll() as $row) {
            $ip = $row['ip'];
            $loc = get_ip_location($ip);
            if ($loc !== '') {
                $up = $db->prepare("UPDATE `access_logs` SET `location`=? WHERE `domain_id`=? AND `ip`=? AND (`location` IS NULL OR `location`='')");
                $up->execute([$loc, $id, $ip]);
                $backfill_count++;
            }
        }
    } catch (Throwable $e) {
        error_log('backfill_location error: ' . $e->getMessage());
    }
    header('Location: ?id=' . $id . '&range=' . $range . '&backfill=' . $backfill_count);
    exit;
}
$backfill_count = (int)($_GET['backfill'] ?? 0);

// 时间范围
$range = $_GET['range'] ?? '7';
$range = in_array($range,['1','7','30']) ? $range : '7';
if ($range==='1') {
    $where_time="DATE(l.created_at)=CURDATE()"; $interval=0; $label='今天';
} elseif ($range==='30') {
    $where_time="l.created_at>=CURDATE()-INTERVAL 29 DAY"; $interval=29; $label='近30天';
} else {
    $where_time="l.created_at>=CURDATE()-INTERVAL 6 DAY"; $interval=6; $label='近7天';
}

// 汇总
try {
    $s1=$db->prepare("SELECT COUNT(*) FROM `access_logs` l WHERE l.domain_id=? AND DATE(l.created_at)=CURDATE()");$s1->execute([$id]);$today_pv=(int)$s1->fetchColumn();
    $s2=$db->prepare("SELECT COUNT(DISTINCT ip) FROM `access_logs` l WHERE l.domain_id=? AND DATE(l.created_at)=CURDATE()");$s2->execute([$id]);$today_ip=(int)$s2->fetchColumn();
    $s3=$db->prepare("SELECT COUNT(*) FROM `access_logs` l WHERE l.domain_id=?");$s3->execute([$id]);$total_pv=(int)$s3->fetchColumn();
    $s4=$db->prepare("SELECT COUNT(*) FROM `access_logs` l WHERE l.domain_id=? AND {$where_time}");$s4->execute([$id]);$range_pv=(int)$s4->fetchColumn();
} catch(Throwable $e){ $today_pv=$today_ip=$total_pv=$range_pv=0; }

// 趋势
$chart_labels=[]; $chart_map=[];
try {
    if ($range==='1') {
        for($h=0;$h<24;$h++){$chart_labels[]=sprintf('%02d:00',$h);$chart_map[$h]=0;}
        $rs=$db->prepare("SELECT HOUR(l.created_at) AS h,COUNT(*) AS cnt FROM `access_logs` l WHERE l.domain_id=? AND DATE(l.created_at)=CURDATE() GROUP BY h");
        $rs->execute([$id]); foreach($rs->fetchAll() as $r) $chart_map[(int)$r['h']]=(int)$r['cnt'];
    } else {
        for($i=$interval;$i>=0;$i--){$day=date('Y-m-d',strtotime("-{$i} day"));$chart_labels[]=date('m/d',strtotime($day));$chart_map[$day]=0;}
        $rs=$db->prepare("SELECT DATE(l.created_at) AS day,COUNT(*) AS cnt FROM `access_logs` l WHERE l.domain_id=? AND {$where_time} GROUP BY day");
        $rs->execute([$id]); foreach($rs->fetchAll() as $r) $chart_map[$r['day']]=(int)$r['cnt'];
    }
} catch(Throwable $e){}
$chart_data=array_values($chart_map);

// 归属地分布
$location_labels = []; $location_data = [];
try {
    $rs=$db->prepare("SELECT l.location,COUNT(*) AS cnt FROM `access_logs` l WHERE l.domain_id=? AND {$where_time} AND l.location IS NOT NULL AND l.location != '' GROUP BY l.location ORDER BY cnt DESC LIMIT 8");
    $rs->execute([$id]); 
    $loc_rows = $rs->fetchAll();
    $location_labels = array_column($loc_rows, 'location');
    $location_data = array_map('intval', array_column($loc_rows, 'cnt'));
} catch(Throwable $e){}

// 设备
$device_data=[0,0,0];
try {
    $rs=$db->prepare("SELECT
      SUM(CASE WHEN l.user_agent LIKE '%Mobile%' OR l.user_agent LIKE '%Android%' OR l.user_agent LIKE '%iPhone%' THEN 1 ELSE 0 END) AS mobile,
      SUM(CASE WHEN l.user_agent LIKE '%iPad%' THEN 1 ELSE 0 END) AS tablet,
      SUM(CASE WHEN l.user_agent NOT LIKE '%Mobile%' AND l.user_agent NOT LIKE '%Android%' AND l.user_agent NOT LIKE '%iPhone%' AND l.user_agent NOT LIKE '%iPad%' THEN 1 ELSE 0 END) AS desktop
      FROM `access_logs` l WHERE l.domain_id=? AND {$where_time}");
    $rs->execute([$id]); $ua=$rs->fetch();
    $device_data=[(int)($ua['mobile']??0),(int)($ua['tablet']??0),(int)($ua['desktop']??0)];
} catch(Throwable $e){}

// 最近记录
$recent=[]; $hit_map=[];
try {
    $rs=$db->prepare("SELECT l.created_at,l.ip,l.location,l.user_agent FROM `access_logs` l WHERE l.domain_id=? ORDER BY l.id DESC LIMIT 20");
    $rs->execute([$id]); $recent=$rs->fetchAll();
    if (!empty($recent)) {
        $ips = array_unique(array_column($recent, 'ip'));
        $ph = implode(',', array_fill(0, count($ips), '?'));
        $hc=$db->prepare("SELECT ip,COUNT(*) AS cnt FROM `access_logs` WHERE domain_id=? AND ip IN({$ph}) GROUP BY ip");
        $hc->execute(array_merge([$id], $ips));
        foreach ($hc->fetchAll() as $hr) $hit_map[$hr['ip']]=(int)$hr['cnt'];
    }
} catch(Throwable $e){}

$page_title = e($domain['domain']).' 统计';
$active_nav = 'domains';
require dirname(__DIR__) . '/_layout_header.php';
?>
<div class="flex items-center gap-3" style="margin-bottom:20px">
  <a href="/admin/domains/index.php" class="btn btn-ghost btn-sm">&#8592; 返回</a>
  <h2 style="font-size:16px;font-weight:600"><?=e($domain['domain'])?> <span class="text-muted text-sm">数据统计</span></h2>
  <span class="badge <?=$domain['status']==='active'?'badge-active':'badge-paused'?>"><?=$domain['status']==='active'?'活跃':'暂停'?></span>
  <span class="text-muted text-sm">添加人：<?=e($domain['owner_name']??'—')?></span>
</div>

<div style="display:flex;gap:8px;margin-bottom:20px;align-items:center">
  <span class="text-muted text-sm">时间范围：</span>
  <a href="?id=<?=$id?>&range=1"  class="btn <?=$range==='1' ?'btn-primary':'btn-ghost'?> btn-sm">今天</a>
  <a href="?id=<?=$id?>&range=7"  class="btn <?=$range==='7' ?'btn-primary':'btn-ghost'?> btn-sm">近7天</a>
  <a href="?id=<?=$id?>&range=30" class="btn <?=$range==='30'?'btn-primary':'btn-ghost'?> btn-sm">近30天</a>
</div>

<div class="stats-grid" style="margin-bottom:20px">
  <div class="stat-card accent"><span class="stat-label">今日PV</span><span class="stat-value"><?=number_format($today_pv)?></span></div>
  <div class="stat-card"><span class="stat-label">今日IP</span><span class="stat-value"><?=number_format($today_ip)?></span></div>
  <div class="stat-card"><span class="stat-label"><?=e($label)?> PV</span><span class="stat-value"><?=number_format($range_pv)?></span></div>
  <div class="stat-card"><span class="stat-label">累计PV</span><span class="stat-value"><?=number_format($total_pv)?></span></div>
</div>

<div class="card" style="margin-bottom:20px">
  <div class="card-header"><span class="card-title">访问趋势（<?=e($label)?>）</span></div>
  <canvas id="chartLine" height="75"></canvas>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
  <div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
      <span class="card-title">归属地分布</span>
      <form method="POST" style="display:inline">
        <input type="hidden" name="csrf_token" value="<?=csrf_token()?>">
        <input type="hidden" name="action" value="backfill_location">
        <button type="submit" class="btn btn-ghost btn-sm">刷新归属地</button>
      </form>
    </div>
    <?php if($backfill_count > 0): ?>
    <div style="padding:4px 16px;font-size:12px;color:#22d3a5">已补全 <?= $backfill_count ?> 个IP的归属地</div>
    <?php endif; ?>
    <div style="max-width:260px;margin:0 auto"><canvas id="chartLocation"></canvas></div>
  </div>
  <div class="card">
    <div class="card-header"><span class="card-title">设备分布</span></div>
    <div style="max-width:220px;margin:0 auto"><canvas id="chartDevice"></canvas></div>
  </div>
</div>

<div class="card">
  <div class="card-header"><span class="card-title">最近访问记录</span></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>时间</th><th>IP</th><th>IP地区</th><th>访问次数</th><th>User Agent</th></tr></thead>
      <tbody>
      <?php if(empty($recent)): ?>
        <tr><td colspan="5" class="text-muted" style="padding:24px;text-align:center">暂无记录</td></tr>
      <?php else: foreach($recent as $r):
          $loc = $r['location'] ?? '';
      ?>
        <tr>
          <td class="text-muted text-sm"><?=e($r['created_at'])?></td>
          <td><?=e($r['ip'])?></td>
          <td class="text-muted text-sm"><?=e($loc)?></td>
          <td><?=number_format($hit_map[$r['ip']] ?? 0)?></td>
          <td class="text-muted text-sm truncate"><?=e($r['user_agent'])?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
(function(){
  var COLORS=['#7c6af7','#22d3a5','#f7a94a','#f76a6a','#6ab0f7','#c96af7','#6af7a9','#f7e06a'];
  var gridColor='rgba(255,255,255,.06)',tickColor='#8b8fa8';
  
  // 趋势折线图
  new Chart(document.getElementById('chartLine'),{
    type:'line',
    data:{labels:<?=json_encode($chart_labels)?>,datasets:[{label:'PV',data:<?=json_encode($chart_data)?>,
      borderColor:'#7c6af7',backgroundColor:'rgba(124,106,247,.13)',borderWidth:2,pointRadius:3,fill:true,tension:.4}]},
    options:{responsive:true,plugins:{legend:{display:false}},
      scales:{x:{grid:{color:gridColor},ticks:{color:tickColor}},y:{grid:{color:gridColor},ticks:{color:tickColor,precision:0},beginAtZero:true}}}
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
  
  // 设备分布
  var dd=<?=json_encode($device_data)?>;
  if(dd.reduce(function(a,b){return a+b;},0)===0) dd=[0,0,1];
  new Chart(document.getElementById('chartDevice'),{
    type:'doughnut',
    data:{labels:['手机','平板','桌面'],datasets:[{data:dd,backgroundColor:COLORS,borderWidth:0}]},
    options:{responsive:true,plugins:{legend:{position:'bottom',labels:{color:tickColor,boxWidth:12}}}}
  });
})();
</script>
<?php require dirname(__DIR__) . '/_layout_footer.php'; ?>
