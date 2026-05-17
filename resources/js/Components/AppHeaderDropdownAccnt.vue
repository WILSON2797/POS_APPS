<script setup>
import { router, usePage } from '@inertiajs/vue3'

const page = usePage()

const logout = () => {
  router.post('/logout')
}
</script>

<template>
  <CDropdown variant="nav-item" placement="bottom-end">
    <CDropdownToggle class="py-0 pe-0" :caret="false">
      <CAvatar color="primary" text-color="white" size="md">
        {{ page.props.auth?.user?.name?.charAt(0)?.toUpperCase() || 'U' }}
      </CAvatar>
    </CDropdownToggle>
    <CDropdownMenu class="pt-0">
      <CDropdownHeader component="h6" class="bg-body-secondary fw-semibold mb-2 rounded-top">
        Account
      </CDropdownHeader>
      <CDropdownItem href="/profile" class="d-flex align-items-center">
        <CIcon icon="cil-user" class="me-2" /> Profile
      </CDropdownItem>
      <CDropdownDivider />
      <CDropdownItem component="button" @click="logout" class="d-flex align-items-center">
        <CIcon icon="cil-account-logout" class="me-2" /> Logout
      </CDropdownItem>
    </CDropdownMenu>
  </CDropdown>
</template>
