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
              h(resolveComponent('CIcon'), {
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
              item.icon
                ? h(resolveComponent('CIcon'), {
                    customClassName: 'nav-icon',
                    name: item.icon,
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
                item.icon
                  ? h(resolveComponent('CIcon'), {
                      customClassName: 'nav-icon',
                      name: item.icon,
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

    return () =>
      h(
        CSidebarNav,
        {
          as: simplebar,
        },
        {
          default: () => nav.map((item) => renderItem(item)),
        },
      )
  },
})

export { AppSidebarNav }
