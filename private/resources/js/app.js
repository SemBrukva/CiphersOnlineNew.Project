import 'bootstrap'
import '../css/app.css'
import { ApiClient } from './api.js'
import { initContactsPage } from './pages/contacts.js'
import { initRegistrationPage } from './pages/registration.js'
import { initLoginForms } from './pages/login.js'
import { initTextDiffPage } from './pages/text-diff.js'
import { initUuidGeneratorPage } from './pages/uuid-generator.js'
import { initPasswordGeneratorPage } from './pages/password-generator.js'
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
// Тяжёлое поддерево инструмента шифра (декодеры, hash-wasm, chart.js) грузим
// отдельным чанком только на странице инструмента — вне её оно не нужно.
if (document.querySelector('[data-page="cipher-tool"][data-cipher-tool]')) {
    import('./pages/cipher-tool.js').then(m => m.initCipherToolPage())
}
initTextDiffPage()
initUuidGeneratorPage()
initPasswordGeneratorPage()
updateNavFavCount()
initFavoriteButton()
initFavoritesPage()
initMobileNavSearch()
initNavDropdownOverflowGuard()

/**
 * Прижимает открытые дропдауны навигации первого уровня к правому краю кнопки,
 * если при выравнивании влево они выходят за границу вьюпорта.
 * Flyout-подменю третьего уровня (.dropdown-submenu) позиционируются через CSS
 * и здесь не участвуют.
 */
function initNavDropdownOverflowGuard() {
    const nav = document.querySelector('.site-header__nav')
    if (!nav) return

    function adjust() {
        const vw = window.innerWidth
        nav.querySelectorAll(':scope > .dropdown').forEach(dropdown => {
            const menu = dropdown.querySelector(':scope > .dropdown-menu')
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
