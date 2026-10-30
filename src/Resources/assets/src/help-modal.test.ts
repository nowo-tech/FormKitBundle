import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

type HelpModalModule = typeof import('./help-modal');

let mod: HelpModalModule;

const NONCE = 'test-nonce-0123456789ab';

/** Prints the per-request nonce meta in <head> (Twig: nowo_form_kit_help_modal_meta()). */
function setNonceMeta(value: string | null = NONCE): void {
  document.head.querySelectorAll('meta[name="nowo-form-kit-help-modal-nonce"]').forEach((m) => m.remove());
  if (value === null) {
    return;
  }
  const meta = document.createElement('meta');
  meta.name = 'nowo-form-kit-help-modal-nonce';
  meta.content = value;
  document.head.appendChild(meta);
}

function setDom(body: string): void {
  // Help labels are only honoured inside a <form> (Symfony form rendering).
  document.body.innerHTML = `<form>${body}</form>`;
}

function payload(overrides: Record<string, unknown> = {}): string {
  return JSON.stringify({
    id: 'hm-1',
    framework: 'tailwind',
    icon_html: '<button type="button" class="icon-btn">?</button>',
    trigger_class: 'nowo-help-modal-trigger nowo-help-modal-trigger--circle',
    title: 'Help',
    content_html: '<p>content</p>',
    nonce: NONCE,
    ...overrides,
  });
}

/** The <form> help labels must live in (created when a test starts from an empty body). */
function helpForm(): HTMLFormElement {
  let form = document.querySelector('form');
  if (!form) {
    form = document.createElement('form');
    document.body.appendChild(form);
  }
  return form;
}

async function importModuleFresh(): Promise<HelpModalModule> {
  mod?.stopHelpModalDomObserver();
  vi.resetModules();
  mod = await import('./help-modal');
  return mod;
}

async function flushPortalScan(): Promise<void> {
  // MutationObserver callback + requestAnimationFrame coalesce used by the portal watcher.
  await Promise.resolve();
  await new Promise<void>((resolve) => {
    requestAnimationFrame(() => resolve());
  });
}

describe('help-modal', () => {
  beforeEach(() => {
    vi.restoreAllMocks();
    mod?.stopHelpModalDomObserver();
    setDom('');
    setNonceMeta();
    delete (window as any).bootstrap;
    delete (window as any).$;
    delete (window as any).jQuery;
    vi.spyOn(console, 'log').mockImplementation(() => {});
    vi.spyOn(console, 'debug').mockImplementation(() => {});
    vi.spyOn(console, 'warn').mockImplementation(() => {});
    vi.spyOn(console, 'info').mockImplementation(() => {});
    vi.spyOn(console, 'error').mockImplementation(() => {});
  });

  afterEach(() => {
    mod?.stopHelpModalDomObserver();
  });

  it('loads and attaches icon for valid data attribute', async () => {
    setDom(`<label id="label-a" data-nowo-help-modal='${payload()}'></label>`);
    await importModuleFresh();

    const label = document.getElementById('label-a') as HTMLLabelElement;
    expect(label.querySelector('.nowo-help-modal-trigger')).toBeTruthy();
  });

  it('ignores help labels outside a form (user HTML that kept label + data-*)', async () => {
    document.body.innerHTML = `<div class="comment"><label id="label-x" data-nowo-help-modal='${payload({
      icon_html: '<img src=x onerror="window.__pwned=1">',
    })}'></label></div>`;
    await importModuleFresh();

    const label = document.getElementById('label-x') as HTMLLabelElement;
    expect(label.querySelector('.nowo-help-modal-trigger')).toBeNull();
    expect(label.innerHTML).toBe('');
    expect(mod.isFormHelpLabel(label)).toBe(false);
  });

  it('ignores invalid JSON payload and missing id payload', async () => {
    setDom(`
      <label id="label-b" data-nowo-help-modal='not-json'></label>
      <label id="label-c" data-nowo-help-modal='{"framework":"tailwind"}'></label>
      <label id="label-empty" data-nowo-help-modal=''></label>
    `);
    await importModuleFresh();

    expect((document.getElementById('label-b') as HTMLElement).querySelector('.nowo-help-modal-trigger')).toBeNull();
    expect((document.getElementById('label-c') as HTMLElement).querySelector('.nowo-help-modal-trigger')).toBeNull();
    expect((document.getElementById('label-empty') as HTMLElement).querySelector('.nowo-help-modal-trigger')).toBeNull();
  });

  it('exports parser and covers parse branches', async () => {
    const { parseHelpModalData } = await importModuleFresh();
    expect(parseHelpModalData('{"id":"x","framework":"tailwind","icon_html":"?","title":null,"content":""}')).toBeTruthy();
    expect(parseHelpModalData('{"framework":"tailwind"}')).toBeNull();
    expect(parseHelpModalData('not-json')).toBeNull();
  });

  it('shows bootstrap5 modal through bootstrap API and can hide via close hook', async () => {
    const show = vi.fn();
    const hide = vi.fn();
    (window as any).bootstrap = {
      Modal: {
        getOrCreateInstance: vi.fn(() => ({ show, hide })),
      },
    };

    setDom(
      `<label id="label-d" data-nowo-help-modal='${payload({
        id: 'hm-b5',
        framework: 'bootstrap5',
        content_html: '<button data-help-modal-close="1">Close</button>',
      })}'></label>`,
    );

    const { HELP_MODAL_ROOT_ATTR } = await importModuleFresh();

    const iconBtn = (document.getElementById('label-d') as HTMLElement).querySelector(
      '.nowo-help-modal-trigger',
    ) as HTMLElement;
    iconBtn.click();
    expect(show).toHaveBeenCalled();

    const modal = document.getElementById('hm-b5') as HTMLElement;
    expect(modal.getAttribute(HELP_MODAL_ROOT_ATTR)).toBe('1');
    expect(modal.parentElement).toBe(document.body);

    const closeBtn = document.querySelector('#hm-b5 [data-help-modal-close="1"]') as HTMLElement;
    closeBtn.click();
    expect(hide).toHaveBeenCalled();
  });

  it('shows bootstrap4 modal through jQuery API and can hide', async () => {
    const modalFn = vi.fn();
    const jq: any = vi.fn(() => ({ modal: modalFn }));
    jq.fn = { modal: vi.fn() };
    (window as any).$ = jq;

    setDom(
      `<label id="label-e" data-nowo-help-modal='${payload({
        id: 'hm-b4',
        framework: 'bootstrap4',
        content_html: '<button data-help-modal-close="1">Close</button>',
      })}'></label>`,
    );
    await importModuleFresh();

    const iconBtn = (document.getElementById('label-e') as HTMLElement).querySelector(
      '.nowo-help-modal-trigger',
    ) as HTMLElement;
    iconBtn.click();
    expect(modalFn).toHaveBeenCalledWith('show');

    const closeBtn = document.querySelector('#hm-b4 [data-help-modal-close="1"]') as HTMLElement;
    closeBtn.click();
    expect(modalFn).toHaveBeenCalledWith('hide');
  });

  it('tailwind and foundation use fallback display toggles', async () => {
    setDom(
      `<label id="label-f" data-nowo-help-modal='${payload({
        id: 'hm-tw',
        framework: 'tailwind',
      })}'></label>
      <label id="label-g" data-nowo-help-modal='${payload({
        id: 'hm-fd',
        framework: 'foundation',
      })}'></label>`,
    );

    await importModuleFresh();

    ((document.getElementById('label-f') as HTMLElement).querySelector('.nowo-help-modal-trigger') as HTMLElement).click();
    const closeTw = document.querySelector('#hm-tw [data-help-modal-close="1"]') as HTMLElement;
    closeTw.click();
    const modalTw = document.getElementById('hm-tw') as HTMLElement;
    expect(modalTw.style.display).toBe('none');
    expect(modalTw.parentElement).toBe(document.body);

    ((document.getElementById('label-g') as HTMLElement).querySelector('.nowo-help-modal-trigger') as HTMLElement).click();
    const closeFd = document.querySelector('#hm-fd [data-help-modal-close="1"]') as HTMLElement;
    closeFd.click();
    const modalFd = document.getElementById('hm-fd') as HTMLElement;
    expect(modalFd.style.display).toBe('none');
  });

  it('showModal recreates and portals to body; ignores unrelated .modal nodes', async () => {
    const { HELP_MODAL_ROOT_ATTR, showModal } = await importModuleFresh();

    const unrelated = document.createElement('div');
    unrelated.id = 'app-modal';
    unrelated.className = 'modal';
    document.body.appendChild(unrelated);

    const stale = document.createElement('div');
    stale.id = 'hm-refresh';
    stale.setAttribute(HELP_MODAL_ROOT_ATTR, '1');
    stale.textContent = 'stale';
    document.body.appendChild(stale);

    showModal({
      id: 'hm-refresh',
      framework: 'foundation',
      icon_html: '?',
      title: null,
      content_html: '<p>fresh</p>',
    });

    const portaled = document.getElementById('hm-refresh') as HTMLElement;
    expect(portaled).not.toBe(stale);
    expect(portaled.parentElement).toBe(document.body);
    expect(portaled.querySelector('[data-nowo-help-modal-body]')?.innerHTML).toBe('<p>fresh</p>');
    expect(document.getElementById('app-modal')).toBe(unrelated);

    showModal({
      id: 'hm-b5-fallback',
      framework: 'bootstrap5',
      icon_html: '?',
      title: 'T',
      content: '',
    });
    const created = document.getElementById('hm-b5-fallback') as HTMLElement;
    expect(created).toBeTruthy();
    expect(created.getAttribute(HELP_MODAL_ROOT_ATTR)).toBe('1');
    expect(created.style.display).toBe('');
  });

  it('hideModal no-op when target element does not exist', async () => {
    const { hideModal } = await importModuleFresh();
    hideModal({
      id: 'missing-id',
      framework: 'foundation',
      icon_html: '?',
      title: null,
      content: '',
    });
    expect(document.getElementById('missing-id')).toBeNull();
  });

  it('uses DOM shell template when Twig template is present', async () => {
    setDom(`
    <template id="nowo-formkit-help-modal-shell-bootstrap5">
      <div class="modal-dialog modal-dialog--from-twig">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" data-nowo-help-modal-title></h5>
          </div>
          <div class="modal-body" data-nowo-help-modal-body></div>
        </div>
      </div>
    </template>
    `);
    const { createModalElement, HELP_MODAL_ROOT_ATTR } = await importModuleFresh();
    const b5 = createModalElement({
      id: 'c-twig',
      framework: 'bootstrap5',
      icon_html: '?',
      title: 'From Twig',
      content_html: '<p>twig-body</p>',
    });
    expect(b5.querySelector('.modal-dialog--from-twig')).toBeTruthy();
    expect(b5.querySelector('[data-nowo-help-modal-body]')?.innerHTML).toBe('<p>twig-body</p>');
    expect(b5.querySelector('[data-nowo-help-modal-title]')?.textContent).toBe('From Twig');
    expect(b5.getAttribute(HELP_MODAL_ROOT_ATTR)).toBe('1');
  });

  it('createModalElement covers bootstrap and fallback constructors', async () => {
    const { createModalElement } = await importModuleFresh();
    const b5 = createModalElement({
      id: 'c1',
      framework: 'bootstrap5',
      icon_html: '?',
      title: 'A',
      content: 'B',
    });
    expect(b5.className).toContain('modal');
    expect(b5.querySelector('[data-nowo-help-modal-title]')?.textContent).toBe('A');
    expect(b5.querySelector('.modal-body')?.innerHTML).toBe('B');

    const b5html = createModalElement({
      id: 'c1b',
      framework: 'bootstrap5',
      icon_html: '?',
      title: 'ignored',
      title_html: '<em>HTML</em>',
      content: '',
    });
    expect(b5html.querySelector('[data-nowo-help-modal-title]')?.innerHTML).toBe('<em>HTML</em>');

    const b4 = createModalElement({
      id: 'c2',
      framework: 'bootstrap4',
      icon_html: '?',
      title: 'A',
      content: 'B',
    });
    expect(b4.className).toContain('modal');

    const tw = createModalElement({
      id: 'c3',
      framework: 'tailwind',
      icon_html: '?',
      title: 'A',
      content: 'B',
    });
    expect(tw.className).toContain('tailwind');

    const fd = createModalElement({
      id: 'c4',
      framework: 'foundation',
      icon_html: '?',
      title: 'A',
      content: 'B',
    });
    expect(fd.className).toContain('foundation');

    const implicitB5 = createModalElement({
      id: 'c5',
      framework: '',
      icon_html: '?',
      title: null,
      content: '',
    });
    expect(implicitB5.className).toContain('modal');
  });

  it('showModal logs at info level when opening (debug opt-in)', async () => {
    (globalThis as any).NOWO_FORM_KIT_DEBUG = true;
    const { showModal } = await importModuleFresh();
    delete (globalThis as any).NOWO_FORM_KIT_DEBUG;
    const info = vi.spyOn(console, 'info');
    showModal({
      id: 'info-open',
      framework: 'foundation',
      icon_html: '?',
      title: 'T',
      content_html: '<p>x</p>',
    });
    expect(info).toHaveBeenCalled();
  });

  it('handles document readyState loading branch and DOMContentLoaded', async () => {
    setDom(`<label id="label-h" data-nowo-help-modal='${payload({ id: 'hm-load' })}'></label>`);
    const addEvent = vi.spyOn(document, 'addEventListener');
    Object.defineProperty(document, 'readyState', {
      configurable: true,
      get: () => 'loading',
    });

    await importModuleFresh();
    expect(addEvent).toHaveBeenCalledWith('DOMContentLoaded', expect.any(Function));

    document.dispatchEvent(new Event('DOMContentLoaded'));
    expect((document.getElementById('label-h') as HTMLElement).querySelector('.nowo-help-modal-trigger')).toBeTruthy();

    Object.defineProperty(document, 'readyState', {
      configurable: true,
      get: () => 'complete',
    });
  });

  it('relocateHelpModalToBody portals nested modals and is idempotent at body end', async () => {
    const { createModalElement, relocateHelpModalToBody } = await importModuleFresh();

    const wrapper = document.createElement('div');
    wrapper.id = 'hidden-panel';
    wrapper.hidden = true;
    document.body.appendChild(wrapper);

    const modal = createModalElement({
      id: 'hm-nested',
      framework: 'tailwind',
      icon_html: '?',
      title: 'N',
      content_html: '<p>n</p>',
    });
    wrapper.appendChild(modal);

    expect(relocateHelpModalToBody(modal)).toBe(true);
    expect(modal.parentElement).toBe(document.body);
    expect(document.body.lastElementChild).toBe(modal);
    expect(relocateHelpModalToBody(modal)).toBe(false);

    const spacer = document.createElement('div');
    document.body.appendChild(spacer);
    expect(relocateHelpModalToBody(modal)).toBe(true);
    expect(document.body.lastElementChild).toBe(modal);
  });

  it('relocateAllHelpModalsToBody removes stale duplicates when a refreshed root appears', async () => {
    const { createModalElement, relocateAllHelpModalsToBody } = await importModuleFresh();

    const oldModal = createModalElement({
      id: 'hm-dup',
      framework: 'foundation',
      icon_html: '?',
      title: 'old',
      content_html: '<p>old</p>',
    });
    document.body.appendChild(oldModal);

    const panel = document.createElement('div');
    panel.style.display = 'none';
    document.body.appendChild(panel);

    const refreshed = createModalElement({
      id: 'hm-dup',
      framework: 'foundation',
      icon_html: '?',
      title: 'new',
      content_html: '<p>new</p>',
    });
    panel.appendChild(refreshed);

    relocateAllHelpModalsToBody(panel);

    expect(document.body.contains(oldModal)).toBe(false);
    expect(refreshed.parentElement).toBe(document.body);
    expect(refreshed.querySelector('[data-nowo-help-modal-body]')?.innerHTML).toBe('<p>new</p>');

    const alone = createModalElement({
      id: 'hm-alone',
      framework: 'tailwind',
      icon_html: '?',
      title: null,
      content: '',
    });
    panel.appendChild(alone);
    relocateAllHelpModalsToBody(alone);
    expect(alone.parentElement).toBe(document.body);
  });

  it('relocateAllHelpModalsToBody ignores non-HTML matches and roots without querySelectorAll', async () => {
    const { HELP_MODAL_ROOT_ATTR, relocateAllHelpModalsToBody } = await importModuleFresh();

    const panel = document.createElement('div');
    document.body.appendChild(panel);
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute(HELP_MODAL_ROOT_ATTR, '1');
    panel.appendChild(svg);

    relocateAllHelpModalsToBody(panel);
    expect(svg.parentElement).toBe(panel);

    expect(() => relocateAllHelpModalsToBody({} as ParentNode)).not.toThrow();
  });

  it('MutationObserver attaches triggers for labels loaded later and portals refreshed modals', async () => {
    const { createModalElement } = await importModuleFresh();

    const form = document.createElement('form');
    form.innerHTML = `<label id="label-late" data-nowo-help-modal='${payload({ id: 'hm-late' })}'></label>`;
    document.body.appendChild(form);
    await flushPortalScan();

    const label = document.getElementById('label-late') as HTMLLabelElement;
    expect(label.querySelector('.nowo-help-modal-trigger')).toBeTruthy();

    const hidden = document.createElement('div');
    hidden.hidden = true;
    document.body.appendChild(hidden);

    const nested = createModalElement({
      id: 'hm-observe',
      framework: 'tailwind',
      icon_html: '?',
      title: 'obs',
      content_html: '<p>obs</p>',
    });
    hidden.appendChild(nested);
    await flushPortalScan();

    expect(nested.parentElement).toBe(document.body);
  });

  it('ignores mutations that only add non-element nodes', async () => {
    await importModuleFresh();
    const raf = vi.spyOn(window, 'requestAnimationFrame');

    document.body.appendChild(document.createTextNode('only text'));
    document.body.appendChild(document.createComment('only a comment'));
    await Promise.resolve();
    await Promise.resolve();

    expect(raf).not.toHaveBeenCalled();
  });

  it('re-reads updated label payload on click and keyboard open', async () => {
    setDom(`<label id="label-k" data-nowo-help-modal='${payload({ id: 'hm-k', content_html: '<p>v1</p>' })}'></label>`);
    await importModuleFresh();

    const label = document.getElementById('label-k') as HTMLLabelElement;
    const trigger = label.querySelector('.nowo-help-modal-trigger') as HTMLElement;

    label.setAttribute('data-nowo-help-modal', payload({ id: 'hm-k', content_html: '<p>v2</p>' }));
    trigger.click();
    expect(document.querySelector('#hm-k [data-nowo-help-modal-body]')?.innerHTML).toBe('<p>v2</p>');

    document.getElementById('hm-k')?.remove();
    label.setAttribute('data-nowo-help-modal', payload({ id: 'hm-k', content_html: '<p>v3</p>' }));
    trigger.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true }));
    expect(document.querySelector('#hm-k [data-nowo-help-modal-body]')?.innerHTML).toBe('<p>v3</p>');

    document.getElementById('hm-k')?.remove();
    trigger.dispatchEvent(new KeyboardEvent('keydown', { key: ' ', bubbles: true }));
    expect(document.getElementById('hm-k')).toBeTruthy();

    trigger.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
  });

  it('startHelpModalDomObserver is a no-op when already running; stop clears pending rAF', async () => {
    const { startHelpModalDomObserver, stopHelpModalDomObserver } = await importModuleFresh();
    startHelpModalDomObserver();
    startHelpModalDomObserver();

    const label = document.createElement('label');
    label.id = 'label-raf';
    label.setAttribute('data-nowo-help-modal', payload({ id: 'hm-raf' }));
    document.body.appendChild(label);
    stopHelpModalDomObserver();
    await flushPortalScan();
  });

  it('skips starting observer when MutationObserver is unavailable', async () => {
    const original = globalThis.MutationObserver;
    // @ts-expect-error intentional for coverage
    globalThis.MutationObserver = undefined;
    try {
      await importModuleFresh();
      mod.startHelpModalDomObserver();
    } finally {
      globalThis.MutationObserver = original;
    }
  });

  it('coalesces multiple mutations into a single scheduled scan', async () => {
    await importModuleFresh();

    const a = document.createElement('label');
    a.id = 'label-coalesce-a';
    a.setAttribute('data-nowo-help-modal', payload({ id: 'hm-ca' }));
    const b = document.createElement('label');
    b.id = 'label-coalesce-b';
    b.setAttribute('data-nowo-help-modal', payload({ id: 'hm-cb' }));
    helpForm().appendChild(a);
    helpForm().appendChild(b);
    await flushPortalScan();

    expect(a.querySelector('.nowo-help-modal-trigger')).toBeTruthy();
    expect(b.querySelector('.nowo-help-modal-trigger')).toBeTruthy();
  });

  it('reacts to data-nowo-help-modal attribute changes on existing labels', async () => {
    setDom(`<label id="label-attr">Name</label>`);
    await importModuleFresh();

    const label = document.getElementById('label-attr') as HTMLLabelElement;
    expect(label.querySelector('.nowo-help-modal-trigger')).toBeNull();

    label.setAttribute('data-nowo-help-modal', payload({ id: 'hm-attr' }));
    await flushPortalScan();
    expect(label.querySelector('.nowo-help-modal-trigger')).toBeTruthy();
  });

  it('covers empty-id stale cleanup and non-element mutation nodes', async () => {
    const { createModalElement, relocateHelpModalToBody } = await importModuleFresh();

    const modal = createModalElement({
      id: 'hm-empty-check',
      framework: 'tailwind',
      icon_html: '?',
      title: null,
      content: '',
    });
    modal.removeAttribute('id');
    document.body.appendChild(document.createElement('div')).appendChild(modal);
    expect(relocateHelpModalToBody(modal)).toBe(true);

    // Text node addition should be ignored by the observer filter.
    document.body.appendChild(document.createTextNode('noop'));
    await flushPortalScan();
  });

  it('uses default trigger class and falls back when title/body slots are missing', async () => {
    const { createModalElement } = await importModuleFresh();

    setDom(`
      <label id="label-default-trigger" data-nowo-help-modal='${JSON.stringify({
        id: 'hm-default-trigger',
        framework: 'tailwind',
        icon_html: '?',
        title: null,
        content: '',
        nonce: NONCE,
      })}'></label>
    `);
    // Re-init after DOM change without a second module load.
    mod.initHelpModal();
    const trigger = document
      .getElementById('label-default-trigger')!
      .querySelector('.nowo-help-modal-trigger') as HTMLElement;
    expect(trigger.className).toContain('nowo-help-modal-trigger--circle');

    const bare = document.createElement('div');
    bare.id = 'hm-bare';
    // Force fillModalTitle / fillModalBody early-return paths via createModalElement internals:
    // replace shell with markup that omits the slots after creation.
    const withSlots = createModalElement({
      id: 'hm-slots',
      framework: 'foundation',
      icon_html: '?',
      title: 't',
      content: 'c',
    });
    withSlots.querySelector('[data-nowo-help-modal-title]')?.remove();
    withSlots.querySelector('[data-nowo-help-modal-body]')?.remove();
    // Re-run fillers indirectly by creating from a custom shell without slots.
    setDom(`
      <template id="nowo-formkit-help-modal-shell-foundation">
        <div class="shell-without-slots"></div>
      </template>
    `);
    const noSlots = createModalElement({
      id: 'hm-noslots',
      framework: 'foundation',
      icon_html: '?',
      title: 't',
      title_html: '<b>x</b>',
      content_html: '<p>y</p>',
    });
    expect(noSlots.querySelector('.shell-without-slots')).toBeTruthy();
    expect(noSlots.querySelector('[data-nowo-help-modal-title]')).toBeNull();
    expect(bare.id).toBe('hm-bare');
  });

  it('covers attribute mutations that do not match help-modal targets and pending rAF coalesce', async () => {
    await importModuleFresh();

    const stray = document.createElement('div');
    document.body.appendChild(stray);
    // Attribute is watched but target is neither a help label nor a marked root → continue branch.
    stray.setAttribute('data-nowo-help-modal', '{"id":"ignored"}');
    await Promise.resolve();

    // Two separate observer deliveries before rAF → second scheduleDomScan hits pending early-return.
    const a = document.createElement('label');
    a.setAttribute('data-nowo-help-modal', payload({ id: 'hm-pending-a' }));
    helpForm().appendChild(a);
    await Promise.resolve();

    const b = document.createElement('label');
    b.setAttribute('data-nowo-help-modal', payload({ id: 'hm-pending-b' }));
    helpForm().appendChild(b);
    await Promise.resolve();

    const nest = document.createElement('div');
    const marked = document.createElement('div');
    marked.id = 'hm-attr-root';
    nest.appendChild(marked);
    document.body.appendChild(nest);
    // Set marker after insert to hit ROOT attribute-match branch.
    marked.setAttribute(mod.HELP_MODAL_ROOT_ATTR, '1');
    await flushPortalScan();

    expect(a.querySelector('.nowo-help-modal-trigger')).toBeTruthy();
    expect(b.querySelector('.nowo-help-modal-trigger')).toBeTruthy();
    // Marker set by page markup (not created by the script): never relocated (L2).
    expect(marked.parentElement).toBe(nest);
  });

  it('uses custom aria_label from payload when provided', async () => {
    setDom(
      `<label id="label-aria" data-nowo-help-modal='${payload({
        id: 'hm-aria',
        aria_label: 'Más información',
      })}'></label>`,
    );
    await importModuleFresh();

    const trigger = document
      .getElementById('label-aria')!
      .querySelector('.nowo-help-modal-trigger') as HTMLElement;
    expect(trigger.getAttribute('aria-label')).toBe('Más información');
  });

  it('falls back to captured payload when label attribute is cleared before open', async () => {
    setDom(`<label id="label-cleared" data-nowo-help-modal='${payload({ id: 'hm-cleared' })}'></label>`);
    await importModuleFresh();

    const label = document.getElementById('label-cleared') as HTMLLabelElement;
    const trigger = label.querySelector('.nowo-help-modal-trigger') as HTMLElement;
    label.removeAttribute('data-nowo-help-modal');
    trigger.click();
    expect(document.getElementById('hm-cleared')).toBeTruthy();

    document.getElementById('hm-cleared')?.remove();
    trigger.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true }));
    expect(document.getElementById('hm-cleared')).toBeTruthy();
  });

  describe('origin binding (SEC-004 L1)', () => {
    it('ignores a forged label inside a host form when the payload nonce does not match the page nonce', async () => {
      setDom(`<div class="comment"><label id="forged" data-nowo-help-modal='${payload({
        id: 'hm-forged',
        nonce: 'guessed-nonce-000000000000',
        icon_html: '<img src=x onerror="window.__pwned=1">',
      })}'></label></div>`);
      await importModuleFresh();

      const label = document.getElementById('forged') as HTMLLabelElement;
      expect(label.innerHTML).toBe('');
      expect(label.querySelector('img')).toBeNull();
    });

    it('ignores payloads without nonce and warns once when the page has no nonce meta', async () => {
      setNonceMeta(null);
      const warn = vi.spyOn(console, 'warn');
      setDom(`
        <label id="nm-a" data-nowo-help-modal='${payload({ id: 'hm-nm-a' })}'></label>
        <label id="nm-b" data-nowo-help-modal='${payload({ id: 'hm-nm-b', nonce: undefined })}'></label>
      `);
      const m = await importModuleFresh();

      expect(m.getPageNonce()).toBeNull();
      expect((document.getElementById('nm-a') as HTMLElement).innerHTML).toBe('');
      expect((document.getElementById('nm-b') as HTMLElement).innerHTML).toBe('');
      const hints = warn.mock.calls.filter((c) => String(c[0]).includes('nowo_form_kit_help_modal_meta'));
      expect(hints).toHaveLength(1);
    });

    it('treats an empty nonce meta as missing and only reads the meta from <head>', async () => {
      setNonceMeta('   ');
      const m = await importModuleFresh();
      expect(m.getPageNonce()).toBeNull();

      setNonceMeta(null);
      const bodyMeta = document.createElement('meta');
      bodyMeta.name = 'nowo-form-kit-help-modal-nonce';
      bodyMeta.content = 'from-user-html-0000000000';
      document.body.appendChild(bodyMeta);
      expect(m.getPageNonce()).toBeNull();
      expect(m.isBoundPayload({ id: 'x', framework: 'tailwind', icon_html: '?', title: null, nonce: 'from-user-html-0000000000' })).toBe(false);
    });

    it('escapes content and title by default; *_html fields are opt-in HTML', async () => {
      const { createModalElement } = await importModuleFresh();
      const plain = createModalElement({
        id: 'esc-1',
        framework: 'foundation',
        icon_html: '?',
        title: '<b>t</b>',
        content: '<img src=x onerror="window.__pwned=1">',
      });
      expect(plain.querySelector('[data-nowo-help-modal-body]')?.textContent).toBe('<img src=x onerror="window.__pwned=1">');
      expect(plain.querySelector('img')).toBeNull();
      expect(plain.querySelector('[data-nowo-help-modal-title]')?.textContent).toBe('<b>t</b>');

      const html = createModalElement({
        id: 'esc-2',
        framework: 'foundation',
        icon_html: '?',
        title: null,
        content: 'ignored',
        content_html: '<p>trusted</p>',
      });
      expect(html.querySelector('[data-nowo-help-modal-body]')?.innerHTML).toBe('<p>trusted</p>');

      const empty = createModalElement({ id: 'esc-3', framework: 'foundation', icon_html: '?', title: null });
      expect(empty.querySelector('[data-nowo-help-modal-body]')?.textContent).toBe('');
    });

    it('uses a text "?" trigger when icon_html is empty', async () => {
      setDom(`<label id="no-icon" data-nowo-help-modal='${payload({ id: 'hm-no-icon', icon_html: '' })}'></label>`);
      await importModuleFresh();
      const trigger = document.querySelector('#no-icon .nowo-help-modal-trigger') as HTMLElement;
      expect(trigger.textContent).toBe('?');
      expect(trigger.children).toHaveLength(0);
    });

    it('keeps the last bound payload when a morph brings a payload with another nonce', async () => {
      setDom(`<label id="morph" data-nowo-help-modal='${payload({ id: 'hm-morph', content_html: '<p>v1</p>' })}'></label>`);
      const m = await importModuleFresh();
      const label = document.getElementById('morph') as HTMLLabelElement;
      const trigger = label.querySelector('.nowo-help-modal-trigger') as HTMLElement;

      label.setAttribute('data-nowo-help-modal', payload({ id: 'hm-morph', nonce: 'other-request-nonce-00000', content_html: '<img src=x>' }));
      trigger.click();
      expect(document.querySelector('#hm-morph [data-nowo-help-modal-body]')?.innerHTML).toBe('<p>v1</p>');

      // Trigger removed by the morph: re-attached from the remembered payload.
      trigger.remove();
      m.initHelpModal();
      expect(label.querySelector('.nowo-help-modal-trigger')).toBeTruthy();

      // A label node never accepted before gets nothing.
      const fresh = document.createElement('label');
      fresh.setAttribute('data-nowo-help-modal', payload({ id: 'hm-fresh', nonce: 'other-request-nonce-00000' }));
      helpForm().appendChild(fresh);
      expect(m.readTrustedPayload(fresh)).toBeNull();
      expect(m.readTrustedPayload(document.createElement('label'))).toBeNull();
    });
  });

  it('opens the captured payload when the label left its form before the click', async () => {
    setDom(`<label id="moved" data-nowo-help-modal='${payload({ id: 'hm-moved' })}'></label>`);
    await importModuleFresh();
    const label = document.getElementById('moved') as HTMLLabelElement;
    document.body.appendChild(label);
    (label.querySelector('.nowo-help-modal-trigger') as HTMLElement).click();
    expect(document.getElementById('hm-moved')).toBeTruthy();
  });

  describe('relocation (SEC-004 L2)', () => {
    it('never moves page markup that only carries the help-modal marker', async () => {
      const { HELP_MODAL_ROOT_ATTR, relocateHelpModalToBody, relocateAllHelpModalsToBody } = await importModuleFresh();
      const container = document.createElement('section');
      const fake = document.createElement('div');
      fake.id = 'phish';
      fake.setAttribute(HELP_MODAL_ROOT_ATTR, '1');
      container.appendChild(fake);
      document.body.appendChild(container);

      expect(relocateHelpModalToBody(fake)).toBe(false);
      relocateAllHelpModalsToBody();
      await flushPortalScan();
      expect(fake.parentElement).toBe(container);
    });
  });

  describe('logging (SEC-004 I7)', () => {
    it('is silent by default and never logs the raw payload', async () => {
      const warn = vi.spyOn(console, 'warn');
      const m = await importModuleFresh();
      expect(m.parseHelpModalData('<img src=x onerror=alert(1)>')).toBeNull();
      expect(warn).not.toHaveBeenCalled();
    });

    it('logs diagnostics without the raw payload when window.NOWO_FORM_KIT_DEBUG is true', async () => {
      (globalThis as any).NOWO_FORM_KIT_DEBUG = true;
      try {
        const warn = vi.spyOn(console, 'warn');
        const m = await importModuleFresh();
        m.parseHelpModalData('<img src=x onerror=alert(1)>');
        expect(warn).toHaveBeenCalled();
        expect(JSON.stringify(warn.mock.calls)).not.toContain('onerror');
      } finally {
        delete (globalThis as any).NOWO_FORM_KIT_DEBUG;
      }
    });
  });

  describe('Turbo fetch nonce header', () => {
    function turboEvent(detail: unknown): Event {
      return new CustomEvent('turbo:before-fetch-request', { detail });
    }

    it('adds the page nonce to same-origin Turbo requests (plain object and Headers)', async () => {
      await importModuleFresh();
      const headers: Record<string, string> = {};
      document.dispatchEvent(turboEvent({ url: new URL('/frame', window.location.href), fetchOptions: { headers } }));
      expect(headers['X-Nowo-Form-Kit-Help-Modal-Nonce']).toBe(NONCE);

      const h = new Headers();
      document.dispatchEvent(turboEvent({ url: '/frame', fetchOptions: { headers: h } }));
      expect(h.get('X-Nowo-Form-Kit-Help-Modal-Nonce')).toBe(NONCE);

      // Installed once even if bootstrapped again.
      mod.bootstrapHelpModal();
      const once: Record<string, string> = {};
      document.dispatchEvent(turboEvent({ fetchOptions: { headers: once } }));
      expect(once['X-Nowo-Form-Kit-Help-Modal-Nonce']).toBe(NONCE);
    });

    it('never sends the nonce cross-origin, without a nonce, or without headers; stop removes the listener', async () => {
      const m = await importModuleFresh();
      const cross: Record<string, string> = {};
      document.dispatchEvent(turboEvent({ url: 'https://evil.example/x', fetchOptions: { headers: cross } }));
      expect(cross).toEqual({});

      expect(() => document.dispatchEvent(turboEvent({ url: '/x', fetchOptions: {} }))).not.toThrow();
      expect(() => document.dispatchEvent(turboEvent({ url: '/x', fetchOptions: { headers: null } }))).not.toThrow();
      expect(() => document.dispatchEvent(new CustomEvent('turbo:before-fetch-request'))).not.toThrow();

      setNonceMeta(null);
      const noNonce: Record<string, string> = {};
      document.dispatchEvent(turboEvent({ url: '/x', fetchOptions: { headers: noNonce } }));
      expect(noNonce).toEqual({});

      setNonceMeta();
      m.stopHelpModalDomObserver();
      const stopped: Record<string, string> = {};
      document.dispatchEvent(turboEvent({ url: '/x', fetchOptions: { headers: stopped } }));
      expect(stopped).toEqual({});
    });
  });
});
