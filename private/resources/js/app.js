import 'bootstrap'
import '../css/app.css'
import { ApiClient } from './api.js'
import { initContactsPage } from './pages/contacts.js'
import { initRegistrationPage } from './pages/registration.js'
import { initLoginForms } from './pages/login.js'
import { initCipherToolPage } from './pages/cipher-tool.js'
import { initFavoriteButton, initFavoritesPage, updateNavFavCount } from './pages/favorites.js'
import { initMobileNavSearch } from './pages/mobile-nav-search.js'
import { initCookieConsent } from './pages/cookie-consent.js'
import { initTrackingConsent } from './pages/tracking-consent.js'

// Глобальный экземпляр API-клиента доступен как window.api в шаблонах
window.api = new ApiClient()
initCookieConsent()
initTrackingConsent()
initRegistrationPage()
initContactsPage()
initLoginForms()
initCipherToolPage()
updateNavFavCount()
initFavoriteButton()
initFavoritesPage()
initMobileNavSearch()
initNavDropdownOverflowGuard()

/**
 * Прижимает дропдауны навигации к правому краю кнопки,
 * если при выравнивании влево они выходят за границу вьюпорта.
 */
function initNavDropdownOverflowGuard() {
    const nav = document.querySelector('.site-header nav')
    if (!nav) return

    function adjust() {
        const vw = window.innerWidth
        nav.querySelectorAll('.dropdown').forEach(dropdown => {
            const menu = dropdown.querySelector('.dropdown-menu')
            if (!menu) return
            menu.classList.remove('dropdown-menu-end')
            const rect = dropdown.getBoundingClientRect()
            const minW = parseFloat(getComputedStyle(menu).minWidth) || 210
            if (rect.left + minW > vw - 8) {
                menu.classList.add('dropdown-menu-end')
            }
        })
    }

    adjust()
    window.addEventListener('resize', adjust, { passive: true })
}
