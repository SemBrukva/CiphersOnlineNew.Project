# Guide content quality

Use this checklist for every article and locale.

## Editorial standard

- Answer the reader's main question early and state what the article will help them do or understand.
- Build sections in a teaching sequence: prerequisite, mechanism, worked example, interpretation, limitations, and next step as appropriate.
- Prefer concrete examples and decisions over broad claims and throat-clearing.
- Make every section add a new idea; remove repeated introductions and conclusions.
- Keep the guide useful without requiring the reader to open a related tool.
- Avoid filler, keyword stuffing, fake quotations, invented anecdotes, and claims about what “many experts” believe.
- Match the category:
  - `how-to`: actionable steps, inputs, expected results, mistakes, and verification;
  - `deep-dive`: mechanism, terminology, examples, boundaries, and security context;
  - `history`: chronology, people, evidence, uncertainty, and historical significance;
  - `lists`: explicit selection criteria, meaningful differences, and non-arbitrary ordering.

## Factual standard

- Recalculate cipher examples character by character and verify them through the project API or implementation when available.
- Distinguish encryption, encoding, hashing, signing, and obfuscation accurately.
- Qualify attack feasibility by algorithm, keyspace, message length, language, threat model, and computational assumptions.
- Do not imply that classical cryptanalysis defeats modern encryption.
- Distinguish historically documented facts from legends, disputed claims, and later retellings.
- Prefer standards, original papers, official documentation, museums or archives, and academic sources for uncertain claims.
- Never invent dates, quotations, attribution, performance numbers, or solved/unsolved status.

## Localization standard

- Use established technical terminology for each locale.
- Adapt alphabet-dependent examples and frequency claims to the language being taught. If retaining an English example is pedagogically necessary, label it explicitly and explain why.
- Rework wordplay, cribs, acronyms, punctuation, and cultural assumptions rather than translating them mechanically.
- Preserve technical distinctions even when common translations blur them.
- Localize SEO fields and relationship labels; never localize canonical slugs.
- Read each locale as standalone prose and remove English syntax, untranslated fragments, and unnatural calques.
- Keep the tone consistent within each locale, including formal or informal second-person address.

## Search and page intent

- Let one article satisfy one dominant informational intent plus closely related follow-ups.
- Use semantic-core evidence when available, but write for the reader rather than reproducing query lists.
- Do not make the guide compete with a tool page for “online calculator/encoder/decoder” intent.
- Link to the tool at the moment it becomes a useful next action, not in every section.
- Keep `meta_title` specific and readable; keep `meta_description` an accurate value proposition rather than a keyword inventory.
- Make the excerpt self-contained because it appears on the guide index.

## Field guidance

- `guide.title`: recognizable, specific, and aligned with the fixed planning title when one exists.
- `guide.excerpt`: one or two sentences describing the outcome and scope.
- `guide.meta_title`: distinct from the H1 when a concise SEO clarification helps.
- `guide.meta_description`: state what the reader learns and the concrete coverage.
- `guide.reading_time`: realistic whole minutes based on the localized article, not copied blindly.
- `body`: normally four to eight substantial, non-overlapping sections.
- `faq`: normally two to five direct answers to genuine residual questions.
- `tags`: a small stable classification set, not localized search-query variations.
- `related_guides`: broader, narrower, contrasting, prerequisite, or logical-next articles.
- `related_terms`: concepts the reader may need defined separately.
- `related_tools`: tools that let the reader apply or verify the article.

## Trusted HTML and links

Use only tags allowed by `docs/guide-article-json.md`.

- Keep `<h2>` text in `body[].title`; use an inline `<h3>` only when a section genuinely needs a subordinate heading.
- Wrap normal prose and FAQ answers in `<p>`.
- Use tables only when row-and-column comparison is clearer than prose or a list.
- Do not add scripts, styles, embeds, forms, event attributes, or presentational classes.
- Balance every tag and keep link text descriptive.
- Verify every internal target against the repository.
- Preserve locale prefixes in inline internal URLs for non-default locales. Structured related links receive locale-aware URLs at runtime.

## Cross-locale review

Confirm across the full locale set:

- identical canonical slug, category, publication status, relationship slugs, and publication date;
- consistent `updated_at` for the same revision;
- equivalent factual claims, outline coverage, and warnings;
- locally correct examples and terminology;
- localized SEO copy, headings, FAQ, and relationship labels;
- no accidental default-locale inline links;
- all files parse and `php bin/console guides:validate` succeeds.
