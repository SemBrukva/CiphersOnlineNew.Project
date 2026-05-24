import 'bootstrap'
import '../css/admin.css'
import { ApiClient } from './api.js'
import { initAdminCipherCategoryEdit } from './pages/admin-cipher-category-edit.js'
import { initAdminCipherEdit } from './pages/admin-cipher-edit.js'

window.api = new ApiClient()

// Сворачивание/разворачивание боковой панели
const SIDEBAR_KEY = 'admin_sidebar_collapsed'
const sidebar     = document.getElementById('admin-sidebar')
const toggle      = document.getElementById('sidebar-toggle')

if (sidebar && toggle) {
    if (localStorage.getItem(SIDEBAR_KEY) === '1') {
        sidebar.classList.add('collapsed')
    }

    toggle.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed')
        localStorage.setItem(SIDEBAR_KEY, sidebar.classList.contains('collapsed') ? '1' : '0')
    })
}

initAdminCipherCategoryEdit()
initAdminCipherEdit()
