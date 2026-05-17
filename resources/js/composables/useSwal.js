/**
 * composables/useSwal.js
 * Global SweetAlert2 helper — sesuai spec: showSuccess, showError, showConfirm, showLoading, closeLoading
 */
import Swal from 'sweetalert2'

const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 3000,
  timerProgressBar: true,
})

export function useSwal() {
  const showSuccess = (message, title = 'Berhasil!') =>
    Toast.fire({ icon: 'success', title: message ?? title })

  const showError = (message, title = 'Gagal!') =>
    Toast.fire({ icon: 'error', title: message ?? title })

  const showWarning = (message) =>
    Toast.fire({ icon: 'warning', title: message })

  const showConfirm = (options = {}) =>
    Swal.fire({
      title: options.title ?? 'Yakin?',
      text: options.text ?? 'Tindakan ini tidak dapat dibatalkan.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#6c757d',
      confirmButtonText: options.confirmText ?? 'Ya, lanjutkan!',
      cancelButtonText: 'Batal',
    })

  const showLoading = (message = 'Memproses...') =>
    Swal.fire({
      title: message,
      allowOutsideClick: false,
      allowEscapeKey: false,
      didOpen: () => Swal.showLoading(),
    })

  const closeLoading = () => Swal.close()

  return { showSuccess, showError, showWarning, showConfirm, showLoading, closeLoading }
}
