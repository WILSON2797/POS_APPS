import { defineStore } from 'pinia'

export const useCartStore = defineStore('cart', {
  state: () => ({
    items: [], // { product, qty, discount: 0, note: '' }
    customer: null, // selected customer object
    discountType: 'flat', // 'flat' or 'percent'
    discountValue: 0, // transaction discount
    taxRate: 11, // default 11% tax rate
    paymentMethod: 'cash',
    amountPaid: 0,
    transactionNote: '',
    heldTransactions: JSON.parse(localStorage.getItem('held_transactions') || '[]'),
  }),

  getters: {
    itemSubtotal: (state) => {
      return state.items.reduce((sum, item) => {
        const itemPrice = Number(item.product.selling_price)
        const itemQty = Number(item.qty)
        const discount = Number(item.discount || 0)
        return sum + (itemPrice * itemQty - discount)
      }, 0)
    },

    transactionDiscountAmount(state) {
      const subtotal = this.itemSubtotal
      if (state.discountType === 'percent') {
        return (subtotal * Number(state.discountValue || 0)) / 100
      }
      return Number(state.discountValue || 0)
    },

    taxAmount() {
      const subtotalAfterDiscount = Math.max(0, this.itemSubtotal - this.transactionDiscountAmount)
      return (subtotalAfterDiscount * Number(this.taxRate || 0)) / 100
    },

    grandTotal() {
      const subtotalAfterDiscount = Math.max(0, this.itemSubtotal - this.transactionDiscountAmount)
      return Math.round(subtotalAfterDiscount + this.taxAmount)
    },

    changeAmount(state) {
      if (!state.amountPaid || state.amountPaid < this.grandTotal) return 0
      return state.amountPaid - this.grandTotal
    },
    
    totalQty: (state) => state.items.reduce((sum, item) => sum + Number(item.qty), 0)
  },

  actions: {
    addItem(product) {
      const existing = this.items.find(item => item.product.id === product.id)
      if (existing) {
        if (existing.qty < product.stock) {
          existing.qty++
        }
      } else {
        if (product.stock > 0) {
          this.items.push({
            product,
            qty: 1,
            discount: 0,
            note: ''
          })
        }
      }
    },

    removeItem(productId) {
      this.items = this.items.filter(item => item.product.id !== productId)
    },

    updateQty(productId, qty) {
      const item = this.items.find(item => item.product.id === productId)
      if (item) {
        const parsedQty = Math.max(1, parseInt(qty) || 1)
        if (parsedQty <= item.product.stock) {
          item.qty = parsedQty
        } else {
          item.qty = item.product.stock
        }
      }
    },

    updateItemDiscount(productId, discount) {
      const item = this.items.find(item => item.product.id === productId)
      if (item) {
        const maxDiscount = Number(item.product.selling_price) * item.qty
        item.discount = Math.min(maxDiscount, Math.max(0, Number(discount) || 0))
      }
    },

    updateItemNote(productId, note) {
      const item = this.items.find(item => item.product.id === productId)
      if (item) {
        item.note = note
      }
    },

    setCustomer(customer) {
      this.customer = customer
    },

    setDiscount(type, value) {
      this.discountType = type
      this.discountValue = Math.max(0, Number(value) || 0)
    },

    setTaxRate(rate) {
      this.taxRate = Math.max(0, Number(rate) || 0)
    },

    setPaymentMethod(method) {
      this.paymentMethod = method
      if (method !== 'cash') {
        this.amountPaid = this.grandTotal
      }
    },

    setAmountPaid(amount) {
      this.amountPaid = Math.max(0, Number(amount) || 0)
    },

    clearCart() {
      this.items = []
      this.customer = null
      this.discountValue = 0
      this.amountPaid = 0
      this.transactionNote = ''
      this.paymentMethod = 'cash'
    },

    // suspended/hold transactions feature
    holdCurrentTransaction() {
      if (this.items.length === 0) return false
      
      const newHold = {
        id: Date.now(),
        time: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
        items: [...this.items],
        customer: this.customer,
        discountType: this.discountType,
        discountValue: this.discountValue,
        taxRate: this.taxRate,
        transactionNote: this.transactionNote
      }

      this.heldTransactions.push(newHold)
      localStorage.setItem('held_transactions', JSON.stringify(this.heldTransactions))
      this.clearCart()
      return true
    },

    resumeTransaction(heldId) {
      const held = this.heldTransactions.find(h => h.id === heldId)
      if (held) {
        this.items = held.items
        this.customer = held.customer
        this.discountType = held.discountType
        this.discountValue = held.discountValue
        this.taxRate = held.taxRate
        this.transactionNote = held.transactionNote
        
        this.heldTransactions = this.heldTransactions.filter(h => h.id !== heldId)
        localStorage.setItem('held_transactions', JSON.stringify(this.heldTransactions))
        return true
      }
      return false
    },

    deleteHeldTransaction(heldId) {
      this.heldTransactions = this.heldTransactions.filter(h => h.id !== heldId)
      localStorage.setItem('held_transactions', JSON.stringify(this.heldTransactions))
    }
  }
})
