/**
 * Form Kit help modal: reads JSON from `label[data-nowo-help-modal]`, injects a clickable trigger
 * (icon) after the label text, and opens a modal for the configured UI framework.
 *
 * Modal shells may be cloned from Twig-rendered `<template id="nowo-formkit-help-modal-shell-*">`
 * elements; if missing, minimal inline HTML fallbacks are used per framework.
 *
 * Help modal roots are always portaled to `document.body` (direct child, last) so they remain
 * visible even when the form lives inside a container that is later hidden, clipped, or replaced
 * (Turbo / Live Component / AJAX). A MutationObserver keeps watching for new labels and for
 * refreshed modal roots that need to be re-homed.
 *
 * @packageDocumentation
 */

import { createBundleLogger, getLogger, setBundleLogger } from './logger';

/** Payload deserialized from `data-nowo-help-modal` and passed to {@link showModal} / {@link createModalElement}. */
export type HelpModalData = {
  id: string;
  framework: string;
  icon_html: string;
  /** CSS classes on the clickable wrapper (after label text + required suffix) */
  trigger_class?: string;
  /** Plain-text title (escaped in DOM when title_html is not set) */
  title: string | null;
  /** Optional HTML title (not escaped); takes precedence over title */
  title_html?: string | null;
  /** Modal body HTML (trusted; from your app / translations) */
  content: string;
  /** a11y label for the trigger button */
  aria_label?: string | null;
};

declare const __FORM_KIT_HELP_MODAL_BUILD_TIME__: string;

const resolvedBuildTime =
  (globalThis as Record<string, unknown>).__FORM_KIT_HELP_MODAL_BUILD_TIME__ as string | undefined;

const log = createBundleLogger('form-kit-help-modal', {
  buildTime: resolvedBuildTime,
  alwaysLog: true,
});
log.scriptLoaded();
setBundleLogger(log);

/**
 * Marker on help-modal root elements only (never on arbitrary `.modal` nodes).
 * Used by the portal observer so we only relocate Form Kit help modals.
 */
export const HELP_MODAL_ROOT_ATTR = 'data-nowo-formkit-help-modal';

/** Labels that carry a help-modal JSON payload. */
const HELP_MODAL_LABEL_SELECTOR = `label[data-nowo-help-modal]`;

/** Help-modal roots previously marked by this script (or server markup that opts in). */
const HELP_MODAL_ROOT_SELECTOR = `[${HELP_MODAL_ROOT_ATTR}]`;

/** CSS selector for the title slot inside a shell template. */
const TITLE_SLOT = '[data-nowo-help-modal-title]';

/** CSS selector for the body slot inside a shell template. */
const BODY_SLOT = '[data-nowo-help-modal-body]';

/** Active MutationObserver instance (at most one per page / module load). */
let domObserver: MutationObserver | null = null;

/** Coalesce bursty DOM mutations (Live / Turbo morphs) into one scan. */
let pendingDomScan: number | null = null;

/** Supported CSS framework keys; drives shell template id and fallback markup. */
type ShellKey = 'bootstrap5' | 'bootstrap4' | 'tailwind' | 'foundation';

/** Maps each {@link ShellKey} to the `<template>` element id in the DOM (Twig include). */
const SHELL_TEMPLATE_IDS: Record<ShellKey, string> = {
  bootstrap5: 'nowo-formkit-help-modal-shell-bootstrap5',
  bootstrap4: 'nowo-formkit-help-modal-shell-bootstrap4',
  tailwind: 'nowo-formkit-help-modal-shell-tailwind',
  foundation: 'nowo-formkit-help-modal-shell-foundation',
};

/**
 * Clones the first child of a Twig-rendered `<template id="nowo-formkit-help-modal-shell-*">`.
 * Applications may override markup via `templates/bundles/NowoFormKitBundle/help_modal/shell_*.html.twig`.
 *
 * @param shellKey - Which framework template to resolve.
 * @returns The cloned element, or null if the template is missing.
 */
function cloneShellFromDomTemplate(shellKey: ShellKey): HTMLElement | null {
  const id = SHELL_TEMPLATE_IDS[shellKey];
  const tpl = document.getElementById(id) as HTMLTemplateElement | null;
  const first = tpl?.content?.firstElementChild;
  if (!first) {
    return null;
  }

  return first.cloneNode(true) as HTMLElement;
}

/** Writes the modal title into {@link TITLE_SLOT} using HTML or plain text from `data`. */
function fillModalTitle(root: HTMLElement, data: HelpModalData): void {
  const el = root.querySelector(TITLE_SLOT);
  if (!el) {
    return;
  }
  if (data.title_html) {
    el.innerHTML = data.title_html;
  } else {
    el.textContent = data.title ?? '';
  }
}

/** Injects trusted HTML into {@link BODY_SLOT}. */
function fillModalBody(root: HTMLElement, contentHtml: string): void {
  const el = root.querySelector(BODY_SLOT);
  if (!el) {
    return;
  }
  el.innerHTML = contentHtml;
}

/**
 * Parses the JSON string from `data-nowo-help-modal`. Returns null if JSON is invalid or `id` is missing.
 *
 * @param raw - Raw attribute value (JSON string).
 */
export function parseHelpModalData(raw: string): HelpModalData | null {
  try {
    const parsed = JSON.parse(raw) as HelpModalData;
    if (!parsed || typeof parsed.id !== 'string') {
      getLogger().warn('Invalid help modal payload (missing id)', parsed);
      return null;
    }
    return parsed;
  } catch (error) {
    getLogger().warn('Cannot parse help modal payload', { raw, error });
    return null;
  }
}

/** Inline modal markup when the Bootstrap 5 Twig template is not present. */
function fallbackBootstrap5InnerHtml(): string {
  return `
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" data-nowo-help-modal-title></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" data-nowo-help-modal-body></div>
        </div>
      </div>
    `;
}

/** Inline modal markup when the Bootstrap 4 Twig template is not present. */
function fallbackBootstrap4InnerHtml(): string {
  return `
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" data-nowo-help-modal-title></h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body" data-nowo-help-modal-body></div>
        </div>
      </div>
    `;
}

/** Inline modal markup when the Tailwind Twig template is not present. */
function fallbackTailwindInnerHtml(): string {
  return `
      <div class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/50" data-help-modal-close="1"></div>
        <div class="relative z-10 w-full max-w-lg mx-4 rounded-lg bg-white dark:bg-gray-900 shadow-lg">
          <div class="flex items-start justify-between p-4 border-b border-gray-200 dark:border-gray-800">
            <div class="text-left">
              <h3 class="text-lg font-semibold" data-nowo-help-modal-title></h3>
            </div>
            <button type="button" class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-800" data-help-modal-close="1" aria-label="Close">✕</button>
          </div>
          <div class="p-4" style="max-height:70vh; overflow:auto;" data-nowo-help-modal-body></div>
        </div>
      </div>
    `;
}

/** Inline modal markup when the Foundation Twig template is not present. */
function fallbackFoundationInnerHtml(): string {
  return `
    <div class="nowo-help-modal-overlay fixed inset-0 z-50" style="background: rgba(0,0,0,.6); display:flex; align-items:center; justify-content:center;">
      <div class="nowo-help-modal-panel rounded shadow" style="background:#fff; max-width: 640px; width: calc(100% - 32px);">
        <div style="padding:12px 16px; border-bottom:1px solid rgba(0,0,0,.1); display:flex; justify-content:space-between; align-items:center;">
          <div data-nowo-help-modal-title style="flex:1;"></div>
          <button type="button" aria-label="Close" data-help-modal-close="1" style="cursor:pointer; border:0; background:transparent; font-size:18px; line-height:1;">✕</button>
        </div>
        <div style="padding:12px 16px; max-height:70vh; overflow:auto;" data-nowo-help-modal-body></div>
      </div>
    </div>
  `;
}

/**
 * Appends a cloned shell under `root`, or assigns `fallbackInnerHtml` as `innerHTML` if the template is missing.
 */
function appendShellOrFallback(root: HTMLElement, shellKey: ShellKey, fallbackInnerHtml: string): void {
  const cloned = cloneShellFromDomTemplate(shellKey);
  if (cloned) {
    root.appendChild(cloned);

    return;
  }
  root.innerHTML = fallbackInnerHtml;
}

/**
 * Marks a root as a Form Kit help modal so the portal observer can identify it
 * without matching arbitrary Bootstrap/Tailwind dialogs.
 */
function markHelpModalRoot(modal: HTMLElement): void {
  modal.setAttribute(HELP_MODAL_ROOT_ATTR, '1');
}

/**
 * Builds a detached modal root element for the given framework, fills title/body, and returns it.
 *
 * @param data - Payload including `framework`, `id`, and HTML content.
 */
export function createModalElement(data: HelpModalData): HTMLElement {
  const framework = data.framework || 'bootstrap5';
  const contentHtml = data.content || '';

  if (framework === 'bootstrap5') {
    const modal = document.createElement('div');
    modal.id = data.id;
    modal.className = 'modal fade';
    modal.setAttribute('tabindex', '-1');
    modal.setAttribute('aria-hidden', 'true');
    markHelpModalRoot(modal);

    appendShellOrFallback(modal, 'bootstrap5', fallbackBootstrap5InnerHtml());
    fillModalBody(modal, contentHtml);
    fillModalTitle(modal, data);

    return modal;
  }

  if (framework === 'bootstrap4') {
    const modal = document.createElement('div');
    modal.id = data.id;
    modal.className = 'modal fade';
    modal.setAttribute('tabindex', '-1');
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-hidden', 'true');
    markHelpModalRoot(modal);

    appendShellOrFallback(modal, 'bootstrap4', fallbackBootstrap4InnerHtml());
    fillModalBody(modal, contentHtml);
    fillModalTitle(modal, data);

    return modal;
  }

  if (framework === 'tailwind') {
    const root = document.createElement('div');
    root.id = data.id;
    root.className = 'nowo-help-modal-tailwind';
    root.setAttribute('role', 'dialog');
    root.setAttribute('aria-modal', 'true');
    markHelpModalRoot(root);

    appendShellOrFallback(root, 'tailwind', fallbackTailwindInnerHtml());
    fillModalBody(root, contentHtml);
    fillModalTitle(root, data);

    return root;
  }

  // Foundation and any unknown framework value: use Foundation shell/fallback.
  const root = document.createElement('div');
  root.id = data.id;
  root.className = 'nowo-help-modal-foundation';
  root.setAttribute('role', 'dialog');
  root.setAttribute('aria-modal', 'true');
  markHelpModalRoot(root);

  appendShellOrFallback(root, 'foundation', fallbackFoundationInnerHtml());
  fillModalBody(root, contentHtml);
  fillModalTitle(root, data);

  return root;
}

/**
 * Removes stale help-modal roots that share `modal.id` (except `modal` itself).
 * Needed when a form morph/refresh re-injects a modal while the previous portal copy still exists.
 */
function removeStaleHelpModalsById(modal: HTMLElement): void {
  const id = modal.id;
  if (!id) {
    return;
  }

  document.querySelectorAll(HELP_MODAL_ROOT_SELECTOR).forEach((el) => {
    if (el instanceof HTMLElement && el.id === id && el !== modal) {
      getLogger().debug('Removing stale help modal before portal', { id });
      el.remove();
    }
  });
}

/**
 * Moves a help-modal root to the end of `document.body` so stacking contexts / hidden ancestors
 * cannot hide it. Idempotent when the node is already the last child of body.
 *
 * @returns true when the node was moved (or stale siblings removed), false when already portaled.
 */
export function relocateHelpModalToBody(modal: HTMLElement): boolean {
  markHelpModalRoot(modal);
  removeStaleHelpModalsById(modal);

  const alreadyPortaled =
    modal.parentElement === document.body && document.body.lastElementChild === modal;

  if (alreadyPortaled) {
    return false;
  }

  document.body.appendChild(modal);
  getLogger().debug('Help modal portaled to document.body', { id: modal.id });

  return true;
}

/**
 * Scans `root` (default: document) for marked help-modal roots and portals each to body.
 * Also accepts a single Element that itself is a help-modal root.
 */
export function relocateAllHelpModalsToBody(root: ParentNode | Element = document): void {
  const found = new Set<HTMLElement>();

  if (root instanceof Element && root.matches(HELP_MODAL_ROOT_SELECTOR)) {
    found.add(root as HTMLElement);
  }

  if ('querySelectorAll' in root) {
    root.querySelectorAll(HELP_MODAL_ROOT_SELECTOR).forEach((el) => {
      if (el instanceof HTMLElement) {
        found.add(el);
      }
    });
  }

  found.forEach((modal) => {
    relocateHelpModalToBody(modal);
  });
}

/**
 * Appends a fresh modal to `document.body` (always recreates so refreshed payloads win),
 * wires close handlers for non-Bootstrap UIs, then shows it.
 *
 * Bootstrap 5 uses `window.bootstrap.Modal`; Bootstrap 4 uses jQuery's `.modal('show')`.
 * Tailwind/Foundation rely on visibility and `[data-help-modal-close]` buttons.
 */
export function showModal(data: HelpModalData): void {
  getLogger().info('Help modal opened', { id: data.id, framework: data.framework });

  // Drop any previous portal / in-tree copy so a form refresh cannot leave a stale dialog.
  document.querySelectorAll(HELP_MODAL_ROOT_SELECTOR).forEach((el) => {
    if (el instanceof HTMLElement && el.id === data.id) {
      el.remove();
    }
  });

  const modalEl = createModalElement(data);
  relocateHelpModalToBody(modalEl);

  // Wire close buttons for non-bootstrap frameworks.
  const closeEls = modalEl.querySelectorAll<HTMLElement>('[data-help-modal-close]');
  closeEls.forEach((el) => {
    el.addEventListener('click', () => {
      hideModal(data);
    });
  });

  if (data.framework === 'bootstrap5' && (window as any).bootstrap?.Modal) {
    (window as any).bootstrap.Modal.getOrCreateInstance(modalEl).show();
    return;
  }

  if (data.framework === 'bootstrap4' && (window as any).$ && typeof (window as any).$.fn?.modal === 'function') {
    (window as any).$(modalEl).modal('show');
    return;
  }

  // Tailwind / Foundation / bootstrap fallback: just ensure it is visible.
  modalEl.style.display = '';
}

/** Hides the modal using the framework-specific API or `display: none` as a fallback. */
export function hideModal(data: HelpModalData): void {
  getLogger().debug('hideModal', { id: data.id, framework: data.framework });
  const modalEl = document.getElementById(data.id);
  if (!modalEl) return;

  if (data.framework === 'bootstrap5' && (window as any).bootstrap?.Modal) {
    (window as any).bootstrap.Modal.getOrCreateInstance(modalEl).hide();
    return;
  }

  if (data.framework === 'bootstrap4' && (window as any).$ && typeof (window as any).$.fn?.modal === 'function') {
    (window as any).$(modalEl).modal('hide');
    return;
  }

  modalEl.style.display = 'none';
}

/** Default CSS classes for the trigger wrapper when `trigger_class` is omitted in payload. */
function defaultTriggerClass(): string {
  return 'nowo-help-modal-trigger nowo-help-modal-trigger--circle';
}

/**
 * Finds all labels with `data-nowo-help-modal`, appends an icon trigger once per label, and binds open handlers.
 * Safe to call repeatedly (e.g. after background form loads); skips labels that already have a trigger.
 */
export function initHelpModal(): void {
  getLogger().debug('initHelpModal start');

  const labels = Array.from(document.querySelectorAll<HTMLLabelElement>(HELP_MODAL_LABEL_SELECTOR));
  getLogger().debug('labels detected', { count: labels.length });
  labels.forEach((label) => {
    const raw = label.getAttribute('data-nowo-help-modal');
    if (!raw) return;

    const data = parseHelpModalData(raw);
    if (!data) return;

    const triggerSelector = '.nowo-help-modal-trigger';
    if (label.querySelector(triggerSelector)) {
      return;
    }

    const wrap = document.createElement('span');
    wrap.className = data.trigger_class?.trim() || defaultTriggerClass();
    wrap.setAttribute('role', 'button');
    wrap.setAttribute('tabindex', '0');
    wrap.setAttribute('aria-label', data.aria_label?.trim() || 'Help');
    wrap.innerHTML = data.icon_html;
    label.appendChild(wrap);

    const open = (ev: Event): void => {
      ev.preventDefault();
      ev.stopPropagation();
      // Re-read payload on open so a morph that updated the attribute is honoured.
      const latestRaw = label.getAttribute('data-nowo-help-modal');
      const latest = latestRaw ? parseHelpModalData(latestRaw) : null;
      showModal(latest ?? data);
    };

    wrap.addEventListener('click', open);
    wrap.addEventListener('keydown', (ev: KeyboardEvent) => {
      if (ev.key === 'Enter' || ev.key === ' ') {
        ev.preventDefault();
        const latestRaw = label.getAttribute('data-nowo-help-modal');
        const latest = latestRaw ? parseHelpModalData(latestRaw) : null;
        showModal(latest ?? data);
      }
    });

    getLogger().debug('help modal trigger attached', { id: data.id });
  });
}

/**
 * True when `node` is (or contains) a help-modal label or a marked help-modal root.
 */
function nodeNeedsHelpModalScan(node: Node): boolean {
  if (!(node instanceof Element)) {
    return false;
  }

  if (node.matches(HELP_MODAL_LABEL_SELECTOR) || node.matches(HELP_MODAL_ROOT_SELECTOR)) {
    return true;
  }

  return (
    node.querySelector(HELP_MODAL_LABEL_SELECTOR) !== null ||
    node.querySelector(HELP_MODAL_ROOT_SELECTOR) !== null
  );
}

/**
 * Runs label init + portal scan. Debounced via rAF so morph bursts coalesce.
 */
function scheduleDomScan(): void {
  if (pendingDomScan !== null) {
    return;
  }

  pendingDomScan = window.requestAnimationFrame(() => {
    pendingDomScan = null;
    initHelpModal();
    relocateAllHelpModalsToBody();
  });
}

/**
 * Continuously watches the document for:
 * - labels with `data-nowo-help-modal` (forms loaded in background / Turbo / Live)
 * - marked help-modal roots that appear outside `body` (or are re-injected after a refresh)
 *
 * Only elements with {@link HELP_MODAL_ROOT_ATTR} are relocated — never generic `.modal` nodes.
 */
export function startHelpModalDomObserver(): void {
  if (domObserver !== null || typeof MutationObserver === 'undefined') {
    return;
  }

  domObserver = new MutationObserver((mutations) => {
    for (const mutation of mutations) {
      if (mutation.type === 'attributes') {
        const target = mutation.target;
        if (
          target instanceof Element &&
          (target.matches(HELP_MODAL_LABEL_SELECTOR) || target.matches(HELP_MODAL_ROOT_SELECTOR))
        ) {
          scheduleDomScan();
          return;
        }
        continue;
      }

      for (const node of mutation.addedNodes) {
        if (nodeNeedsHelpModalScan(node)) {
          scheduleDomScan();
          return;
        }
      }
    }
  });

  domObserver.observe(document.documentElement, {
    childList: true,
    subtree: true,
    attributes: true,
    attributeFilter: ['data-nowo-help-modal', HELP_MODAL_ROOT_ATTR],
  });

  getLogger().debug('Help modal DOM observer started');
}

/** Stops the MutationObserver (tests / HMR). */
export function stopHelpModalDomObserver(): void {
  if (domObserver !== null) {
    domObserver.disconnect();
    domObserver = null;
  }
  if (pendingDomScan !== null) {
    window.cancelAnimationFrame(pendingDomScan);
    pendingDomScan = null;
  }
}

/**
 * Initial scan + continuous observation. Called on DOMContentLoaded or immediately if ready.
 */
export function bootstrapHelpModal(): void {
  initHelpModal();
  relocateAllHelpModalsToBody();
  startHelpModalDomObserver();
}

/** Runs {@link bootstrapHelpModal} on DOMContentLoaded or immediately if the document is already ready. */
export function runWhenReady(): void {
  if (document.readyState === 'loading') {
    getLogger().debug('DOM loading: wait DOMContentLoaded');
    document.addEventListener('DOMContentLoaded', bootstrapHelpModal);
  } else {
    getLogger().debug('DOM ready: init immediately');
    bootstrapHelpModal();
  }
}

runWhenReady();
