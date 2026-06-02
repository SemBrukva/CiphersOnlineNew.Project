<section class="ciphers-category-hub-hero">
    <div class="ciphers-category-hub-hero__inner">
        <h1 class="ciphers-category-hub-hero__title">{$category.name}</h1>
        <p class="ciphers-category-hub-hero__desc">{$category.description|default:''}</p>
        {if $tools}
        <div class="ciphers-category-hub-hero__chips">
            {foreach $tools as $tool}
                <a class="ciphers-category-hub-hero__chip" href="{$locale_prefix}/{$category.alias}/{$tool.alias}">{$tool.name_short}</a>
            {/foreach}
        </div>
        {/if}
    </div>
</section>

{if $tools}
<section class="panel ciphers-category-hub-panel" id="category-tools">
    {assign var="tools_title_key" value="CIPHER_CATEGORY_TOOLS_TITLE_"|cat:($category.category|default:'cipher'|upper)}
    <div class="panel-heading">
        <div class="panel-title">
            <i class="bi bi-tools"></i> {trans key=$tools_title_key}
        </div>
    </div>
    <div class="panel-content">
        <div class="ciphers-category-hub-grid">
            {foreach $tools as $tool}
            <article class="ciphers-category-hub-card">
                <h2 class="ciphers-category-hub-card__title">
                    <a href="{$locale_prefix}/{$category.alias}/{$tool.alias}">{$tool.name}</a>
                </h2>
                {if $tool.description}
                <p class="ciphers-category-hub-card__desc">{$tool.description_short}</p>
                {/if}
                {if $tool.tags}
                <div class="ciphers-category-hub-card__badges">
                    {foreach $tool.tags as $tag}
                    <span class="ciphers-category-hub-badge">{$tag}</span>
                    {/foreach}
                </div>
                {/if}
                <span class="ciphers-category-hub-card__arrow" aria-hidden="true">→</span>
            </article>
            {/foreach}
        </div>
    </div>
</section>
{/if}

{if $tasks}
<section class="panel ciphers-category-hub-panel">
    <div class="panel-heading"><div class="panel-title"><i class="bi bi-fire"></i> {trans key='CIPHER_CATEGORY_POPULAR_TASKS_TITLE'}</div></div>
        <div class="panel-content">
            <div class="ciphers-category-hub-popular-grid">
                {foreach $tasks as $task}
                    <a class="ciphers-category-hub-popular-item" href="{$locale_prefix}/{$category.alias}/{$task.cipher_alias}">
                    <span class="ciphers-category-hub-popular-item__icon">
                        <i class="bi bi-list-task"></i>
                    </span>
                        <div class="ciphers-category-hub-popular-item__body">
                            <strong class="ciphers-category-hub-popular-item__title">{$task.title} → {$task.cipher_name_short}</strong>
                            <span class="ciphers-category-hub-popular-item__desc">{$task.description}</span>
                            <span class="ciphers-category-hub-popular-item__tool">{$task.cipher_name_short}</span>
                        </div>
                        <span class="ciphers-category-hub-popular-item__arrow"><i class="bi bi-arrow-right-short"></i></span>
                    </a>
                {/foreach}
            </div>
        </div>
</section>
{/if}

{if $blocks}
    {foreach $blocks as $block}
    <section class="panel ciphers-category-hub-panel">
        <div class="panel-heading">
            <div class="panel-title"><i class="bi bi-info-circle-fill"></i> {$block.title|default:$category.name}</div>
        </div>
        <div class="panel-content">
            {$block.text nofilter}
        </div>
    </section>
    {/foreach}
{/if}

{if $used_together}
<section class="panel ciphers-category-hub-panel">
        <div class="panel-heading"><div class="panel-title"><i class="bi bi-shuffle"></i> {trans key='CIPHER_CATEGORY_USED_TOGETHER_TITLE'}</div></div>
        <div class="panel-content">
            <div class="ciphers-category-hub-combo-grid">
                {foreach $used_together as $item}
                    <div class="ciphers-category-hub-combo-card">
                        <div class="ciphers-category-hub-combo-card__tools">
                            <a class="ciphers-category-hub-combo-tag" href="{$locale_prefix}/{$category.alias}/{$item.first_cipher_alias}">{$item.first_cipher_name_short}</a>
                            <span class="ciphers-category-hub-combo-card__connector"><i class="bi bi-arrow-left-right"></i></span>
                            <a class="ciphers-category-hub-combo-tag" href="{$locale_prefix}/{$category.alias}/{$item.second_cipher_alias}">{$item.second_cipher_name_short}</a>
                        </div>
                        <p class="ciphers-category-hub-combo-card__desc">{$item.title}</p>
                    </div>
                {/foreach}
            </div>
        </div>
</section>
{/if}

{if $faq}
<section class="panel ciphers-category-hub-panel">
    <div class="panel-heading">
        <div class="panel-title"><i class="bi bi-question-circle-fill"></i> {trans key='CIPHER_TOOL_FAQ_TITLE'}</div>
    </div>
    <div class="panel-content">
        <div class="accordion" id="category-faq">
            {foreach $faq as $item}
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#category-faq-{$item.id}">
                        <span class="accordion-button-text">{$item.question}</span>
                    </button>
                </h2>
                <div id="category-faq-{$item.id}" class="accordion-collapse collapse" data-bs-parent="#category-faq">
                    <div class="accordion-body">{$item.answer}</div>
                </div>
            </div>
            {/foreach}
        </div>
    </div>
</section>
{/if}
