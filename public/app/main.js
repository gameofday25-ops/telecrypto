const tg = window.Telegram?.WebApp;
if (tg) tg.ready();

const initData = tg?.initData || new URLSearchParams(location.search).get('initData') || '';
const headers = {'Content-Type':'application/json','X-Telegram-InitData': initData};
const cart = [];

async function api(path, method='GET', body=null){
  const res = await fetch('/api'+path, {method, headers, body: body ? JSON.stringify(body) : null});
  return await res.json();
}

function switchTab(id){
  document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
  document.getElementById(id).classList.add('active');
  document.querySelectorAll('nav button').forEach(b=>b.classList.remove('active'));
  document.querySelector(`button[data-tab="${id}"]`).classList.add('active');
}

document.querySelectorAll('nav button').forEach(btn=>btn.onclick=()=>switchTab(btn.dataset.tab));

async function loadTokens(search=''){
  const r = await api(`/tokens?search=${encodeURIComponent(search)}&active=1`);
  const wrap = document.getElementById('tokens');
  wrap.innerHTML='';
  if(!r.ok) return wrap.textContent = r.error.message;
  r.data.forEach(t=>{
    const el = document.createElement('div');
    el.className='card';
    el.innerHTML = `<b>${t.name} (${t.symbol})</b><br>chain: ${t.chain}<br>source: ${t.source}<br>risk: ${t.risk_level}<br><button>В избранное</button> <button>Добавить 10 USDT</button>`;
    el.querySelectorAll('button')[0].onclick = ()=>api('/favorites/toggle','POST',{tokenId:t.id});
    el.querySelectorAll('button')[1].onclick = ()=>{cart.push({tokenId:t.id,amountUsdt:'10.00000000'}); renderCart();};
    wrap.appendChild(el);
  });
}

async function loadCollections(){
  const r = await api('/collections');
  const w = document.getElementById('collectionsList');
  w.innerHTML='';
  if(!r.ok) return;
  r.data.forEach(c=>{
    const d=document.createElement('div');d.className='card';
    d.innerHTML=`<b>${c.title}</b><br>${c.description||''}<br>items: ${c.items.map(i=>i.symbol).join(', ')}`;
    w.appendChild(d);
  });
}

async function loadMe(){
  const r = await api('/me');
  document.getElementById('me').textContent = r.ok ? `Пользователь ${r.data.first_name || ''}, баланс available=${r.data.balance.available}, reserved=${r.data.balance.reserved}` : r.error.message;
}

async function loadOrders(){
  const r = await api('/orders/list');
  document.getElementById('orders').textContent = JSON.stringify(r, null, 2);
}

function renderCart(){
  document.getElementById('cart').textContent = JSON.stringify(cart,null,2);
}

document.getElementById('search').addEventListener('input',e=>loadTokens(e.target.value));
document.getElementById('createOrder').onclick = async()=>{
  if(!cart.length) return alert('Корзина пуста');
  const r = await api('/orders/create','POST',{items:cart});
  alert(JSON.stringify(r));
};
document.getElementById('testDeposit').onclick = async()=>{
  const r = await api('/deposits/create','POST',{amountUsdt:'100.00000000',method:'TEST'});
  alert(JSON.stringify(r));
};
document.getElementById('loadOrders').onclick = loadOrders;

loadTokens(); loadCollections(); loadMe();
