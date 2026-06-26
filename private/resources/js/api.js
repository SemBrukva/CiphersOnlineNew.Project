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
        /** POST /api/tools/affine */
        affine: (data) => this.#request('POST', '/tools/affine', data),
        /** POST /api/tools/atbash */
        atbash: (data) => this.#request('POST', '/tools/atbash', data),
        /** POST /api/tools/playfair */
        playfair: (data) => this.#request('POST', '/tools/playfair', data),
        /** POST /api/tools/beaufort */
        beaufort: (data) => this.#request('POST', '/tools/beaufort', data),
        /** POST /api/tools/porta */
        porta: (data) => this.#request('POST', '/tools/porta', data),
        /** POST /api/tools/autokey */
        autokey: (data) => this.#request('POST', '/tools/autokey', data),
        /** POST /api/tools/gronsfeld */
        gronsfeld: (data) => this.#request('POST', '/tools/gronsfeld', data),
        /** POST /api/tools/vigenere */
        vigenere: (data) => this.#request('POST', '/tools/vigenere', data),
        /** POST /api/tools/vernam */
        vernam: (data) => this.#request('POST', '/tools/vernam', data),
        /** POST /api/tools/bacon */
        bacon: (data) => this.#request('POST', '/tools/bacon', data),
        /** POST /api/tools/rot13 */
        rot13: (data) => this.#request('POST', '/tools/rot13', data),
        /** POST /api/tools/a1z26 */
        a1z26: (data) => this.#request('POST', '/tools/a1z26', data),
        /** POST /api/tools/rail-fence */
        'rail-fence': (data) => this.#request('POST', '/tools/rail-fence', data),
        /** POST /api/tools/columnar-transposition */
        'columnar-transposition': (data) => this.#request('POST', '/tools/columnar-transposition', data),
        /** POST /api/tools/polybius-square */
        'polybius-square': (data) => this.#request('POST', '/tools/polybius-square', data),
        /** POST /api/tools/hill */
        hill: (data) => this.#request('POST', '/tools/hill', data),
        /** POST /api/tools/caesar-brute-force */
        'caesar-brute-force': (data) => this.#request('POST', '/tools/caesar-brute-force', data),
        /** POST /api/tools/affine-brute-force */
        'affine-brute-force': (data) => this.#request('POST', '/tools/affine-brute-force', data),
        /** POST /api/tools/simple-substitution */
        'simple-substitution': (data) => this.#request('POST', '/tools/simple-substitution', data),
        /** POST /api/tools/xor */
        xor: (data) => this.#request('POST', '/tools/xor', data),
        /** POST /api/tools/vigenere-cracker */
        'vigenere-cracker': (data) => this.#request('POST', '/tools/vigenere-cracker', data),
        /** POST /api/tools/bifid */
        bifid: (data) => this.#request('POST', '/tools/bifid', data),
        /** POST /api/tools/trifid */
        trifid: (data) => this.#request('POST', '/tools/trifid', data),
        /** POST /api/tools/alberti */
        alberti: (data) => this.#request('POST', '/tools/alberti', data),
        /** POST /api/tools/enigma */
        enigma: (data) => this.#request('POST', '/tools/enigma', data),
        /** POST /api/tools/cipher-identifier */
        'cipher-identifier': (data) => this.#request('POST', '/tools/cipher-identifier', data),
        /** POST /api/tools/anagram-solver */
        'anagram-solver': (data) => this.#request('POST', '/tools/anagram-solver', data),
        /** GET /api/tools/search?q=...&locale=ru */
        searchTools: (q, locale = '') => {
            const params = 'q=' + encodeURIComponent(q) + (locale ? '&locale=' + encodeURIComponent(locale) : '')
            return this.#request('GET', '/tools/search?' + params)
        },
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
