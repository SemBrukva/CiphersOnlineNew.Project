const CONSENT_EVENT = 'ciphersonline:cookie-consent'
const APPLIED_EVENT = 'ciphersonline:tracking-consent-applied'
const READY_EVENT = 'ciphersonline:tracking-ready'

let googleConsentInitialized = false
let googleTagLoaded = false
let googleAnalyticsConfigured = false
let adsenseLoaded = false
let yandexMetricaLoaded = false
let yandexMetricaInitialized = false
let yandexRsyaLoaded = false

function readConfig() {
  const root = document.getElementById('trackingConfig')
  if (!root) {
    return null
  }

  return {
    gaMeasurementId: normalizeGaId(root.dataset.gaMeasurementId || ''),
    adsenseClientId: normalizeAdsenseClientId(root.dataset.adsenseClientId || ''),
    yandexMetricaId: normalizeNumericId(root.dataset.yandexMetricaId || ''),
    yandexMetricaWebvisor: root.dataset.yandexMetricaWebvisor === '1',
    yandexRsyaEnabled: root.dataset.yandexRsyaEnabled === '1',
  }
}

function normalizeGaId(value) {
  const trimmed = value.trim()
  return /^G-[A-Z0-9-]+$/i.test(trimmed) ? trimmed : ''
}

function normalizeAdsenseClientId(value) {
  const trimmed = value.trim()
  return /^ca-pub-\d+$/i.test(trimmed) ? trimmed : ''
}

function normalizeNumericId(value) {
  const trimmed = value.trim()
  return /^\d+$/.test(trimmed) ? trimmed : ''
}

function currentConsent() {
  return window.CiphersOnlineConsent?.get() || {
    necessary: true,
    preferences: false,
    analytics: false,
    marketing: false,
  }
}

function googleConsentState(consent) {
  const analytics = consent.analytics ? 'granted' : 'denied'
  const marketing = consent.marketing ? 'granted' : 'denied'
  const preferences = consent.preferences ? 'granted' : 'denied'

  return {
    analytics_storage: analytics,
    ad_storage: marketing,
    ad_user_data: marketing,
    ad_personalization: marketing,
    functionality_storage: preferences,
    personalization_storage: preferences,
    security_storage: 'granted',
  }
}

function ensureGoogleConsentDefaults() {
  if (googleConsentInitialized) {
    return
  }

  window.dataLayer = window.dataLayer || []
  window.gtag = window.gtag || function gtag() {
    window.dataLayer.push(arguments)
  }
  window.gtag('consent', 'default', {
    analytics_storage: 'denied',
    ad_storage: 'denied',
    ad_user_data: 'denied',
    ad_personalization: 'denied',
    functionality_storage: 'denied',
    personalization_storage: 'denied',
    security_storage: 'granted',
    wait_for_update: 500,
  })

  googleConsentInitialized = true
}

function updateGoogleConsent(consent) {
  ensureGoogleConsentDefaults()
  window.gtag('consent', 'update', googleConsentState(consent))
}

function loadScript(id, src, attributes = {}) {
  const existing = document.getElementById(id)
  if (existing) {
    return Promise.resolve(existing)
  }

  return new Promise((resolve, reject) => {
    const script = document.createElement('script')
    script.id = id
    script.async = true
    script.src = src

    Object.entries(attributes).forEach(([key, value]) => {
      if (value !== null && value !== undefined && value !== '') {
        script.setAttribute(key, String(value))
      }
    })

    script.addEventListener('load', () => resolve(script), { once: true })
    script.addEventListener('error', () => reject(new Error(`Failed to load ${src}`)), { once: true })
    document.head.appendChild(script)
  })
}

async function loadGoogleTag(measurementId) {
  if (!measurementId || googleTagLoaded) {
    return
  }

  await loadScript(
    'google-gtag-js',
    `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(measurementId)}`
  )

  googleTagLoaded = true
  window.gtag('js', new Date())
  window.dispatchEvent(new CustomEvent(READY_EVENT, { detail: { provider: 'google-tag' } }))
}

async function enableGoogleAnalytics(measurementId) {
  if (!measurementId || googleAnalyticsConfigured) {
    return
  }

  await loadGoogleTag(measurementId)
  window.gtag('config', measurementId, {
    anonymize_ip: true,
    send_page_view: true,
  })

  googleAnalyticsConfigured = true
  window.dispatchEvent(new CustomEvent(READY_EVENT, { detail: { provider: 'google-analytics' } }))
}

async function enableAdsense(clientId) {
  if (!clientId || adsenseLoaded) {
    return
  }

  await loadScript(
    'google-adsense-js',
    `https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=${encodeURIComponent(clientId)}`,
    { crossorigin: 'anonymous', 'data-ad-client': clientId }
  )

  adsenseLoaded = true
  window.adsbygoogle = window.adsbygoogle || []
  window.dispatchEvent(new CustomEvent(READY_EVENT, { detail: { provider: 'google-adsense' } }))
}

function setYandexMetricaDisabled(id, disabled) {
  if (!id) {
    return
  }

  window[`disableYaCounter${id}`] = disabled
}

async function enableYandexMetrica(id, webvisor) {
  if (!id) {
    return
  }

  setYandexMetricaDisabled(id, false)

  window.ym = window.ym || function ym() {
    window.ym.a = window.ym.a || []
    window.ym.a.push(arguments)
  }
  window.ym.l = window.ym.l || Number(new Date())

  if (!yandexMetricaLoaded) {
    await loadScript('yandex-metrica-js', 'https://mc.yandex.ru/metrika/tag.js')
    yandexMetricaLoaded = true
  }

  if (!yandexMetricaInitialized) {
    window.ym(Number(id), 'init', {
      clickmap: true,
      trackLinks: true,
      accurateTrackBounce: true,
      webvisor,
    })
    yandexMetricaInitialized = true
  }

  window.dispatchEvent(new CustomEvent(READY_EVENT, { detail: { provider: 'yandex-metrica' } }))
}

async function enableYandexRsya() {
  if (yandexRsyaLoaded) {
    return
  }

  window.yaContextCb = window.yaContextCb || []
  await loadScript('yandex-rsya-context-js', 'https://yandex.ru/ads/system/context.js')
  yandexRsyaLoaded = true
  window.dispatchEvent(new CustomEvent(READY_EVENT, { detail: { provider: 'yandex-rsya' } }))
}

function dispatchApplied(consent) {
  window.dispatchEvent(new CustomEvent(APPLIED_EVENT, {
    detail: {
      consent,
      google: googleConsentState(consent),
    },
  }))
}

async function applyTrackingConsent(config, consent) {
  updateGoogleConsent(consent)
  setYandexMetricaDisabled(config.yandexMetricaId, !consent.analytics)

  if (consent.analytics) {
    await Promise.all([
      enableGoogleAnalytics(config.gaMeasurementId),
      enableYandexMetrica(config.yandexMetricaId, config.yandexMetricaWebvisor),
    ])
  }

  if (consent.marketing) {
    await Promise.all([
      enableAdsense(config.adsenseClientId),
      config.yandexRsyaEnabled ? enableYandexRsya() : Promise.resolve(),
    ])
  }

  dispatchApplied(consent)
}

export function initTrackingConsent() {
  const config = readConfig()
  if (!config) {
    return
  }

  ensureGoogleConsentDefaults()
  applyTrackingConsent(config, currentConsent()).catch(() => {
    dispatchApplied(currentConsent())
  })

  window.addEventListener(CONSENT_EVENT, (event) => {
    const consent = event.detail || currentConsent()
    applyTrackingConsent(config, consent).catch(() => {
      dispatchApplied(consent)
    })
  })
}
