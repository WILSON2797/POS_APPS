import { defineComponent, h, onMounted, ref, resolveComponent } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

import { CBadge, CSidebarNav, CNavItem, CNavGroup, CNavTitle } from '@coreui/vue'
import nav from '@/_nav.js'

import simplebar from 'simplebar-vue'
import 'simplebar-vue/dist/simplebar.min.css'

const normalizePath = (path) =>
  decodeURI(path)
    .replace(/#.*$/, '')
    .replace(/(index)?\.(html)$/, '')

const isActiveLink = (currentUrl, link) => {
  if (link === undefined) {
    return false
  }
  const currentPath = normalizePath(currentUrl)
  const targetPath = normalizePath(link)
  return currentPath === targetPath
}

const isActiveItem = (currentUrl, item) => {
  if (isActiveLink(currentUrl, item.to)) {
    return true
  }
  if (item.items) {
    return item.items.some((child) => isActiveItem(currentUrl, child))
  }
  return false
}

const featherIcons = {
  'cil-speedometer': `
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather-nav-icon">
      <rect x="3" y="3" width="7" height="9"></rect>
      <rect x="14" y="3" width="7" height="5"></rect>
      <rect x="14" y="12" width="7" height="9"></rect>
      <rect x="3" y="16" width="7" height="5"></rect>
    </svg>
  `,
  'cil-cart': `
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather-nav-icon">
      <circle cx="9" cy="21" r="1"></circle>
      <circle cx="20" cy="21" r="1"></circle>
      <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
    </svg>
  `,
  'cil-tags': `
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather-nav-icon">
      <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
      <line x1="7" y1="7" x2="7.01" y2="7"></line>
    </svg>
  `,
  'cil-basket': `
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather-nav-icon">
      <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line>
      <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
      <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
      <line x1="12" y1="22.08" x2="12" y2="12"></line>
    </svg>
  `,
  'cil-truck': `
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather-nav-icon">
      <rect x="1" y="3" width="15" height="13"></rect>
      <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
      <circle cx="5.5" cy="18.5" r="2.5"></circle>
      <circle cx="18.5" cy="18.5" r="2.5"></circle>
    </svg>
  `,
  'cil-people': `
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather-nav-icon">
      <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
      <circle cx="9" cy="7" r="4"></circle>
      <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
      <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
    </svg>
  `,
  'cil-settings': `
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather-nav-icon">
      <circle cx="12" cy="12" r="3"></circle>
      <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
    </svg>
  `,
  'cil-history': `
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather-nav-icon">
      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
      <polyline points="14 2 14 8 20 8"></polyline>
      <line x1="16" y1="13" x2="8" y2="13"></line>
      <line x1="16" y1="17" x2="8" y2="17"></line>
      <polyline points="10 9 9 9 8 9"></polyline>
    </svg>
  `,
  'cil-cloud-download': `
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather-nav-icon">
      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
      <polyline points="7 10 12 15 17 10"></polyline>
      <line x1="12" y1="15" x2="12" y2="3"></line>
    </svg>
  `,
  'cil-dollar': `
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather-nav-icon">
      <line x1="12" y1="1" x2="12" y2="23"></line>
      <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
    </svg>
  `
}

const AppSidebarNav = defineComponent({
  name: 'AppSidebarNav',
  components: {
    CNavItem,
    CNavGroup,
    CNavTitle,
  },
  setup() {
    const page = usePage()
    const firstRender = ref(true)

    onMounted(() => {
      firstRender.value = false
    })

    const renderItem = (item) => {
      const currentUrl = page.url

      if (item.items) {
        return h(
          CNavGroup,
          {
            as: 'div',
            compact: true,
            ...(firstRender.value && {
              visible: item.items.some((child) => isActiveItem(currentUrl, child)),
            }),
          },
          {
            togglerContent: () => [
              item.icon && featherIcons[item.icon]
                ? h('span', {
                    class: 'nav-icon d-inline-flex align-items-center justify-content-center',
                    innerHTML: featherIcons[item.icon]
                  })
                : h(resolveComponent('CIcon'), {
                    customClassName: 'nav-icon',
                    name: item.icon,
                  }),
              item.name,
            ],
            default: () => item.items.map((child) => renderItem(child)),
          },
        )
      }

      if (item.href) {
        return h(
          resolveComponent(item.component),
          {
            href: item.href,
            target: '_blank',
            rel: 'noopener noreferrer',
          },
          {
            default: () => [
              item.icon && featherIcons[item.icon]
                ? h('span', {
                    class: 'nav-icon d-inline-flex align-items-center justify-content-center',
                    innerHTML: featherIcons[item.icon]
                  })
                : h('span', { class: 'nav-icon' }, h('span', { class: 'nav-icon-bullet' })),
              item.name,
              item.badge &&
                h(
                  CBadge,
                  {
                    class: 'ms-auto',
                    color: item.badge.color,
                    size: 'sm',
                  },
                  {
                    default: () => item.badge.text,
                  },
                ),
            ],
          },
        )
      }

      return item.to
        ? h(
            resolveComponent(item.component),
            {
              active: isActiveLink(currentUrl, item.to),
              as: 'a',
              href: item.to,
              onClick: (e) => {
                e.preventDefault()
                Link.visit ? Link.visit(item.to) : window.location.href = item.to
              },
            },
            {
              default: () => [
                item.icon && featherIcons[item.icon]
                  ? h('span', {
                      class: 'nav-icon d-inline-flex align-items-center justify-content-center',
                      innerHTML: featherIcons[item.icon]
                    })
                  : h('span', { class: 'nav-icon' }, h('span', { class: 'nav-icon-bullet' })),
                item.name,
                item.badge &&
                  h(
                    CBadge,
                    {
                      class: 'ms-auto',
                      color: item.badge.color,
                      size: 'sm',
                    },
                    {
                      default: () => item.badge.text,
                    },
                  ),
              ],
            },
          )
        : h(
            resolveComponent(item.component),
            {
              as: 'div',
            },
            {
              default: () => item.name,
            },
          )
    }

    return () => {
      const userRole = page.props.auth?.user?.role
      const filterNav = (items) => {
        return items
          .filter((item) => {
            if (item.roles) {
              return item.roles.includes(userRole)
            }
            return true
          })
          .map((item) => {
            if (item.items) {
              return {
                ...item,
                items: filterNav(item.items),
              }
            }
            return item
          })
      }

      const filteredNav = filterNav(nav)

      return h(
        CSidebarNav,
        {
          as: simplebar,
        },
        {
          default: () => filteredNav.map((item) => renderItem(item)),
        },
      )
    }
  },
})

export { AppSidebarNav }
