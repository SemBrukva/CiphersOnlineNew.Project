const ANALYTICS_COOLDOWN_MS = 5 * 60 * 1000

/**
 * Определяет источник события: 'embed' для встраиваемых виджетов (/embed/...),
 * иначе 'local' (основной сайт). Учитывает возможный языковой префикс (/de/embed/...).
 *
 * @returns {'embed'|'local'}
 */
function detectSource() {
  try {
    return /^(?:\/[a-z]{2})?\/embed\//.test(window.location.pathname) ? 'embed' : 'local'
  } catch {
    return 'local'
  }
}

/**
 * Отправляет beacon аналитики использования клиентского инструмента.
 *
 * localStorage используется как первый фильтр — повторные события в пределах
 * cooldown-окна не отправляются. Ключ включает источник, чтобы embed и local
 * не подавляли события друг друга. Сервер дополнительно проверяет cooldown через кеш.
 *
 * @param {string} toolSlug
 * @param {string} mode
 */
export function sendAnalyticsBeacon(toolSlug, mode) {
  const source = detectSource()
  const key = `analytics:cd:${source}:${toolSlug}`
  try {
    const last = parseInt(localStorage.getItem(key) ?? '0', 10)
    if (Date.now() - last < ANALYTICS_COOLDOWN_MS) return
    localStorage.setItem(key, String(Date.now()))
  } catch {
    // localStorage недоступен — отправляем без фильтрации
  }
  const body = JSON.stringify({ tool: toolSlug, mode, source })
  if (typeof navigator.sendBeacon === 'function') {
    navigator.sendBeacon('/api/analytics/use', new Blob([body], { type: 'application/json' }))
  } else {
    fetch('/api/analytics/use', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body,
      keepalive: true,
    }).catch(() => {})
  }
}
