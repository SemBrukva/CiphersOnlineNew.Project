/**
 * Инициализирует страницу комплексного редактирования категории шифров и переводов.
 */
export function initAdminCipherCategoryEdit() {
    const root = document.querySelector('[data-page="admin-cipher-category-edit"]')

    if (!root) {
        return
    }

    const saveButton = root.querySelector('[data-role="save-category"]')
    const alertBox = root.querySelector('[data-role="save-alert"]')
    const form = root.querySelector('form')

    if (!(saveButton instanceof HTMLButtonElement) || !(alertBox instanceof HTMLElement) || !(form instanceof HTMLFormElement)) {
        return
    }

    const categoryId = Number(root.getAttribute('data-category-id') || '0')
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

        if (!categoryId || !csrfToken) {
            showAlert('danger', 'Не удалось определить служебные параметры формы.')
            return
        }

        const payload = {
            settings: {
                alias: String(root.querySelector('[name="alias"]')?.value ?? '').trim(),
                sort_order: Number(root.querySelector('[name="sort_order"]')?.value ?? 0),
                published: Boolean(root.querySelector('[name="published"]')?.checked ?? false),
            },
            translations: {},
        }

        root.querySelectorAll('[data-language]').forEach((section) => {
            const language = String(section.getAttribute('data-language') || '').trim().toLowerCase()

            if (!language) {
                return
            }

            payload.translations[language] = {
                name: String(section.querySelector('[data-field="name"]')?.value ?? '').trim(),
                description: String(section.querySelector('[data-field="description"]')?.value ?? '').trim(),
                meta_title: String(section.querySelector('[data-field="meta_title"]')?.value ?? '').trim(),
                meta_description: String(section.querySelector('[data-field="meta_description"]')?.value ?? '').trim(),
            }
        })

        saveButton.disabled = true
        const originalText = saveButton.textContent
        saveButton.textContent = 'Сохранение...'

        try {
            await window.api.admin.saveCipherCategory(categoryId, payload, csrfToken)
            showAlert('success', 'Категория и переводы сохранены.')
        } catch (error) {
            const message = error?.response?.error?.message || error?.message || 'Ошибка сохранения.'
            showAlert('danger', message)
        } finally {
            saveButton.disabled = false
            saveButton.textContent = originalText || 'Сохранить'
        }
    })
}
