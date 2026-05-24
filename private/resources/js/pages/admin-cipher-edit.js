/**
 * Инициализирует страницу админского редактирования шифра и связанного контента.
 */
export function initAdminCipherEdit() {
    const root = document.querySelector('[data-page="admin-cipher-edit"]')

    if (!root) {
        return
    }

    const form = root.querySelector('form')
    const saveButton = root.querySelector('[data-role="save-cipher"]')
    const alertBox = document.querySelector('[data-role="save-alert"]')

    if (!(form instanceof HTMLFormElement) || !(saveButton instanceof HTMLButtonElement) || !(alertBox instanceof HTMLElement)) {
        return
    }

    const cipherId = Number(root.getAttribute('data-cipher-id') || '0')
    const csrfToken = String(root.getAttribute('data-csrf-token') || '')

    const showAlert = (kind, message) => {
        alertBox.className = `alert alert-${kind}`
        alertBox.textContent = message
        alertBox.classList.remove('d-none')
    }

    const clearAlert = () => {
        alertBox.classList.add('d-none')
        alertBox.textContent = ''
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault()
        clearAlert()

        if (!cipherId || !csrfToken) {
            showAlert('danger', 'Не удалось определить служебные параметры формы.')
            return
        }

        const payload = {
            settings: {
                alias: String(root.querySelector('[name="alias"]')?.value ?? '').trim(),
                category_id: Number(root.querySelector('[name="category_id"]')?.value ?? 0),
                sort_order: Number(root.querySelector('[name="sort_order"]')?.value ?? 0),
                published: Boolean(root.querySelector('[name="published"]')?.checked ?? false),
            },
            translations: {},
            blocks: [],
            faq: [],
            examples: [],
        }

        root.querySelectorAll('[data-language]').forEach((section) => {
            const language = String(section.getAttribute('data-language') || '').trim().toLowerCase()

            if (!language) {
                return
            }

            payload.translations[language] = {
                name: String(section.querySelector('[data-cipher-field="name"]')?.value ?? '').trim(),
                name_short: String(section.querySelector('[data-cipher-field="name_short"]')?.value ?? '').trim(),
                description: String(section.querySelector('[data-cipher-field="description"]')?.value ?? '').trim(),
                meta_title: String(section.querySelector('[data-cipher-field="meta_title"]')?.value ?? '').trim(),
                meta_description: String(section.querySelector('[data-cipher-field="meta_description"]')?.value ?? '').trim(),
            }

            section.querySelectorAll('[data-entity="block"]').forEach((item) => {
                const id = Number(item.getAttribute('data-id') || '0')
                if (!id) {
                    return
                }

                let row = payload.blocks.find((existing) => existing.id === id)
                if (!row) {
                    row = { id, translations: {} }
                    payload.blocks.push(row)
                }

                row.translations[language] = {
                    title: String(item.querySelector('[data-translation-field="title"]')?.value ?? '').trim(),
                    text: String(item.querySelector('[data-translation-field="text"]')?.value ?? '').trim(),
                }
            })

            section.querySelectorAll('[data-entity="faq"]').forEach((item) => {
                const id = Number(item.getAttribute('data-id') || '0')
                if (!id) {
                    return
                }

                let row = payload.faq.find((existing) => existing.id === id)
                if (!row) {
                    row = { id, translations: {} }
                    payload.faq.push(row)
                }

                row.translations[language] = {
                    question: String(item.querySelector('[data-translation-field="question"]')?.value ?? '').trim(),
                    answer: String(item.querySelector('[data-translation-field="answer"]')?.value ?? '').trim(),
                }
            })

            section.querySelectorAll('[data-entity="example"]').forEach((item) => {
                const id = Number(item.getAttribute('data-id') || '0')
                if (!id) {
                    return
                }

                let row = payload.examples.find((existing) => existing.id === id)
                if (!row) {
                    row = { id, translations: {} }
                    payload.examples.push(row)
                }

                row.translations[language] = {
                    title: String(item.querySelector('[data-translation-field="title"]')?.value ?? '').trim(),
                    input: String(item.querySelector('[data-translation-field="input"]')?.value ?? '').trim(),
                    output: String(item.querySelector('[data-translation-field="output"]')?.value ?? '').trim(),
                    description: String(item.querySelector('[data-translation-field="description"]')?.value ?? '').trim(),
                }
            })
        })

        saveButton.disabled = true
        const originalText = saveButton.textContent
        saveButton.textContent = 'Сохранение...'

        try {
            await window.api.admin.saveCipher(cipherId, payload, csrfToken)
            showAlert('success', 'Шифр и локализованный контент сохранены.')
        } catch (error) {
            const message = error?.response?.error?.message || error?.message || 'Ошибка сохранения.'
            showAlert('danger', message)
        } finally {
            saveButton.disabled = false
            saveButton.textContent = originalText || 'Сохранить'
        }
    })
}
