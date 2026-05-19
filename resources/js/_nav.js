export default [
  {
    component: 'CNavItem',
    name: 'Dashboard',
    to: '/dashboard',
    icon: 'cil-speedometer',
  },
  {
    component: 'CNavItem',
    name: 'POS Kasir',
    to: '/pos',
    icon: 'cil-cart',
  },
  {
    component: 'CNavTitle',
    name: 'Master Data',
  },
  {
    component: 'CNavItem',
    name: 'Kategori',
    to: '/categories',
    icon: 'cil-tags',
  },
  {
    component: 'CNavItem',
    name: 'Master Product',
    to: '/products',
    icon: 'cil-basket',
  },
  {
    component: 'CNavItem',
    name: 'Report Stock',
    to: '/products/stock-movements',
    icon: 'cil-history',
  },
  {
    component: 'CNavItem',
    name: 'Supplier',
    to: '/suppliers',
    icon: 'cil-truck',
  },
  {
    component: 'CNavItem',
    name: 'Penerimaan Barang',
    to: '/goods-receipts',
    icon: 'cil-cloud-download',
  },
  {
    component: 'CNavItem',
    name: 'Customer',
    to: '/customers',
    icon: 'cil-people',
  },
  {
    component: 'CNavTitle',
    name: 'Pengaturan',
  },
  {
    component: 'CNavItem',
    name: 'Upload Logo',
    to: '/settings/logo',
    icon: 'cil-settings',
  },
]
