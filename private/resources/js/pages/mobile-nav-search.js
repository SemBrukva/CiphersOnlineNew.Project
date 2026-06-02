import { Collapse } from 'bootstrap'

/**
 * Клиентская фильтрация элементов мобильного меню по поисковому запросу.
 */
export function initMobileNavSearch() {
    const input = document.getElementById('siteNavSearch')
    if (!input) return

    const nav = document.getElementById('siteNav')

    // Запоминаем исходное состояние collapse при каждом открытии меню
    nav.addEventListener('show.bs.offcanvas', () => {
        nav.querySelectorAll('.site-nav__sub').forEach(el => {
            el.dataset.initShown = el.classList.contains('show') ? '1' : '0'
        })
    })

    input.addEventListener('input', () => {
        filterNav(nav, input.value.trim().toLowerCase())
    })

    // Сброс при закрытии
    nav.addEventListener('hidden.bs.offcanvas', () => {
        input.value = ''
        filterNav(nav, '')
    })
}

/**
 * @param {HTMLElement} nav
 * @param {string} query
 */
function filterNav(nav, query) {
    const groups = nav.querySelectorAll('.site-nav__item--group')
    const simpleItems = nav.querySelectorAll('.site-nav__item:not(.site-nav__item--group)')
    const noResults = nav.querySelector('.site-nav__no-results')
    let anyVisible = false

    simpleItems.forEach(item => {
        const match = !query || item.textContent.toLowerCase().includes(query)
        item.hidden = !match
        if (match) anyVisible = true
    })

    groups.forEach(group => {
        const collapseEl = group.querySelector('.site-nav__sub')
        const subLinks = group.querySelectorAll('.site-nav__sublink:not(.site-nav__sublink--category)')
        const labelEl = group.querySelector('.site-nav__group-label')
        const categoryLabel = labelEl ? labelEl.textContent.toLowerCase() : ''

        if (!query) {
            group.hidden = false
            subLinks.forEach(link => { link.closest('li').hidden = false })
            anyVisible = true
            if (collapseEl) {
                const wasShown = collapseEl.dataset.initShown === '1'
                const isShown = collapseEl.classList.contains('show')
                if (wasShown && !isShown) {
                    Collapse.getOrCreateInstance(collapseEl, { toggle: false }).show()
                } else if (!wasShown && isShown) {
                    Collapse.getOrCreateInstance(collapseEl, { toggle: false }).hide()
                }
            }
            return
        }

        const groupMatch = categoryLabel.includes(query)
        let anyChildMatch = false

        subLinks.forEach(link => {
            const match = link.textContent.trim().toLowerCase().includes(query)
            link.closest('li').hidden = !match
            if (match) anyChildMatch = true
        })

        const shouldShow = groupMatch || anyChildMatch
        group.hidden = !shouldShow

        if (!shouldShow) return

        anyVisible = true

        // Если совпала сама категория — показываем все её шифры
        if (groupMatch) {
            subLinks.forEach(link => { link.closest('li').hidden = false })
        }

        // Раскрываем collapse
        if (collapseEl && !collapseEl.classList.contains('show')) {
            Collapse.getOrCreateInstance(collapseEl, { toggle: false }).show()
        }
    })

    if (noResults) noResults.hidden = anyVisible
}
