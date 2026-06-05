const STORAGE_KEY = 'ciphersonline_cookie_consent'
const EVENT_NAME = 'ciphersonline:cookie-consent'
const OPTIONAL_CATEGORIES = ['preferences', 'analytics', 'marketing']
const PREFERENCE_STORAGE_PREFIXES = ['cipher-tool:state:']
const PREFERENCE_STORAGE_KEYS = ['cipher_favorites']

function defaultConsent(version) {
  return {
    version,
    necessary: true,
    preferences: false,
    analytics: false,
    marketing: false,
    updatedAt: null,
  }
}

function readConsent(version) {
  try {
    const raw = window.localStorage.getItem(STORAGE_KEY)
    if (!raw) {
      return null
    }

    const parsed = JSON.parse(raw)
    if (!parsed || Number(parsed.version) !== Number(version)) {
      return null
    }

    return {
      ...defaultConsent(version),
      ...parsed,
      necessary: true,
    }
  } catch {
    return null
  }
}

function writeConsent(consent) {
  const payload = {
    ...consent,
    necessary: true,
    updatedAt: new Date().toISOString(),
  }

  if (!payload.preferences) {
    clearPreferenceStorage()
  }

  window.localStorage.setItem(STORAGE_KEY, JSON.stringify(payload))
  window.dispatchEvent(new CustomEvent(EVENT_NAME, { detail: payload }))

  return payload
}

function clearPreferenceStorage() {
  PREFERENCE_STORAGE_KEYS.forEach((key) => {
    window.localStorage.removeItem(key)
  })

  Object.keys(window.localStorage).forEach((key) => {
    if (PREFERENCE_STORAGE_PREFIXES.some((prefix) => key.startsWith(prefix))) {
      window.localStorage.removeItem(key)
    }
  })
}

function setHidden(element, hidden) {
  if (!element) {
    return
  }

  element.hidden = hidden
}

function fillCheckboxes(root, consent) {
  OPTIONAL_CATEGORIES.forEach((category) => {
    root.querySelectorAll(`[data-cookie-category="${category}"]`).forEach((input) => {
      input.checked = !!consent[category]
    })
  })
}

function selectedConsent(root, version) {
  const consent = defaultConsent(version)

  OPTIONAL_CATEGORIES.forEach((category) => {
    const input = root.querySelector(`[data-cookie-category="${category}"]`)
    consent[category] = !!(input && input.checked)
  })

  return consent
}

function exposeConsentApi(root, version) {
  window.CiphersOnlineConsent = {
    get() {
      return readConsent(version) || defaultConsent(version)
    },
    has(category) {
      const consent = this.get()
      return category === 'necessary' ? true : !!consent[category]
    },
    open() {
      fillCheckboxes(root, this.get())
      setHidden(root, false)
      setHidden(root.querySelector('[data-cookie-consent-actions]'), true)
      setHidden(root.querySelector('[data-cookie-consent-settings]'), false)
    },
  }
}

export function initCookieConsent() {
  const root = document.querySelector('[data-cookie-consent]')
  if (!root) {
    return
  }

  const version = Number(root.dataset.version || 1)
  const stored = readConsent(version)
  const actions = root.querySelector('[data-cookie-consent-actions]')
  const settings = root.querySelector('[data-cookie-consent-settings]')

  exposeConsentApi(root, version)

  if (stored) {
    window.dispatchEvent(new CustomEvent(EVENT_NAME, { detail: stored }))
  } else {
    setHidden(root, false)
  }

  document.querySelectorAll('[data-cookie-settings-open]').forEach((button) => {
    button.addEventListener('click', () => {
      window.CiphersOnlineConsent.open()
    })
  })

  root.querySelectorAll('[data-cookie-accept]').forEach((button) => {
    button.addEventListener('click', () => {
      const consent = defaultConsent(version)
      OPTIONAL_CATEGORIES.forEach((category) => {
        consent[category] = true
      })
      writeConsent(consent)
      setHidden(root, true)
    })
  })

  root.querySelectorAll('[data-cookie-reject]').forEach((button) => {
    button.addEventListener('click', () => {
      writeConsent(defaultConsent(version))
      setHidden(root, true)
    })
  })

  root.querySelectorAll('[data-cookie-settings]').forEach((button) => {
    button.addEventListener('click', () => {
      fillCheckboxes(root, readConsent(version) || defaultConsent(version))
      setHidden(actions, true)
      setHidden(settings, false)
    })
  })

  root.querySelectorAll('[data-cookie-back]').forEach((button) => {
    button.addEventListener('click', () => {
      setHidden(settings, true)
      setHidden(actions, false)
    })
  })

  settings?.addEventListener('submit', (event) => {
    event.preventDefault()
    writeConsent(selectedConsent(root, version))
    setHidden(root, true)
  })
}
