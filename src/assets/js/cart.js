(function(){
  const { createApp, ref, reactive, computed, onMounted, toRefs } = window.Vue;

  function useCart(storageKey = 'simplecart_cart') {
    const items = ref([]);
    const load = () => {
      try { items.value = JSON.parse(localStorage.getItem(storageKey) || '[]'); } catch(e) { items.value = []; }
    };
    const save = () => localStorage.setItem(storageKey, JSON.stringify(items.value));
    const clear = () => { items.value = []; save(); };
    const add = (p) => {
      const found = items.value.find(i => i.product_id === p.id);
      if (found) { found.quantity += 1; } else { items.value.push({ product_id: p.id, name: p.name, price: p.price, quantity: 1 }); }
      save();
    };
    const inc = (i) => { i.quantity += 1; save(); };
    const dec = (i) => { i.quantity = Math.max(1, i.quantity - 1); save(); };
    const remove = (i) => { items.value = items.value.filter(x => x !== i); save(); };
    const total = computed(() => items.value.reduce((s, i) => s + i.price * i.quantity, 0));
    load();
    return { items, add, inc, dec, remove, total, clear };
  }

  function currency(n){ return new Intl.NumberFormat(undefined, { style:'currency', currency:'USD'}).format(n); }

  function boot(opts){
    const el = document.querySelector(opts.mount);
    if (!el) return;

    const products = ref([]);
    const state = reactive({ token: null, tokenName: 'simplecart', submitting:false, message:'', customer:{ name:'', email:'', phone:'', address:'' } });
    const cart = useCart();

    const t = (k) => (opts.i18n && opts.i18n[k]) || k;
    const loadProducts = async () => {
      try {
        const r = await fetch(opts.ajaxUrl + '?action=products');
        const j = await r.json();
        products.value = j.products || [];
      } catch(e) { /* ignore */ }
    };

    const loadToken = async () => {
      try {
        const r = await fetch(opts.ajaxUrl + '?action=token');
        const j = await r.json();
        if (j.ok) { state.token = j.token; state.tokenName = j.token_name || 'simplecart'; }
      } catch(e) { /* ignore */ }
    };

    const placeOrder = async () => {
      state.submitting = true; state.message = '';
      try {
        if (!cart.items.value.length) { state.message = t('empty_cart'); state.submitting=false; return; }
        if (!state.token) await loadToken();
        const payload = { token: state.token, items: cart.items.value.map(i => ({ product_id: i.product_id, quantity: i.quantity })), customer: state.customer };
        const r = await fetch(opts.ajaxUrl + '?action=place_order', { method:'POST', headers:{ 'Content-Type':'application/json' }, body: JSON.stringify(payload) });
        const j = await r.json();
        if (j.ok) { cart.clear(); state.message = t('order_success') + ' #' + j.order_id; }
        else { state.message = j.error || 'Error'; }
      } catch(e) { state.message = e.message || 'Error'; }
      state.submitting = false;
    };

    const app = createApp({
      setup(){
        const checkoutUrl = opts.checkoutUrl || '#';
        onMounted(() => { if (el.id === 'simplecart-app') { loadProducts(); } else { loadToken(); } });
        return {
          products,
          cartItems: cart.items,
          addToCart: cart.add,
          inc: cart.inc,
          dec: cart.dec,
          removeItem: cart.remove,
          total: cart.total,
          currency,
          t,
          checkoutUrl,
          // checkout
          customer: state.customer,
          placeOrder,
          submitting: computed(() => state.submitting),
          message: computed(() => state.message)
        };
      }
    });

    app.mount(el);
  }

  window.SIMPLECART_BOOT = boot;
})();

