<?php

declare(strict_types=1);

require_once __DIR__ . '/../../app/src/bootstrap.php';

use App\Src\Config;

$webAppUrl = Config::get('WEBAPP_URL', '');
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Admin Panel</title>
  <style>body{font-family:Arial;max-width:900px;margin:20px auto}textarea,input{width:100%;margin:5px 0}button{margin-top:8px}</style>
  <script src="https://telegram.org/js/telegram-web-app.js"></script>
</head>
<body>
  <h1>Админка</h1>
  <p>Авторизация через Telegram WebApp initData.</p>
  <button onclick="adminLogin()">Login as admin</button>

  <h2>Token create/update</h2>
  <textarea id="tokenJson" rows="8">{"name":"Demo","symbol":"DMO","chain":"ETH","expected_listing_at":"2026-01-01 00:00:00","risk_level":"MEDIUM","description":"demo","source":"manual","is_active":1}</textarea>
  <button onclick="post('/api/admin/token/create', tokenJson.value)">Save token</button>

  <h2>Collection create/update</h2>
  <textarea id="collectionJson" rows="6">{"title":"My Collection","description":"demo","is_active":1,"items":[1,2]}</textarea>
  <button onclick="post('/api/admin/collection/create', collectionJson.value)">Save collection</button>

  <h2>Order status update</h2>
  <textarea id="orderJson" rows="3">{"orderId":1,"status":"CONFIRMED"}</textarea>
  <button onclick="post('/api/admin/order/status', orderJson.value)">Update order</button>

  <h2>Approve deposit</h2>
  <textarea id="depJson" rows="3">{"depositId":1}</textarea>
  <button onclick="post('/api/admin/deposit/approve', depJson.value)">Approve</button>

  <pre id="out"></pre>

<script>
const tg = window.Telegram?.WebApp;
if (tg) tg.ready();

async function adminLogin(){
  const res = await fetch('/api/admin/token/create', {
    method:'POST',
    headers:{'Content-Type':'application/json','X-Telegram-InitData':tg?.initData || ''},
    body: JSON.stringify({name:'tmp',symbol:'TMP',chain:'ETH',risk_level:'LOW',description:'tmp',source:'manual',is_active:0})
  });
  out.textContent = await res.text();
}

async function post(url, raw){
  const res = await fetch(url, {
    method:'POST',
    headers:{'Content-Type':'application/json','X-Telegram-InitData':tg?.initData || ''},
    body: raw
  });
  out.textContent = await res.text();
}
</script>
</body>
</html>
