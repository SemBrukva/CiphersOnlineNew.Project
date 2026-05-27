/**
 * Инициализирует форму обратной связи на странице контактов.
 */
export function initContactsPage() {
    const root = document.getElementById('contactPage')
    if (!root) {
        return
    }

    const nameNode = document.getElementById('contact-name')
    const emailNode = document.getElementById('contact-email')
    const messageNode = document.getElementById('contact-text')
    const buttonNode = document.getElementById('contact_form_send')
    const spinnerNode = document.getElementById('contact-submit-spinner')
    const submitErrorNode = document.getElementById('contact-submit-error')
    const csrfTokenNode = document.getElementById('contact_token')

    const nameErrorNode = document.getElementById('contact-name-error')
    const emailErrorNode = document.getElementById('contact-email-error')
    const messageErrorNode = document.getElementById('contact-message-error')

    const text = {
        sending: root.dataset.sending ?? 'Sending...',
        success: root.dataset.success ?? 'Message sent successfully.',
        failed: root.dataset.failed ?? 'Failed to send message. Please try again.',
        errorName: root.dataset.errorName ?? 'Enter your name.',
        errorEmail: root.dataset.errorEmail ?? 'Enter a valid email.',
        errorMessage: root.dataset.errorMessage ?? 'Enter a message.',
        errorMessageMax: root.dataset.errorMessageMax ?? 'Message is too long.',
    }

    const clearError = (input, node) => {
        if (!input || !node) return
        input.classList.remove('is-invalid')
        node.textContent = ''
        node.classList.add('d-none')
    }

    const setError = (input, node, message) => {
        if (!input || !node) return
        input.classList.add('is-invalid')
        node.textContent = message
        node.classList.remove('d-none')
    }

    const showSubmitMessage = (message, type) => {
        if (!submitErrorNode) return
        submitErrorNode.className = `contact-submit-error alert alert-${type}`
        submitErrorNode.textContent = message
        submitErrorNode.classList.remove('d-none')
    }

    const clearSubmitMessage = () => {
        if (!submitErrorNode) return
        submitErrorNode.textContent = ''
        submitErrorNode.classList.add('d-none')
    }

    const validate = () => {
        clearError(nameNode, nameErrorNode)
        clearError(emailNode, emailErrorNode)
        clearError(messageNode, messageErrorNode)

        let ok = true
        const name = nameNode?.value.trim() ?? ''
        const email = emailNode?.value.trim() ?? ''
        const message = messageNode?.value.trim() ?? ''

        if (name === '' || name.length > 100) {
            setError(nameNode, nameErrorNode, text.errorName)
            ok = false
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
        if (email === '' || email.length > 100 || !emailRegex.test(email)) {
            setError(emailNode, emailErrorNode, text.errorEmail)
            ok = false
        }

        if (message === '') {
            setError(messageNode, messageErrorNode, text.errorMessage)
            ok = false
        } else if (message.length > 10000) {
            setError(messageNode, messageErrorNode, text.errorMessageMax)
            ok = false
        }

        return ok
    }

    nameNode?.addEventListener('input', () => clearError(nameNode, nameErrorNode))
    emailNode?.addEventListener('input', () => clearError(emailNode, emailErrorNode))
    messageNode?.addEventListener('input', () => clearError(messageNode, messageErrorNode))

    buttonNode?.addEventListener('click', async () => {
        clearSubmitMessage()
        if (!validate()) {
            return
        }

        buttonNode.disabled = true
        spinnerNode?.classList.remove('invisible')
        const initialText = buttonNode.querySelector('#button_text')?.textContent ?? ''
        const buttonTextNode = buttonNode.querySelector('#button_text')
        if (buttonTextNode) {
            buttonTextNode.textContent = text.sending
        }

        try {
            await window.api.guest.contact(
                {
                    name: nameNode.value.trim(),
                    email: emailNode.value.trim(),
                    message: messageNode.value.trim(),
                },
                csrfTokenNode?.value ?? ''
            )

            showSubmitMessage(text.success, 'success')
            messageNode.value = ''
        } catch (error) {
            const message = error?.message ?? error?.response?.error?.message ?? text.failed
            showSubmitMessage(message, 'danger')
        } finally {
            buttonNode.disabled = false
            spinnerNode?.classList.add('invisible')
            if (buttonTextNode) {
                buttonTextNode.textContent = initialText
            }
        }
    })
}
