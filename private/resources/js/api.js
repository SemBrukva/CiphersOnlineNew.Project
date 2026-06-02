/**
 * Клиент для работы с API скелетона.
 *
 * Использование:
 *   const result = await api.guest.ping();
 *   const profile = await api.user.profile();
 *   const stats   = await api.admin.stats();
 *
 * При ошибке (HTTP 4xx / 5xx) выбрасывается ApiError с полями:
 *   error.message  — текст из JSON-ответа (поле "error.message")
 *   error.status   — HTTP-статус код
 *   error.response — полный JSON-ответ
 */
export class ApiError extends Error {
    /**
     * @param {string} message
     * @param {number} status
     * @param {object} response
     */
    constructor(message, status, response) {
        super(message)
        this.name     = 'ApiError'
        this.status   = status
        this.response = response
    }
}

export class ApiClient {
    #baseUrl

    /** @param {string} baseUrl */
    constructor(baseUrl = '/api') {
        this.#baseUrl = baseUrl
    }

    /**
     * @param {string}      method
     * @param {string}      endpoint
     * @param {object|null} data
     * @returns {Promise<any>}
     */
    async #request(method, endpoint, data = null, extraHeaders = {}) {
        const headers = {
            'Accept':           'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...extraHeaders,
        }

        let url  = this.#baseUrl + endpoint
        let body = null

        if (data !== null && Object.keys(data).length > 0) {
            if (method === 'GET') {
                url += '?' + new URLSearchParams(data).toString()
            } else {
                headers['Content-Type'] = 'application/json'
                body = JSON.stringify(data)
            }
        }

        const response = await fetch(url, { method, headers, body, credentials: 'same-origin' })
        const json = await response.json()

        if (!response.ok) {
            throw new ApiError(this.#extractErrorMessage(json, response.status), response.status, json)
        }

        return json
    }

    /**
     * Возвращает унифицированное сообщение об ошибке из JSON API.
     */
    #extractErrorMessage(json, status) {
        if (json && typeof json === 'object') {
            const error = json.error
            if (error && typeof error === 'object' && typeof error.message === 'string' && error.message !== '') {
                return error.message
            }

            if (typeof error === 'string' && error !== '') {
                return error
            }
        }

        return `HTTP ${status}`
    }

    guest = {
        /** GET /api/ping */
        ping: () => this.#request('GET', '/ping'),
        /** POST /api/auth/register */
        register: (data) => this.#request('POST', '/auth/register', data),
        /** POST /api/auth/login */
        login: (data) => this.#request('POST', '/auth/login', data),
        /** POST /api/contact */
        contact: (data, csrfToken) => this.#request('POST', '/contact', data, { 'X-CSRF-Token': csrfToken }),
        /** POST /api/tools/caesar */
        caesar: (data) => this.#request('POST', '/tools/caesar', data),
        /** POST /api/tools/atbash */
        atbash: (data) => this.#request('POST', '/tools/atbash', data),
        /** POST /api/tools/playfair */
        playfair: (data) => this.#request('POST', '/tools/playfair', data),
        /** POST /api/tools/beaufort */
        beaufort: (data) => this.#request('POST', '/tools/beaufort', data),
        /** POST /api/tools/gronsfeld */
        gronsfeld: (data) => this.#request('POST', '/tools/gronsfeld', data),
        /** POST /api/tools/vigenere */
        vigenere: (data) => this.#request('POST', '/tools/vigenere', data),
        /** POST /api/tools/vernam */
        vernam: (data) => this.#request('POST', '/tools/vernam', data),
        /** POST /api/tools/bacon */
        bacon: (data) => this.#request('POST', '/tools/bacon', data),
        /** POST /api/tools/a1z26 */
        a1z26: (data) => this.#request('POST', '/tools/a1z26', data),
        /** GET /api/favorites/ciphers?slugs[]=...&locale=ru */
        getFavorites: (slugs, locale = '') => {
            const params = slugs.map(s => 'slugs[]=' + encodeURIComponent(s)).join('&')
            const localeParam = locale ? '&locale=' + encodeURIComponent(locale) : ''
            return this.#request('GET', '/favorites/ciphers?' + params + localeParam)
        },
    }

    user = {
        /** GET /api/user/profile */
        profile: () => this.#request('GET', '/user/profile'),
    }

    admin = {
        /** GET /api/admin/stats */
        stats: () => this.#request('GET', '/admin/stats'),
        /** POST /api/admin/cipher-categories/{id} */
        saveCipherCategory: (id, data, csrfToken) => this.#request(
            'POST',
            `/admin/cipher-categories/${id}`,
            data,
            { 'X-CSRF-Token': csrfToken }
        ),
        /** POST /api/admin/ciphers/{id} */
        saveCipher: (id, data, csrfToken) => this.#request(
            'POST',
            `/admin/ciphers/${id}`,
            data,
            { 'X-CSRF-Token': csrfToken }
        ),
    }
}
