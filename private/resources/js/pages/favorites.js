const STORAGE_KEY = 'cipher_favorites'

/** @returns {string[]} */
function getFavorites() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY)
        const parsed = JSON.parse(raw || '[]')
        return Array.isArray(parsed) ? parsed.filter(s => typeof s === 'string') : []
    } catch {
        return []
    }
}

/** @param {string[]} slugs */
function saveFavorites(slugs) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(slugs))
}

/** @param {string} slug @returns {boolean} */
export function isFavorite(slug) {
    return getFavorites().includes(slug)
}

/**
 * @param {string} slug
 * @returns {boolean} true если добавлено, false если удалено
 */
export function toggleFavorite(slug) {
    const favorites = getFavorites()
    const idx = favorites.indexOf(slug)
    if (idx === -1) {
        favorites.push(slug)
        saveFavorites(favorites)
        return true
    }
    favorites.splice(idx, 1)
    saveFavorites(favorites)
    return false
}

/**
 * Обновляет бейдж с количеством избранных в навигации.
 */
export function updateNavFavCount() {
    const count = getFavorites().length
    document.querySelectorAll('a.site-header-link[href$="/favorites"], a.site-nav__link[href$="/favorites"]').forEach(link => {
        let badge = link.querySelector('.nav-fav-count')
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span')
                badge.className = 'nav-fav-count'
                link.appendChild(badge)
            }
            badge.textContent = count > 99 ? '99+' : String(count)
        } else if (badge) {
            badge.remove()
        }
    })
}

/**
 * Инициализирует кнопку «В избранное» на странице шифра.
 */
export function initFavoriteButton() {
    const btn = document.getElementById('ciphers-favorite')
    if (!btn) return

    const slug    = btn.dataset.slug || ''
    const icon    = btn.querySelector('i')
    const toolUi  = (() => {
        try {
            const el = document.querySelector('[data-cipher-ui]')
            return el ? JSON.parse(el.dataset.cipherUi) : {}
        } catch {
            return {}
        }
    })()

    const setActive = (active) => {
        btn.classList.toggle('is-favorite', active)
        icon.className = active ? 'bi bi-star-fill' : 'bi bi-star'
        btn.title = active
            ? (toolUi.favoriteRemoveLabel || 'Remove from favorites')
            : (toolUi.favoriteAddLabel   || 'Add to favorites')
    }

    setActive(isFavorite(slug))
    updateNavFavCount()

    btn.addEventListener('click', () => {
        const added = toggleFavorite(slug)
        setActive(added)
        updateNavFavCount()

        const feedback = document.getElementById('ciphers-feedback')
        if (feedback) {
            feedback.textContent = added
                ? (toolUi.feedbackFavoriteAdded   || '★ Added to favorites')
                : (toolUi.feedbackFavoriteRemoved || '☆ Removed from favorites')
            clearTimeout(btn._feedbackTimer)
            btn._feedbackTimer = setTimeout(() => { feedback.textContent = '' }, 2500)
        }
    })
}

/**
 * Инициализирует страницу избранного: загружает данные через API и рендерит карточки.
 */
export async function initFavoritesPage() {
    const skeleton = document.getElementById('favorites-skeleton')
    const empty    = document.getElementById('favorites-empty')
    const grid     = document.getElementById('favorites-grid')

    if (!grid) return

    updateNavFavCount()

    const slugs  = getFavorites()
    const locale = grid.dataset.locale || ''

    if (slugs.length === 0) {
        if (skeleton) skeleton.style.display = 'none'
        if (empty)    empty.style.display    = ''
        return
    }

    try {
        const data = await window.api.guest.getFavorites(slugs, locale)
        const ciphers = data.ciphers || []

        if (skeleton) skeleton.style.display = 'none'

        if (ciphers.length === 0) {
            if (empty) empty.style.display = ''
            return
        }

        grid.innerHTML = ciphers.map(c => buildCard(c)).join('')
        grid.style.display = ''

        grid.querySelectorAll('.favorites-card__remove').forEach(removeBtn => {
            removeBtn.addEventListener('click', (e) => {
                e.preventDefault()
                const card = removeBtn.closest('.ciphers-category-hub-card')
                const slug = removeBtn.dataset.slug
                toggleFavorite(slug)
                updateNavFavCount()
                if (card) {
                    card.style.transition = 'opacity .2s'
                    card.style.opacity    = '0'
                    setTimeout(() => {
                        card.remove()
                        if (!grid.querySelector('.ciphers-category-hub-card')) {
                            grid.style.display    = 'none'
                            if (empty) empty.style.display = ''
                        }
                    }, 200)
                }
            })
        })
    } catch {
        if (skeleton) skeleton.style.display = 'none'
        if (empty)    empty.style.display    = ''
    }
}

/** @param {{slug:string, name:string, description:string, url:string}} cipher */
function buildCard(cipher) {
    const desc = cipher.description
        ? `<p class="ciphers-category-hub-card__desc">${escHtml(cipher.description)}</p>`
        : ''

    return `<article class="ciphers-category-hub-card favorites-hub-card">
        <div class="favorites-hub-card__header">
            <h2 class="ciphers-category-hub-card__title favorites-hub-card__title">
                <a href="${escHtml(cipher.url)}">${escHtml(cipher.name)}</a>
            </h2>
            <button class="favorites-card__remove" data-slug="${escHtml(cipher.slug)}" type="button" title="Убрать из избранного">
                <i class="bi bi-x-circle-fill"></i>
            </button>
        </div>
        ${desc}
    </article>`
}

/** @param {string} str @returns {string} */
function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
}
