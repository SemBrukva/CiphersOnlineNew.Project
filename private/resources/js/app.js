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
