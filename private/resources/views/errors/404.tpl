<section class="py-5">
    <div class="text-center mb-5">
        <span class="badge bg-danger-subtle text-danger-emphasis border border-danger-subtle mb-3">404</span>
        <h1 class="display-5 fw-bold mb-3">{$t.ERROR_404_HEADING}</h1>
        <p class="lead text-muted mx-auto" style="max-width: 760px;">
            {$t.ERROR_404_TEXT}
        </p>
    </div>

    <div class="row g-4 justify-content-center mb-4">
        <div class="col-lg-7">
            <form method="GET" action="{$locale_prefix}/search" class="card shadow-sm border-0 p-3 p-md-4">
                <label for="site-search" class="form-label fw-semibold mb-2">{$t.ERROR_404_SEARCH_LABEL}</label>
                <div class="input-group">
                    <input
                        id="site-search"
                        type="search"
                        name="q"
                        class="form-control"
                        placeholder="{$t.ERROR_404_SEARCH_PLACEHOLDER}"
                    >
                    <button type="submit" class="btn btn-primary">{$t.ERROR_404_SEARCH_BUTTON}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 justify-content-center">
        <div class="col-sm-6 col-lg-3">
            <a href="{$locale_prefix|default:'/'}" class="card h-100 text-decoration-none shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h6 mb-2 text-body">{$t.ERROR_404_HOME_TITLE}</h2>
                    <p class="text-muted small mb-0">{$t.ERROR_404_HOME_TEXT}</p>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-lg-3">
            <a href="{$locale_prefix}/contacts" class="card h-100 text-decoration-none shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h6 mb-2 text-body">{$t.ERROR_404_CONTACTS_TITLE}</h2>
                    <p class="text-muted small mb-0">{$t.ERROR_404_CONTACTS_TEXT}</p>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-lg-3">
            <a href="{$locale_prefix}/login" class="card h-100 text-decoration-none shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h6 mb-2 text-body">{$t.ERROR_404_LOGIN_TITLE}</h2>
                    <p class="text-muted small mb-0">{$t.ERROR_404_LOGIN_TEXT}</p>
                </div>
            </a>
        </div>

        <div class="col-sm-6 col-lg-3">
            <a href="/cabinet" class="card h-100 text-decoration-none shadow-sm border-0">
                <div class="card-body">
                    <h2 class="h6 mb-2 text-body">{$t.ERROR_404_CABINET_TITLE}</h2>
                    <p class="text-muted small mb-0">{$t.ERROR_404_CABINET_TEXT}</p>
                </div>
            </a>
        </div>
    </div>
</section>
