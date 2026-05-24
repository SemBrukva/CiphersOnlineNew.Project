/**
 * Инициализирует все формы входа на странице (статичная + модал).
 */
export function initLoginForms() {
    document.querySelectorAll('[data-login-form]').forEach(initLoginForm)
}

/**
 * @param {HTMLElement} root
 */
function initLoginForm(root) {
    const alertNode    = root.querySelector('[data-login-alert]')
    const emailNode    = root.querySelector('[data-login-email]')
    const emailError   = root.querySelector('[data-login-email-error]')
    const passwordNode = root.querySelector('[data-login-password]')
    const passwordError = root.querySelector('[data-login-password-error]')
    const submitBtn    = root.querySelector('[data-login-submit]')

    const text = {
        redirectUrl:      root.dataset.redirectUrl ?? '',
        emailRequired:    root.dataset.errorEmailRequired ?? 'Email is required.',
        emailInvalid:     root.dataset.errorEmailInvalid ?? 'Please enter a valid email.',
        passwordRequired: root.dataset.errorPasswordRequired ?? 'Password is required.',
        errorInvalid:     root.dataset.errorInvalid ?? 'Invalid email or password.',
    }

    const showAlert = (message, type) => {
        alertNode.className = 'alert alert-' + type
        alertNode.textContent = message
    }

    const clearFieldError = (input, errorDiv) => {
        input.classList.remove('is-invalid')
        errorDiv.textContent = ''
    }

    const setFieldError = (input, errorDiv, message) => {
        input.classList.add('is-invalid')
        errorDiv.textContent = message
    }

    const validateEmail = () => {
        const value = emailNode.value.trim()
        if (value === '') return text.emailRequired
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) return text.emailInvalid
        return null
    }

    const validatePassword = () => {
        if (passwordNode.value === '') return text.passwordRequired
        return null
    }

    const validateAll = () => {
        const emailErr    = validateEmail()
        const passwordErr = validatePassword()

        clearFieldError(emailNode, emailError)
        clearFieldError(passwordNode, passwordError)

        if (emailErr) setFieldError(emailNode, emailError, emailErr)
        if (passwordErr) setFieldError(passwordNode, passwordError, passwordErr)

        return !emailErr && !passwordErr
    }

    emailNode.addEventListener('input', () => {
        clearFieldError(emailNode, emailError)
        const error = validateEmail()
        if (error) setFieldError(emailNode, emailError, error)
    })

    passwordNode.addEventListener('input', () => {
        clearFieldError(passwordNode, passwordError)
        const error = validatePassword()
        if (error) setFieldError(passwordNode, passwordError, error)
    })

    emailNode.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') passwordNode.focus()
    })

    passwordNode.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') submitBtn?.click()
    })

    submitBtn?.addEventListener('click', async () => {
        alertNode.className = 'alert d-none'
        alertNode.textContent = ''

        if (!validateAll()) return

        submitBtn.disabled = true

        try {
            await window.api.guest.login({
                email:    emailNode.value.trim(),
                password: passwordNode.value,
            })

            if (text.redirectUrl) {
                window.location.href = text.redirectUrl
            } else {
                window.location.reload()
            }
        } catch (err) {
            const message = err?.status === 422
                ? text.errorInvalid
                : (err?.message ?? 'Internal Server Error')
            showAlert(message, 'danger')
        } finally {
            submitBtn.disabled = false
        }
    })
}
