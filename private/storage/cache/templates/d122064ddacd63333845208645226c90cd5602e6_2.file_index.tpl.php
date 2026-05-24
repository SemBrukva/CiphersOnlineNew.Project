<?php
/* Smarty version 5.8.0, created on 2026-05-24 10:41:15
  from 'file:contacts/index.tpl' */

/* @var \Smarty\Template $_smarty_tpl */
if ($_smarty_tpl->getCompiled()->isFresh($_smarty_tpl, array (
  'version' => '5.8.0',
  'unifunc' => 'content_6a12d5cb9d71a3_27461738',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'd122064ddacd63333845208645226c90cd5602e6' => 
    array (
      0 => 'contacts/index.tpl',
      1 => 1779485356,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
))) {
function content_6a12d5cb9d71a3_27461738 (\Smarty\Template $_smarty_tpl) {
$_smarty_current_dir = '/Users/brukva/MyProjects/Skeleton/private/resources/views/contacts';
?><div class="contact-page"
     id="contactPage"
     data-sending="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['CONTACT_SENDING']), ENT_QUOTES, 'UTF-8');?>
"
     data-success="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['CONTACT_SUCCESS']), ENT_QUOTES, 'UTF-8');?>
"
     data-failed="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['CONTACT_FAILED']), ENT_QUOTES, 'UTF-8');?>
"
     data-error-name="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['CONTACT_ERROR_NAME']), ENT_QUOTES, 'UTF-8');?>
"
     data-error-email="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['CONTACT_ERROR_EMAIL']), ENT_QUOTES, 'UTF-8');?>
"
     data-error-message="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['CONTACT_ERROR_MESSAGE']), ENT_QUOTES, 'UTF-8');?>
"
     data-error-message-max="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['CONTACT_ERROR_MESSAGE_MAX']), ENT_QUOTES, 'UTF-8');?>
">
    <div class="row g-0 contact-layout">
        <div class="col-lg-5 col-xl-4">
            <div class="contact-info-panel">
                <div class="contact-info-panel__orb contact-info-panel__orb--1"></div>
                <div class="contact-info-panel__orb contact-info-panel__orb--2"></div>
                <div class="contact-info-panel__orb contact-info-panel__orb--3"></div>
                <div class="contact-info-panel__content">
                    <div class="contact-info-panel__brand"><i class="bi bi-envelope-heart-fill me-2"></i><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('app_name')), ENT_QUOTES, 'UTF-8');?>
</div>
                    <p class="contact-info-panel__desc"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['CONTACT_DESCRIPTION']), ENT_QUOTES, 'UTF-8');?>
</p>
                    <div class="contact-info-panel__divider"></div>
                    <ul class="contact-info-panel__features">
                        <li>
                            <span class="contact-info-panel__feature-icon"><i class="bi bi-lightning-charge-fill"></i></span>
                            <span><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['CONTACT_FEATURE_FAST']), ENT_QUOTES, 'UTF-8');?>
</span>
                        </li>
                        <li>
                            <span class="contact-info-panel__feature-icon"><i class="bi bi-shield-check"></i></span>
                            <span><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['CONTACT_FEATURE_SECURE']), ENT_QUOTES, 'UTF-8');?>
</span>
                        </li>
                        <li>
                            <span class="contact-info-panel__feature-icon"><i class="bi bi-chat-heart"></i></span>
                            <span><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['CONTACT_FEATURE_FRIENDLY']), ENT_QUOTES, 'UTF-8');?>
</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-7 col-xl-8">
            <div class="contact-form-panel">
                <h1 class="contact-form-panel__title"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['CONTACTS_TITLE']), ENT_QUOTES, 'UTF-8');?>
</h1>
                <div id="contact_form">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="contact-field">
                                <label class="contact-field__label" for="contact-name"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['CONTACT_YOUR_NAME']), ENT_QUOTES, 'UTF-8');?>
</label>
                                <input class="form-control contact-field__input" id="contact-name" name="name" maxlength="100" value="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('contact_prefill_name')), ENT_QUOTES, 'UTF-8');?>
">
                                <div class="error contact-field__error d-none" id="contact-name-error"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="contact-field">
                                <label class="contact-field__label" for="contact-email"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['CONTACT_YOUR_EMAIL']), ENT_QUOTES, 'UTF-8');?>
</label>
                                <input class="form-control contact-field__input" id="contact-email" name="email" maxlength="100" value="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('contact_prefill_email')), ENT_QUOTES, 'UTF-8');?>
">
                                <div class="error contact-field__error d-none" id="contact-email-error"></div>
                            </div>
                        </div>
                    </div>
                    <div class="contact-field mb-4">
                        <label class="contact-field__label" for="contact-text"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['CONTACT_MESSAGE']), ENT_QUOTES, 'UTF-8');?>
</label>
                        <textarea maxlength="10000" id="contact-text" class="form-control contact-field__input" rows="8" name="text"></textarea>
                        <span class="error contact-field__error d-none" id="contact-message-error"></span>
                    </div>
                    <div class="contact-submit-error d-none alert" id="contact-submit-error"></div>
                    <div class="d-flex justify-content-end">
                        <input type="hidden" name="language" value="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('language')), ENT_QUOTES, 'UTF-8');?>
">
                        <input type="hidden" id="contact_timestamp" value="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('timestamp')), ENT_QUOTES, 'UTF-8');?>
">
                        <input type="hidden" id="contact_token" value="<?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('token')), ENT_QUOTES, 'UTF-8');?>
">
                        <button type="button" class="btn btn-primary contact-submit-btn" id="contact_form_send">
                            <span class="spinner-block d-flex align-items-center gap-2">
                                <span class="spinner spinner-border spinner-border-sm text-light invisible" role="status" id="contact-submit-spinner">
                                    <span class="sr-only">Loading...</span>
                                </span>
                                <span id="button_text"><?php echo htmlspecialchars((string) ($_smarty_tpl->getValue('t')['CONTACT_SEND']), ENT_QUOTES, 'UTF-8');?>
</span>
                                <i class="bi bi-send-fill contact-send-icon"></i>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php }
}
