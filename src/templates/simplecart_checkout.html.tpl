<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
<link rel="stylesheet" href="<{$simplecart_module_url}>assets/css/simplecart.css">
<style>[x-cloak] { display: none !important; }</style>

<section class="section" x-data="checkoutForm('<{$simplecart_ajax_url}>', '<{$csrf_token}>', {
  empty_cart: '<{$smarty.const._MD_SIMPLECART_EMPTY_CART|escape:'javascript'}>',
  order_success: '<{$smarty.const._MD_SIMPLECART_ORDER_SUCCESS|escape:'javascript'}>'
})">
  <div class="container">
    <h1 class="title is-4"><{$smarty.const._MD_SIMPLECART_CHECKOUT}></h1>

    <!-- Cart items display -->
    <div x-show="$store.cart.items.length > 0" x-cloak>
      <table class="table is-fullwidth is-striped is-hoverable">
        <thead>
          <tr>
            <th><{$smarty.const._MD_SIMPLECART_NAME}></th>
            <th class="has-text-right"><{$smarty.const._MD_SIMPLECART_TOTAL}></th>
          </tr>
        </thead>
        <tbody>
          <template x-for="item in $store.cart.items" :key="item.product_id">
            <tr>
              <td><span x-text="item.name"></span> × <span x-text="item.quantity"></span></td>
              <td class="has-text-right" x-text="currency(item.price * item.quantity)"></td>
            </tr>
          </template>
        </tbody>
      </table>

      <div class="has-text-right is-size-5 has-text-weight-semibold sc-total">
        <{$smarty.const._MD_SIMPLECART_TOTAL}>: <span x-text="currency($store.cart.total)"></span>
      </div>

      <!-- Checkout form -->
      <form @submit.prevent="placeOrder">
        <div class="field">
          <label class="label"><{$smarty.const._MD_SIMPLECART_NAME}></label>
          <div class="control"><input class="input" x-model="customer.name" required></div>
        </div>
        <div class="field">
          <label class="label"><{$smarty.const._MD_SIMPLECART_EMAIL}></label>
          <div class="control"><input class="input" type="email" x-model="customer.email" required></div>
        </div>
        <div class="field">
          <label class="label"><{$smarty.const._MD_SIMPLECART_PHONE}></label>
          <div class="control"><input class="input" x-model="customer.phone"></div>
        </div>
        <div class="field">
          <label class="label"><{$smarty.const._MD_SIMPLECART_ADDRESS}></label>
          <div class="control"><textarea class="textarea" x-model="customer.address"></textarea></div>
        </div>
        <div class="field">
          <div class="control">
            <button :disabled="submitting" class="button is-primary">
              <span x-show="!submitting"><{$smarty.const._MD_SIMPLECART_PLACE_ORDER}></span>
              <span x-show="submitting">Processing...</span>
            </button>
          </div>
        </div>
      </form>

      <!-- Success/Error message -->
      <div x-show="message" x-cloak class="notification is-info mt-4" x-text="message"></div>

      <!-- SEPA QR Code -->
      <div x-show="qrCode" x-cloak class="box has-text-centered mt-4">
        <h3 class="title is-5"><{$smarty.const._MD_SIMPLECART_PAY_WITH_SEPA}></h3>
        <img :src="qrCode" alt="SEPA QR Code" />
        <p class="help"><{$smarty.const._MD_SIMPLECART_SCAN_TO_PAY}></p>
      </div>
    </div>

    <!-- Empty cart message -->
    <div x-show="$store.cart.items.length === 0" class="notification is-light">
      <{$smarty.const._MD_SIMPLECART_EMPTY_CART}>
    </div>
  </div>
</section>

<!-- Load cart.js first to register Alpine store and checkoutForm, then Alpine.js -->
<script src="<{$simplecart_module_url}>assets/js/cart.js?v=4"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
