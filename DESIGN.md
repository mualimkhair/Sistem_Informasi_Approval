# Letters

## Mission
Create implementation-ready, token-driven UI guidance for Letters that is optimized for consistency, accessibility, and fast delivery across marketing site.

## Style Foundations
- Visual style: clean, functional, implementation-oriented
- Main font style: `font.family.primary=Open Runde`, `font.family.stack=Open Runde, Open Runde Placeholder, sans-serif`, `font.size.base=14px`, `font.weight.base=500`, `font.lineHeight.base=21px`
- Typography scale: `font.size.xs=10px`, `font.size.sm=11px`, `font.size.md=12px`, `font.size.lg=13px`, `font.size.xl=14px`, `font.size.2xl=16px`, `font.size.3xl=17px`, `font.size.4xl=18px`
- Color palette: `color.text.primary=#60606c`, `color.text.secondary=#070709`, `color.surface.base=#000000`, `color.text.inverse=#ffffff`, `color.surface.strong=#efeff1`
- Spacing scale: `space.1=7px`, `space.2=12px`, `space.3=14px`, `space.4=16px`, `space.5=18px`, `space.6=20px`, `space.7=24px`, `space.8=32px`
- Radius/shadow/motion tokens: `radius.xs=10px`, `radius.sm=18px`, `radius.md=36px`, `radius.lg=38px`, `radius.xl=40px`, `radius.2xl=100px` | `shadow.1=rgba(36, 36, 40, 0.1) 0px 1px 2px 0px, rgba(36, 36, 40, 0.09) 0px 3px 3px 0px, rgba(36, 36, 40, 0.05) 0px 6px 4px 0px, rgba(36, 36, 40, 0.01) 0px 11px 4px 0px, rgba(36, 36, 40, 0) 0px 17px 5px 0px`, `shadow.2=rgba(16, 55, 132, 0) 0px 17px 37px 0px, rgba(16, 55, 132, 0) 0px 67px 67px 0px, rgba(16, 55, 132, 0) 0px 150px 90px 0px, rgba(16, 55, 132, 0) 0px 266px 106px 0px, rgba(16, 55, 132, 0) 0px 416px 116px 0px`, `shadow.3=rgba(22, 107, 197, 0) 0px 0.421531px 0.421531px 0px, rgba(22, 107, 197, 0.01) 0px 1.60197px 1.60197px 0px, rgba(22, 107, 197, 0.05) 0px 7px 7px 0px`, `shadow.4=rgba(16, 55, 132, 0.03) 0px 17px 37px 0px, rgba(16, 55, 132, 0.03) 0px 67px 67px 0px, rgba(16, 55, 132, 0.02) 0px 150px 90px 0px, rgba(16, 55, 132, 0) 0px 266px 106px 0px, rgba(16, 55, 132, 0) 0px 416px 116px 0px`

## Accessibility
- Target: WCAG 2.2 AA
- Keyboard-first interactions required.
- Focus-visible rules required.
- Contrast constraints required.

## Writing Tone
Concise, confident, implementation-focused.

## Rules: Do
- Use semantic tokens, not raw hex values, in component guidance.
- Every component must define states for default, hover, focus-visible, active, disabled, loading, and error.
- Component behavior should specify responsive and edge-case handling.
- Interactive components must document keyboard, pointer, and touch behavior.
- Accessibility acceptance criteria must be testable in implementation.

## Rules: Don't
- Do not allow low-contrast text or hidden focus indicators.
- Do not introduce one-off spacing or typography exceptions.
- Do not use ambiguous labels or non-descriptive actions.
- Do not ship component guidance without explicit state rules.

## Guideline Authoring Workflow
1. Restate design intent in one sentence.
2. Define foundations and semantic tokens.
3. Define component anatomy, variants, interactions, and state behavior.
4. Add accessibility acceptance criteria with pass/fail checks.
5. Add anti-patterns, migration notes, and edge-case handling.
6. End with a QA checklist.

## Required Output Structure
- Context and goals.
- Design tokens and foundations.
- Component-level rules (anatomy, variants, states, responsive behavior).
- Accessibility requirements and testable acceptance criteria.
- Content and tone standards with examples.
- Anti-patterns and prohibited implementations.
- QA checklist.

## Component Rule Expectations
- Include keyboard, pointer, and touch behavior.
- Include spacing and typography token requirements.
- Include long-content, overflow, and empty-state handling.
- Include known page component density: links (31), navigation (1).

- Extraction diagnostics: Audience and product surface inference confidence is low; verify generated brand context.

## Quality Gates
- Every non-negotiable rule must use "must".
- Every recommendation should use "should".
- Every accessibility rule must be testable in implementation.
- Teams should prefer system consistency over local visual exceptions.
