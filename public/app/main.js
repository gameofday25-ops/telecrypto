const tg = window.Telegram?.WebApp;
if (tg) {
  tg.ready();
  tg.expand();
}

const cart = [];
const initData = tg?.initData || new URLSearchParams(location.search).get('initData') || '';
const API_BASE = '../api';
const headers = { 'Content-Type': 'application/json', 'X-Telegram-InitData': initData };

const $ = (id) => document.getElementById(id);

function toast(message) {
  const el = $('toast');
  el.textContent = message;
  el.classList.add('show');
  setTimeout(() => el.classList.remove('show'), 2800);
}

async function api(path, method = 'GET', body = null) {
  const res = await fetch(`${API_BASE}${path}`, {
    method,
    headers,
    body: body ? JSON.stringify(body) : null,
  });

  const text = await res.text();
  let data;
  try {
    data = JSON.parse(text);
  } catch (_e) {
    throw new Error(`Server returned non-JSON (${res.status}). Проверьте /api роутинг и PHP логи.`);
  }

  if (!data.ok) {
    throw new Error(data.error?.message || 'API error');
  }
  return data.data;
}

function switchTab(id) {
  document.querySelectorAll('.tab').forEach((t) => t.classList.remove('active'));
  document.querySelectorAll('.tabs button').forEach((b) => b.classList.remove('active'));
  document.getElementById(id).classList.add('active');
  document.querySelector(`button[data-tab="${id}"]`).classList.add('active');
}

document.querySelectorAll('.tabs button').forEach((btn) => {
  btn.addEventListener('click', () => switchTab(btn.dataset.tab));
});

function renderCart() {
  const root = $('cart');
  root.innerHTML = '';
  if (!cart.length) {
    root.innerHTML = '<p class="muted">Пока пусто. Добавьте монеты из витрины.</p>';
    return;
  }

  let total = 0;
  cart.forEach((item, idx) => {
    total += Number(item.amountUsdt);
    const row = document.createElement('div');
    row.className = 'row';
    row.innerHTML = `<span>${item.symbol} — ${item.amountUsdt} USDT</span><button class="secondary">Удалить</button>`;
    row.querySelector('button').onclick = () => {
      cart.splice(idx, 1);
      renderCart();
    };
    root.appendChild(row);
  });

  const totalRow = document.createElement('div');
  totalRow.className = 'row';
  totalRow.innerHTML = `<strong>Итого</strong><strong>${total.toFixed(8)} USDT</strong>`;
  root.appendChild(totalRow);
}

async function loadMe() {
  const me = await api('/me');
  $('balanceBadge').textContent = `Avail: ${me.balance.available} USDT`;
  $('meCard').innerHTML = `
    <h3>${me.first_name || ''} ${me.last_name || ''}</h3>
    <div class="token-meta">@${me.username || 'no_username'} · ID: ${me.telegram_id}</div>
    <div class="row"><span>Доступно</span><strong>${me.balance.available} USDT</strong></div>
    <div class="row"><span>В резерве</span><strong>${me.balance.reserved} USDT</strong></div>
  `;
}

async function loadTokens(search = '') {
  const tokens = await api(`/tokens?search=${encodeURIComponent(search)}&active=1`);
  const root = $('tokens');
  root.innerHTML = '';

  if (!tokens.length) {
    root.innerHTML = '<p class="muted">Ничего не найдено.</p>';
    return;
  }

  tokens.forEach((t) => {
    const card = document.createElement('article');
    card.className = 'card';
    card.innerHTML = `
      <div class="card-top">
        <div>
          <div class="token-title">${t.name} (${t.symbol})</div>
          <div class="token-meta">${t.chain} · source: ${t.source} · risk: ${t.risk_level}</div>
        </div>
        <span class="status">${t.is_active == 1 ? 'ACTIVE' : 'OFF'}</span>
      </div>
      <p>${t.description || 'Описание пока не добавлено.'}</p>
      <div class="actions">
        <button class="secondary" data-fav>В избранное</button>
        <button data-add>Добавить в резерв</button>
      </div>
    `;

    card.querySelector('[data-fav]').onclick = async () => {
      await api('/favorites/toggle', 'POST', { tokenId: t.id });
      toast('Избранное обновлено');
    };

    card.querySelector('[data-add]').onclick = () => {
      const amount = prompt(`Сумма USDT для ${t.symbol}`, '25');
      if (!amount) return;
      const value = Number(amount);
      if (!Number.isFinite(value) || value <= 0) {
        toast('Некорректная сумма');
        return;
      }
      cart.push({ tokenId: t.id, symbol: t.symbol, amountUsdt: value.toFixed(8) });
      renderCart();
      toast('Добавлено в резерв');
    };

    root.appendChild(card);
  });
}

async function loadCollections() {
  const collections = await api('/collections');
  const root = $('collectionsList');
  root.innerHTML = '';

  collections.forEach((c) => {
    const el = document.createElement('article');
    el.className = 'card';
    el.innerHTML = `
      <div class="token-title">${c.title}</div>
      <p>${c.description || 'Без описания'}</p>
      <div class="token-meta">Монеты: ${c.items.map((i) => `${i.symbol}`).join(', ') || 'нет'}</div>
    `;
    root.appendChild(el);
  });
}

function renderRows(root, rows, renderer) {
  root.innerHTML = '';
  if (!rows.length) {
    root.innerHTML = '<p class="muted">Список пуст.</p>';
    return;
  }
  rows.forEach((item) => {
    const row = document.createElement('div');
    row.className = 'row';
    row.innerHTML = renderer(item);
    root.appendChild(row);
  });
}

async function loadOrders() {
  const orders = await api('/orders/list');
  renderRows($('orders'), orders, (o) => (`<span>#${o.id} · ${o.total_amount_usdt} USDT <span class="status ${o.status}">${o.status}</span></span><span>${new Date(o.created_at).toLocaleString()}</span>`));
}

async function loadDeposits() {
  const deposits = await api('/deposits/list');
  renderRows($('deposits'), deposits, (d) => (`<span>#${d.id} · ${d.amount_usdt} USDT (${d.method}) <span class="status ${d.status}">${d.status}</span></span><span>${new Date(d.created_at).toLocaleString()}</span>`));
}

$('search').addEventListener('input', (e) => loadTokens(e.target.value));
$('loadOrders').onclick = () => loadOrders().catch((e) => toast(e.message));
$('loadDeposits').onclick = () => loadDeposits().catch((e) => toast(e.message));

$('createDeposit').onclick = async () => {
  try {
    const amount = Number($('depositAmount').value || '0');
    if (!Number.isFinite(amount) || amount <= 0) {
      throw new Error('Введите корректную сумму');
    }
    const method = $('depositMethod').value;
    const result = await api('/deposits/create', 'POST', { amountUsdt: amount.toFixed(8), method });

    if (result.payUrl) {
      if (tg?.openTelegramLink) {
        tg.openTelegramLink(result.payUrl);
      } else {
        window.open(result.payUrl, '_blank');
      }
      toast(`Инвойс создан #${result.invoiceId}`);
    } else {
      toast(`Заявка на пополнение #${result.depositId} создана`);
    }

    $('depositAmount').value = '';
    await loadDeposits();
  } catch (e) {
    toast(e.message);
  }
};

$('testTopup').onclick = async () => {
  try {
    const result = await api('/deposits/create', 'POST', { amountUsdt: '100.00000000', method: 'TEST' });
    toast(`Тестовое пополнение создано #${result.depositId}`);
    await loadDeposits();
  } catch (e) {
    toast(e.message);
  }
};

$('createOrder').onclick = async () => {
  try {
    if (!cart.length) {
      throw new Error('Корзина пуста');
    }
    const result = await api('/orders/create', 'POST', { items: cart.map(({ tokenId, amountUsdt }) => ({ tokenId, amountUsdt })) });
    toast(`Заявка #${result.orderId} создана`);
    cart.length = 0;
    renderCart();
    await Promise.all([loadMe(), loadOrders()]);
  } catch (e) {
    toast(e.message);
  }
};

(async function init() {
  try {
    if (!initData) {
      toast('Откройте приложение из Telegram');
    }
    await Promise.all([loadMe(), loadTokens(), loadCollections(), loadOrders(), loadDeposits()]);
    renderCart();
  } catch (e) {
    toast(e.message);
  }
})();
