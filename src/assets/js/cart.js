/**
 * SimpleCart - Alpine.js Implementation
 * Cart state management with localStorage persistence
 */

// Currency formatter - global function for use in templates
function currency(n) {
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(n);
}

// Initialize Alpine.js store when Alpine loads
document.addEventListener('alpine:init', () => {
  // Cart store with localStorage persistence
  Alpine.store('cart', {
    items: [],

    init() {
      // Load cart from localStorage on initialization
      try {
        this.items = JSON.parse(localStorage.getItem('simplecart_cart') || '[]');
      } catch (e) {
        this.items = [];
      }
    },

    save() {
      localStorage.setItem('simplecart_cart', JSON.stringify(this.items));
    },

    add(product) {
      const found = this.items.find(i => i.product_id === product.id);
      if (found) {
        found.quantity++;
      } else {
        this.items.push({
          product_id: product.id,
          name: product.name,
          price: product.price,
          quantity: 1
        });
      }
      this.save();
    },

    inc(item) {
      item.quantity++;
      this.save();
    },

    dec(item) {
      item.quantity = Math.max(1, item.quantity - 1);
      this.save();
    },

    remove(item) {
      this.items = this.items.filter(i => i.product_id !== item.product_id);
      this.save();
    },

    clear() {
      this.items = [];
      this.save();
    },

    get total() {
      return this.items.reduce((sum, i) => sum + i.price * i.quantity, 0);
    },

    get count() {
      return this.items.reduce((sum, i) => sum + i.quantity, 0);
    }
  });
});

/**
 * Checkout form component data
 * Usage: x-data="checkoutForm('ajax-url', 'csrf-token', {...i18n})"
 */
function checkoutForm(ajaxUrl, token, i18n) {
  return {
    token: token,
    submitting: false,
    message: '',
    qrCode: null,
    customer: {
      name: '',
      email: '',
      phone: '',
      address: ''
    },

    t(key) {
      return (i18n && i18n[key]) || key;
    },

    async placeOrder() {
      this.submitting = true;
      this.message = '';
      this.qrCode = null;

      try {
        const cart = Alpine.store('cart');

        if (!cart.items.length) {
          this.message = this.t('empty_cart');
          this.submitting = false;
          return;
        }

        const payload = {
          token: this.token,
          items: cart.items.map(i => ({
            product_id: i.product_id,
            quantity: i.quantity
          })),
          customer: this.customer
        };

        const response = await fetch(ajaxUrl + '?action=place_order', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });

        const text = await response.text();
        let result;

        try {
          result = JSON.parse(text);
        } catch (parseErr) {
          console.error('JSON parse error. Response text:', text);
          throw new Error('Invalid response from server');
        }

        if (result.ok) {
          cart.clear();
          this.message = this.t('order_success') + ' #' + result.order_id;
          if (result.qr_code) {
            this.qrCode = result.qr_code;
          }
        } else {
          this.message = result.error || 'Error';
        }
      } catch (e) {
        this.message = e.message || 'Error';
      }

      this.submitting = false;
    }
  };
}
