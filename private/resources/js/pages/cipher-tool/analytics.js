const ANALYTICS_COOLDOWN_MS = 5 * 60 * 1000

/**
 * Отправляет beacon аналитики использования клиентского инструмента.
 *
 * localStorage используется как первый фильтр — повторные события в пределах
 * cooldown-окна не отправляются. Сервер дополнительно проверяет cooldown через кеш.
 *
 * @param {string} toolSlug
 * @param {string} mode
 */
export function sendAnalyticsBeacon(toolSlug, mode) {
  const key = `analytics:cd:${toolSlug}`
  try {
    const last = parseInt(localStorage.getItem(key) ?? '0', 10)
    if (Date.now() - last < ANALYTICS_COOLDOWN_MS) return
    localStorage.setItem(key, String(Date.now()))
  } catch {
    // localStorage недоступен — отправляем без фильтрации
  }
  const body = JSON.stringify({ tool: toolSlug, mode })
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
