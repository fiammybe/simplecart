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

  function currency(n, currencyCode = 'EUR'){ return new Intl.NumberFormat(undefined, { style:'currency', currency: currencyCode }).format(n); }

  function boot(opts){
    const el = document.querySelector(opts.mount);
    if (!el) return;

    const products = ref([]);
    const state = reactive({ token: null, tokenName: 'simplecart', submitting:false, message:'', customer:{ name:'', email:'', phone:'', shift:'', helpendehanden:'' }, lastOrderId: null, showSepaQr: false, sepaInfo: { beneficiary_name: '', beneficiary_iban: '', amount: 0 } });
    const errors = reactive({ shift: '', helpendehanden: '' });
    const cart = useCart();
    const currencyCode = opts.currency || 'EUR';

    const t = (k) => (opts.i18n && opts.i18n[k]) || k;
    const currencyFormatter = (n) => currency(n, currencyCode);
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
        if (j.ok) {
          state.token = j.token;
          state.tokenName = j.token_name || 'simplecart';
          console.log('Token loaded successfully');
        }
      } catch(e) { console.error('Failed to load token:', e); }
    };

    const validateForm = () => {
      errors.shift = '';
      errors.helpendehanden = '';
      let isValid = true;

      if (!state.customer.shift) {
        errors.shift = 'Please select a shift';
        isValid = false;
      }
      if (!state.customer.helpendehanden) {
        errors.helpendehanden = 'Please select an option';
        isValid = false;
      }

      return isValid;
    };

    const placeOrder = async () => {
      state.submitting = true; state.message = '';
      try {
        if (!cart.items.value.length) { state.message = t('empty_cart'); state.submitting=false; return; }
        if (!validateForm()) { state.submitting=false; return; }
        if (!state.token) await loadToken();

        console.log('Placing order with token:', state.token);
        const payload = { token: state.token, items: cart.items.value.map(i => ({ product_id: i.product_id, quantity: i.quantity })), customer: state.customer };
        console.log('Payload:', payload);

        const r = await fetch(opts.ajaxUrl + '?action=place_order', { method:'POST', headers:{ 'Content-Type':'application/json' }, body: JSON.stringify(payload) });
        const j = await r.json();
        console.log('Response:', j);

        if (j.ok) {
          cart.clear();
          state.message = t('order_success') + ' #' + j.order_id;
          state.lastOrderId = j.order_id;
          state.showSepaQr = true;
          // Load SEPA QR code data
          await loadSepaQrCode(j.order_id);
        }
        else { state.message = j.error || 'Error'; }
      } catch(e) { state.message = e.message || 'Error'; }
      state.submitting = false;
    };

    const loadSepaQrCode = async (orderId) => {
      try {
        const r = await fetch(opts.ajaxUrl + '?action=sepa_qr_data&order_id=' + orderId);
        const responseText = await r.text();
        console.log('SEPA QR raw response:', responseText);

        let j;
        try {
          j = JSON.parse(responseText);
        } catch(parseError) {
          console.error('JSON parse error:', parseError.message);
          console.error('Response text:', responseText);
          throw new Error('Invalid JSON response: ' + responseText.substring(0, 100));
        }

        console.log('SEPA QR response:', j);
        if (j.ok && j.qr_data) {
          // Store payment information for display
          state.sepaInfo = {
            beneficiary_name: j.beneficiary_name || '',
            beneficiary_iban: j.beneficiary_iban || '',
            amount: j.amount || 0
          };

          // Clear previous QR code
          const qrContainer = document.getElementById('sepa-qr-code');
          if (qrContainer) {
            qrContainer.innerHTML = '';
            // Generate QR code using qrcode.js library
            new QRCode(qrContainer, {
              text: j.qr_data,
              width: 256,
              height: 256,
              colorDark: '#000000',
              colorLight: '#ffffff',
              correctLevel: QRCode.CorrectLevel.M
            });
            console.log('QR code generated successfully');
          } else {
            console.error('QR code container not found');
          }
        } else {
          console.error('SEPA QR data error:', j.error || 'Unknown error');
        }
      } catch(e) {
        console.error('Failed to load SEPA QR code:', e.message);
      }
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
          currency: currencyFormatter,
          t,
          checkoutUrl,
          // checkout
          customer: state.customer,
          placeOrder,
          submitting: computed(() => state.submitting),
          message: computed(() => state.message),
          errors,
          // SEPA QR Code
          showSepaQr: computed(() => state.showSepaQr),
          lastOrderId: computed(() => state.lastOrderId),
          sepaInfo: computed(() => state.sepaInfo)
        };
      }
    });

    app.mount(el);
  }

  window.SIMPLECART_BOOT = boot;
})();

